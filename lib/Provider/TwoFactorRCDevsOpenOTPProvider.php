<?php

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

namespace OCA\OpenOTPAuth\Provider;

use Exception;
use OCA\OpenOTPAuth\AppInfo\Application as OpenOTPAuthApp;
use OCA\OpenOTPAuth\AuthService\OpenotpAuth;
use OCA\OpenOTPAuth\Settings\Admin\AdminSettings;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\TwoFactorException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Template;
use OCP\Util;
use Psr\Log\LoggerInterface;

class OpenOTPsendRequestException extends Exception
{
}

class TwoFactorRCDevsOpenOTPProvider implements IProvider
{

	/** @obj IConfig $Config */
	private $config;
	/** @var LoggerInterface */
	private $logger;
	/** $obj IL10N $trans */
	private $trans;
	/** $obj Session $session */
	private $session;
	/** @obj IURLGenerator $urlGenerator */
	private $urlGenerator;
	/** @array challenge_params */
	private $challenge_params = array();
	/** @var $openOTPsendRequest */
	private $openOTPsendRequestStatus = "";
	/** @var IAppManager */
	private $appManager;
	// private string $otpname;

	public function __construct(
		IConfig $config,
		IAppManager $appManager,
		LoggerInterface $logger,
		private IRequest $request,
		IL10N $trans,
		IURLGenerator $urlGenerator,
		private IUserManager $userManager,
	) {
		$this->config = $config;
		$this->appManager = $appManager;
		$this->logger = $logger;
		$this->trans = $trans;
		// $this->request = $request;
		$this->session = \OC::$server->getSession();
		$this->urlGenerator = $urlGenerator;

		// $this->otpname = OpenOTPAuthApp::APP_ID;
	}

	/**
	 * Get unique identifier of this 2FA provider
	 *
	 * @return string
	 */
	public function getId(): string
	{
		// return $this->otpname;
		return OpenOTPAuthApp::APP_ID;
	}

	/**
	 * Get the display name for selecting the 2FA provider
	 *
	 * @return string
	 */
	public function getDisplayName(): string
	{
		return 'RCDevs OpenOTP Auth';
	}

