<?php

declare(strict_types=1);

/** SPDX-License-Identifier: AGPL-3.0-or-later */

namespace OCA\OpenOTPAuth\Controller;

use OC\Installer;
use OC\App\AppManager;
use OCA\OpenOTPAuth\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class PurgeController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private AppManager $appManager,
		private Installer $installer,
		private IAppConfig $appConfig,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	// Deliberately restricted to full administrators, with CSRF protection.
	#[PasswordConfirmationRequired]
	public function purge(): JSONResponse {
		$appId = Application::APP_ID;
		try {
			$appPath = $this->appManager->getAppPath($appId);
			$installPath = $this->installer->getInstallPath();
			// Installer removes from its first writable app directory. Do not let
			// it remove another copy or report success for a different location.
			if ($this->appManager->isShipped($appId)
				|| $installPath === null
				|| realpath($appPath) === false
				|| realpath($appPath) !== realpath($installPath . '/' . $appId)
				|| !is_writable($appPath)
				|| !is_writable($installPath)) {
				throw new RuntimeException('OpenOTP cannot be removed from its installation directory.');
			}

			// Runs OOA's uninstall repair step, removing only its 2FA associations.
			$this->appManager->disableApp($appId);
			if (!$this->installer->removeApp($appId) || file_exists($appPath)) {
				throw new RuntimeException('OpenOTP was disabled but its files could not be completely removed. Settings were retained.');
			}

			$this->config->deleteAppFromAllUsers($appId);
			$this->appManager->removeOverwriteNextcloudRequirement($appId);
			$this->appConfig->deleteApp($appId);
			$this->appManager->clearAppsCache();
			return new JSONResponse(['status' => true]);
		} catch (Throwable $e) {
			$this->logger->error('OpenOTP purge did not complete.', ['app' => $appId, 'exception' => $e]);
			return new JSONResponse(['status' => false], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
