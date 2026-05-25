<?php
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
