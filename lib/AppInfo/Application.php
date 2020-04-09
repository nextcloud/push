<?php declare(strict_types=1);

/**
 * Push - Nextcloud Push Service
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019
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

namespace OCA\Push\AppInfo;

use OCA\Push\Helper\JWT;
use OCA\Push\Listener\BroadcastListener;
use OCA\Push\Listener\CspListener;
use OCA\Push\Service\Gateway\MercureGateway;
use OCA\Push\Service\GatewayFactory;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Broadcast\Events\IBroadcastEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\IUserSession;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Util;

class Application extends App {

	const APP_NAME = 'push';

	/**
	 * @param array $params
	 */
	public function __construct(array $params = []) {
		parent::__construct(self::APP_NAME, $params);

		$container = $this->getContainer();
		$this->registerClientSideAdapter($container);
		$this->registerEvents($container);
	}

	private function registerClientSideAdapter(IAppContainer $container) {
		Util::addScript(Application::APP_NAME, 'event-bus-adapter');

		/** @var IInitialStateService $initialState */
		$initialState = $container->query(IInitialStateService::class);
		$initialState->provideLazyInitialState(Application::APP_NAME, 'config', function () use ($container) {
			/** @var GatewayFactory $factory */
			$factory = $container->query(GatewayFactory::class);
			/** @var ITimeFactory $timeFactory */
			$timeFactory = $container->query(ITimeFactory::class);


			/** @var IUserSession $userSession */
			$userSession = $container->query(IUserSession::class);
			/** @var IConfig $config */
			$config = $container->query(IConfig::class);
			$mercureConfig = $config->getSystemValue('push_mercure', false);

			$jwt = null;
			if ($mercureConfig !== false && $userSession->getUser() !== null) {
				$uid = $userSession->getUser()->getUID();
				$jwt = JWT::generateJWT(['users/'.$uid], [], $mercureConfig['jwt_secret']);
			}

			$gateway = $factory->getGateway();
			if ($gateway instanceof MercureGateway) {
				return [
					'gateway' => 'mercure',
					'hubUrl' => $gateway->getUrl(),
					'jwt' => $jwt,
				];
			}

			return [
				'gateway' => 'poll',
				'now' => $timeFactory->getTime(),
			];
		});
	}

	private function registerEvents(IAppContainer $container): void {
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $container->query(IEventDispatcher::class);

		$dispatcher->addServiceListener(AddContentSecurityPolicyEvent::class, CspListener::class);
		$dispatcher->addServiceListener(IBroadcastEvent::class, BroadcastListener::class);
	}
}
