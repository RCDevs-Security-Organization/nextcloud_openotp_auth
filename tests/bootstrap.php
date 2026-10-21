<?php

declare(strict_types=1);

// Load class definitions only: never bootstrap or modify a live Nextcloud instance.
$serverRoot = dirname(__DIR__, 3);
require_once $serverRoot . '/3rdparty/autoload.php';
spl_autoload_register(static function (string $class) use ($serverRoot): void {
	$prefixes = [
		'OCA\\OpenOTPAuth\\' => dirname(__DIR__) . '/lib/',
		'OCP\\' => $serverRoot . '/lib/public/',
		'OC\\' => $serverRoot . '/lib/private/',
	];
	foreach ($prefixes as $prefix => $path) {
		if (str_starts_with($class, $prefix)) {
			$file = $path . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
			if (is_file($file)) require_once $file;
			return;
		}
	}
});
