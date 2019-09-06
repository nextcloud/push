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
use OC\Push\Model\Helper\PushNotification;
use OCA\Push\Service\ConfigService;
use OCA\Push\Service\MiscService;
use OCP\IUserManager;
use OCP\Push\Exceptions\ItemNotFoundException;
use OCP\Push\Exceptions\PushInstallException;
use OCP\Push\Exceptions\UnknownStreamTypeException;
use OCP\Push\IPushManager;
use OCP\Push\Model\IPushItem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class Test extends Base {


	/** @var OutputInterface */
	private $output;

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
			 ->addOption(
				 'keyword', 'k', InputOption::VALUE_REQUIRED, 'editable content, using keyword', ''
			 )
			 ->setDescription('Nextcloud Push testing tools');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->output = $output;

		$userId = $input->getArgument('user');

		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new Exception('unknown user');
		}

		if (!$this->pushManager->isAvailable()) {
			throw new Exception('Nextcloud Push is not available');
		}

		if (($keyword = $input->getOption('keyword')) !== '') {
			$this->manageKeyword($userId, $keyword);

			return;
		}

		$pushHelper = $this->pushManager->getPushHelper();
		$pushHelper->test($userId);
	}


	/**
	 * @param string $userId
	 * @param string $keyword
	 *
	 * @throws PushInstallException
	 * @throws UnknownStreamTypeException
	 */
	private function manageKeyword(string $userId, string $keyword) {

		if ($keyword === 'new') {
			$notification = new PushNotification('push', IPushItem::TTL_FEW_HOURS);
			$notification->setTitle('Testing Push');
			$notification->setLevel(PushNotification::LEVEL_SUCCESS);
			$notification->setKeyword('test');
			$notification->setMessage("If you cannot see this, it means it is not working.");
			$notification->addUser($userId);
			$pushHelper = $this->pushManager->getPushHelper();
			$pushHelper->pushNotification($notification);

			return;
		}

		$pushService = $this->pushManager->getPushService();
		try {
			$item = $pushService->getItemByKeyword('push', $userId, 'test');
		} catch (ItemNotFoundException $e) {
			$this->output->writeln('Item not available anymore. Run ./occ push:test --keyword new');

			return;
		}

		$this->output->writeln('Current Item: ');
		$this->output->writeln(json_encode($item, JSON_PRETTY_PRINT));

		$payload = $item->getPayload();
		$payload['message'] = $keyword;
		$item->setPayload($payload);
		$pushService->update($item);

		try {
			$item = $pushService->getItemByKeyword('push', $userId, 'test');
			$this->output->writeln('');
			$this->output->writeln('New Item: ');

			$this->output->writeln(json_encode($item, JSON_PRETTY_PRINT));
		} catch (ItemNotFoundException $e) {
		} catch (UnknownStreamTypeException $e) {
		}
	}

}

