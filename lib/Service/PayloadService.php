<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Push\Service;


use daita\NcSmallPhpTools\Traits\TArrayTools;
use OCA\Push\Db\PushRequest;
use OCA\Push\Model\Polling;
use OCP\Push\Exceptions\ItemNotFoundException;
use OCP\Push\Model\IPushItem;


/**
 * Class PushService
 *
 * @package OCA\Push\Service
 */
class PayloadService {


	use TArrayTools;


	/** @var PushRequest */
	private $pushRequest;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * PushService constructor.
	 *
	 * @param PushRequest $pushRequest
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		PushRequest $pushRequest, ConfigService $configService,
		MiscService $miscService
	) {
		$this->pushRequest = $pushRequest;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param Polling $polling
	 */
	public function processPolling(Polling $polling) {
		if ($polling->getLastEventId() === -1) {
			$this->initPolling($polling);

			return;
		}

		$this->checkPolling($polling);
	}


	/**
	 * @param Polling $polling
	 */
	private function initPolling(Polling $polling) {
		$this->pushRequest->removeExpiredItems();

		$debug = $this->configService->getAppValue(ConfigService::DEBUG) === '1' ? true : false;
		$type = $this->configService->getAppValue(ConfigService::TYPE_POLLING);
		$delay = $this->configService->getAppValue(ConfigService::DELAY_POLLING);
		$polling->addMetaBool('debug', $debug);
		$polling->addMeta('polling', $type);
		$polling->addMetaInt('delay', (int)$delay);

		try {
			$this->fillPolling($polling, false);
		} catch (ItemNotFoundException $e) {
			$polling->setStatus(1);
			$polling->setLastEventId(0);
		}
	}


	/**
	 * @param Polling $polling
	 * @param bool $includeAll - include recently published events.
	 *
	 * @throws ItemNotFoundException
	 */
	private function fillPolling(Polling $polling, bool $includeAll = true) {
		$this->pushRequest->fillPollingWithItems($polling, $includeAll);
		$this->publishedItems($polling);
	}


	/**
	 * @param Polling $polling
	 */
	private function checkPolling(Polling $polling) {
		$delay = (int)$this->configService->getAppValue(ConfigService::DELAY_POLLING);
		while (true) {
			try {
				$this->fillPolling($polling, true);

				return;
			} catch (ItemNotFoundException $e) {
				if (!$this->configService->isPollingType(ConfigService::POLLING_TYPE_LONG)) {
					return;
				}

				echo ' ';
				if (ob_get_contents() !== false) {
					ob_flush();
				}
				flush();
			}

			sleep($delay);
		}
	}


	/**
	 * @param Polling $polling
	 */
	private function publishedItems(Polling $polling) {
		$ids = array_map(
			function(IPushItem $stream): int {
				return $stream->getId();
			}, $polling->getItems()
		);

		$this->pushRequest->publishedIds($ids);
	}

}

