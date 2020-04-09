<?php declare(strict_types=1);

namespace OCA\Push\Service\Gateway;

use Exception;
use JsonSerializable;
use OCA\Push\Exception\PushException;
use OCA\Push\Helper\JWT;
use OCP\Http\Client\IClientService;

class MercureGateway implements IPushGateway {

	/** @var string */
	private $url;

	/** @var string */
	private $jwtSecret;

	/** @var IClientService */
	private $clientService;

	public function __construct(string $url,
								string $jwtSecret,
								IClientService $clientService) {
		$this->url = $url;
		$this->jwtSecret = $jwtSecret;
		$this->clientService = $clientService;
	}

	public function push(string $name,
						 string $uid,
						 JsonSerializable $payload): void {
		$client = $this->clientService->newClient();

		$jwt = JWT::generateJWT(['users/'.$uid], [ 'users/'.$uid ], $this->jwtSecret);

		try {
			$client->post(
				$this->url . '/.well-known/mercure',
				[
					'headers' => [
						'Authorization' => 'Bearer ' . $jwt,
					],
					'body' => [
						'topic' => 'users/' . $uid,
						'data' => json_encode([
							'name' => $name,
							'payload' => $payload
						]),
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
