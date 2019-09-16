<?php declare(strict_types=1);

namespace OCA\Push\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version0Date20190916221226 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('push')) {
			$schema->dropTable('push');
		}

		$table = $schema->createTable('push_events');
		$table->addColumn('id', 'bigint', [
			'autoincrement' => true,
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('channel', 'string', [
			'notnull' => true,
		]);
		$table->addColumn('payload', 'string', [
			'notnull' => true,
		]);
		$table->addColumn('created_at', 'integer', [
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->setPrimaryKey(['id']);

		return $schema;
	}

}
