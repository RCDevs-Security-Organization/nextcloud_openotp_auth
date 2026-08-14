<!--
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
-->

<template>
	<opaMain>
		<button class="changelog-tab" type="button" :style="{ right: changelogPanelRight }" @click="openChangelog">
			Changelog
		</button>

		<transition name="changelog-slide">
			<aside v-if="changelogOpen" class="changelog-panel" :style="{ left: changelogPanelLeft, right: changelogPanelRight }" aria-modal="true" role="dialog" aria-label="Changelog">
				<header class="changelog-panel__header">
					<h2>Changelog</h2>
					<button class="changelog-panel__close" type="button" aria-label="Close changelog" @click="closeChangelog">
						×
					</button>
				</header>
				<div class="changelog-panel__body">
					<p v-if="changelogLoading" class="changelog-panel__state">
						{{ getT('Loading') }}...
					</p>
					<p v-else-if="changelogError" class="changelog-panel__state">
						{{ changelogError }}
					</p>
					<div v-else class="changelog-markdown" v-html="changelogHtml"></div>
				</div>
			</aside>
		</transition>

		<h2>{{ getT('OpenOTP Two-Factor Authentication Settings') }}</h2>
		<h3>{{ getT('Installed version') }} : {{ installedVersion }}</h3>

		<opaSettingsContainer id="appName">
			<opaSettingsHeader>
				<opaItem>{{ getT('Enter your OpenOTP server settings in the fields below.') }}</opaItem>
				<opaItem>{{ getT('Two-Factor Authentication is managed by administrators for users.') }}</opaItem>
			</opaSettingsHeader>

			<opaSettingsPartsContainer>
				<opaSettingsRow>
					<opaItem class="opaSettingsLabel">
						{{ getT('OpenOTP server URL') + '#1' }}
					</opaItem>
					<opaItem class="opaSettingsInput">
						<input id="openotp_server_url1" ref="serverUrl1" v-model="serverUrl1" type="text" name="openotp_server_url1" maxlength="300" :placeholder="`${placeHolderServerUrl}`" @focus="storeServerUrlBeforeEdit('1')" @blur="checkServerUrlOnBlur('1')" />
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
						<input id="openotp_server_url2" ref="serverUrl2" v-model="serverUrl2" type="text" name="openotp_server_url2" maxlength="300" :placeholder="`${placeHolderServerUrl}`" @focus="storeServerUrlBeforeEdit('2')" @blur="checkServerUrlOnBlur('2')" />
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
						<input id="openotp_client_id" ref="clientId" v-model="clientId" type="text" name="openotp_client_id" maxlength="300" :placeholder="`${placeHolderServerUrl}`" />
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
						{{ getT('Proxy Port') }}
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
						<input id="proxy_password" ref="proxyPassword" v-model="proxyPassword" type="password" name="proxy_password" maxlength="255" />
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
					<opaItem class="withSimpleBottomMargin">{{ getT('Disable OpenOTP for local users (use standard authentication)') }}</opaItem>
					<opaSettingsRow>
						<NcCheckboxRadioSwitch v-model="disableOtpLocalUsers" class="opaChkBox yesNo" :button-variant="true" value="on" name="disableOtpLocalUsers" type="radio" button-variant-grouped="horizontal">
							{{ getT('Yes') }}
							<template #icon>
								<CancelIcon :size="20" />
							</template>
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch v-model="disableOtpLocalUsers" class="opaChkBox yesNo" :button-variant="true" value="off" name="disableOtpLocalUsers" type="radio" button-variant-grouped="horizontal">
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
					<NcCheckboxRadioSwitch v-model="authenticationMethod" class="opaChkBox" value="1" name="authenticationMethod" type="radio">{{ getT('Enable OpenOTP for all users (two-factor authentication)') }}</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch v-model="authenticationMethod" class="opaChkBox" value="0" name="authenticationMethod" type="radio">{{ getT('Disable OpenOTP (standard authentication)') }}</NcCheckboxRadioSwitch>
				</opaSettingsCol>
			</opaSettingsPartsContainer>
		</opaSettingsContainer>

	</opaMain>
</template>

<script>
import {loadState} from '@nextcloud/initial-state';
import axios from '@nextcloud/axios';
import {showError, showSuccess} from '@nextcloud/dialogs';
import {generateFilePath, generateUrl} from '@nextcloud/router';
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch';
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
const AUTOSAVE_DELAY = 600;
const initialSettings = loadState(appName, 'initialSettings') || {};

