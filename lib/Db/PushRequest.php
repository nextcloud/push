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


namespace OCA\Push\Db;


use Exception;
use OCA\Push\Exceptions\ItemNotFoundException;
use OCA\Push\Exceptions\UnknownStreamTypeException;
use OCA\Push\Model\Polling;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Push\Model\IPushItem;
use OCP\Push\Model\IPushWrapper;


/**
 * Class PushRequest
 *
 * @package OCA\Push\Db
 */
class PushRequest extends PushRequestBuilder {


	/**
	 * @param IPushWrapper $wrapper
	 */
	public function save(IPushWrapper $wrapper) {
		if (!$wrapper->hasItem()) {
			return;
		}

		$item = $wrapper->getItem();
		$item->setCreation(time());

		$users = $wrapper->getRecipients();
		asort($users);

		$prec = '';
		foreach ($users as $userId) {
			if ($userId === $prec) {
				continue;
			}

			$prec = $userId;
			try {
				$qb = $this->getPushInsertSql();
				$qb->setValue('token', $qb->createNamedParameter($item->getToken()))
				   ->setValue('app', $qb->createNamedParameter($item->getApp()))
				   ->setValue('source', $qb->createNamedParameter($item->getSource()))
				   ->setValue('type', $qb->createNamedParameter($item->getType()))
				   ->setValue('meta', $qb->createNamedParameter(json_encode($item->getMeta())))
				   ->setValue('user_id', $qb->createNamedParameter($userId))
				   ->setValue('ttl', $qb->createNamedParameter($item->getTtl()))
				   ->setValue(
					   'payload', $qb->createNamedParameter(json_encode($item->getPayload()))
				   )
				   ->setValue('creation', $qb->createNamedParameter($item->getCreation()));

				$qb->execute();
			} catch (Exception $e) {
				$this->miscService->log('Issue while saving PushItem: ' . $e->getMessage(), 2);
			}
		}
	}


	/**
	 * return int
	 *
	 * @param Polling $polling
	 *
	 * @throws ItemNotFoundException
	 */
	public function fillPollingWithItems(Polling $polling): void {
		$qb = $this->getPushSelectSql();
		$qb->orderBy('id', 'asc');
		// TODO: set limit !
		$qb->limitToUserId($polling->getUserId());
		$qb->limitToNewerStreams($polling->getLastEventId());

		$lastId = 0;
		$items = $this->getListFromRequest($qb, $lastId);
		if (empty($items)) {
			throw new ItemNotFoundException();
		}

		$polling->setLastEventId($lastId);
		$polling->setItems($items);
		$polling->setStatus(1);
	}


	/**
	 *
	 */
	public function removeExpiredItems() {
		$qb = $this->getPushDeleteSql();
		$expr = $qb->expr();
		$func = $qb->func();

		$qb->andWhere($expr->lt($func->add('creation', 'ttl'), $qb->createNamedParameter(time())));
		$qb->execute();
	}


	/**
	 * @param array $ids
	 */
	public function removeIds(array $ids) {
		$qb = $this->getPushDeleteSql();
		$qb->limitToList('id', $ids);

		$qb->execute();
	}


	/**
	 * @param int $id
	 */
	public function delete(int $id) {
		$qb = $this->getPushDeleteSql();
		$qb->limitToId($id);

		$qb->execute();
	}


	/**
	 * @param IQueryBuilder $qb
	 *
	 * @return IPushItem
	 * @throws ItemNotFoundException
	 * @throws UnknownStreamTypeException
	 */
	private function getItemFromRequest(IQueryBuilder $qb): IPushItem {
		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new ItemNotFoundException();
		}

		return $this->parsePushSelectSql($data);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param int $lastId
	 *
	 * @return IPushItem[]
	 */
	private function getListFromRequest(IQueryBuilder $qb, int &$lastId = 0): array {
		$items = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			try {
				$item = $this->parsePushSelectSql($data);
				$lastId = ($item->getId() > $lastId) ? $item->getId() : $lastId;
				$items[] = $item;
			} catch (UnknownStreamTypeException $e) {
			}
		}
		$cursor->closeCursor();

		return $items;
	}


	/**
	 *
	 * @throws Exception
	 */
	public function clearAll(): void {
		$qb = $this->getPushDeleteSql();

		$qb->execute();
	}

}

