<?php declare(strict_types=1);

namespace OCA\Push\Service\Gateway;

use JsonSerializable;
use OCA\Push\Exception\ServiceException;

interface IPushGateway {

	/**
	 * @param string $name
	 * @param string $channel
	 * @param string $uid
	 * @param JsonSerializable $payload
	 * @throws ServiceException
	 */
	public function push(string $name, string $channel, string $uid, JsonSerializable $payload): void;

}
