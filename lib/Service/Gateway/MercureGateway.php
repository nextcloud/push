<?php declare(strict_types=1);

namespace OCA\Push\Service\Gateway;

use Exception;
use JsonSerializable;
use OCA\Push\Exception\PushException;
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

	public function push(string $name,
						 string $uid,
						 JsonSerializable $payload): void {
		$client = $this->clientService->newClient();

		try {
			$client->post(
				$this->url,
				[
					'headers' => [
						'Authorization' => 'Bearer ' . $this->jwt,
					],
					'body' => [
						'topic' => $uid,
						'data' => json_encode($payload->jsonSerialize()),
					]
				]
			);
		} catch (Exception $e) {
			throw new PushException("Could not push event to Mercure", 0, $e);
		}
	}

	public function getUrl(): string {
		return $this->url;
	}

}
