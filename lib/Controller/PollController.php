<?php declare(strict_types=1);

namespace OCA\Push\Controller;

use OCA\Push\AppInfo\Application;
use OCA\Push\Db\PushEventMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class PollController extends Controller {

	/** @var PushEventMapper */
	private $eventMapper;

	public function __construct(IRequest $request,
								PushEventMapper $eventMapper) {
		parent::__construct(Application::APP_NAME, $request);
		$this->eventMapper = $eventMapper;
	}

	/**
	 * @NoAdminRequired
	 */
	public function index(int $cursor): JSONResponse {
		return new JSONResponse($this->eventMapper->findSince($cursor));
	}

}
