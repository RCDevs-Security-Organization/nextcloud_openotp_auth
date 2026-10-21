<?php

declare(strict_types=1);

/** SPDX-License-Identifier: AGPL-3.0-or-later */

namespace OCA\OpenOTPAuth\Tests\Unit;

use DOMDocument;
use PHPUnit\Framework\TestCase;

class AppManifestTest extends TestCase {
	public function testManifestConformsToNextcloudSchema(): void {
		$appRoot = dirname(__DIR__, 2);
		$serverRoot = dirname($appRoot, 2);
		$previous = libxml_use_internal_errors(true);
		try {
			libxml_clear_errors();
			$document = new DOMDocument();
			self::assertTrue($document->load($appRoot . '/appinfo/info.xml'));
			$valid = $document->schemaValidate($serverRoot . '/resources/app-info.xsd');
			$errors = array_map(
				static fn ($error): string => trim($error->message) . ' (line ' . $error->line . ')',
				libxml_get_errors()
			);
			self::assertTrue($valid, implode("\n", $errors));
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors($previous);
		}
	}
}
