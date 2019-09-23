<?php declare(strict_types=1);

namespace OCA\Push\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class PushEventMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'push_events');
	}

	/**
	 * @param string $userId
	 * @param int $time
	 *
	 * @return PushEvent[]
	 */
	public function findSince(string $userId, int $time): array {
		$qb = $this->db->getQueryBuilder();

		$query = $qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->gt('created_at', $qb->createNamedParameter($time)));

		return $this->findEntities($query);
	}

}
