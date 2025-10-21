<?php

declare(strict_types=1);

/**
 *
 * @copyright Copyright (c) 2025, RCDevs (info@rcdevs.com)
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

namespace OCA\OpenOTPAuth\Controller;

use OCA\OpenOTPAuth\AppInfo\Application as OpenOTPAuthApp;
use OCA\OpenOTPAuth\AuthService\OpenotpAuth;
use OCA\OpenOTPAuth\Event\StateChanged;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\TwoFactorAuth\ALoginSetupController;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SettingsController extends ALoginSetupController
{

	/** @var IEventDispatcher */
	private $eventDispatcher;

	public function __construct(
		// Defaults $defaults,
		IEventDispatcher $eventDispatcher,
		private IAppManager $appManager,
		private IAppConfig $appConfig,
		private IL10N $l10n,
		private LoggerInterface $logger,
		protected IUserManager $userManager,
		IRequest $request,
		string $appName,
	) {
		parent::__construct($appName, $request);
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * Normalize a request param to string for config storage.
	 * Returns '' for null/array/object values.
	 */
	private function paramAsString(
		string $name
	): string {
		$v = $this->request->getParam($name);
		if (is_string($v)) {
			return $v;
		}
		if (is_int($v) || is_float($v)) {
			return (string) $v;
		}
		// arrays, null, bool, objects → store empty string
		return '';
	}

	/**
	 * 
	 * @return array<string, string> 
	 * @throws Exception 
	 * @throws RuntimeException 
	 */
	public function saveSettings(): array
	{
		$this->appConfig->setValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_allow_user_administer_openotp',	'off');
		$this->appConfig->setValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_api_key',						$this->paramAsString('rcdevsopenotp_api_key'));
		$this->appConfig->setValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_authentication_method',			$this->paramAsString('rcdevsopenotp_authentication_method'));
		$this->appConfig->setValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_client_id',						$this->paramAsString('rcdevsopenotp_client_id'));
		$this->appConfig->setValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_disable_otp_local_users',		$this->paramAsString('rcdevsopenotp_disable_otp_local_users'));
		$this->appConfig->setValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_host',					$this->paramAsString('rcdevsopenotp_proxy_host'));
		$this->appConfig->setValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_password',				$this->paramAsString('rcdevsopenotp_proxy_password'));
		$this->appConfig->setValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_port',					$this->paramAsString('rcdevsopenotp_proxy_port'));
		$this->appConfig->setValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_username',				$this->paramAsString('rcdevsopenotp_proxy_username'));
		$this->appConfig->setValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_server_url1',					$this->paramAsString('rcdevsopenotp_server_url1'));
		$this->appConfig->setValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_server_url2',					$this->paramAsString('rcdevsopenotp_server_url2'));

		switch ($this->request->getParam('rcdevsopenotp_authentication_method')) {
			/** @var bool|null $stateChanged */
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

		/* @var $backend \OCP\UserInterface */
		foreach ($this->userManager->getBackends() as $backend) {
			if (
				($backend instanceof \OC\User\Database) &&
				$this->request->getParam('rcdevsopenotp_disable_otp_local_users') === 'on'
			) {
				$limit = 500;
				$offset = 0;
				do {
					$users = $backend->getUsers('', $limit, $offset);
					/** @var list<non-empty-string> $users */
					foreach ($users as $user) {
						$userObj = $this->userManager->get($user); // IUser|null
						if ($userObj === null) {
							$this->logger->warning('UserManager->get() returned null for uid "{uid}"', ['uid' => $user]);
							continue;
						}
						$this->eventDispatcher->dispatchTyped(new StateChanged($userObj, false));
					}

					$offset += $limit;
				} while (count($users) >= $limit);

				continue;
			}

			$limit = 500;
			$offset = 0;
			do {
				/** @var list<non-empty-string> $users */
				$users = $backend->getUsers('', $limit, $offset);
				foreach ($users as $user) {
					if (!is_null($stateChanged)) {
						$userObj = $this->userManager->get($user); // IUser|null
						if ($userObj === null) {
							$this->logger->warning('UserManager->get() returned null for uid "{uid}"', ['uid' => $user]);
							continue;
						}
						$this->eventDispatcher->dispatchTyped(new StateChanged($userObj, $stateChanged));
					}
				}
				$offset += $limit;
			} while (count($users) >= $limit);
		}
		return [
			'code'	=> '1',
			'status'	=> 'success',
			'message'	=> $this->l10n->t("Your settings have been saved succesfully")
		];
	}

	/**
	 * @return \OCP\AppFramework\Http\JSONResponse<
	 *   int,
	 *   array{code:int,status:bool,message:string},
	 *   array<string,string>
	 * >
	 */
	public function checkServerUrl(
		string $serverNumber,
	): JSONResponse {
		$params['rcdevsopenotp_api_key']					= $this->appConfig->getValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_api_key');
		$params['rcdevsopenotp_authentication_method']		= $this->appConfig->getValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_authentication_method');
		$params['rcdevsopenotp_client_id']					= $this->appConfig->getValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_client_id');
		$params['rcdevsopenotp_disable_otp_local_users']	= $this->appConfig->getValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_disable_otp_local_users');
		$params['rcdevsopenotp_proxy_host']					= $this->appConfig->getValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_host');
		$params['rcdevsopenotp_proxy_password']				= $this->appConfig->getValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_password');
		$params['rcdevsopenotp_proxy_port']					= $this->appConfig->getValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_port');
		$params['rcdevsopenotp_proxy_username']				= $this->appConfig->getValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_username');
		$params['rcdevsopenotp_server_url1']				= $this->appConfig->getValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_server_url1');
		$params['rcdevsopenotp_server_url2']				= $this->appConfig->getValueString(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_server_url2');

		try {
			$appPath = $this->appManager->getAppPath(OpenOTPAuthApp::APP_ID);
		} catch (\Throwable $th) {
			$this->logger->warning($th->getMessage(), array('app' => OpenOTPAuthApp::APP_ID));
			//throw $th;
		}

		$openotpAuth = new OpenotpAuth($params, $this->logger);
		$resp = $openotpAuth->openOTPStatus(intval($serverNumber));

		$this->logger->info("OpenOTP server checkd : " . json_encode($resp), array('app' => OpenOTPAuthApp::APP_ID));

		if (is_array($resp) && isset($resp['status']) && $resp['status'] === 'true')
			return new JSONResponse(
				data: [
					'code' => 1,
					'status' => true,
					'message' => is_string($resp['message'] ?? null) ? $resp['message'] : '',
				]
			);
		else {
			$this->logger->error("Could not connect to host #{$serverNumber}", array('app' => OpenOTPAuthApp::APP_ID));
			return new JSONResponse(
				data: [
					'code' => 0,
					'status' => false,
					'message' => $this->l10n->t('Could not connect to host'),
				]
			);
		}
	}
}
