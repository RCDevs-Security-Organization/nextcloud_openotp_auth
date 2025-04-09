<?php

declare(strict_types=1);

/**
 *
 * @copyright Copyright (c) 2024, RCDevs (info@rcdevs.com)
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
use OCA\OpenOTPAuth\Service\WebAuthnManager;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\TwoFactorAuth\ALoginSetupController;
use OCP\EventDispatcher\IEventDispatcher;
// use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class SettingsController extends ALoginSetupController
{

	// /** @var WebAuthnManager */
	// private $manager;

	// /** @var IUserSession */
	// private $userSession;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	public function __construct(
		// Defaults $defaults,
		IEventDispatcher $eventDispatcher,
		private IAppManager $appManager,
		private IConfig $config,
		private IL10N $l10n,
		private IUserSession $userSession,
		private LoggerInterface $logger,
		protected IUserManager $userManager,
		IRequest $request,
		string $appName,
		// private WebAuthnManager $manager,
	) {
		parent::__construct($appName, $request);
		$this->eventDispatcher = $eventDispatcher;

		// $this->manager = $manager;
		// $this->userSession = $userSession;
	}

	// /**
	//  * @NoAdminRequired
	//  * @PasswordConfirmationRequired
	//  * @UseSession
	//  */
	// public function startRegister(): JSONResponse
	// {
	// 	return new JSONResponse($this->manager->startRegistration($this->userSession->getUser(), $this->request->getServerHost()));
	// }

	// /**
	//  * @NoAdminRequired
	//  * @PasswordConfirmationRequired
	//  *
	//  * @param string $name
	//  * @param string $data
	//  */
	// public function finishRegister(string $name, string $data): JSONResponse
	// {
	// 	return new JSONResponse(
	// 		$this->manager->finishRegister(
	// 			$this->userSession->getUser(),
	// 			$name,
	// 			$data
	// 		)
	// 	);
	// }

	// /**
	//  * @NoAdminRequired
	//  * @PasswordConfirmationRequired
	//  */
	// public function remove(int $id): JSONResponse
	// {
	// 	$this->manager->removeDevice($this->userSession->getUser(), $id);
	// 	return new JSONResponse([]);
	// }

	// /**
	//  * @NoAdminRequired
	//  * @PasswordConfirmationRequired
	//  */
	// public function changeActivationState(int $id, bool $active): JSONResponse
	// {
	// 	$this->manager->changeActivationState($this->userSession->getUser(), $id, $active);
	// 	return new JSONResponse([]);
	// }

	public function saveSettings($post)
	{
		$this->config->setAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_allow_user_administer_openotp',	'off');
		$this->config->setAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_api_key',							$this->request->getParam('rcdevsopenotp_api_key'));
		$this->config->setAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_authentication_method',			$this->request->getParam('rcdevsopenotp_authentication_method'));
		$this->config->setAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_client_id',						$this->request->getParam('rcdevsopenotp_client_id'));
		$this->config->setAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_disable_otp_local_users',			$this->request->getParam('rcdevsopenotp_disable_otp_local_users'));
		$this->config->setAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_host',						$this->request->getParam('rcdevsopenotp_proxy_host'));
		$this->config->setAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_password',					$this->request->getParam('rcdevsopenotp_proxy_password'));
		$this->config->setAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_port',						$this->request->getParam('rcdevsopenotp_proxy_port'));
		$this->config->setAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_username',					$this->request->getParam('rcdevsopenotp_proxy_username'));
		$this->config->setAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_server_url1',						$this->request->getParam('rcdevsopenotp_server_url1'));
		$this->config->setAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_server_url2',						$this->request->getParam('rcdevsopenotp_server_url2'));

		switch ($this->request->getParam('rcdevsopenotp_authentication_method')) {
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
			if (
				// $backend->getBackendName() === "Database" &&
				// isset($POST["rcdevsopenotp_disable_otp_local_users"])
				($backend instanceof \OC\User\Database) &&
				$this->request->getParam('rcdevsopenotp_disable_otp_local_users') === 'on'
			) {
				$limit = 500;
				$offset = 0;
				do {
					$users = $backend->getUsers('', $limit, $offset);
					foreach ($users as $user) {
						// $this->eventDispatcher->dispatch(StateChanged::class, new StateChanged($this->userManager->get($user), false));
						$this->eventDispatcher->dispatchTyped(new StateChanged($this->userManager->get($user), false));
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
						// $this->eventDispatcher->dispatch(StateChanged::class, new StateChanged($this->userManager->get($user), $stateChanged));
						$this->eventDispatcher->dispatchTyped(new StateChanged($this->userManager->get($user), $stateChanged));
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

	public function checkServerUrl(string $serverNumber)
	{
		$params['rcdevsopenotp_api_key'] = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_api_key');
		$params['rcdevsopenotp_authentication_method'] = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_authentication_method');
		$params['rcdevsopenotp_client_id'] = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_client_id');
		$params['rcdevsopenotp_disable_otp_local_users'] = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_disable_otp_local_users');
		$params['rcdevsopenotp_proxy_host'] = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_host');
		$params['rcdevsopenotp_proxy_password'] = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_password');
		$params['rcdevsopenotp_proxy_port'] = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_port');
		$params['rcdevsopenotp_proxy_username'] = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_username');
		$params['rcdevsopenotp_server_url1'] = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_server_url1');
		$params['rcdevsopenotp_server_url2'] = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_server_url2');

		try {
			$appPath = $this->appManager->getAppPath(OpenOTPAuthApp::APP_ID);
		} catch (\Throwable $th) {
			$this->logger->warning($th->getMessage(), array('app' => OpenOTPAuthApp::APP_ID));
			//throw $th;
		}

		$openotpAuth = new OpenotpAuth($this->logger, $params, $appPath);
		$resp = $openotpAuth->openOTPStatus($serverNumber);

		$this->logger->info("OpenOTP server checkd : " . json_encode($resp), array('app' => OpenOTPAuthApp::APP_ID));

		if (isset($resp['status']) && $resp['status'] === 'true')
			return new JSONResponse(
				[
					'code' => 1,
					'status' => true,
					'message' => nl2br($resp['message']),
				]
			);
		else {
			$this->logger->error("Could not connect to host #{$serverNumber}", array('app' => OpenOTPAuthApp::APP_ID));
			return new JSONResponse(
				[
					'code' => 0,
					'status' => false,
					'message' => $this->l10n->t('Could not connect to host'),
				]
			);
		}
	}
}
