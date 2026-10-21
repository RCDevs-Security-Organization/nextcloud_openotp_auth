<?php

declare(strict_types=1);

/** SPDX-License-Identifier: AGPL-3.0-or-later */

namespace OCA\OpenOTPAuth\Migration;

use OCA\OpenOTPAuth\AppInfo\Application;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Throwable;

class MigrateLegacyProvider implements IRepairStep {
	private const LEGACY_PROVIDER_ID = 'rcdevsopenotp';
	private const TABLE = 'twofactor_providers';
	private const BATCH_SIZE = 500;

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function getName(): string {
		return 'Migrate legacy OpenOTP two-factor provider associations';
	}

	public function run(IOutput $output): void {
		$total = 0;
		do {
			$count = $this->migrateBatch();
			$total += $count;
		} while ($count === self::BATCH_SIZE);

		$output->info(sprintf('Migrated %d OpenOTP two-factor provider associations.', $total));
	}

	private function migrateBatch(): int {
		$this->connection->beginTransaction();
		try {
			// IRegistry has no rename or per-user removal operation. Keep this
			// one-time data migration confined to the two OpenOTP provider IDs.
			$query = $this->connection->getQueryBuilder();
			$query->select('uid', 'enabled')
				->from(self::TABLE)
				->where($query->expr()->eq('provider_id', $query->createNamedParameter(self::LEGACY_PROVIDER_ID)))
				->orderBy('uid')
				->setMaxResults(self::BATCH_SIZE);
			$result = $query->executeQuery();
			try {
				$rows = $result->fetchAll();
			} finally {
				$result->closeCursor();
			}

			foreach ($rows as $row) {
				$uid = (string)$row['uid'];
				$enabled = (int)$row['enabled'] === 1;
				$this->connection->insertIgnoreConflict(self::TABLE, [
					'provider_id' => Application::APP_ID,
					'uid' => $uid,
					'enabled' => $enabled ? 1 : 0,
				]);

				// A stale disabled entry for either ID must not override an
				// active second factor. Reapplying admin policy is a separate task.
				if ($enabled) {
					$update = $this->connection->getQueryBuilder();
					$update->update(self::TABLE)
						->set('enabled', $update->createNamedParameter(1, IQueryBuilder::PARAM_INT))
						->where($update->expr()->eq('provider_id', $update->createNamedParameter(Application::APP_ID)))
						->andWhere($update->expr()->eq('uid', $update->createNamedParameter($uid)))
						->executeStatement();
				}

				$delete = $this->connection->getQueryBuilder();
				$delete->delete(self::TABLE)
					->where($delete->expr()->eq('provider_id', $delete->createNamedParameter(self::LEGACY_PROVIDER_ID)))
					->andWhere($delete->expr()->eq('uid', $delete->createNamedParameter($uid)))
					->executeStatement();
			}

			$this->connection->commit();
			// No offset: committed batches have removed their legacy rows.
			return count($rows);
		} catch (Throwable $e) {
			$this->connection->rollBack();
			throw $e;
		}
	}
}
