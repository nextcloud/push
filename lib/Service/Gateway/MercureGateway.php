<?php declare(strict_types=1);

namespace OCA\Push\Service\Gateway;

use JsonSerializable;
use OCP\Http\Client\IClientService;

class MercureGateway implements IPushGateway {

	/** @var string */
	private $url;

	/** @var string */
	private $jwt;

	/** @var IClientService */
	private $clientService;

	public function __construct(string $url,
								string $jwt,
								IClientService $clientService) {
		$this->url = $url;
		$this->jwt = $jwt;
		$this->clientService = $clientService;
	}

	public function push(string $name, string $channel, JsonSerializable $payload): void {
		$client = $this->clientService->newClient();

		$response = $client->post(
			$this->url,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $this->jwt,
				],
				'auth_bearer' => $this->jwt,
				'body' => [
					'topic' => $channel,
					'data' => json_encode($payload->jsonSerialize()),
				]
			]
		);

		$h = $response->getHeaders();
		$body = $response->getBody();
		$x = 4;
	}

	public function getUrl(): string {
		return $this->url;
	}

}
