<?php declare(strict_types=1);

namespace OCA\Push\Listener;

use OCA\Push\Exception\ServiceException;
use OCA\Push\Service\PushService;
use OCP\Broadcast\Events\IBroadcastEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;

class BroadcastListener implements IEventListener {

	/** @var PushService */
	private $pushService;

	/** @var ILogger */
	private $logger;

	public function __construct(PushService $pushService,
								ILogger $logger) {
		$this->pushService = $pushService;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof IBroadcastEvent)) {
			return;
		}

		foreach ($event->getUids() as $uid) {
			try {
				$this->pushService->push(
					$event->getName(),
					$uid,
					$event->getPayload()
				);
			} catch (ServiceException $e) {
				$this->logger->logException($e, [
					'message' => 'Could not push ' . $event->getName() . ' event',
				]);
			}
		}

		// Confirm broadcasting to emitter
		$event->setBroadcasted();
	}

}
