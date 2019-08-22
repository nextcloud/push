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


namespace OCA\Push\Controller;


use daita\MySmallPhpTools\Traits\Nextcloud\TNCDataResponse;
use Exception;
use OCA\Push\AppInfo\Application;
use OCA\Push\Model\Polling;
use OCA\Push\Service\ConfigService;
use OCA\Push\Service\MiscService;
use OCA\Push\Service\PayloadService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;


/**
 * Class SettingsController
 *
 * @package OCA\Push\Controller
 */
class PushController extends Controller {


	use TNCDataResponse;


	/** @var string */
	private $userId;

	/** @var PayloadService */
	private $payloadService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * SettingsController constructor.
	 *
	 * @param $userId
	 * @param IRequest $request
	 * @param PayloadService $payloadService
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		$userId, IRequest $request, PayloadService $payloadService, ConfigService $configService,
		MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);
		$this->userId = $userId;
		$this->payloadService = $payloadService;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $eventId
	 *
	 * @return DataResponse
	 */
	public function polling(int $eventId): DataResponse {
		$polling = new Polling($this->userId);
		$polling->setLastEventId($eventId);

		try {
			$this->payloadService->processPolling($polling);

			return $this->directSuccess($polling);
		} catch (Exception $e) {
			return $this->fail($e, $polling->jsonSerialize());
		}
	}

}

