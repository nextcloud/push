<?php declare(strict_types=1);

namespace OCA\Push\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class PushEventMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'push_events');
	}

	/**
	 * @param int $time
	 * @return PushEvent[]
	 */
	public function findSince(int $time): array {
		$qb = $this->db->getQueryBuilder();

		$query = $qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->gte('created_at', $qb->createNamedParameter($time)));

		return $this->findEntities($query);
	}

}
