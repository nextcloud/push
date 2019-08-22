<?php
declare(strict_types=1);


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


namespace OCA\Push\Command;


use Exception;
use OC\Core\Command\Base;
use OCA\Push\Service\ConfigService;
use OCA\Push\Service\MiscService;
use OCP\IUserManager;
use OCP\Push\IPushManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Test extends Base {


	/** @var IUserManager */
	private $userManager;

	/** @var IPushManager */
	private $pushManager;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * CacheUpdate constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IPushManager $pushManager
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IUserManager $userManager, IPushManager $pushManager, ConfigService $configService,
		MiscService $miscService
	) {
		parent::__construct();

		$this->userManager = $userManager;
		$this->pushManager = $pushManager;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('push:test')
			 ->addArgument('user', InputArgument::REQUIRED, 'user')
			 ->setDescription('Nextcloud Push testing tools');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$userId = $input->getArgument('user');

		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new Exception('unknown user');
		}

		if (!$this->pushManager->isAvailable()) {
			throw new Exception('Nextcloud Push is not available');
		}

		$pushHelper = $this->pushManager->getPushHelper();
		$pushHelper->test($userId);
	}

}

