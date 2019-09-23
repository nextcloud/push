<?php declare(strict_types=1);

namespace OCA\Push\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method string getUid()
 * @method void setUid(string $uid)
 * @method string getPayload()
 * @method void setPayload(string $payload)
 * @method string getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class PushEvent extends Entity implements JsonSerializable {

	/** @var string */
	protected $name;

	/** @var string */
	protected $uid;

	/** @var string */
	protected $payload;

	/** @var int */
	protected $createdAt;

	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'payload' => json_decode($this->getPayload(), true),
			'createdAt' => $this->getCreatedAt(),
		];
	}

}
