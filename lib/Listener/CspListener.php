<?php declare(strict_types=1);

namespace OCA\Push\Listener;

use OCA\Push\Service\Gateway\MercureGateway;
use OCA\Push\Service\GatewayFactory;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class CspListener implements IEventListener {

	/** @var GatewayFactory */
	private $gatewayFactory;

	public function __construct(GatewayFactory $gatewayFactory) {
		$this->gatewayFactory = $gatewayFactory;
	}

	public function handle(Event $event): void {
		if (!($event instanceof AddContentSecurityPolicyEvent)) {
			return;
		}

		$gateway = $this->gatewayFactory->getGateway();
		if ($gateway instanceof MercureGateway) {
			$csp = new ContentSecurityPolicy();
			$csp->addAllowedConnectDomain($gateway->getUrl());
			$event->addPolicy($csp);
		}
	}

}
