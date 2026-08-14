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

/** @var array $_ */
$settings = $_['initialSettings'] ?? [];
$hasAdminBundle = (bool)($_['hasAdminBundle'] ?? false);
?>

<div id="admin_settings"></div>

<?php if (!$hasAdminBundle): ?>
	<div class="settings-hint">
		<p><strong>OpenOTP admin frontend bundle is missing.</strong></p>
		<p>The app was deployed from source without compiled JS assets. A server-side fallback is shown below.</p>
	</div>

	<table class="grid">
		<tr><td><strong>API Key</strong></td><td><?php p((string)($settings['apiKey'] ?? '')); ?></td></tr>
		<tr><td><strong>Server URL 1</strong></td><td><?php p((string)($settings['serverUrl1'] ?? '')); ?></td></tr>
		<tr><td><strong>Server URL 2</strong></td><td><?php p((string)($settings['serverUrl2'] ?? '')); ?></td></tr>
		<tr><td><strong>Client ID</strong></td><td><?php p((string)($settings['clientId'] ?? '')); ?></td></tr>
		<tr><td><strong>Proxy Host</strong></td><td><?php p((string)($settings['proxyHost'] ?? '')); ?></td></tr>
		<tr><td><strong>Proxy Port</strong></td><td><?php p((string)($settings['proxyPort'] ?? '')); ?></td></tr>
		<tr><td><strong>Proxy Username</strong></td><td><?php p((string)($settings['proxyUsername'] ?? '')); ?></td></tr>
		<tr><td><strong>Auth Method</strong></td><td><?php p((string)($settings['authenticationMethod'] ?? '')); ?></td></tr>
		<tr><td><strong>Disable OTP Local Users</strong></td><td><?php p((string)($settings['disableOtpLocalUsers'] ?? '')); ?></td></tr>
	</table>
<?php endif; ?>