	/**
	 * Get the description for selecting the 2FA provider
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return 'Two-Factor RCDevs OpenOTP';
	}

	/**
	 *
	 *
	 * @UseSession
	 * @param string $otp OTP
	 * @throws OpenOTPsendRequestException
	 */
	private function openOTPsendRequest(IUser $user, $otp = NULL, $sample = NULL)
	{
		$user = $this->userManager->get($user->getUID());

		$message = array();
		$params = array();
		$username = $user->getUID();
		//Clean Session Nonce /!\  must be used only for Push request response
		$this->session->remove('rcdevsopenotp_nonce');
		$this->logger->info("********* New OpenOTP Authentication *********", array('app' => OpenOTPAuthApp::APP_ID));

		// $params = $this->getAllAppValue();
		$params['rcdevsopenotp_allow_user_administer_openotp'] = 'off';
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

		// $params['rcdevsopenotp_remote_addr'] = $this->request->getRemoteAddress();
		try {
			$appPath = $this->appManager->getAppPath(OpenOTPAuthApp::APP_ID);
		} catch (AppPathNotFoundException $e) {
		}
		//TODO: OC_App - Static method of private class must not be called
		$appWebPath = \OC_App::getAppWebPath(OpenOTPAuthApp::APP_ID);

		$openotpAuth = new OpenotpAuth($this->logger, $params, $appPath);

		// Get context cookie
		$context_name = $openotpAuth->getContext_name();
		$context_size = $openotpAuth->getContext_size();
		$context_time = $openotpAuth->getContext_time();

		if (isset($_COOKIE[$context_name])) $context = $_COOKIE[$context_name];
		else $context = bin2hex(openssl_random_pseudo_bytes($context_size / 2));

		$domain = "";
		$password = NULL;
		/* Don't check LDAP password, validate localy OR via third party User integration (LDAP plugin, etc...) */
		$option = "-LDAP,WEBAUTH";

		$POST = array();
		$POST[] = $this->request->getParam("password");

		$u2f = isset($_POST['openotp_u2f']) ? $_POST['openotp_u2f'] : "";
		if ($u2f !== "") $otp = NULL;
		$state = isset($_POST['rcdevsopenotp_session']) ? $_POST['rcdevsopenotp_session'] : "";

		$t_domain = $openotpAuth->getDomain($username);
		if (is_array($t_domain)) {
			$username = $t_domain['username'];
			$domain = $t_domain['domain'];
		} elseif (isset($_POST['rcdevsopenotp_domain']) && $_POST['rcdevsopenotp_domain'] !== "") $domain = $_POST['rcdevsopenotp_domain'];
		else $domain = $t_domain;
		if ($domain !== "") $this->logger->info("Domain found in username field", array('app' => OpenOTPAuthApp::APP_ID));

		if ($state !== "") {
			// OpenOTP Challenge
			$this->logger->info("New OpenOTP Challenge for user " . $username, array('app' => OpenOTPAuthApp::APP_ID));
			$resp = $openotpAuth->openOTPChallenge($username, $domain, $state, $otp, $u2f, $sample);
		} else {
			// OpenOTP Login
			$this->logger->info("New OpenOTP SimpleLogin for user " . $username, array('app' => OpenOTPAuthApp::APP_ID));
			$resp = $openotpAuth->openOTPSimpleLogin($username, $domain, $password, $option, $context);
		}

		if (!$resp || !isset($resp['code'])) {
			$this->logger->error("Invalid OpenOTP response for user " . $username, array('app' => OpenOTPAuthApp::APP_ID));
			$message[] = $this->trans->t("Invalid OpenOTP response for user") . " " . $username;
		}

		switch ($resp['code']) {
			case '0':
				if ($resp['message']) $message[] = $resp['message'];
				else $message[] = $this->trans->t("OpenOTP Authentication failed for user " . $username);
				$this->logger->info("OpenOTP Authentication failed for user " . $username, array('app' => OpenOTPAuthApp::APP_ID));
				$this->openOTPsendRequestStatus = "error";
				break;
			case '1':
				$this->logger->info("User $username has authenticated with OpenOTP.", array('app' => OpenOTPAuthApp::APP_ID));
				if (!$state) {
					$this->openOTPsendRequestStatus = "pushSuccess";
					$PolicyNonce = \OC::$server->getContentSecurityPolicyNonceManager()->getNonce();
					$rcdevsopenotp_nonce = sha1($PolicyNonce);

					$this->challenge_params['rcdevsopenotp_nonce'] = $rcdevsopenotp_nonce;
					$this->session->set('rcdevsopenotp_nonce', $rcdevsopenotp_nonce);
				} else $this->openOTPsendRequestStatus = "success";

				// set context cookie
				if (extension_loaded('openssl')) {
					if (strlen($context) === $context_size)	setcookie($context_name, $context, time() + $context_time, '/', NULL, true, true);
				} else {
					$this->logger->info("Openssl extension not loaded - context authentication not available", array('app' => OpenOTPAuthApp::APP_ID));
				}

				break;
			case '2':
				$this->logger->info("OpenOTP Response require Challenge", array('app' => OpenOTPAuthApp::APP_ID));
				$this->logger->debug(json_encode($resp), array('app' => OpenOTPAuthApp::APP_ID));

				$this->challenge_params = array(
					'rcdevsopenotp_otpChallenge'							=> (array_key_exists('otpChallenge', $resp) ? $resp['otpChallenge'] : null),
					'rcdevsopenotp_u2fChallenge'							=> (array_key_exists('u2fChallenge', $resp) ? $resp['u2fChallenge'] : null),
					'rcdevsopenotp_voiceLogin'								=> (array_key_exists('otpChallenge', $resp) ? strstr($resp['otpChallenge'], "VOICE") : null),
					'rcdevsopenotp_voiceOnly'								=> (array_key_exists('otpChallenge', $resp) ? strcmp($resp['otpChallenge'], "VOICE") == 0 : -1),
					'rcdevsopenotp_message'									=> $resp['message'],
					'rcdevsopenotp_username'								=> $username,
					'rcdevsopenotp_session'									=> $resp['session'],
					'rcdevsopenotp_timeout'									=> (array_key_exists('timeout', $resp) ? $resp['timeout'] : 0), // $resp['timeout'],
					'rcdevsopenotp_password'								=> $password,
					'rcdevsopenotp_appPath'									=> $appPath,
					'rcdevsopenotp_appWebPath'								=> $appWebPath,
					'rcdevsopenotp_domain'									=> $domain,
				);
				$this->openOTPsendRequestStatus = "challenge";
				break;
			default:
				$this->trans->t("OpenOTP Authentication failed for user") . " " . $username;
				$this->logger->info("OpenOTP Authentication failed for user " . $username, array('app' => OpenOTPAuthApp::APP_ID));
				$this->openOTPsendRequestStatus = "error";
				break;
		}

		if ($message) throw new OpenOTPsendRequestException(implode(", ", $message));
	}

