<?php
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


namespace OCA\Push\Helper;


use OC\Push\Model\Helper\PushNotification;
use OC\Push\Model\PushItem;
use OC\Push\Model\PushWrapper;
use OCA\Push\Service\MiscService;
use OCA\Push\Service\PushService;
use OCP\Push\Helper\IPushHelper;
use OCP\Push\Model\Helper\IPushCallback;
use OCP\Push\Model\Helper\IPushEvent;
use OCP\Push\Model\Helper\IPushNotification;
use OCP\Push\Model\IPushItem;
use OCP\Push\Model\IPushRecipients;
use OCP\Push\Model\IPushWrapper;


/**
 * Class PushHelper
 *
 * @package OCA\Push\Helper
 */
class PushHelper implements IPushHelper {


	/** @var PushService */
	private $pushService;

	/** @var MiscService */
	private $miscService;


	/**
	 * PushHelper constructor.
	 *
	 * @param PushService $pushService
	 * @param MiscService $miscService
	 */
	public function __construct(PushService $pushService, MiscService $miscService) {
		$this->pushService = $pushService;
		$this->miscService = $miscService;
	}


	/**
	 * @param string $userId
	 *
	 * @return IPushWrapper
	 */
	public function test(string $userId): IPushWrapper {
		$notification = new PushNotification('push', IPushItem::TTL_INSTANT);
		$notification->setTitle('Testing Nextcloud Push');
		$notification->setLevel(IPushNotification::LEVEL_MESSAGE);
		$notification->setMessage("If you cannot see this, it means it is not working.");
		$notification->addUser($userId);

		return $this->pushNotification($notification);
	}


	/**
	 * @param IPushCallback $callback
	 *
	 * @return IPushWrapper
	 */
	public function toCallback(IPushCallback $callback): IPushWrapper {
		$wrapper = $this->generateFromCallback($callback);
		$this->pushService->push($wrapper);

		return $wrapper;
	}

	/**
	 * @param IPushCallback $callback
	 *
	 * @return IPushWrapper
	 */
	public function generateFromCallback(IPushCallback $callback): IPushWrapper {
		$item = new PushItem($callback->getApp(), IPushCallback::TYPE);
		$item->setSource($callback->getSource());
		$item->setTtl(IPushItem::TTL_INSTANT);
		$item->setPayload($callback->getPayload());

		$this->fillMeta($item, $callback);

		$wrapper = new PushWrapper($item);
		$this->pushService->fillRecipients($wrapper, $callback);

		return $wrapper;
	}


	/**
	 * @param IPushNotification $notification
	 *
	 * @return IPushWrapper
	 */
	public function pushNotification(IPushNotification $notification): IPushWrapper {
		$wrapper = $this->generateFromNotification($notification);
		$this->pushService->push($wrapper);

		return $wrapper;
	}

	/**
	 * @param IPushNotification $notification
	 *
	 * @return IPushWrapper
	 */
	public function generateFromNotification(IPushNotification $notification): IPushWrapper {
		$item = new PushItem($notification->getApp(), IPushNotification::TYPE);
		$item->setSource($notification->getTitle());
		$item->setTtl($notification->getTtl());
		$item->setPayload(
			[
				'message' => $notification->getMessage(),
				'level'   => $notification->getLevel()
			]
		);

		$this->fillMeta($item, $notification);

		$wrapper = new PushWrapper($item);
		$this->pushService->fillRecipients($wrapper, $notification);

		return $wrapper;
	}


	/**
	 * @param IPushEvent $event
	 *
	 * @return IPushWrapper
	 */
	public function broadcastEvent(IPushEvent $event): IPushWrapper {
		$wrapper = $this->generateFromEvent($event);
		$this->pushService->push($wrapper);

		return $wrapper;
	}

	/**
	 * @param IPushEvent $event
	 *
	 * @return IPushWrapper
	 */
	public function generateFromEvent(IPushEvent $event): IPushWrapper {
		$item = new PushItem($event->getApp(), IPushEvent::TYPE);
		$item->setSource($event->getCommand());
		$item->setTtl(IPushItem::TTL_INSTANT);
		$item->setPayload($event->getPayload());

		$this->fillMeta($item, $event);

		$wrapper = new PushWrapper($item);
		$this->pushService->fillRecipients($wrapper, $event);

		return $wrapper;
	}


	/**
	 * @param IPushItem $item
	 * @param IPushRecipients $recipients
	 */
	private function fillMeta(IPushItem $item, IPushRecipients $recipients) {
		$item->addMetaArray('filteredApps', $recipients->getFilteredApps());
		$item->addMetaArray('limitedToApps', $recipients->getLimitedToApps());
	}

}

