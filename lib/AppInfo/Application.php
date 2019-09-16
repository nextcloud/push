<?php declare(strict_types=1);

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

namespace OCA\Push\AppInfo;

use OC;
use OCA\Push\Helper\PushHelper;
use OCA\Push\Listener\BroadcastListener;
use OCA\Push\Service\Extensions\NextcloudFilesAppService;
use OCA\Push\Service\PushService;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\QueryException;
use OCP\Broadcast\Events\IBroadcastEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Push\IPushManager;
use OCP\Util;

class Application extends App {

	const APP_NAME = 'push';

	/**
	 * @param array $params
	 */
	public function __construct(array $params = []) {
		parent::__construct(self::APP_NAME, $params);

		BootstrapSingleton::getInstance($this->getContainer())->boot();
	}

}
