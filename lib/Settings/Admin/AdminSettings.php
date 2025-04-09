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
 
/**
 * OpenOTP authentication Config
 * @package openotp_auth
 */
namespace OCA\OpenOTPAuth\Settings\Admin;

use OCA\OpenOTPAuth\AppInfo\Application as OpenOTPAuthApp;
use OCA\OpenOTPAuth\Config;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

class AdminSettings implements ISettings
{	
		/** @var IConfig */
		// protected $config;
	
		/**
		 * @param IConfig $config
		 */
		public function __construct(private IConfig $config, private IInitialState $initialState)
		{
		}
	
		/**
		 * @return TemplateResponse
		 */
		public function getForm(): TemplateResponse
		{
			$initialSettings = [
	
				//'server_url' =>         $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'server_url'),
	
				'installedVersion'  			=> $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'installed_version'),
				'apiKey'            			=> $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_api_key'),
				'serverUrl1'      				=> $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_server_url1'),
				'serverUrl2'    				=> $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_server_url2'),
				'clientId'						=> $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_client_id'),
				'proxyHost'         			=> $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_host'),
				'proxyPort'         			=> $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_port'),
				'proxyUsername'     			=> $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_username'),
				'proxyPassword'     			=> $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_proxy_password'),
				'allowUserAdministerOpenotp'	=> $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_allow_user_administer_openotp'),
				'disableOtpLocalUsers'          => $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_disable_otp_local_users'),
				'authenticationMethod'          => $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'rcdevsopenotp_authentication_method'),
				// 'types'          				=> $this->config->getAppValue(OpenOTPAuthApp::APP_ID, 'types'),
			];
	
			$this->initialState->provideInitialState('initialSettings', $initialSettings);
	
			Util::addScript(Config::APP_ID, Config::APP_ID . '-admin-settings');
	
			return new TemplateResponse(Config::APP_ID, 'settings/admin-settings', [], '');
		}
	
		/**
		 * @return string the section ID, e.g. 'sharing'
		 */
		public function getSection()
		{
			return OpenOTPAuthApp::APP_ID;
		}
	
		/**
		 * @return int whether the form should be rather on the top or bottom of
		 * the admin section. The forms are arranged in ascending order of the
		 * priority values. It is required to return a value between 0 and 100.
		 *
		 * E.g.: 70
		 */
		public function getPriority()
		{
			return 55;
		}
	
}