export default {
	name: 'AdminSettings',
	components: {
		NcCheckboxRadioSwitch,
	},
	watch: {
		serverUrl1: 'queueSaveSettings',
		serverUrl2: 'queueSaveSettings',
		clientId: 'queueSaveSettings',
		apiKey: 'queueSaveSettings',
		proxyHost: 'queueSaveSettings',
		proxyPort: 'queueSaveSettings',
		proxyUsername: 'queueSaveSettings',
		proxyPassword: 'queueSaveSettings',
		disableOtpLocalUsers: 'queueSaveSettings',
		authenticationMethod: 'queueSaveSettings',
	},

	data() {
		return {
			getT: getT,
			checkServerUrl: checkServerUrl,
			reqOpenOTP: [],
			reqServerUrl,
			serverUrlBeforeEdit: {},
			// From DB table Settings `oc_appconfig`
			// Server intel
			installedVersion: initialSettings.installedVersion || '',
			serverUrl1: initialSettings.serverUrl1 || '',
			serverUrl2: initialSettings.serverUrl2 || '',
			clientId: initialSettings.clientId || '',
			apiKey: initialSettings.apiKey || '',
			// Proxy intel
			proxyHost: initialSettings.proxyHost || '',
			proxyPort: initialSettings.proxyPort || '',
			proxyUsername: initialSettings.proxyUsername || '',
			proxyPassword: initialSettings.proxyPassword || '',
			// Misc intel
			allowUserAdministerOpenotp: initialSettings.allowUserAdministerOpenotp || '',
			disableOtpLocalUsers: initialSettings.disableOtpLocalUsers || '',
			authenticationMethod: initialSettings.authenticationMethod || '',

			success: false,
			failure: false,
			saved: false,
			autosaveReady: false,
			saveTimeout: null,
			saveInProgress: false,
			saveAgain: false,
			changelogOpen: false,
			changelogLoading: false,
			changelogError: '',
			changelogContent: '',
			changelogPanelLeft: '300px',
			changelogPanelRight: '0px',
		};
	},

	mounted() {
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

		this.saved = true;
		this.autosaveReady = true;

		// Call server check
		this.testConnection();
		this.updateChangelogLayout();
		window.addEventListener('keydown', this.handleKeydown);
		window.addEventListener('resize', this.updateChangelogLayout);
	},

	beforeUnmount() {
		if (this.saveTimeout !== null) {
			clearTimeout(this.saveTimeout);
		}
		window.removeEventListener('keydown', this.handleKeydown);
		window.removeEventListener('resize', this.updateChangelogLayout);
	},

	methods: {
		handleKeydown(event) {
			if (event.key === 'Escape' && this.changelogOpen) {
				this.closeChangelog();
			}
		},

		openChangelog() {
			this.updateChangelogLayout();
			this.changelogOpen = true;

			if (this.changelogContent || this.changelogLoading) {
				return;
			}

			this.changelogLoading = true;
			this.changelogError = '';

			axios
				.get(generateUrl(baseUrl + '/api/v1/settings/changelog'))
				.then((response) => {
					if (response.data?.status === true) {
						this.changelogContent = response.data.content || '';
					} else {
						this.changelogError = this.getT('Could not read changelog');
					}
				})
				.catch(() => {
					this.changelogError = this.getT('Could not read changelog');
				})
				.finally(() => {
					this.changelogLoading = false;
				});
		},

		closeChangelog() {
			this.changelogOpen = false;
		},

		updateChangelogLayout() {
			const content = this.$el?.closest('#app-content-vue, .app-content')
				|| document.querySelector('#app-content-vue, .app-content')
				|| document.querySelector('#content');
			const contentRect = content?.getBoundingClientRect();
			const bodyStyles = window.getComputedStyle(document.body);
			const bodyContainerMargin = parseFloat(bodyStyles.getPropertyValue('--body-container-margin')) || 0;
			const contentRight = contentRect && contentRect.right > 0 ? window.innerWidth - contentRect.right : 0;
			this.changelogPanelRight = `${Math.max(bodyContainerMargin, Math.ceil(contentRight))}px`;

			if (window.innerWidth < 1024) {
				this.changelogPanelLeft = '0px';
				return;
			}

			const navigation = document.querySelector('#app-navigation, #app-navigation-vue');
			const navigationRect = navigation?.getBoundingClientRect();

			if (!navigationRect || navigationRect.right <= 0) {
				this.changelogPanelLeft = '300px';
				return;
			}

			this.changelogPanelLeft = `${Math.ceil(navigationRect.right)}px`;
		},

		escapeHtml(value) {
			return String(value)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		},

		renderInlineMarkdown(value) {
			return this.escapeHtml(value)
				.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
				.replace(/`([^`]+)`/g, '<code>$1</code>')
				.replace(/\[([^\]]+)\]\((https?:\/\/[^)]+)\)/g, '<a href="$2" target="_blank" rel="noreferrer noopener">$1</a>');
		},

		renderMarkdown(markdown) {
			let inList = false;
			let inCode = false;
			const html = [];

			markdown.split(/\r?\n/).forEach((line) => {
				if (line.trim().startsWith('```')) {
					if (inCode) {
						html.push('</code></pre>');
						inCode = false;
					} else {
						if (inList) {
							html.push('</ul>');
							inList = false;
						}
						html.push('<pre><code>');
						inCode = true;
					}
					return;
				}

				if (inCode) {
					html.push(this.escapeHtml(line) + '\n');
					return;
				}

				if (line.trim() === '') {
					if (inList) {
						html.push('</ul>');
						inList = false;
					}
					return;
				}

				const title = line.match(/^(#{1,4})\s+(.+)$/);
				if (title) {
					if (inList) {
						html.push('</ul>');
						inList = false;
					}
					const level = title[1].length;
					html.push(`<h${level}>${this.renderInlineMarkdown(title[2])}</h${level}>`);
					return;
				}

				const item = line.match(/^\s*[-*]\s+(.+)$/);
				if (item) {
					if (!inList) {
						html.push('<ul>');
						inList = true;
					}
					html.push(`<li>${this.renderInlineMarkdown(item[1])}</li>`);
					return;
				}

				if (inList) {
					html.push('</ul>');
					inList = false;
				}

				html.push(`<p>${this.renderInlineMarkdown(line)}</p>`);
			});

			if (inList) {
				html.push('</ul>');
			}
			if (inCode) {
				html.push('</code></pre>');
			}

			return html.join('');
		},

		clearIcons() {
			this.reqServerUrl['1'].enable = false;
			this.reqServerUrl['2'].enable = false;
		},

		queueSaveSettings() {
			if (!this.autosaveReady) {
				return;
			}

			this.saved = false;
			if (this.saveTimeout !== null) {
				clearTimeout(this.saveTimeout);
			}

			this.saveTimeout = setTimeout(() => {
				this.saveTimeout = null;
				this.saveSettings();
			}, AUTOSAVE_DELAY);
		},

		storeServerUrlBeforeEdit(serverNumber) {
			this.serverUrlBeforeEdit[serverNumber] = this.getServerUrlValue(serverNumber);
		},

		checkServerUrlOnBlur(serverNumber) {
			const currentValue = this.getServerUrlValue(serverNumber);

			if (currentValue === this.serverUrlBeforeEdit[serverNumber]) {
				return;
			}

			this.serverUrlBeforeEdit[serverNumber] = currentValue;
			this.testConnection(serverNumber);
		},

		getServerUrlValue(serverNumber) {
			return this[`serverUrl${serverNumber}`] || '';
		},

		resetValueAndCo(refData) {
			this[refData] = '';
			this.clearIcons();
			this.$refs[refData].focus();
			this.saved = false;
		},

		saveSettings() {
			if (this.saveInProgress) {
				this.saveAgain = true;
				return;
			}

			this.success = false;
			this.failure = false;
			this.saveInProgress = true;

			axios
				.post(generateUrl(baseUrl + '/api/v1/settings/save'), {
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
					if (!this.saveAgain) {
						showSuccess(this.getT('OpenOTP settings saved'), {timeout: 2000});
					}
				})
				.catch((error) => {
					this.failure = true;
					this.saved = false;
					showError(this.getT('OpenOTP settings could not be saved'));
					// eslint-disable-next-line
					console.log(error);
				})
				.finally(() => {
					this.saveInProgress = false;
					if (this.saveAgain) {
						this.saveAgain = false;
						this.queueSaveSettings();
					}
				});
		},

		testConnection(serverNumber) {
			const apiUrl = '/api/v1/settings/check/server';

			if (serverNumber) {
				this.checkServerUrl(serverNumber, apiUrl, {
					reqServerUrl: this.reqServerUrl[serverNumber],
					serverUrl: this.getServerUrlValue(serverNumber),
				});
			} else {
				this.checkServerUrl('1', apiUrl, {reqServerUrl: this.reqServerUrl['1'], serverUrl: this.serverUrl1});
				this.checkServerUrl('2', apiUrl, {reqServerUrl: this.reqServerUrl['2'], serverUrl: this.serverUrl2});
			}
		},

		updateId(wspId) {
			this.workspaceId = wspId;
		},
	},

	computed: {
		changelogHtml() {
			return this.renderMarkdown(this.changelogContent);
		},
	},
};
</script>

<style>
@import '../styles/opaSettings.css';
@import '../styles/rcdevsNxC.css';
@import '../styles/utility.css';
</style>
