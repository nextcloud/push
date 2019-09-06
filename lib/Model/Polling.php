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


namespace OCA\Push\Model;


use daita\NcSmallPhpTools\Traits\TArrayTools;
use JsonSerializable;
use OCP\Push\Model\IPushItem;

/**
 * Class Polling
 *
 * @package OCA\Push\Model
 */
class Polling implements JsonSerializable {


	use TArrayTools;


	/** @var string */
	private $userId = '';

	/** @var IPushItem[] */
	private $items = [];

	/** @var int */
	private $lastEventId = 0;

	/** @var int */
	private $status = 0;

	/** @var array */
	private $meta = [];


	/**
	 * Polling constructor.
	 *
	 * @param string $userId
	 * @param int $lastEventId
	 */
	public function __construct(string $userId = '', int $lastEventId = 0) {
		$this->userId = $userId;
		$this->lastEventId = $lastEventId;
	}


	/**
	 * @return string
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * @param string $userId
	 *
	 * @return Polling
	 */
	public function setUserId(string $userId): Polling {
		if ($userId !== '') {
			$this->userId = $userId;
		}

		return $this;
	}


	/**
	 * @return IPushItem[]
	 */
	public function getItems(): array {
		return $this->items;
	}

	/**
	 * @param IPushItem[] $items
	 *
	 * @return Polling
	 */
	public function setItems(array $items): Polling {
		$this->items = $items;

		return $this;
	}

	/**
	 * @param IPushItem $item
	 *
	 * @return Polling
	 */
	public function addItem(IPushItem $item): Polling {
		$this->items[] = $item;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getLastEventId(): int {
		return $this->lastEventId;
	}

	/**
	 * @param int $lastEventId
	 *
	 * @return Polling
	 */
	public function setLastEventId(int $lastEventId): Polling {
		$this->lastEventId = $lastEventId;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}

	/**
	 * @param int $status
	 *
	 * @return Polling
	 */
	public function setStatus(int $status): Polling {
		$this->status = $status;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getMeta(): array {
		return $this->meta;
	}

	/**
	 * @param array $meta
	 *
	 * @return Polling
	 */
	public function setMeta(array $meta): self {
		$this->meta = $meta;

		return $this;
	}

	/**
	 * @param string $k
	 * @param string $v
	 *
	 * @return Polling
	 */
	public function addMeta(string $k, string $v): self {
		$this->meta[$k] = $v;

		return $this;
	}

	/**
	 * @param string $k
	 * @param int $v
	 *
	 * @return Polling
	 */
	public function addMetaInt(string $k, int $v): self {
		$this->meta[$k] = $v;

		return $this;
	}

	/**
	 * @param string $k
	 * @param bool $v
	 *
	 * @return Polling
	 */
	public function addMetaBool(string $k, bool $v): self {
		$this->meta[$k] = $v;

		return $this;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'userId'      => $this->getUserId(),
			'lastEventId' => $this->getLastEventId(),
			'status'      => $this->getStatus(),
			'meta'        => $this->getMeta(),
			'items'       => $this->getItems()
		];
	}

}

