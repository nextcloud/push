<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Push\Service;

use OCA\Push\Exception\PushException;
use OCA\Push\Helper\JWT;
use OCA\Push\Service\Gateway\MercureGateway;
use OCA\Push\Service\GatewayFactory;
use OCP\IConfig;
use OCP\Push\IPushApp;

class PushClient implements IPushApp {

	/** @var GatewayFactory */
	private $factory;

	/** @var IConfig */
	private $config;

	public function __construct(GatewayFactory $factory, IConfig $config) {
		$this->factory = $factory;
		$this->config = $config;
	}

	public function isAvailable(): bool {
		return $this->factory->getGateway() instanceof MercureGateway;
	}

	public function push(string $appId, string $topic, \JsonSerializable $payload): void {
		$topic = $appId . '/' . $topic;

		try {
			$this->factory->getGateway()->push($topic, $payload);
		} catch (PushException $e) {
			//TODO: log
		}
	}

	public function generateJWT(string $appId, string $topic): string {
		$mercureConfig = $this->config->getSystemValue('push_mercure', false);

		if ($mercureConfig === false) {
			return '';
		}

		if (!isset($mercureConfig['jwt_secret'])) {
			return '';
		}

		$secret = $mercureConfig['jwt_secret'];

		$topic = $appId . '/' . $topic;
		return JWT::generateJWT([$topic], [], $secret);
	}

	public function getEndpoint(string $appId, string $topic): string {
		$mercureConfig = $this->config->getSystemValue('push_mercure', false);

		if ($mercureConfig === false) {
			return '';
		}

		if (!isset($mercureConfig['hub_url'])) {
			return '';
		}

		$hub = $mercureConfig['hub_url'];

		$topic = $appId . '/' . $topic;
		return $hub . '/.well-known/mercure?topic=' . $topic;
	}

}
