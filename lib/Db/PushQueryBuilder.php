<?php
declare(strict_types=1);


/**
 * Push - Nextcloud Push Service
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019, Maxence Lange <maxence@artificial-owl.com>
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


use daita\NcSmallPhpTools\Db\ExtendedQueryBuilder;


/**
 * Class PushQueryBuilder
 *
 * @package OCA\Push\Db
 */
class PushQueryBuilder extends ExtendedQueryBuilder {



	/**
	 * Limit the request to app
	 *
	 * @param string $app
	 *
	 * @return PushQueryBuilder
	 */
	public function limitToApp(string $app): PushQueryBuilder {
		$this->limitToDBField('app', $app, true);

		return $this;
	}




	/**
	 * Limit the request to keyword
	 *
	 * @param string $keyword
	 *
	 * @return PushQueryBuilder
	 */
	public function limitToKeyword(string $keyword): PushQueryBuilder {
		$this->limitToDBField('keyword', $keyword, true);

		return $this;
	}


	/**
	 * Limit the request to the Type
	 *
	 * @param string $type
	 *
	 * @return PushQueryBuilder
	 */
	public function limitToType(string $type): PushQueryBuilder {
		$this->limitToDBField('type', $type, false);

		return $this;
	}


	/**
	 * @param int $id
	 */
	public function limitToNewerStreams(int $id) {
		$expr = $this->expr();
		$cond = $expr->gt('id', $this->createNamedParameter($id));

		$this->andWhere($cond);
	}


	/**
	 * @param string $field
	 * @param array $ids
	 */
	public function limitToList(string $field, array $ids) {
		$expr = $this->expr();
		$orX = $expr->orX();

		foreach ($ids as $id) {
			$orX->add($expr->eq($field, $this->createNamedParameter($id)));
		}

		$this->andWhere($orX);
	}

}

