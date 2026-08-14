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

namespace OCA\OpenOTPAuth\Provider;

use Exception;
use OCA\OpenOTPAuth\AuthService\OpenotpAuth;
use OCA\OpenOTPAuth\Config;
use OCA\OpenOTPAuth\Helper\RequestParamHelper;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\TwoFactorException;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\IUserManager;
use OCP\Template\ITemplate;
use OCP\Template\ITemplateManager;
use OCP\Util;
use Psr\Log\LoggerInterface;

class OpenOTPsendRequestException extends Exception {}

class TwoFactorRCDevsOpenOTPProvider implements IProvider
{
	/** @var array<string,mixed> */
	private array $challenge_params = [];
	private string $openOTPsendRequestStatus = '';

	public function __construct(
		private IAppManager $appManager,
		private IL10N $trans,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
		private IAppConfig $appConfig,
		private IRequest $request,
		private ISession $session,
		private ITemplateManager $templateManager,
		private IUserManager $userManager,
	) {
	}

	/**
	 * Get unique identifier of this 2FA provider
	 *
	 * @return string
	 */
	public function getId(): string
	{
		// return $this->otpname;
		return Config::APP_ID;
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
	private function openOTPsendRequest(IUser $user, ?string $otp = null, ?string $sample = null): void
	{
		$username = $user->getUID();
		$managedUser = $this->userManager->get($username);
		if ($managedUser !== null) {
			$user = $managedUser;
		}

		$message = [];
		$params = [];
		//Clean Session Nonce /!\  must be used only for Push request response
		$this->session->remove('rcdevsopenotp_nonce');
		$this->logger->info("********* New OpenOTP Authentication *********", array('app' => Config::APP_ID));

		$params['rcdevsopenotp_allow_user_administer_openotp'] =	'off';
		$params['rcdevsopenotp_api_key'] =							$this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_api_key');
		$params['rcdevsopenotp_authentication_method'] =			$this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_authentication_method');
		$params['rcdevsopenotp_client_id'] =						$this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_client_id');
		$params['rcdevsopenotp_disable_otp_local_users'] =			$this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_disable_otp_local_users');
		$params['rcdevsopenotp_proxy_host'] =						$this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_proxy_host');
		$params['rcdevsopenotp_proxy_password'] =					$this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_proxy_password');
		$params['rcdevsopenotp_proxy_port'] =						$this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_proxy_port');
		$params['rcdevsopenotp_proxy_username'] =					$this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_proxy_username');
		$params['rcdevsopenotp_server_url1'] =						$this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_server_url1');
		$params['rcdevsopenotp_server_url2'] =						$this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_server_url2');

		$appPath = '';
		try {
			$appPath = $this->appManager->getAppPath(Config::APP_ID);
		} catch (AppPathNotFoundException $e) {
		}
		$appWebPath = $this->urlGenerator->linkTo(Config::APP_ID, '');

		$openotpAuth = new OpenotpAuth($this->logger, $params, $appPath, (string)$this->request->getRemoteAddress());

		// Get context cookie
		$context_name = $openotpAuth->getContextName();
		$context_size = $openotpAuth->getContextSize();
		$context_time = $openotpAuth->getContextTime();

		$contextCookie = $this->request->getCookie($context_name);
		if (is_string($contextCookie) && $contextCookie !== '') {
			$context = $contextCookie;
		} else {
			$context = bin2hex(random_bytes(max(1, intdiv($context_size, 2))));
		}

		$domain = "";
		$password = null;
		/* Don't check LDAP password, validate localy OR via third party User integration (LDAP plugin, etc...) */
		$option = "-LDAP,WEBAUTH";

		$u2f = RequestParamHelper::requestStringParam($this->request, 'openotp_u2f');
		if ($u2f !== '') {
			$otp = null;
		}
		$state = RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_session');

		$t_domain = $openotpAuth->getDomain($username);
		if (is_array($t_domain)) {
			$username = $t_domain['username'];
			$domain = $t_domain['domain'];
		} elseif (($requestDomain = RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_domain')) !== '') {
			$domain = $requestDomain;
		}
		else $domain = $t_domain;
		if ($domain !== "") $this->logger->info("Domain found in username field", array('app' => Config::APP_ID));

		if ($state !== "") {
			// OpenOTP Challenge
			$this->logger->info("New OpenOTP Challenge for user " . $username, array('app' => Config::APP_ID));
			$resp = $openotpAuth->openOTPChallenge($username, $domain, $state, $otp, $u2f, $sample);
		} else {
			// OpenOTP Login
			$this->logger->info("New OpenOTP SimpleLogin for user " . $username, array('app' => Config::APP_ID));
			$resp = $openotpAuth->openOTPSimpleLogin($username, $domain, $password, $option, $context);
		}

		if (!$resp || !isset($resp['code'])) {
			$this->logger->error("Invalid OpenOTP response for user " . $username, array('app' => Config::APP_ID));
			$message[] = $this->trans->t("Invalid OpenOTP response for user") . " " . $username;
			throw new OpenOTPsendRequestException(implode(', ', $message));
		}

		switch ($resp['code']) {
			case '0':
				if ($resp['message']) $message[] = $resp['message'];
				else $message[] = $this->trans->t("OpenOTP Authentication failed for user " . $username);
				$this->logger->info("OpenOTP Authentication failed for user " . $username, array('app' => Config::APP_ID));
				$this->openOTPsendRequestStatus = "error";
				break;
			case '1':
				$this->logger->info("User $username has authenticated with OpenOTP.", array('app' => Config::APP_ID));
				if (!$state) {
					$this->openOTPsendRequestStatus = "pushSuccess";
					// App-scoped nonce (40 hex chars) to replace deprecated CSP-dependent nonce
					$rcdevsopenotp_nonce = bin2hex(random_bytes(20));


					$this->challenge_params['rcdevsopenotp_nonce'] = $rcdevsopenotp_nonce;
					$this->session->set('rcdevsopenotp_nonce', $rcdevsopenotp_nonce);
				} else $this->openOTPsendRequestStatus = "success";

				// set context cookie
				if (extension_loaded('openssl')) {
					if (strlen($context) === $context_size)	setcookie(
						name: $context_name,
						value: $context,
						expires_or_options: time() + $context_time,
						path: '/',
						secure: true,
						httponly: true
					);
				} else {
					$this->logger->info("Openssl extension not loaded - context authentication not available", array('app' => Config::APP_ID));
				}

				break;
			case '2':
				$this->logger->info("OpenOTP Response require Challenge", array('app' => Config::APP_ID));
				$encodedResponse = json_encode($resp);
				$this->logger->debug($encodedResponse !== false ? $encodedResponse : '', array('app' => Config::APP_ID));

				$otpChallenge = RequestParamHelper::stringValue($resp['otpChallenge'] ?? '');
				$this->challenge_params = [
					'rcdevsopenotp_otpChallenge'							=> ($otpChallenge !== '' ? $otpChallenge : null),
					'rcdevsopenotp_u2fChallenge'							=> (array_key_exists('u2fChallenge', $resp) ? $resp['u2fChallenge'] : null),
					'rcdevsopenotp_voiceLogin'								=> ($otpChallenge !== '' ? strstr($otpChallenge, "VOICE") : null),
					'rcdevsopenotp_voiceOnly'								=> ($otpChallenge !== '' ? strcmp($otpChallenge, "VOICE") == 0 : -1),
					'rcdevsopenotp_message'									=> $resp['message'],
					'rcdevsopenotp_username'								=> $username,
					'rcdevsopenotp_session'									=> $resp['session'],
					'rcdevsopenotp_timeout'									=> (array_key_exists('timeout', $resp) ? $resp['timeout'] : 0),
					'rcdevsopenotp_password'								=> $password,
					'rcdevsopenotp_appPath'									=> $appPath,
					'rcdevsopenotp_appWebPath'								=> $appWebPath,
					'rcdevsopenotp_domain'									=> $domain,
				];
				$this->openOTPsendRequestStatus = "challenge";
				break;
			default:
				$this->trans->t("OpenOTP Authentication failed for user") . " " . $username;
				$this->logger->info("OpenOTP Authentication failed for user " . $username, array('app' => Config::APP_ID));
				$this->openOTPsendRequestStatus = "error";
				break;
		}

		if ($message) throw new OpenOTPsendRequestException(implode(", ", $message));
	}

	/**
	 * Get the template for rending the 2FA provider view
	 *
	 * @param IUser $user
	 * @return ITemplate
	 */
	public function getTemplate(IUser $user): ITemplate
	{
		try {
			$this->openOTPsendRequest($user);
		} catch (OpenOTPsendRequestException $e) {
			$error_message = $e->getMessage();
		}

		$template = $this->templateManager->getTemplate(Config::APP_ID, 'challenge');
		$displayName = $user->getDisplayName();

		$template->assign(
			"userID",
			$displayName !== '' ? $displayName : $user->getUID()
		);
		$template->assign("status", $this->openOTPsendRequestStatus);
		$template->assign("error_msg", isset($error_message) ? $error_message : "");
		$template->assign("challenge_params", $this->challenge_params);

		Util::addStyle(Config::APP_ID, 'settings');
		Util::addScript(Config::APP_ID, 'challenge/base64');
		Util::addScript(Config::APP_ID, 'challenge/fidou2f');
		Util::addScript(Config::APP_ID, 'challenge/voice');

		return $template;
	}

	/**
	 * Verify the given challenge
	 *
	 * @UseSession
	 * @param IUser $user
	 * @param string $challenge => OTP password
	 * @return bool True in case of success
	 */
	public function verifyChallenge(IUser $user, string $challenge): bool
	{
		$postNonce = RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_nonce');
		$sessionNonce = RequestParamHelper::stringValue($this->session->get('rcdevsopenotp_nonce'));

		$this->logger->debug("----- verifyChallenge -------:" . $challenge, array('app' => Config::APP_ID));
		$this->logger->debug('POST NONCE:' . $postNonce, array('app' => Config::APP_ID));
		$this->logger->debug("SESSION NONCE:" . $sessionNonce, array('app' => Config::APP_ID));

		$rcdevsopenotp_nonce = "";
		$nonce = "";

		if ($sessionNonce !== '') {
			$rcdevsopenotp_nonce =  $sessionNonce;
			$this->session->remove('rcdevsopenotp_nonce');
		}
		$nonce = $postNonce;
		$this->logger->info("SESSION NONCE SUPP:" . RequestParamHelper::stringValue($this->session->get('rcdevsopenotp_nonce')), array('app' => Config::APP_ID));
		if ($challenge === "passme" && $nonce && $rcdevsopenotp_nonce && $nonce === $rcdevsopenotp_nonce) return true;

		try {
			$this->openOTPsendRequest($user, $challenge, RequestParamHelper::requestStringParam($this->request, 'rcdevsopenotp_sample'));
		} catch (OpenOTPsendRequestException $e) {
			$error_message = $e->getMessage();
		}

		if ($this->openOTPsendRequestStatus && ($this->openOTPsendRequestStatus === "success" || $this->openOTPsendRequestStatus === "pushSuccess")) {
			return true;
		}

		throw new TwoFactorException($error_message ?? $this->trans->t('OpenOTP Authentication failed'));
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
		$disable_otp_local_users = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_disable_otp_local_users');
		$authentication_method = $this->appConfig->getValueString(Config::APP_ID, 'rcdevsopenotp_authentication_method');
		// 0 => AUTHENTICATION_METHOD_STD (Standard)
		// 1 => AUTHENTICATION_METHOD_OTP (OTP)

		$backend = $user->getBackend();
		if ($disable_otp_local_users === "on" && $backend instanceof IUserBackend && $backend->getBackendName() !== 'LDAP') {
			$this->logger->info("2FA NOT ACTIVED", array('app' => Config::APP_ID));
			return false;
		}

		if ($authentication_method === "1") {
			$this->logger->info("2FA ACTIVED", array('app' => Config::APP_ID));
			return true;
		}

		$this->logger->info("2FA NOT ACTIVED", array('app' => Config::APP_ID));
		return false;
	}
}
