<?php

declare(strict_types=1);

/** SPDX-License-Identifier: AGPL-3.0-or-later */

namespace OCA\OpenOTPAuth\Tests\Unit;

use OCA\OpenOTPAuth\Config;
use OCA\OpenOTPAuth\Controller\SettingsController;
use OCA\OpenOTPAuth\Event\StateChanged;
use OCA\OpenOTPAuth\Migration\ApplyAdminPolicy;
use OCA\OpenOTPAuth\Provider\TwoFactorRCDevsOpenOTPProvider;
use OCA\OpenOTPAuth\Service\AdminPolicy;
use OCA\OpenOTPAuth\Service\RegistrySynchronizer;
use OCP\App\IAppManager;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

class RegistrySynchronizerTest extends TestCase {
	private array $settings = [];
	private array $states = [];
	private array $users = [];
	private array $events = [];
	private ?string $failingUser = null;
	private RegistrySynchronizer $synchronizer;
	private IAppConfig $appConfig;

	protected function setUp(): void {
		$config = $this->createMock(IAppConfig::class);
		$this->appConfig = $config;
		$config->method('getValueString')->willReturnCallback(function (string $app, string $key, string $default = ''): string {
			self::assertSame(Config::APP_ID, $app);
			return $this->settings[$key] ?? $default;
		});
		$config->method('setValueString')->willReturnCallback(function (string $app, string $key, string $value): bool {
			self::assertSame(Config::APP_ID, $app);
			$this->settings[$key] = $value;
			return true;
		});
		$provider = $this->createMock(TwoFactorRCDevsOpenOTPProvider::class);
		$provider->method('getId')->willReturn('openotp_auth');
		$registry = $this->createMock(IRegistry::class);
		$registry->method('getProviderStates')->willReturnCallback(fn (IUser $user): array => $this->states[$user->getUID()] ?? []);
		$registry->method('enableProviderFor')->willReturnCallback(function (IProvider $provider, IUser $user): void {
			$this->writeState($provider, $user, true);
		});
		$registry->method('disableProviderFor')->willReturnCallback(function (IProvider $provider, IUser $user): void {
			$this->writeState($provider, $user, false);
		});
		$registry->expects(self::never())->method('cleanUp');
		$users = $this->createMock(IUserManager::class);
		$users->method('callForAllUsers')->willReturnCallback(function (\Closure $callback): void {
			foreach ($this->users as $user) {
				$callback($user);
			}
		});
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->method('dispatchTyped')->willReturnCallback(function (StateChanged $event): void {
			// Activities must only announce successfully persisted changes.
			self::assertSame($event->isEnabled(), $this->states[$event->getUser()->getUID()]['openotp_auth']);
			$this->events[] = $event;
		});
		$this->synchronizer = new RegistrySynchronizer(new AdminPolicy($config), $provider, $registry, $users, $dispatcher);
	}

	public function testFlagTransitionsUpdateExistingUsersAndPreserveOtherProviders(): void {
		$this->addUser('local', 'Database');
		$this->addUser('ldap', 'LDAP');
		$this->addUser('external', 'SAML');
		$this->states = [
			'local' => ['openotp_auth' => false, 'backup_codes' => true],
			'ldap' => ['openotp_auth' => false, 'totp' => true],
			'external' => ['openotp_auth' => false],
		];
		$this->setPolicy('1', 'off');
		self::assertSame(3, $this->synchronizer->synchronize());
		self::assertTrue($this->states['local']['openotp_auth']);
		self::assertTrue($this->states['ldap']['openotp_auth']);
		self::assertTrue($this->states['external']['openotp_auth']);

		$this->setPolicy('1', 'on');
		self::assertSame(1, $this->synchronizer->synchronize());
		self::assertFalse($this->states['local']['openotp_auth']);
		self::assertTrue($this->states['ldap']['openotp_auth']);
		self::assertTrue($this->states['external']['openotp_auth']);

		$this->setPolicy('1', 'off');
		self::assertSame(1, $this->synchronizer->synchronize());
		self::assertTrue($this->states['local']['openotp_auth']);

		$this->setPolicy('0', 'off');
		self::assertSame(3, $this->synchronizer->synchronize());
		self::assertFalse($this->states['local']['openotp_auth']);
		self::assertFalse($this->states['ldap']['openotp_auth']);
		self::assertFalse($this->states['external']['openotp_auth']);
		self::assertTrue($this->states['local']['backup_codes']);
		self::assertTrue($this->states['ldap']['totp']);
		self::assertCount(8, $this->events);

		self::assertSame(0, $this->synchronizer->synchronize());
		self::assertCount(8, $this->events);
	}

	public function testNeverLoggedInUsersReceiveExplicitStates(): void {
		$this->addUser('new-local', 'Database');
		$this->addUser('new-ldap', 'LDAP');
		$this->setPolicy('1', 'on');
		self::assertSame(2, $this->synchronizer->synchronize());
		self::assertSame([
			'new-local' => ['openotp_auth' => false],
			'new-ldap' => ['openotp_auth' => true],
		], $this->states);
	}

	/** @dataProvider unsetModes */
	public function testUnconfiguredPolicyPreservesMigratedStates(string $mode): void {
		$this->addUser('igor', 'Database');
		$this->states['igor'] = ['openotp_auth' => true];
		$this->setPolicy($mode, 'on');
		self::assertSame(0, $this->synchronizer->synchronize());
		self::assertTrue($this->states['igor']['openotp_auth']);
		self::assertSame([], $this->events);
	}

