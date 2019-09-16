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

use OCA\Push\Listener\BroadcastListener;
use OCA\Push\Listener\CspListener;
use OCP\AppFramework\IAppContainer;
use OCP\Broadcast\Events\IBroadcastEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

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

		$this->registerEvents($this->container);

		$this->booted = true;
	}

	private function registerEvents(IAppContainer $container): void {
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $container->query(IEventDispatcher::class);

		$dispatcher->addServiceListener(AddContentSecurityPolicyEvent::class, CspListener::class);
		$dispatcher->addServiceListener(IBroadcastEvent::class, BroadcastListener::class);
	}

}
