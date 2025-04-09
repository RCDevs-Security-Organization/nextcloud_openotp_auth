<!--
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
-->

<template>
	<opaMain>
		<h2>{{ getT('OpenOTP Two-Factor Authentication Settings') }}</h2>
		<h3>{{ getT('Installed version') }} : {{ installedVersion }}</h3>

		<opaSettingsContainer id="appName">
			<opaSettingsHeader>
				<opaItem>{{ getT('Enter your OpenOTP server settings in the fields below.') }}</opaItem>
				<opaItem>{{ getT('You can also enable or disable Two-Factor Authentication by editing the personal settings on the Users page.') }}</opaItem>
			</opaSettingsHeader>

			<opaSettingsPartsContainer>
				<opaSettingsRow>
					<opaItem class="opaSettingsLabel">
						{{ getT('OpenOTP server URL') + '#1' }}
					</opaItem>
					<opaItem class="opaSettingsInput">
						<input id="openotp_server_url1" ref="serverUrl1" v-model="serverUrl1" type="text" :name="openotp_server_url1" maxlength="300" :placeholder="`${placeHolderServerUrl}`" />
						<deleteIcon @click="resetValueAndCo('serverUrl1')">x</deleteIcon>
					</opaItem>
					<opaItem class="opaSettingsImage" @click="testConnection('1')">
						<transition name="fade">
							<img v-if="!reqServerUrl['1'].enable" class="opaClickable statusLoader" :src="disableImg" />
							<img v-if="reqServerUrl['1'].request" class="opaClickable statusLoader statusRequest" :src="requestImg" />
							<img v-if="!reqServerUrl['1'].request && reqServerUrl['1'].status" class="opaClickable statusLoader" :src="successImg" />
							<img v-if="!reqServerUrl['1'].request && !reqServerUrl['1'].status" class="opaClickable statusLoader" :src="failureImg" />
						</transition>
					</opaItem>
				</opaSettingsRow>
			</opaSettingsPartsContainer>

			<opaSettingsPartsContainer>
				<opaSettingsRow>
					<opaItem class="opaSettingsLabel">
						{{ getT('OpenOTP server URL') + '#2' }}
					</opaItem>
					<opaItem class="opaSettingsInput">
						<input id="openotp_server_url2" ref="serverUrl2" v-model="serverUrl2" type="text" :name="openotp_server_url2" maxlength="300" :placeholder="`${placeHolderServerUrl}`" />
						<deleteIcon @click="resetValueAndCo('serverUrl2')">x</deleteIcon>
					</opaItem>
					<opaItem class="opaSettingsImage" @click="testConnection('2')">
						<transition name="fade">
							<img v-if="!reqServerUrl['2'].enable" class="opaClickable statusLoader" :src="disableImg" />
							<img v-if="reqServerUrl['2'].request" class="opaClickable statusLoader statusRequest" :src="requestImg" />
							<img v-if="!reqServerUrl['2'].request && reqServerUrl['2'].status" class="opaClickable statusLoader" :src="successImg" />
							<img v-if="!reqServerUrl['2'].request && !reqServerUrl['2'].status" class="opaClickable statusLoader" :src="failureImg" />
						</transition>
					</opaItem>
				</opaSettingsRow>
			</opaSettingsPartsContainer>

			<opaSettingsPartsContainer>
				<opaSettingsRow>
					<opaItem class="opaSettingsLabel">
						{{ getT('OpenOTP client ID') }}
					</opaItem>
					<opaItem class="opaSettingsInput">
						<input id="openotp_client_id" ref="clientId" v-model="clientId" type="text" :name="openotp_client_id" maxlength="300" :placeholder="`${placeHolderServerUrl}`" />
						<deleteIcon @click="resetValueAndCo('clientId')">x</deleteIcon>
					</opaItem>
				</opaSettingsRow>
			</opaSettingsPartsContainer>

			<opaSettingsPartsContainer>
				<opaSettingsRow>
					<opaItem class="opaSettingsLabel">
						{{ getT('OpenOTP API key') }}
					</opaItem>
					<opaItem class="opaSettingsInput">
						<input id="api_key" ref="apiKey" v-model="apiKey" type="text" name="api_key" maxlength="256" :placeholder="`${placeHolderApiKey}`" />
						<deleteIcon @click="resetValueAndCo('apiKey')">x</deleteIcon>
					</opaItem>
				</opaSettingsRow>
			</opaSettingsPartsContainer>
		</opaSettingsContainer>

		<opaSettingsContainer id="proxy">
			<opaSettingsPartsContainer>
				<opaSettingsRow>
					<opaItem class="opaSettingsLabel">
						{{ getT('Proxy Host') }}
					</opaItem>
					<opaItem class="opaSettingsInput">
						<input id="proxy_host" ref="proxyHost" v-model="proxyHost" type="text" name="proxy_host" maxlength="255" />
						<deleteIcon @click="resetValueAndCo('proxyHost')">x</deleteIcon>
					</opaItem>
				</opaSettingsRow>
			</opaSettingsPartsContainer>

			<opaSettingsPartsContainer>
				<opaSettingsRow>
					<opaItem class="opaSettingsLabel">
						{{ getT('Proxy ') }}
					</opaItem>
					<opaItem class="opaSettingsInput">
						<input id="proxy_port" ref="proxyPort" v-model="proxyPort" type="number" name="proxy_port" min="1" max="65535" />
						<deleteIcon @click="resetValueAndCo('proxyPort')">x</deleteIcon>
					</opaItem>
				</opaSettingsRow>
			</opaSettingsPartsContainer>

			<opaSettingsPartsContainer>
				<opaSettingsRow>
					<opaItem class="opaSettingsLabel">
						{{ getT('Proxy Username') }}
					</opaItem>
					<opaItem class="opaSettingsInput">
						<input id="proxy_username" ref="proxyUsername" v-model="proxyUsername" type="text" name="proxy_username" maxlength="255" />
						<deleteIcon @click="resetValueAndCo('proxyUsername')">x</deleteIcon>
					</opaItem>
				</opaSettingsRow>
			</opaSettingsPartsContainer>

			<opaSettingsPartsContainer>
				<opaSettingsRow>
					<opaItem class="opaSettingsLabel">
						{{ getT('Proxy Password') }}
					</opaItem>
					<opaItem class="opaSettingsInput">
						<input id="proxy_password" ref="proxyPassword" v-model="proxyPassword" type="text" name="proxy_password" maxlength="255" />
						<deleteIcon @click="resetValueAndCo('proxyPassword')">x</deleteIcon>
					</opaItem>
				</opaSettingsRow>
			</opaSettingsPartsContainer>

			<opaSettingsPartsContainer>
				<button class="testConnection" @click="testConnection()">
					{{ getT('Test connection') }}
				</button>
			</opaSettingsPartsContainer>
		</opaSettingsContainer>

		<opaSettingsContainer id="misc">

			<opaSettingsPartsContainer class="withDoubleBottomMargin">
				<opaSettingsCol>
					<!-- <opaItem class="withSimpleBottomMargin">{{ getT('Disable OpenOTP for local users (use standard authentication)') }}</opaItem> -->
					<opaItem class="withSimpleBottomMargin">{{ getT('Enable OpenOTP for local users') }}</opaItem>
					<opaSettingsRow>
						<NcCheckboxRadioSwitch class="opaChkBox yesNo" :button-variant="true" :checked.sync="disableOtpLocalUsers" value="off" name="disableOtpLocalUsers" type="radio" button-variant-grouped="horizontal">
							<!-- {{ getT('No') }} -->
							{{ getT('Yes') }}
							<template #icon>
								<CancelIcon :size="20" />
							</template>
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch class="opaChkBox yesNo" :button-variant="true" :checked.sync="disableOtpLocalUsers" value="on" name="disableOtpLocalUsers" type="radio" button-variant-grouped="horizontal">
							<!-- {{ getT('Yes') }} -->
							{{ getT('No') }}
							<template #icon>
								<CheckIcon :size="20" />
							</template>
						</NcCheckboxRadioSwitch>
					</opaSettingsRow>
				</opaSettingsCol>
			</opaSettingsPartsContainer>

			<opaSettingsPartsContainer>
				<opaSettingsCol>
					<NcCheckboxRadioSwitch class="opaChkBox" :checked.sync="authenticationMethod" value="1" name="authenticationMethod" type="radio">{{ getT('Enable OpenOTP for all users (two-factor authentication)') }}</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch class="opaChkBox" :checked.sync="authenticationMethod" value="0" name="authenticationMethod" type="radio">{{ getT('Disable OpenOTP (standard authentication)') }}</NcCheckboxRadioSwitch>
				</opaSettingsCol>
			</opaSettingsPartsContainer>
		</opaSettingsContainer>

		<opaSaveSettings>
			<button @click="saveSettings">
				{{ getT('Save settings') }}
			</button>
			<transition name="fade">
				<p v-if="!saved" class="warning">{{ getT('Do not forget to save your settings!') }}</p>
				<p v-if="success" class="success">{{ getT('Your settings have been saved succesfully') }}</p>
				<p v-if="failure" class="failure">{{ getT('There was an error saving settings') }}</p>
			</transition>
		</opaSaveSettings>
	</opaMain>
