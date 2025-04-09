<?php

declare(strict_types=1);

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

namespace OCA\OpenOTPAuth\AppInfo;

use OCA\OpenOTPAuth\Event\DisabledByAdmin;
use OCA\OpenOTPAuth\Event\StateChanged;
use OCA\OpenOTPAuth\Listener\StateChangeActivity;
use OCA\OpenOTPAuth\Listener\StateChangeRegistryUpdater;
use OCA\OpenOTPAuth\Listener\UserDeleted;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\User\Events\UserDeletedEvent;

require_once __DIR__ . '/../../vendor/autoload.php';

class Application extends App implements IBootstrap {
	public const APP_ID = 'openotp_auth';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(StateChanged::class, StateChangeActivity::class);
		$context->registerEventListener(StateChanged::class, StateChangeRegistryUpdater::class);
		$context->registerEventListener(DisabledByAdmin::class, StateChangeActivity::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeleted::class);
	}

	public function boot(IBootContext $context): void {
	}
}
