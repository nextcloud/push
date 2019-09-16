<?php declare(strict_types=1);

namespace OCA\Push\Listener;

use OCA\Push\Service\PushService;
use OCP\Broadcast\Events\IBroadcastEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class BroadcastListener implements IEventListener {

	/** @var PushService */
	private $pushService;

	public function __construct(PushService $pushService) {
		$this->pushService = $pushService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof IBroadcastEvent)) {
			return;
		}

		foreach ($event->getChannels() as $channel) {
			$this->pushService->push(
				$event->getName(),
				$channel,
				$event->getPayload()
			);
		}
	}

}