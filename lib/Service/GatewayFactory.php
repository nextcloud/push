<?php declare(strict_types=1);

namespace OCA\Push\Service;

use OCA\Push\Service\Gateway\FailedGateway;
use OCA\Push\Service\Gateway\IPushGateway;
use OCA\Push\Service\Gateway\MercureGateway;
use OCP\Http\Client\IClientService;
use OCP\IConfig;

class GatewayFactory {

	/** @var IPushGateway|null */
	private $gateway = null;

	/** @var IConfig */
	private $config;

	/** @var IClientService */
	private $clientService;

	public function __construct(IConfig $config,
								IClientService $clientService) {
		$this->config = $config;
		$this->clientService = $clientService;
	}

	public function getGateway(): IPushGateway {
		if ($this->gateway === null) {
			$mercureConfig = $this->config->getSystemValue('push_mercure', false);
			if ($mercureConfig === false
				|| !isset($mercureConfig['hub_url'], $mercureConfig['jwt_secret'])) {
				// Fallback
				return $this->gateway = new FailedGateway();
			}

			// docker run -e JWT_KEY='!ChangeMe!' -e DEMO=1 -e ALLOW_ANONYMOUS=1 -e CORS_ALLOWED_ORIGINS='https://localhost' -e ADDR=':3000' -p 3000:3000 dunglas/mercure
			return $this->gateway = new MercureGateway(
				$mercureConfig['hub_url'],
				$mercureConfig['jwt_secret'],
				$this->clientService
			);
		}

		return $this->gateway;
	}

}
