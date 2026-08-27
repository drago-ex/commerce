<?php

declare(strict_types=1);

namespace Drago\Commerce\Domain\DiscountCode;

use Dibi\Connection;
use Dibi\Exception;
use Drago\Attr\AttributeDetectionException;
use Drago\Attr\Table;
use Drago\Database\Database;


#[Table(DiscountCodeEntity::Table, DiscountCodeEntity::PrimaryKey, entity: DiscountCodeEntity::class)]
class DiscountCodeRepository
{
	/** @use Database<DiscountCodeEntity> */
	use Database;

	public function __construct(
		protected Connection $connection,
	) {
	}


	/**
	 * Returns a currently usable discount code.
	 *
	 * @throws AttributeDetectionException
	 * @throws Exception
	 */
	public function findValid(string $code): ?DiscountCodeEntity
	{
		$code = strtoupper(trim($code));
		if ($code === '') {
			return null;
		}

		return $this->read('*')
			->where('code = ?', $code)
			->where('active = ?', 1)
			->where('(valid_from IS NULL OR valid_from <= NOW())')
			->where('(valid_to IS NULL OR valid_to >= NOW())')
			->where('(usage_limit IS NULL OR used_count < usage_limit)')
			->record();
	}


	/**
	 * Atomically increments the usage counter, but only while the usage
	 * limit (if any) has not yet been reached. The limit check and the
	 * increment happen in a single SQL statement, so two concurrent
	 * requests using the same limited code cannot both succeed once the
	 * limit is exhausted.
	 *
	 * Returns true when the usage was recorded, false when the limit had
	 * already been reached in the meantime by another concurrent request.
	 *
	 * @throws Exception
	 */
	public function incrementUsage(int $id): bool
	{
		$this->connection->query(
			'UPDATE %n SET used_count = used_count + 1
			WHERE id = %i AND (usage_limit IS NULL OR used_count < usage_limit)',
			DiscountCodeEntity::Table,
			$id,
		);

		return $this->connection->getAffectedRows() > 0;
	}
}
