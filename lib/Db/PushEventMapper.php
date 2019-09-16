<?php declare(strict_types=1);

namespace OCA\Push\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class PushEventMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'push_events');
	}

}
