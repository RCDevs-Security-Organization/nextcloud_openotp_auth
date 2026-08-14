<?php

/**
 *
 * @copyright Copyright (c) 2026, RCDevs (info@rcdevs.com)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace OCA\OpenOTPAuth\Controller;

use OCA\OpenOTPAuth\AuthService\OpenotpAuth;
use OCA\OpenOTPAuth\Config;
use OCA\OpenOTPAuth\Event\StateChanged;
use OCA\OpenOTPAuth\Helper\RequestParamHelper;
use OCA\OpenOTPAuth\Settings\Admin\AdminSettings;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\EventDispatcher\IEventDispatcher;
// use OCP\Defaults;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserBackend;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class SettingsController extends Controller
{
	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private IAppManager $appManager,
		private IAppConfig $appConfig,
		private IL10N $l10n,
		private LoggerInterface $logger,
		protected IUserManager $userManager,
		IRequest $request,
		string $appName,
	) {
		parent::__construct($appName, $request);
	}


		/**
		 * Nextcloud 32 version.
		 * Standard saving process in Settings.
		 *
		 * @return JSONResponse<Http::STATUS_OK, array{code: string, status: string, message: string}, array<string, string>>
		 */
		#[AuthorizedAdminSetting(settings: AdminSettings::class)]
		public function saveSettings(): JSONResponse {
		$authenticationMethod = RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_authentication_method');

		$this->appConfig->setValueString(Config::APP_ID, 'rcdevsopenotp_allow_user_administer_openotp',	'off');
		$this->appConfig->setValueString(Config::APP_ID, 'rcdevsopenotp_api_key',							RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_api_key'));
		$this->appConfig->setValueString(Config::APP_ID, 'rcdevsopenotp_authentication_method',				$authenticationMethod);
		$this->appConfig->setValueString(Config::APP_ID, 'rcdevsopenotp_client_id',							RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_client_id'));
		$this->appConfig->setValueString(Config::APP_ID, 'rcdevsopenotp_disable_otp_local_users',			RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_disable_otp_local_users'));
		$this->appConfig->setValueString(Config::APP_ID, 'rcdevsopenotp_proxy_host',						RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_proxy_host'));
		$this->appConfig->setValueString(Config::APP_ID, 'rcdevsopenotp_proxy_password',					RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_proxy_password'));
		$this->appConfig->setValueString(Config::APP_ID, 'rcdevsopenotp_proxy_port',						RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_proxy_port'));
		$this->appConfig->setValueString(Config::APP_ID, 'rcdevsopenotp_proxy_username',					RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_proxy_username'));
		$this->appConfig->setValueString(Config::APP_ID, 'rcdevsopenotp_server_url1',						RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_server_url1'));
		$this->appConfig->setValueString(Config::APP_ID, 'rcdevsopenotp_server_url2',						RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_server_url2'));

		switch ($authenticationMethod) {
			case '0':
				$stateChanged = false;
				break;

			case '1':
				$stateChanged = true;
				break;

			default:
				$stateChanged = null;
				break;
		}

		// https://github.com/nextcloud/server/pull/9632
		// Admins can enable or disable 2FA for all users, this change give the possibility to be "statefull" in other word
		// we have to register enable/disable state for all users in IRegistry during plugin configuration (all user IRegistry will be populated at first config)						

		/* @var $backend \OCP\UserInterface */
		foreach ($this->userManager->getBackends() as $backend) {
			if ($backend instanceof IUserBackend && $backend->getBackendName() === 'Database' && $this->request->getParam('rcdevsopenotp_disable_otp_local_users') === 'on') {
				$limit = 500;
				$offset = 0;
				do {
					$users = $backend->getUsers('', $limit, $offset);
					foreach ($users as $user) {
						$targetUser = $this->userManager->get($user);
						if ($targetUser !== null) {
							// $this->eventDispatcher->dispatch(StateChanged::class, new StateChanged($targetUser, false));
							$this->eventDispatcher->dispatchTyped(new StateChanged($targetUser, false));
						}
					}
					$offset += $limit;
				} while (count($users) >= $limit);

				continue;
			}

			$limit = 500;
			$offset = 0;
			do {
				$users = $backend->getUsers('', $limit, $offset);
				foreach ($users as $user) {
					if (!is_null($stateChanged)) {
						$targetUser = $this->userManager->get($user);
						if ($targetUser !== null) {
							// $this->eventDispatcher->dispatch(StateChanged::class, new StateChanged($targetUser, $stateChanged));
							$this->eventDispatcher->dispatchTyped(new StateChanged($targetUser, $stateChanged));
						}
					}
				}
				$offset += $limit;
			} while (count($users) >= $limit);
		}

		return new JSONResponse([
			'code' => '1',
			'status' => 'success',
			'message' => $this->l10n->t("Your settings have been saved successfully")
		]);
	}

	/**
	 * @return JSONResponse<Http::STATUS_OK, array{status: bool, content: string}, array{}>|JSONResponse<Http::STATUS_NOT_FOUND, array{status: bool, message: string}, array{}>
	 */
	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	public function getChangelog(): JSONResponse {
		try {
			$appPath = $this->appManager->getAppPath(Config::APP_ID);
			$changelogPath = $appPath . '/CHANGELOG.md';
			$content = file_get_contents($changelogPath);
		} catch (\Throwable $th) {
			$this->logger->warning($th->getMessage(), ['app' => Config::APP_ID]);
			$content = false;
		}

		if ($content === false) {
			return new JSONResponse([
				'status' => false,
				'message' => $this->l10n->t('Could not read changelog'),
			], Http::STATUS_NOT_FOUND);
		}

		return new JSONResponse([
			'status' => true,
			'content' => $content,
		]);
	}

	/**
	 * @return JSONResponse<Http::STATUS_OK, array{code: int, status: bool, message: string}, array{}>
	 */
	#[AuthorizedAdminSetting(settings: AdminSettings::class)]
	public function checkServerUrl(string $serverNumber): JSONResponse {
		$params['rcdevsopenotp_api_key'] = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_api_key');
		$params['rcdevsopenotp_authentication_method'] = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_authentication_method');
		$params['rcdevsopenotp_client_id'] = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_client_id');
		$params['rcdevsopenotp_disable_otp_local_users'] = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_disable_otp_local_users');
		$params['rcdevsopenotp_proxy_host'] = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_proxy_host');
		$params['rcdevsopenotp_proxy_password'] = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_proxy_password');
		$params['rcdevsopenotp_proxy_port'] = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_proxy_port');
		$params['rcdevsopenotp_proxy_username'] = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_proxy_username');
		$params['rcdevsopenotp_server_url1'] = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_server_url1');
		$params['rcdevsopenotp_server_url2'] = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_server_url2');

		$requestServerUrl = $this->request->getParam('serverUrl', null);
		if ($requestServerUrl !== null && in_array($serverNumber, ['1', '2'], true)) {
			$requestServerUrl = RequestParamHelper::stringValue($requestServerUrl);
			$params['rcdevsopenotp_server_url' . $serverNumber] = $requestServerUrl;
		}

		$appPath = '';
		try {
			$appPath = $this->appManager->getAppPath(Config::APP_ID);
		} catch (\Throwable $th) {
			$this->logger->warning($th->getMessage(), array('app' => Config::APP_ID));
		}

		$openotpAuth = new OpenotpAuth($this->logger, $params, $appPath);
		$resp = $openotpAuth->openOTPStatus($serverNumber);

		$this->logger->info("OpenOTP server checkd : " . json_encode($resp), array('app' => Config::APP_ID));

			if (isset($resp['status']) && $resp['status'] === 'true') {
				$message = RequestParamHelper::stringValue($resp['message'] ?? '');
				return new JSONResponse(
					[
						'code' => 1,
						'status' => true,
						'message' => nl2br($message),
					]
				);
			}

		$this->logger->error("Could not connect to host #{$serverNumber}", array('app' => Config::APP_ID));
		return new JSONResponse(
			[
				'code' => 0,
				'status' => false,
				'message' => $this->l10n->t('Could not connect to host'),
			]
		);
	}
}
