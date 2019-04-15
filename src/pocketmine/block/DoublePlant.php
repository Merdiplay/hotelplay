<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\level\BlockTransaction;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\Player;

class DoublePlant extends Flowable{
	private const BITFLAG_TOP = 0x08;

	/** @var bool */
	protected $top = false;

	protected function writeStateToMeta() : int{
		return ($this->top ? self::BITFLAG_TOP : 0);
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->top = ($stateMeta & self::BITFLAG_TOP) !== 0;
	}

	public function getStateBitmask() : int{
		return 0b1000;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		$id = $blockReplace->getSide(Facing::DOWN)->getId();
		if(($id === BlockLegacyIds::GRASS or $id === BlockLegacyIds::DIRT) and $blockReplace->getSide(Facing::UP)->canBeReplaced()){
			$top = clone $this;
			$top->top = true;

			$transaction = new BlockTransaction($this->level);
			$transaction->addBlock($blockReplace, $this)->addBlock($blockReplace->getSide(Facing::UP), $top);
			return $transaction->apply();
		}

		return false;
	}

	/**
	 * Returns whether this double-plant has a corresponding other half.
	 * @return bool
	 */
	public function isValidHalfPlant() : bool{
		$other = $this->getSide($this->top ? Facing::DOWN : Facing::UP);

		return (
			$other instanceof DoublePlant and
			$other->isSameType($this) and
			$other->top !== $this->top
		);
	}

	public function onNearbyBlockChange() : void{
		if(!$this->isValidHalfPlant() or (!$this->top and $this->getSide(Facing::DOWN)->isTransparent())){
			$this->getLevel()->useBreakOn($this);
		}
	}

	public function getDrops(Item $item) : array{
		return $this->top ? parent::getDrops($item) : [];
	}

	public function getAffectedBlocks() : array{
		if($this->isValidHalfPlant()){
			return [$this, $this->getSide($this->top ? Facing::DOWN : Facing::UP)];
		}

		return parent::getAffectedBlocks();
	}
}
