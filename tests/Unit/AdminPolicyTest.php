<?php

declare(strict_types=1);

/** SPDX-License-Identifier: AGPL-3.0-or-later */

namespace OCA\OpenOTPAuth\Tests\Unit;

use OCA\OpenOTPAuth\Config;
use OCA\OpenOTPAuth\Provider\TwoFactorRCDevsOpenOTPProvider;
use OCA\OpenOTPAuth\Service\AdminPolicy;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\IUserManager;
use OCP\Template\ITemplateManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class AdminPolicyTest extends TestCase {
	/** @dataProvider policies */
	public function testSavedPolicyAndFirstLoginUseTheSameFlags(string $mode, string $excludeLocal, string $backendName, bool $expected): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnMap([
			[Config::APP_ID, 'rcdevsopenotp_authentication_method', '', false, $mode],
			[Config::APP_ID, 'rcdevsopenotp_disable_otp_local_users', '', false, $excludeLocal],
		]);
		$backend = $this->createMock(IUserBackend::class);
		$backend->method('getBackendName')->willReturn($backendName);
		$user = $this->createMock(IUser::class);
		$user->method('getBackend')->willReturn($backend);
		$policy = new AdminPolicy($config);
		$provider = new TwoFactorRCDevsOpenOTPProvider(
			$this->createMock(IAppManager::class),
			$this->createMock(IL10N::class),
			$this->createMock(IURLGenerator::class),
			new NullLogger(),
			$config,
			$this->createMock(IRequest::class),
			$this->createMock(ISession::class),
			$this->createMock(ITemplateManager::class),
			$this->createMock(IUserManager::class),
			$policy,
		);

		self::assertTrue($policy->isConfigured());
		self::assertSame($expected, $policy->isEnabledForUser($user));
		self::assertSame($expected, $provider->isTwoFactorAuthEnabledForUser($user));
		self::assertSame('openotp_auth', $provider->getId());
	}

	public static function policies(): array {
		return [
			'disabled local, exclusion off' => ['0', 'off', 'Database', false],
			'disabled LDAP, exclusion off' => ['0', 'off', 'LDAP', false],
			'disabled local, exclusion on' => ['0', 'on', 'Database', false],
			'disabled LDAP, exclusion on' => ['0', 'on', 'LDAP', false],
			'enabled local, exclusion off' => ['1', 'off', 'Database', true],
			'enabled LDAP, exclusion off' => ['1', 'off', 'LDAP', true],
			'enabled local, exclusion on' => ['1', 'on', 'Database', false],
			'enabled LDAP, exclusion on' => ['1', 'on', 'LDAP', true],
			'exclusion applies only to Database' => ['1', 'on', 'SAML', true],
			'disabled other backend' => ['0', 'on', 'SAML', false],
			'unset exclusion does not exclude local users' => ['1', '', 'Database', true],
		];
	}

	/** @dataProvider unconfiguredModes */
	public function testUnconfiguredOrUnknownModeIsNotAnExplicitPolicy(string $mode): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn($mode);
		$policy = new AdminPolicy($config);
		self::assertFalse($policy->isConfigured());
		self::assertFalse($policy->isEnabledForUser($this->createMock(IUser::class)));
	}

	public static function unconfiguredModes(): array {
		return [[''], ['2'], ['invalid']];
	}
}
