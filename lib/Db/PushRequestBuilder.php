<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018, Maxence Lange <maxence@artificial-owl.com>
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
 */


namespace OCA\Push\Db;


use daita\NcSmallPhpTools\Traits\TArrayTools;
use OC\Push\Model\PushItem;
use OCP\Push\Exceptions\UnknownStreamTypeException;
use OCP\Push\Model\IPushItem;


/**
 * Class PushRequestBuilder
 *
 * @package OCA\Push\Db
 */
class PushRequestBuilder extends CoreRequestBuilder {


	use TArrayTools;


	/**
	 * Base of the Sql Insert request
	 *
	 * @return PushQueryBuilder
	 */
	protected function getPushInsertSql(): PushQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_PUSH);

		return $qb;
	}


	/**
	 * Base of the Sql Update request
	 *
	 * @return PushQueryBuilder
	 */
	protected function getPushUpdateSql(): PushQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_PUSH);

		return $qb;
	}


	/**
	 * Base of the Sql Select request
	 *
	 * @return PushQueryBuilder
	 */
	public function getPushSelectSql(): PushQueryBuilder {
		$qb = $this->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			's.id', 's.token', 's.app', 's.source', 's.keyword', 's.type', 's.user_id', 's.ttl', 's.payload',
			's.meta', 's.creation'
		)
		   ->from(self::TABLE_PUSH, 's');

		$qb->setDefaultSelectAlias('s');

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return PushQueryBuilder
	 */
	protected function getPushDeleteSql(): PushQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_PUSH);

		return $qb;
	}


	/**
	 * @param array $data
	 *
	 * @return IPushItem
	 * @throws UnknownStreamTypeException
	 */
	protected function parsePushSelectSql(array $data): IPushItem {
		$item = new PushItem();
		$item->import($data);

		if ($item->getType() === '') {
			throw new UnknownStreamTypeException();
		}

		return $item;
	}

}

