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

use JsonSerializable;
use OCA\Push\Exception\ServiceException;
use OCP\IGroupManager;
use OCP\IUser;

class PushService {

	/** @var GatewayFactory */
	private $gatewayFactory;

	public function __construct(GatewayFactory $gatewayFactory) {
		$this->gatewayFactory = $gatewayFactory;
	}

	/**
	 * @param string $name
	 * @param string $channel
	 * @param string $uid
	 * @param JsonSerializable $payload
	 * @throws ServiceException
	 */
	public function push(string $name,
						 string $channel,
						 string $uid,
						 JsonSerializable $payload) {
		$this->gatewayFactory->getGateway()->push(
			$name,
			$channel,
			$uid,
			$payload
		);
	}

}
