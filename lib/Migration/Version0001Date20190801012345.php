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


namespace OCA\Push\Migration;


use Closure;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;


/**
 * Class Version0001Date20190801012345
 *
 * @package OCA\Push\Migration
 */
class Version0001Date20190801012345 extends SimpleMigrationStep {


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options
	): ISchemaWrapper {

		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		/**
		 * push
		 *
		 * - id             int
		 * - token          string
		 * - app            string
		 * - source        string
		 * - type           string
		 * - user_id        string
		 * - ttl            int
		 * - payload        text
		 * - meta           text
		 * - creation       int
		 */
		$table = $schema->createTable('push');
		$table->addColumn(
			'id', Type::BIGINT,
			[
				'notnull'       => true,
				'autoincrement' => true,
				'unsigned'      => true,
				'length'        => 14,
			]
		);
		$table->addColumn(
			'token', Type::STRING,
			[
				'notnull' => true,
				'length'  => 15
			]
		);
		$table->addColumn(
			'app', Type::STRING,
			[
				'notnull' => true,
				'length'  => 63
			]
		);
		$table->addColumn(
			'source', Type::STRING,
			[
				'notnull' => true,
				'length'  => 127
			]
		);
		$table->addColumn(
			'type', Type::STRING,
			[
				'notnull' => true,
				'length'  => 63
			]
		);
		$table->addColumn(
			'user_id', Type::STRING,
			[
				'notnull' => true,
				'length'  => 11,
			]
		);
		$table->addColumn(
			'ttl', Type::INTEGER,
			[
				'notnull' => true,
				'length'  => 7
			]
		);
		$table->addColumn(
			'payload', Type::TEXT,
			[
				'notnull' => true
			]
		);
		$table->addColumn(
			'meta', Type::TEXT,
			[
				'notnull' => true
			]
		);
		$table->addColumn(
			'creation', Type::INTEGER,
			[
				'length'  => 11,
				'notnull' => true
			]
		);
		$table->setPrimaryKey(['id']);

		return $schema;
	}


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @throws Exception
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
	}

}

