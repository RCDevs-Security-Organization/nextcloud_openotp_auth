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
	<div>
		<form ref="challengeForm"
			method="POST">
			<input id="challenge"
				:value="challenge"
				type="hidden"
				name="challenge">
		</form>

		<p v-if="error"
			id="webauthn-info">
			<strong>
				{{ t('openotp_auth', 'An error occurred: {msg}', {msg: error}) }}
			</strong>
			<NcButton class="btn sign"
				@click="sign">
				{{ t('openotp_auth', 'Retry') }}
			</NcButton>
		</p>
		<p v-else id="webauthn-info">
			{{ t('openotp_auth', 'Use security key') }}
		</p>
		<p id="webauthn-error"
			style="display: none">
			<strong>{{ t('mail', 'An error occurred. Please try again.') }}</strong>
		</p>

		<p v-if="notSupported">
			<em>
				{{ t('openotp_auth', 'Your browser does not support WebAuthn.') }}
			</em>
		</p>
		<p v-else-if="httpWarning"
			id="webauthn-http-warning">
			<em>
				{{ t('openotp_auth', 'You are accessing this site via an insecure connection. Browsers might therefore refuse the WebAuthn authentication.') }}
			</em>
		</p>
	</div>
</template>

<script>
import { mapGetters } from 'vuex'
import { NcButton } from '@nextcloud/vue'
import logger from '../utils/logger.js'
import { arrayToBase64String, base64StringToArray, base64url2base64 } from '../utils/base64.js'

export default {
	name: 'Challenge',

	components: {
		NcButton,
	},

	data() {
		return {
			notSupported: typeof (PublicKeyCredential) === 'undefined',
			challenge: '',
			error: undefined,
		}
	},

	computed: {
		...mapGetters({
			credentialRequestOptions: 'getCredentialRequestOptions',
		}),
		httpWarning() {
			return document.location.protocol !== 'https:'
		},
	},

	mounted() {
		this.sign()
	},

	methods: {
		async sign() {
			logger.debug('start sign')
			this.error = undefined

			// Clone request options because they are mutated later
			// TODO: make them immutable
			const publicKey = JSON.parse(JSON.stringify(this.credentialRequestOptions))

			publicKey.challenge = base64StringToArray(base64url2base64(publicKey.challenge))
			if (publicKey.allowCredentials) {
				publicKey.allowCredentials = publicKey.allowCredentials.map((data) => ({
					...data,
					id: base64StringToArray(base64url2base64(data.id)),
				}))
			}

			logger.debug('Starting webauthn authentication', { publicKey })

			let data
			try {
				data = await navigator.credentials.get({ publicKey })
			} catch (error) {
				switch (error.name) {
				case 'AbortError':
					this.error = t('openotp_auth', 'Authentication cancelled')
					break
				case 'NotAllowedError':
					this.error = t('openotp_auth', 'Authentication cancelled')
					break
				default:
					this.error = error.toString()
				}
				logger.error('challenge failed', { error })
				return
			}
			logger.debug('got credentials', { data })

			const challenge = {
				id: data.id,
				type: data.type,
				rawId: arrayToBase64String(new Uint8Array(data.rawId)),
				response: {
					authenticatorData: arrayToBase64String(new Uint8Array(data.response.authenticatorData)),
					clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
					signature: arrayToBase64String(new Uint8Array(data.response.signature)),
					userHandle: data.response.userHandle ? arrayToBase64String(new Uint8Array(data.response.userHandle)) : null,
				},
			}
			logger.debug('mapped credentials', { challenge })
			this.challenge = JSON.stringify(challenge)

			// Wait for challenge to propagate to the template
			await this.$nextTick()

			this.$refs.challengeForm.submit()
			logger.debug('submitted challengeForm')
		},
	},
}
</script>

<style scoped>
    .sign {
        margin-top: 1em;
    }
    .btn {
        margin: 0 auto;
        margin-top: 12px;
    }
</style>
