<?php

declare(strict_types=1);

namespace OCA\OpenOTPAuth\Tests\Unit;

use OC\App\AppManager;
use OC\Installer;
use OCA\OpenOTPAuth\Controller\PurgeController;
use OCA\OpenOTPAuth\Migration\DisableTwoFactorOnAppDisable;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PurgeControllerTest extends TestCase {
	private string $directory;
	private $manager;
	private $installer;
	private $appConfig;
	private $config;
	private $logger;
	private PurgeController $controller;

	protected function setUp(): void {
		$this->directory = sys_get_temp_dir() . '/openotp-purge-test-' . bin2hex(random_bytes(8));
		mkdir($this->directory);
		mkdir($this->directory . '/openotp_auth');
		$this->manager = $this->createMock(AppManager::class);
		$this->installer = $this->createMock(Installer::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->manager->method('getAppPath')->with('openotp_auth')->willReturn($this->directory . '/openotp_auth');
		$this->installer->method('getInstallPath')->willReturn($this->directory);
		$this->config->expects(self::never())->method('setSystemValue');
		$this->config->expects(self::never())->method('setSystemValues');
		$this->controller = new PurgeController('openotp_auth', $this->createMock(IRequest::class), $this->manager, $this->installer, $this->appConfig, $this->config, $this->logger);
	}

	protected function tearDown(): void {
		if (is_dir($this->directory . '/openotp_auth')) rmdir($this->directory . '/openotp_auth');
		rmdir($this->directory);
	}

	public function testDisableOnlyCleansOpenOtpAssociationsAndCanRunTwice(): void {
		$registry = $this->createMock(IRegistry::class);
		$registry->expects(self::exactly(2))->method('cleanUp')->with('openotp_auth');
		$step = new DisableTwoFactorOnAppDisable($registry);
		$output = $this->createMock(IOutput::class);
		$step->run($output);
		$step->run($output);
	}

	public function testPurgeDisablesBeforeRemovingAndDeletesOnlyOwnSettings(): void {
		$disabled = false;
		$this->manager->expects(self::once())->method('disableApp')->with('openotp_auth')->willReturnCallback(static function () use (&$disabled): void { $disabled = true; });
		$this->installer->expects(self::once())->method('removeApp')->with('openotp_auth')->willReturnCallback(function () use (&$disabled): bool {
			self::assertTrue($disabled);
			rmdir($this->directory . '/openotp_auth');
			return true;
		});
		$this->config->expects(self::once())->method('deleteAppFromAllUsers')->with('openotp_auth');
		$this->appConfig->expects(self::once())->method('deleteApp')->with('openotp_auth');
		$this->manager->expects(self::once())->method('removeOverwriteNextcloudRequirement')->with('openotp_auth');
		$this->manager->expects(self::once())->method('clearAppsCache');
		$response = $this->controller->purge();
		self::assertSame(200, $response->getStatus());
		self::assertSame(['status' => true], $response->getData());
	}

	public function testPreflightFailureDoesNotDisableOrPurge(): void {
		$this->manager->method('isShipped')->willReturn(true);
		$this->manager->expects(self::never())->method('disableApp');
		$this->installer->expects(self::never())->method('removeApp');
		$this->expectSettingsRetained();
		self::assertSame(500, $this->controller->purge()->getStatus());
	}

	public function testRemovalFailureRetainsSettings(): void {
		$this->manager->expects(self::once())->method('disableApp')->with('openotp_auth');
		$this->installer->method('removeApp')->willReturn(false);
		$this->expectSettingsRetained();
		self::assertSame(500, $this->controller->purge()->getStatus());
	}

	public function testReportedRemovalSuccessWithRemainingFilesIsFailure(): void {
		$this->installer->method('removeApp')->willReturn(true);
		$this->expectSettingsRetained();
		self::assertSame(500, $this->controller->purge()->getStatus());
	}

	public function testDisableFailureStopsRemoval(): void {
		$this->manager->method('disableApp')->willThrowException(new \RuntimeException('Cannot disable'));
		$this->installer->expects(self::never())->method('removeApp');
		$this->expectSettingsRetained();
		self::assertSame(500, $this->controller->purge()->getStatus());
	}

	public function testPurgeRequiresAdminCsrfAndPasswordConfirmation(): void {
		$method = new \ReflectionMethod(PurgeController::class, 'purge');
		self::assertCount(1, $method->getAttributes(PasswordConfirmationRequired::class));
		foreach (['NoAdminRequired', 'NoCSRFRequired', 'PublicPage', 'AuthorizedAdminSetting'] as $attribute) {
			self::assertCount(0, $method->getAttributes('OCP\\AppFramework\\Http\\Attribute\\' . $attribute));
		}
	}

	private function expectSettingsRetained(): void {
		$this->config->expects(self::never())->method('deleteAppFromAllUsers');
		$this->appConfig->expects(self::never())->method('deleteApp');
		$this->logger->expects(self::once())->method('error');
	}
}
