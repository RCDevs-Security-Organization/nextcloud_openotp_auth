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
 
namespace OCA\OpenOTPAuth\Activity;

use InvalidArgumentException;
use OCA\OpenOTPAuth\AppInfo\Application as OpenOTPAuthApp;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\L10N\IFactory as L10nFactory;

class Provider implements IProvider {

	/** @var L10nFactory */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ILogger */
	private $logger;

	/**
	 * @param L10nFactory $l10n
	 * @param IURLGenerator $urlGenerator
	 * @param ILogger $logger
	 */
	public function __construct(L10nFactory $l10n, IURLGenerator $urlGenerator, ILogger $logger) {
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
	}

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent $previousEvent
	 * @return IEvent
	 * @throws InvalidArgumentException
	 */
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getApp() !== OpenOTPAuthApp::APP_ID) {
			throw new InvalidArgumentException();
		}

		$l = $this->l10n->get(OpenOTPAuthApp::APP_ID, $language);

		$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.svg')));
		switch ($event->getSubject()) {
			case 'ootp_enabled_subject':
				$event->setSubject($l->t('You enabled OpenOTP two-factor authentication for your account'));
				break;
			case 'ootp_disabled_subject':
				$event->setSubject($l->t('You disabled OpenOTP two-factor authentication for your account'));
				break;
			case 'ootp_disabled_by_admin':
				$event->setSubject($l->t('OpenOTP two-factor authentication disabled by an admin'));
				break;
		}
		return $event;
	}
}