	public static function unsetModes(): array {
		return [[''], ['invalid']];
	}

	public function testFailedSynchronizationCanBeRetriedWithoutChangingFlags(): void {
		$this->addUser('alice', 'Database');
		$this->addUser('igor', 'LDAP');
		$this->setPolicy('1', 'off');
		$this->failingUser = 'igor';
		try {
			$this->synchronizer->synchronize();
			self::fail('A registry failure must propagate to the caller.');
		} catch (RuntimeException $e) {
			self::assertSame('Registry unavailable', $e->getMessage());
		}
		self::assertSame(['alice' => ['openotp_auth' => true]], $this->states);
		self::assertCount(1, $this->events);

		$this->failingUser = null;
		self::assertSame(1, $this->synchronizer->synchronize());
		self::assertTrue($this->states['igor']['openotp_auth']);
		self::assertCount(2, $this->events);
	}

	public function testUpgradeReconcilesOldStatesAndCanRunAgain(): void {
		$this->addUser('local', 'Database');
		$this->addUser('ldap', 'LDAP');
		$this->states['local'] = ['openotp_auth' => true];
		$this->states['ldap'] = ['openotp_auth' => false];
		$this->setPolicy('1', 'on');
		$step = new ApplyAdminPolicy($this->synchronizer);
		$output = $this->createMock(IOutput::class);
		$output->expects(self::once())->method('info')->with('Updated 2 OpenOTP two-factor provider states.');
		$step->run($output);
		self::assertFalse($this->states['local']['openotp_auth']);
		self::assertTrue($this->states['ldap']['openotp_auth']);
		$step->run($this->createMock(IOutput::class));
		self::assertCount(2, $this->events);
	}

	public function testSettingsSaveAppliesNewFlagsBeforeReportingSuccess(): void {
		$this->addUser('local', 'Database');
		$this->addUser('ldap', 'LDAP');
		$this->setPolicy('0', 'off');
		$this->states['local'] = ['openotp_auth' => false];
		$this->states['ldap'] = ['openotp_auth' => false, 'totp' => true];

		$response = $this->controller('1', 'on')->saveSettings();
		self::assertSame(200, $response->getStatus());
		self::assertSame('success', $response->getData()['status']);
		self::assertFalse($this->states['local']['openotp_auth']);
		self::assertTrue($this->states['ldap']['openotp_auth']);
		self::assertTrue($this->states['ldap']['totp']);

		// Saving an unrelated setting with the same flags must also repair
		// an existing stale entry, without emitting events for correct entries.
		$this->states['ldap']['openotp_auth'] = false;
		$response = $this->controller('1', 'on')->saveSettings();
		self::assertSame(200, $response->getStatus());
		self::assertTrue($this->states['ldap']['openotp_auth']);
		self::assertCount(2, $this->events);
	}

	public function testSettingsSaveReportsPartialFailureAndRetryCompletes(): void {
		$this->addUser('alice', 'Database');
		$this->addUser('igor', 'LDAP');
		$this->setPolicy('0', 'off');
		$this->failingUser = 'igor';
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())->method('error');
		$controller = $this->controller('1', 'off', $logger);

		$response = $controller->saveSettings();
		self::assertSame(500, $response->getStatus());
		self::assertSame('error', $response->getData()['status']);
		self::assertSame('1', $this->settings['rcdevsopenotp_authentication_method']);
		self::assertSame(['alice' => ['openotp_auth' => true]], $this->states);
		self::assertCount(1, $this->events);

		$this->failingUser = null;
		$response = $controller->saveSettings();
		self::assertSame(200, $response->getStatus());
		self::assertTrue($this->states['igor']['openotp_auth']);
		self::assertCount(2, $this->events);
	}

	private function controller(string $mode, string $excludeLocal, ?LoggerInterface $logger = null): SettingsController {
		$params = [
			'rcdevsopenotp_authentication_method' => $mode,
			'rcdevsopenotp_disable_otp_local_users' => $excludeLocal,
		];
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(static fn ($key, $default = null) => $params[$key] ?? $default);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn ($message) => $message);
		return new SettingsController(
			$this->synchronizer,
			$this->createMock(IAppManager::class),
			$this->appConfig,
			$l10n,
			$logger ?? new NullLogger(),
			$request,
			Config::APP_ID,
		);
	}

	private function addUser(string $uid, string $backendName): void {
		$backend = $this->createMock(IUserBackend::class);
		$backend->method('getBackendName')->willReturn($backendName);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$user->method('getBackend')->willReturn($backend);
		$this->users[] = $user;
	}

	private function setPolicy(string $mode, string $excludeLocal): void {
		$this->settings = [
			'rcdevsopenotp_authentication_method' => $mode,
			'rcdevsopenotp_disable_otp_local_users' => $excludeLocal,
		];
	}

	private function writeState(IProvider $provider, IUser $user, bool $enabled): void {
		self::assertSame('openotp_auth', $provider->getId());
		if ($this->failingUser === $user->getUID()) {
			throw new RuntimeException('Registry unavailable');
		}
		$this->states[$user->getUID()][$provider->getId()] = $enabled;
	}
}