</template>

<script>
import {loadState} from '@nextcloud/initial-state';
import axios from '@nextcloud/axios';
import {generateFilePath, generateOcsUrl} from '@nextcloud/router';
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js';
import {appName, baseUrl} from '../utils/config.js';
import {getT, checkServerUrl} from '../utils/utility.js';

const reqServerUrl = {
	'1': {
		enable: true,
		request: false,
		status: false,
		message: '',
		code: false,
	},
	'2': {
		enable: true,
		request: false,
		status: false,
		message: '',
		code: false,
	},
};

export default {
	name: 'AdminSettings',
	components: {
		NcCheckboxRadioSwitch,
	},

	data() {
		const serverUrl1 = this.$parent.serverUrl1;

		return {
			getT: getT,
			checkServerUrl: checkServerUrl,
			reqOpenOTP: [],
			reqServerUrl,
			// From DB table Settings `oc_appconfig`
			// Server intel
			installedVersion: this.$parent.installedVersion,
			serverUrl1: this.$parent.serverUrl1,
			serverUrl2: this.$parent.serverUrl2,
			clientId: this.$parent.clientId,
			apiKey: this.$parent.apiKey,
			// Proxy intel
			proxyHost: this.$parent.proxyHost,
			proxyPort: this.$parent.proxyPort,
			proxyUsername: this.$parent.proxyUsername,
			proxyPassword: this.$parent.proxyPassword,
			// Misc intel
			allowUserAdministerOpenotp: this.$parent.allowUserAdministerOpenotp,
			disableOtpLocalUsers: this.$parent.disableOtpLocalUsers,
			authenticationMethod: this.$parent.authenticationMethod,

			success: false,
			failure: false,
			saved: false,
		};
	},

	mounted() {
		this.loadingImg = generateFilePath(appName, '', 'img/') + appName + '.svg';
		this.requestImg = generateFilePath(appName, '', 'img/') + appName + '_gray.svg';
		this.successImg = generateFilePath(appName, '', 'img/') + appName + '_green.svg';
		this.failureImg = generateFilePath(appName, '', 'img/') + appName + '_red.svg';
		this.disableImg = generateFilePath(appName, '', 'img/') + appName + '_disabled.svg';

		this.reqServerUrl = {
			'1': {
				enable: true,
				request: false,
				status: false,
				message: '',
				code: false,
			},
			'2': {
				enable: true,
				request: false,
				status: false,
				message: '',
				code: false,
			},
		};

		this.placeHolderServerUrl = this.getT('Write OpenOTP url here');
		this.placeHolderApiKey = this.getT('Get API Key from OpenOTP UI');

		// Add Event Listener on all inputs
		const inputs = document.querySelectorAll('input');
		inputs.forEach((input) => {
			input.addEventListener('change', this.inputNotSaved);
		});

		// Add Event listener on NcCheckboxRadioSwitch (FYI, focus on main generated span tag to check if radio is checked or not: the radio does not throw an event)
		const attrObserver = new MutationObserver((mutations) => {
			mutations.forEach((mu) => {
				if (mu.type === 'attributes' && mu.attributeName === 'class') {
					this.inputNotSaved();
				}
			});
		});

		const ELS_test = document.querySelectorAll('.opaChkBox');
		ELS_test.forEach((el) => attrObserver.observe(el, {attributes: true}));

		document.querySelectorAll('.opaChkBox').forEach((btn) => {
			btn.addEventListener('click', () => ELS_test.forEach((el) => el.classList.toggle(btn.dataset.class)));
		});

		this.saved = true;

		// Call server check
		this.testConnection();
	},

	beforeMount() {
		const initialSettings = loadState(appName, 'initialSettings');

		// Server intel
		this.installedVersion = initialSettings.installedVersion;
		this.serverUrl1 = initialSettings.serverUrl1;
		this.serverUrl2 = initialSettings.serverUrl2;
		this.clientId = initialSettings.clientId;
		this.apiKey = initialSettings.apiKey;
		// Proxy intel
		this.proxyHost = initialSettings.proxyHost;
		this.proxyPort = initialSettings.proxyPort;
		this.proxyUsername = initialSettings.proxyUsername;
		this.proxyPassword = initialSettings.proxyPassword;
		// Misc intel
		this.allowUserAdministerOpenotp = initialSettings.allowUserAdministerOpenotp;
		this.disableOtpLocalUsers = initialSettings.disableOtpLocalUsers;
		this.authenticationMethod = initialSettings.authenticationMethod;
	},

	methods: {
		clearIcons() {
			this.reqServerUrl['1'].enable = false;
			this.reqServerUrl['2'].enable = false;
		},

		inputNotSaved(event) {
			this.saved = false;
		},

		resetValueAndCo(refData) {
			this[refData] = '';
			this.clearIcons();
			this.$refs[refData].focus();
			this.saved = false;
		},

		saveSettings() {
			this.success = false;
			this.failure = false;

			axios
				.post(generateOcsUrl(baseUrl + '/api/v1/settings/save'), {
					// appName
					rcdevsopenotp_server_url1: this.serverUrl1,
					rcdevsopenotp_server_url2: this.serverUrl2,
					rcdevsopenotp_client_id: this.clientId,
					rcdevsopenotp_api_key: this.apiKey,
					// proxy
					rcdevsopenotp_proxy_host: this.proxyHost,
					rcdevsopenotp_proxy_port: this.proxyPort,
					rcdevsopenotp_proxy_username: this.proxyUsername,
					rcdevsopenotp_proxy_password: this.proxyPassword,
					// misc
					rcdevsopenotp_allow_user_administer_openotp: this.allowUserAdministerOpenotp,
					rcdevsopenotp_disable_otp_local_users: this.disableOtpLocalUsers,
					rcdevsopenotp_authentication_method: this.authenticationMethod,
				})
				.then((response) => {
					this.success = true;
					this.saved = true;
				})
				.catch((error) => {
					this.failure = true;
					this.saved = false;
					// eslint-disable-next-line
					console.log(error);
				});
		},

		testConnection(serverNumber) {
			let apiUrl = '/api/v1/settings/check/server';

			if (serverNumber) {
				this.checkServerUrl(serverNumber, apiUrl, {reqServerUrl: this.reqServerUrl[serverNumber]});
			} else {
				this.checkServerUrl('1', apiUrl, {reqServerUrl: this.reqServerUrl['1']});
				this.checkServerUrl('2', apiUrl, {reqServerUrl: this.reqServerUrl['2']});
			}
		},

		updateId(wspId) {
			this.workspaceId = wspId;
		},
	},
};
</script>

<style>
@import '../styles/opaSettings.css';
@import '../styles/rcdevsNxC.css';
@import '../styles/utility.css';
</style>
