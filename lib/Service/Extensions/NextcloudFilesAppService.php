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


namespace OCA\Push\Service\Extensions;


use OC\Share\Share;
use OC\Push\Model\Helper\PushNotification;
use OCA\Push\Helper\PushHelper;
use OCA\Push\Service\MiscService;
use OCP\Share\IShare;
use OCP\Push\Model\IPushItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;


/**
 * Class NextcloudFilesAppService
 *
 * @package OCA\Push\Service\Extensions
 */
class NextcloudFilesAppService {


	/** @var PushHelper */
	private $pushHelper;

	/** @var MiscService */
	private $miscService;


	/**
	 * NextcloudFilesAppService constructor.
	 *
	 * @param PushHelper $pushHelper
	 * @param MiscService $miscService
	 */
	public function __construct(PushHelper $pushHelper, MiscService $miscService) {
		$this->pushHelper = $pushHelper;
		$this->miscService = $miscService;
	}


	/**
	 * @param EventDispatcherInterface $eventDispatcher
	 */
	public function attach(EventDispatcherInterface $eventDispatcher) {
		$eventDispatcher->addListener(
			'OCP\Share::postShare', function(GenericEvent $e) {
			/** @var IShare $share */
			$share = $e->getSubject();
			$this->onNewShare($share);
		}
		);
	}


	/**
	 * @param IShare $share
	 */
	private function onNewShare(IShare $share) {

		$notification = new PushNotification('push', IPushItem::TTL_INSTANT);
		$notification->setTitle('Nextcloud Files');
		$notification->setLevel(PushNotification::LEVEL_SUCCESS);

		switch ($share->getShareType()) {
			case Share::SHARE_TYPE_USER:
				$notification->setMessage($share->getSharedBy() . ' shared a file with you');
				$notification->addUser($share->getSharedWith());
				break;

			case Share::SHARE_TYPE_GROUP:
				$notification->setMessage(
					$share->getSharedBy() . ' shared a file with your group \''
					. $share->getSharedWith() . "'"
				);
				$notification->addGroup($share->getSharedWith());
				break;

			default:
				return;
		}

		$notification->removeUser($share->getSharedBy());

		$this->pushHelper->pushNotification($notification);
	}
}
