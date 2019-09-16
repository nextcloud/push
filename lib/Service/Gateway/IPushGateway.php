<?php declare(strict_types=1);

namespace OCA\Push\Service\Gateway;

use JsonSerializable;

interface IPushGateway {

	public function push(string $name, string $channel, JsonSerializable $payload): void;

}
