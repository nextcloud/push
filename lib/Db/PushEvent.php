<?php declare(strict_types=1);

namespace OCA\Push\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getChannel()
 * @method void setChannel(string $channel)
 * @method string getPayload()
 * @method void setPayload(string $payload)
 * @method string getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class PushEvent extends Entity {

	protected $channel;
	protected $payload;
	protected $createdAt;

}