<?php declare(strict_types=1);

namespace OCA\Push\Service;

use OCA\Push\Service\Gateway\IPushGateway;
use OCA\Push\Service\Gateway\MercureGateway;
use OCA\Push\Service\Gateway\PollGateway;
use OCP\Http\Client\IClientService;
use OCP\IConfig;

class GatewayFactory {

	/** @var IConfig */
	private $config;

	/** @var PollGateway */
	private $pollGateway;

	/** @var IClientService */
	private $clientService;

	public function __construct(IConfig $config,
								PollGateway $pollGateway,
								IClientService $clientService) {
		$this->config = $config;
		$this->pollGateway = $pollGateway;
		$this->clientService = $clientService;
	}

	public function getGateway(): IPushGateway {
		// TODO: add fallback magic

		return $this->pollGateway;

		// docker run -e JWT_KEY='!ChangeMe!' -e DEMO=1 -e ALLOW_ANONYMOUS=1 -e CORS_ALLOWED_ORIGINS='https://localhost' -e ADDR=':3000' -p 3000:3000 dunglas/mercure
		return new MercureGateway(
			'http://localhost:3000/hub',
			'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsidGVzdCJdfX0.NLMVrVws6SNZQppDf9DvJ8knkJNr2ooCfaQdhzXjMWI',
			$this->clientService
		);
	}

}
