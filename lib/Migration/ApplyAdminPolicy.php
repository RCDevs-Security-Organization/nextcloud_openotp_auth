<?php

declare(strict_types=1);

/** SPDX-License-Identifier: AGPL-3.0-or-later */

namespace OCA\OpenOTPAuth\Migration;

use OCA\OpenOTPAuth\Service\RegistrySynchronizer;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ApplyAdminPolicy implements IRepairStep {
	public function __construct(
		private RegistrySynchronizer $synchronizer,
	) {
	}

	public function getName(): string {
		return 'Apply OpenOTP administrator policy to the two-factor registry';
	}

	public function run(IOutput $output): void {
		$count = $this->synchronizer->synchronize();
		$output->info(sprintf('Updated %d OpenOTP two-factor provider states.', $count));
	}
}
