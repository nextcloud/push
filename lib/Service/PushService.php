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


use OCA\Push\Db\PushRequest;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Push\Model\IPushRecipients;
use OCP\Push\Model\IPushWrapper;
use OCP\Push\Service\IPushService;


/**
 * Class PushService
 *
 * @package OCA\Push\Service
 */
class PushService implements IPushService {


	/** @var IGroupManager */
	private $groupManager;

	/** @var PushRequest */
	private $pushRequest;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * PushService constructor.
	 *
	 * @param IGroupManager $groupManager
	 * @param PushRequest $pushRequest
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IGroupManager $groupManager, PushRequest $pushRequest, ConfigService $configService,
		MiscService $miscService
	) {
		$this->groupManager = $groupManager;
		$this->pushRequest = $pushRequest;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param IPushWrapper $wrapper
	 */
	public function push(IPushWrapper $wrapper) {
		$this->pushRequest->save($wrapper);
	}


	/**
	 * @param IPushWrapper $wrapper
	 * @param IPushRecipients $recipients
	 */
	public function fillRecipients(IPushWrapper $wrapper, IPushRecipients $recipients) {
		$users = $recipients->getUsers();
		$users = array_merge($users, $this->getUsersFromGroups($recipients->getGroups()));

		$remove = $recipients->getRemovedUsers();
		$remove = array_merge($remove, $this->getUsersFromGroups($recipients->getRemovedGroups()));

		$users = array_values(array_diff($users, $remove));
		$wrapper->setRecipients($users);
	}


	/**
	 * @param array $groups
	 *
	 * @return array
	 */
	private function getUsersFromGroups(array $groups): array {
		$users = [];
		foreach ($groups as $groupName) {
			$group = $this->groupManager->get($groupName);
			$users = array_merge(
				$users, array_values(
						  array_map(
							  function(IUser $user) {
								  return $user->getUID();
							  }, $group->getUsers()
						  )
					  )
			);
		}

		return $users;
	}
}

