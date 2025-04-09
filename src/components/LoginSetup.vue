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
		<p>{{ t('openotp_auth', 'Set up a security key as a second factor.') }}</p>
		<div v-if="!added" class="add-device">
			<AddDeviceDialog :http-warning="httpWarning"
				@add="onAdded" />
		</div>
		<p v-else>
			{{ t('openotp_auth', 'Your security key was added successfully. You are now being redirected to the login page.') }}
		</p>
		<p v-if="notSupported">
			{{ t('openotp_auth', 'Your browser does not support WebAuthn.') }}
		</p>
		<p v-if="httpWarning"
			id="u2f-http-warning">
			{{ t('openotp_auth', 'You are accessing this site via an insecure connection. Browsers might therefore refuse the WebAuthn authentication.') }}
		</p>
		<form ref="confirmForm" method="POST" />
	</div>
</template>

<script>
import AddDeviceDialog from './AddDeviceDialog.vue'

export default {
	name: 'LoginSetup',
	components: {
		AddDeviceDialog,
	},
	data() {
		return {
			added: false,
			notSupported: !window.PublicKeyCredential,
		}
	},
	computed: {
		httpWarning() {
			return document.location.protocol !== 'https:'
		},
	},
	methods: {
		onAdded() {
			this.added = true
			this.$refs.confirmForm.submit()
		},
	},
}
</script>

<style scoped>
.add-device {
	display: flex;
	justify-content: space-around;
}
</style>