	/**
	 * Get the template for rending the 2FA provider view
	 *
	 * @param IUser $user
	 * @return Template
	 */
	public function getTemplate(IUser $user): Template
	{
		try {
			$this->openOTPsendRequest($user);
		} catch (OpenOTPsendRequestException $e) {
			$error_message = $e->getMessage();
		}

		$template = new Template(OpenOTPAuthApp::APP_ID, 'challenge');
		$template->assign("userID", $user->getUID());
		$template->assign("status", $this->openOTPsendRequestStatus);
		$template->assign("error_msg", isset($error_message) ? $error_message : "");
		$template->assign("challenge_params", $this->challenge_params);

		Util::addStyle(OpenOTPAuthApp::APP_ID, 'settings');
		Util::addScript(OpenOTPAuthApp::APP_ID, '../jsStatic/arrive.min');
		Util::addScript(OpenOTPAuthApp::APP_ID, '../jsStatic/base64');
		Util::addScript(OpenOTPAuthApp::APP_ID, '../jsStatic/fidou2f');
		Util::addScript(OpenOTPAuthApp::APP_ID, '../jsStatic/script');
		Util::addScript(OpenOTPAuthApp::APP_ID, '../jsStatic/voice');

		return $template;
	}

	/**
	 * Verify the given challenge
	 *
	 * @UseSession
	 * @param IUser $user
	 * @param string $challenge => OTP password
	 * @return Boolean, True in case of success
	 */
	public function verifyChallenge(IUser $user, string $challenge): bool
	{
		/*$this->logger->info("----- verifyChallenge -------:" . $challenge, array('app' => OpenOTPAuthApp::APP_ID));
    	$this->logger->info("POST NONCE:" . $_POST['rcdevsopenotp_nonce'], array('app' => OpenOTPAuthApp::APP_ID));
		$this->logger->info("SESSION NONCE:" . $this->session->get('rcdevsopenotp_nonce'), array('app' => OpenOTPAuthApp::APP_ID));*/

		$rcdevsopenotp_nonce = "";
		$nonce = "";

		if ($this->session->get('rcdevsopenotp_nonce')) {
			$rcdevsopenotp_nonce =  $this->session->get('rcdevsopenotp_nonce');
			$this->session->remove('rcdevsopenotp_nonce');
		}
		if (isset($_POST['rcdevsopenotp_nonce'])) $nonce = $_POST['rcdevsopenotp_nonce'];
		$this->logger->info("SESSION NONCE SUPP:" . $this->session->get('rcdevsopenotp_nonce'), array('app' => OpenOTPAuthApp::APP_ID));
		if ($challenge === "passme" && $nonce && $rcdevsopenotp_nonce && $nonce === $rcdevsopenotp_nonce) return true;

		try {
			$this->openOTPsendRequest($user, $challenge, isset($_POST['rcdevsopenotp_sample']) ? $_POST['rcdevsopenotp_sample'] : NULL);
		} catch (OpenOTPsendRequestException $e) {
			$error_message = $e->getMessage();
		}

		if ($this->openOTPsendRequestStatus && ($this->openOTPsendRequestStatus === "success" || $this->openOTPsendRequestStatus === "pushSuccess")) {
			return true;
		} else {
			if (class_exists('TwoFactorException')) {
				// OC >= 9.2
				throw new TwoFactorException($error_message);
			} else {
				// OC <= 9.1
				return false;
			}
		}
	}

	/**
	 * Decides whether 2FA is enabled for the given user
	 * This method is called after the user has successfully finished the first
	 * authentication step i.e.
	 * He authenticated with username and password.
	 *
	 * @param IUser $user
	 * @return boolean
	 */
	public function isTwoFactorAuthEnabledForUser(IUser $user): bool
	{
		// Get User Config
		$user_enable_openotp = $this->config->getUserValue($user->getUID(), OpenOTPAuthApp::APP_ID, 'enable_openotp');
		// $allow_user_administer_openotp = $this->getAppValue('rcdevsopenotp_allow_user_administer_openotp');
		$allow_user_administer_openotp = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_allow_user_administer_openotp');
		// $disable_otp_local_users = $this->getAppValue('rcdevsopenotp_disable_otp_local_users');
		$disable_otp_local_users = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_disable_otp_local_users');
		// $authentication_method = $this->getAppValue('rcdevsopenotp_authentication_method');
		$authentication_method = $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_authentication_method');
		// 0 => AUTHENTICATION_METHOD_STD (Standard)
		// 1 => AUTHENTICATION_METHOD_OTP (OTP)

		// if ($disable_otp_local_users === "on" && $user->getBackend()->getBackendName() === "Database") {
		if ($disable_otp_local_users === "on") {
			$this->logger->info("2FA NOT ACTIVED", array('app' => OpenOTPAuthApp::APP_ID));
			return false;
		}

		if (($allow_user_administer_openotp === "off" && $authentication_method === "1") ||
			($allow_user_administer_openotp === "on" && $user_enable_openotp === "yes")
		) {
			$this->logger->info("2FA ACTIVED", array('app' => OpenOTPAuthApp::APP_ID));
			return true;
		}

		$this->logger->info("2FA NOT ACTIVED", array('app' => OpenOTPAuthApp::APP_ID));
		return false;
	}
}
