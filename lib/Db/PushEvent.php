<?php declare(strict_types=1);

namespace OCA\Push\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getChannel()
 * @method void setChannel(string $channel)
 * @method string getPayload()
 * @method void setPayload(string $payload)
 * @method string getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class PushEvent extends Entity implements JsonSerializable {

	/** @var string */
	protected $channel;

	/** @var string */
	protected $payload;

	/** @var int */
	protected $createdAt;

	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'channel' => $this->getChannel(),
			'payload' => json_decode($this->getPayload(), true),
			'createdAt' => $this->getCreatedAt(),
		];
	}

}
