<?php declare(strict_types=1);

namespace OCA\Push\Service\Gateway;

use JsonSerializable;
use OCA\Push\Db\PushEvent;
use OCA\Push\Db\PushEventMapper;
use OCP\AppFramework\Utility\ITimeFactory;

class PollGateway implements IPushGateway {

	/** @var PushEventMapper */
	private $mapper;

	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(PushEventMapper $mapper,
								ITimeFactory $timeFactory) {
		$this->mapper = $mapper;
		$this->timeFactory = $timeFactory;
	}

	public function push(string $name,
						 string $channel,
						 JsonSerializable $payload): void {
		$pushEvent = new PushEvent();
		$pushEvent->setChannel($channel);
		$pushEvent->setPayload(json_encode($payload->jsonSerialize()));
		$pushEvent->setCreatedAt($this->timeFactory->getTime());

		$this->mapper->insert($pushEvent);
	}

}
