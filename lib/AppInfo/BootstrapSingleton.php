<?php declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\Push\AppInfo;

use OCA\Push\Helper\JWT;
use OCA\Push\Listener\BroadcastListener;
use OCA\Push\Listener\CspListener;
use OCA\Push\Service\Gateway\MercureGateway;
use OCA\Push\Service\GatewayFactory;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Broadcast\Events\IBroadcastEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\IUserSession;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Util;

class BootstrapSingleton {

	/** @var BootstrapSingleton */
	private static $instance = null;

	/** @var bool */
	private $booted = false;

	/** @var IAppContainer */
	private $container;

	private function __construct(IAppContainer $container) {
		$this->container = $container;
	}

	public static function getInstance(IAppContainer $container): BootstrapSingleton {
		if (self::$instance === null) {
			self::$instance = new static($container);
		}

		return self::$instance;
	}

	public function boot(): void {
		if ($this->booted) {
			return;
		}

		$this->registerClientSideAdapter($this->container);
		$this->registerEvents($this->container);

		$this->booted = true;
	}

	private function registerClientSideAdapter(IAppContainer $container) {
		Util::addScript(Application::APP_NAME, 'event-bus-adapter');

		/** @var IInitialStateService $initialState */
		$initialState = $container->query(IInitialStateService::class);
		$initialState->provideLazyInitialState(Application::APP_NAME, 'config', function () {
			/** @var GatewayFactory $factory */
			$factory = $this->container->query(GatewayFactory::class);
			/** @var ITimeFactory $timeFactory */
			$timeFactory = $this->container->query(ITimeFactory::class);


			/** @var IUserSession $userSession */
			$userSession = $this->container->query(IUserSession::class);
			/** @var IConfig $config */
			$config = $this->container->query(IConfig::class);
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
