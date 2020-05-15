<?php declare(strict_types=1);

namespace OCA\Push\Service\Gateway;

use JsonSerializable;
use OCA\Push\Exception\ServiceException;

interface IPushGateway {

	/**
	 * @param string $topic
     * @param JsonSerializable $payload
	 * @throws ServiceException
	 */
	public function push(string $topic, JsonSerializable $payload): void;

}
