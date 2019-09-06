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
use OCA\Push\Model\Polling;
use OCA\Push\Service\ConfigService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Push\Exceptions\ItemNotFoundException;
use OCP\Push\Exceptions\UnknownStreamTypeException;
use OCP\Push\Model\IPushItem;
use OCP\Push\Model\IPushWrapper;


/**
 * Class PushRequest
 *
 * @package OCA\Push\Db
 */
class PushRequest extends PushRequestBuilder {


	/**
	 * In case of events sent to the front-end instant before a refreshing/change page.
	 * events published up to (seconds) ago are returned (again) on the next
	 * first polling request from a fresh page.
	 */
	const PUBLISHED_DELAY = 5;


	/**
	 *
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
				if ($item->getKeyword() === '') {
					throw new ItemNotFoundException();
				}

				$previous = $this->getItemByKeyword($item->getApp(), $userId, $item->getKeyword());
				$this->delete($previous->getId());
			} catch (Exception $e) {
			}

			try {
				$qb = $this->getPushInsertSql();
				$qb->setValue('token', $qb->createNamedParameter($item->getToken()))
				   ->setValue('app', $qb->createNamedParameter($item->getApp()))
				   ->setValue('source', $qb->createNamedParameter($item->getSource()))
				   ->setValue('keyword', $qb->createNamedParameter($item->getKeyword()))
				   ->setValue('type', $qb->createNamedParameter($item->getType()))
				   ->setValue('meta', $qb->createNamedParameter(json_encode($item->getMeta())))
				   ->setValue('user_id', $qb->createNamedParameter($userId))
				   ->setValue('ttl', $qb->createNamedParameter($item->getTtl()))
				   ->setValue('payload', $qb->createNamedParameter(json_encode($item->getPayload())))
				   ->setValue('creation', $qb->createNamedParameter($item->getCreation()));

				$qb->execute();
			} catch (Exception $e) {
				$this->miscService->log('Issue while saving PushItem: ' . $e->getMessage(), 2);
			}
		}
	}


	/**
	 * @param IPushItem $item
	 */
	public function update(IPushItem $item) {
		$qb = $this->getPushUpdateSql();
		$qb->set('meta', $qb->createNamedParameter(json_encode($item->getMeta())))
		   ->set('payload', $qb->createNamedParameter(json_encode($item->getPayload())));

		$qb->limitToId($item->getId());
		$qb->execute();
	}


	/**
	 * return int
	 *
	 * @param Polling $polling
	 * @param bool $includeAll
	 *
	 * @throws ItemNotFoundException
	 */
	public function fillPollingWithItems(Polling $polling, bool $includeAll = true): void {
		$qb = $this->getPushSelectSql();
		$qb->orderBy('id', 'asc');
		// TODO: set limit !
		$qb->limitToUserId($polling->getUserId());
		$qb->limitToNewerStreams($polling->getLastEventId());

		if (!$includeAll) {
			$delay = $this->configService->getAppValue(ConfigService::DELAY_POLLING);
			$expr = $qb->expr();
			$func = $qb->func();

			$andX = $expr->andX();
			$andX->add($qb->exprLimitToDBFieldInt('published', 0, '', false));
			$andX->add($expr->gte($func->add('published', $delay), $qb->createNamedParameter(time())));

			$orX = $expr->orX();
			$orX->add($qb->exprLimitToDBFieldInt('published', 0, '', true));
			$orX->add($andX);
		}

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
	 * @param string $app
	 * @param string $userId
	 * @param string $keyword
	 *
	 * @return IPushItem
	 * @throws ItemNotFoundException
	 * @throws UnknownStreamTypeException
	 */
	public function getItemByKeyword(string $app, string $userId, string $keyword): IPushItem {
		$qb = $this->getPushSelectSql();
		$qb->limitToApp($app);
		$qb->limitToKeyword($keyword);
		$qb->limitToUserId($userId);
		$qb->andWhere($qb->exprLimitToDBFieldInt('published', 0, '', true));

		return $this->getItemFromRequest($qb);
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
	public function publishedIds(array $ids) {
		$qb = $this->getPushUpdateSql();
		$qb->limitToList('id', $ids);
		$qb->set('published', $qb->createNamedParameter(time()));

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

