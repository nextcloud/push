<?php declare(strict_types=1);

namespace OCA\Push\Controller;

use OCA\Push\AppInfo\Application;
use OCA\Push\Db\PushEventMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class PollController extends Controller {

	/** @var string|null */
	private $userId;

	/** @var PushEventMapper */
	private $eventMapper;

	public function __construct(IRequest $request,
								?string $UserId,
								PushEventMapper $eventMapper) {
		parent::__construct(Application::APP_NAME, $request);
		$this->userId = $UserId;
		$this->eventMapper = $eventMapper;
	}

	/**
	 * @NoAdminRequired
	 */
	public function index(int $cursor): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse([]);
		}

		return new JSONResponse($this->eventMapper->findSince($this->userId, $cursor));
	}

}
