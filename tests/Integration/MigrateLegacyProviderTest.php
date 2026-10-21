<?php

declare(strict_types=1);

/** SPDX-License-Identifier: AGPL-3.0-or-later */

namespace OCA\OpenOTPAuth\Tests\Integration;

use Doctrine\DBAL\Connection as DbalConnection;
use Doctrine\DBAL\DriverManager;
use OC\Authentication\TwoFactorAuth\Db\ProviderUserAssignmentDao;
use OC\Authentication\TwoFactorAuth\Registry;
use OC\DB\AdapterSqlite;
use OC\DB\Connection;
use OC\DB\ConnectionAdapter;
use OC\DB\QueryBuilder\QueryBuilder;
use OC\SystemConfig;
use OCA\OpenOTPAuth\Migration\ApplyAdminPolicy;
use OCA\OpenOTPAuth\Migration\MigrateLegacyProvider;
use OCA\OpenOTPAuth\Provider\TwoFactorRCDevsOpenOTPProvider;
use OCA\OpenOTPAuth\Service\AdminPolicy;
use OCA\OpenOTPAuth\Service\RegistrySynchronizer;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class MigrateLegacyProviderTest extends TestCase {
	private const TABLE = 'migration_test_twofactor_providers';

	private DbalConnection $database;
	private ConnectionAdapter $connection;
	private MigrateLegacyProvider $migration;
	private ?string $failDeletingUid = null;

	protected function setUp(): void {
		if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
			self::markTestSkipped('The migration integration tests require pdo_sqlite.');
		}

		$this->database = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
		$this->database->executeStatement('CREATE TABLE ' . self::TABLE . ' (
			provider_id VARCHAR(32) NOT NULL,
			uid VARCHAR(64) NOT NULL,
			enabled SMALLINT NOT NULL,
			PRIMARY KEY (provider_id, uid)
		)');

		// Use Nextcloud's real QueryBuilder and conflict-handling adapter with
		// an isolated SQL database, without bootstrapping the live server.
		$inner = $this->createMock(Connection::class);
		$connection = new ConnectionAdapter($inner);
		$this->connection = $connection;
		$config = $this->createMock(SystemConfig::class);
		$config->method('getValue')->willReturnCallback(static fn ($key, $default = '') => $default);
		$inner->method('getDatabasePlatform')->willReturn($this->database->getDatabasePlatform());
		$inner->method('getDatabaseProvider')->willReturn(IDBConnection::PLATFORM_SQLITE);
		$inner->method('getQueryBuilder')->willReturnCallback(
			static fn () => new QueryBuilder($connection, $config, new NullLogger())
		);
		$inner->method('executeQuery')->willReturnCallback(
			fn ($sql, $params = [], $types = []) => $this->database->executeQuery($this->prefix($sql), $params, $types)
		);
		$inner->method('executeStatement')->willReturnCallback(function ($sql, $params = [], $types = []): int {
			if ($this->failDeletingUid !== null
				&& str_starts_with($sql, 'DELETE ')
				&& in_array($this->failDeletingUid, $params, true)) {
				throw new RuntimeException('Simulated migration failure');
			}
			return $this->database->executeStatement($this->prefix($sql), $params, $types);
		});
		$adapter = new AdapterSqlite($inner);
		$inner->method('insertIgnoreConflict')->willReturnCallback([$adapter, 'insertIgnoreConflict']);
		$inner->method('beginTransaction')->willReturnCallback(fn () => $this->database->beginTransaction());
		$inner->method('commit')->willReturnCallback(fn () => $this->database->commit());
		$inner->method('rollBack')->willReturnCallback(fn () => $this->database->rollBack());

		$this->migration = new MigrateLegacyProvider($connection);
	}

	protected function tearDown(): void {
		if (isset($this->database)) {
			$this->database->close();
		}
	}

	/** @dataProvider providerStates */
	public function testMigrationPreservesActivationAndMergesDuplicates(int $legacy, ?int $current, int $expected): void {
		$this->add('igor', 'rcdevsopenotp', $legacy);
		if ($current !== null) {
			$this->add('igor', 'openotp_auth', $current);
		}
		$this->migration->run($this->createMock(IOutput::class));

		self::assertSame([['provider_id' => 'openotp_auth', 'uid' => 'igor', 'enabled' => $expected]], $this->rows());
	}

	public static function providerStates(): array {
		return [
			'active legacy only' => [1, null, 1],
			'inactive legacy only' => [0, null, 0],
			'both active' => [1, 1, 1],
			'active legacy and inactive current' => [1, 0, 1],
			'inactive legacy and active current' => [0, 1, 1],
			'both inactive' => [0, 0, 0],
		];
	}

	public function testOtherProvidersAndUnrelatedUsersAreUnchangedAndRerunIsHarmless(): void {
		$this->add('igor', 'rcdevsopenotp', 1);
		$this->add('igor', 'backup_codes', 1);
		$this->add('igor', 'totp', 0);
		$this->add('another-user', 'openotp_auth', 0);
		$this->add('another-user', 'totp', 1);
		$this->migration->run($this->createMock(IOutput::class));
		$expected = [
			['provider_id' => 'openotp_auth', 'uid' => 'another-user', 'enabled' => 0],
			['provider_id' => 'totp', 'uid' => 'another-user', 'enabled' => 1],
			['provider_id' => 'backup_codes', 'uid' => 'igor', 'enabled' => 1],
			['provider_id' => 'openotp_auth', 'uid' => 'igor', 'enabled' => 1],
			['provider_id' => 'totp', 'uid' => 'igor', 'enabled' => 0],
		];
		self::assertSame($expected, $this->rows());

		$output = $this->createMock(IOutput::class);
		$output->expects(self::once())->method('info')->with('Migrated 0 OpenOTP two-factor provider associations.');
		$this->migration->run($output);
		self::assertSame($expected, $this->rows());
	}

	public function testEmptyRegistryIsUnchanged(): void {
		$this->migration->run($this->createMock(IOutput::class));
		self::assertSame([], $this->rows());
	}

	public function testMultipleBatchesDoNotSkipUsers(): void {
		for ($i = 0; $i < 1001; $i++) {
			$this->add(sprintf('user-%04d', $i), 'rcdevsopenotp', $i % 2);
		}
		$output = $this->createMock(IOutput::class);
		$output->expects(self::once())->method('info')->with('Migrated 1001 OpenOTP two-factor provider associations.');
		$this->migration->run($output);
		$rows = $this->rows();
		self::assertCount(1001, $rows);
		foreach ($rows as $i => $row) {
			self::assertSame(['provider_id' => 'openotp_auth', 'uid' => sprintf('user-%04d', $i), 'enabled' => $i % 2], $row);
		}
	}

	public function testFailedBatchRestoresBothProviderStatesAndCanBeRetried(): void {
		$this->add('alice', 'rcdevsopenotp', 1);
		$this->add('alice', 'openotp_auth', 0);
		$this->add('igor', 'rcdevsopenotp', 1);
		$before = $this->rows();
		$this->failDeletingUid = 'igor';
		$this->runExpectingFailure();
		self::assertSame($before, $this->rows());
		self::assertFalse($this->database->isTransactionActive());

		$this->failDeletingUid = null;
		$this->migration->run($this->createMock(IOutput::class));
		self::assertSame([
			['provider_id' => 'openotp_auth', 'uid' => 'alice', 'enabled' => 1],
			['provider_id' => 'openotp_auth', 'uid' => 'igor', 'enabled' => 1],
		], $this->rows());
	}

	public function testFailureAfterCommittedBatchCanResume(): void {
		for ($i = 0; $i < 501; $i++) {
			$this->add(sprintf('user-%04d', $i), 'rcdevsopenotp', 1);
		}
		$this->failDeletingUid = 'user-0500';
		$this->runExpectingFailure();
		$rows = $this->rows();
		self::assertCount(501, $rows);
		self::assertSame('openotp_auth', $rows[499]['provider_id']);
		self::assertSame('rcdevsopenotp', $rows[500]['provider_id']);
		self::assertFalse($this->database->isTransactionActive());

		$this->failDeletingUid = null;
		$output = $this->createMock(IOutput::class);
		$output->expects(self::once())->method('info')->with('Migrated 1 OpenOTP two-factor provider associations.');
		$this->migration->run($output);
		self::assertSame(['openotp_auth'], array_values(array_unique(array_column($this->rows(), 'provider_id'))));
	}

	public function testUserIdentifiersAreBoundAsParameters(): void {
		$uid = "LDAP\\o'connor";
		$this->add($uid, 'rcdevsopenotp', 1);
		$this->migration->run($this->createMock(IOutput::class));
		self::assertSame([['provider_id' => 'openotp_auth', 'uid' => $uid, 'enabled' => 1]], $this->rows());
	}

	/** @dataProvider lifecyclePhases */
	public function testDeclaredRepairStepsApplyPolicyAfterLegacyMigration(string $phase): void {
		$this->add('local', 'rcdevsopenotp', 1);
		$this->add('local', 'openotp_auth', 0);
		$this->add('local', 'backup_codes', 1);
		$this->add('ldap', 'rcdevsopenotp', 0);
		$this->add('ldap', 'totp', 1);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnMap([
			['openotp_auth', 'rcdevsopenotp_authentication_method', '', false, '1'],
			['openotp_auth', 'rcdevsopenotp_disable_otp_local_users', '', false, 'on'],
		]);
		$users = [];
		foreach (['local' => 'Database', 'ldap' => 'LDAP'] as $uid => $backendName) {
			$backend = $this->createMock(IUserBackend::class);
			$backend->method('getBackendName')->willReturn($backendName);
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$user->method('getBackend')->willReturn($backend);
			$users[] = $user;
		}
		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('callForAllUsers')->willReturnCallback(static function (\Closure $callback) use ($users): void {
			foreach ($users as $user) {
				$callback($user);
			}
		});
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$registry = new Registry(new ProviderUserAssignmentDao($this->connection), $dispatcher);
		$provider = $this->createMock(TwoFactorRCDevsOpenOTPProvider::class);
		$provider->method('getId')->willReturn('openotp_auth');
		$applyPolicy = new ApplyAdminPolicy(new RegistrySynchronizer(
			new AdminPolicy($config), $provider, $registry, $userManager, $dispatcher
		));
		$steps = [MigrateLegacyProvider::class => $this->migration, ApplyAdminPolicy::class => $applyPolicy];
		$manifest = simplexml_load_file(dirname(__DIR__, 2) . '/appinfo/info.xml');
		foreach ($manifest->{'repair-steps'}->{$phase}->step as $step) {
			$steps[(string)$step]->run($this->createMock(IOutput::class));
		}

		// Test the actual lifecycle order and core registry against SQL:
		// the legacy activation must not resurrect an excluded local account.
		self::assertSame([
			['provider_id' => 'openotp_auth', 'uid' => 'ldap', 'enabled' => 1],
			['provider_id' => 'totp', 'uid' => 'ldap', 'enabled' => 1],
			['provider_id' => 'backup_codes', 'uid' => 'local', 'enabled' => 1],
			['provider_id' => 'openotp_auth', 'uid' => 'local', 'enabled' => 0],
		], $this->rows());
	}

	public static function lifecyclePhases(): array {
		return [['install'], ['post-migration']];
	}

	private function runExpectingFailure(): void {
		$output = $this->createMock(IOutput::class);
		$output->expects(self::never())->method('info');
		try {
			$this->migration->run($output);
			self::fail('The migration must propagate a database failure.');
		} catch (RuntimeException $e) {
			self::assertSame('Simulated migration failure', $e->getMessage());
		}
	}

	private function prefix(string $sql): string {
		return str_replace('*PREFIX*', 'migration_test_', $sql);
	}

	private function add(string $uid, string $provider, int $enabled): void {
		$this->database->insert(self::TABLE, ['provider_id' => $provider, 'uid' => $uid, 'enabled' => $enabled]);
	}

	private function rows(): array {
		return $this->database->fetchAllAssociative('SELECT provider_id, uid, enabled FROM ' . self::TABLE . ' ORDER BY uid, provider_id');
	}
}
