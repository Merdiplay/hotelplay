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

use pocketmine\block\utils\BlockDataValidator;
use pocketmine\item\Item;
use pocketmine\level\BlockTransaction;
use pocketmine\level\sound\DoorSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Bearing;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\Player;


abstract class Door extends Transparent{
	/** @var int */
	protected $facing = Facing::NORTH;
	/** @var bool */
	protected $top = false;
	/** @var bool */
	protected $hingeRight = false;

	/** @var bool */
	protected $open = false;
	/** @var bool */
	protected $powered = false;


	protected function writeStateToMeta() : int{
		if($this->top){
			return 0x08 | ($this->hingeRight ? 0x01 : 0) | ($this->powered ? 0x02 : 0);
		}

		return Bearing::fromFacing(Facing::rotateY($this->facing, true)) | ($this->open ? 0x04 : 0);
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->top = $stateMeta & 0x08;
		if($this->top){
			$this->hingeRight = ($stateMeta & 0x01) !== 0;
			$this->powered = ($stateMeta & 0x02) !== 0;
		}else{
			$this->facing = Facing::rotateY(BlockDataValidator::readLegacyHorizontalFacing($stateMeta & 0x03), false);
			$this->open = ($stateMeta & 0x04) !== 0;
		}
	}

	public function getStateBitmask() : int{
		return 0b1111;
	}

	public function readStateFromWorld() : void{
		parent::readStateFromWorld();

		//copy door properties from other half
		$other = $this->getSide($this->top ? Facing::DOWN : Facing::UP);
		if($other instanceof Door and $other->isSameType($this)){
			if($this->top){
				$this->facing = $other->facing;
				$this->open = $other->open;
			}else{
				$this->hingeRight = $other->hingeRight;
				$this->powered = $other->powered;
			}
		}
	}

	public function isSolid() : bool{
		return false;
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB{
		return AxisAlignedBB::one()
			->extend(Facing::UP, 1)
			->trim($this->open ? Facing::rotateY($this->facing, !$this->hingeRight) : $this->facing, 13 / 16);
	}

	public function onNearbyBlockChange() : void{
		if($this->getSide(Facing::DOWN)->getId() === BlockLegacyIds::AIR){ //Replace with common break method
			$this->getLevel()->useBreakOn($this); //this will delete both halves if they exist
		}
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($face === Facing::UP){
			$blockUp = $this->getSide(Facing::UP);
			$blockDown = $this->getSide(Facing::DOWN);
			if(!$blockUp->canBeReplaced() or $blockDown->isTransparent()){
				return false;
			}

			if($player !== null){
				$this->facing = $player->getHorizontalFacing();
			}

			$next = $this->getSide(Facing::rotateY($this->facing, false));
			$next2 = $this->getSide(Facing::rotateY($this->facing, true));

			if($next->isSameType($this) or (!$next2->isTransparent() and $next->isTransparent())){ //Door hinge
				$this->hingeRight = true;
			}

			$topHalf = clone $this;
			$topHalf->top = true;

			$transaction = new BlockTransaction($this->level);
			$transaction->addBlock($blockReplace, $this)->addBlock($blockUp, $topHalf);

			return $transaction->apply();
		}

		return false;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		$this->open = !$this->open;

		$other = $this->getSide($this->top ? Facing::DOWN : Facing::UP);
		if($other instanceof Door and $other->isSameType($this)){
			$other->open = $this->open;
			$this->level->setBlock($other, $other);
		}

		$this->level->setBlock($this, $this);
		$this->level->addSound($this, new DoorSound());

		return true;
	}

	public function getDrops(Item $item) : array{
		if(!$this->top){
			return parent::getDrops($item);
		}

		return [];
	}

	public function getAffectedBlocks() : array{
		$other = $this->getSide($this->top ? Facing::DOWN : Facing::UP);
		if($other->isSameType($this)){
			return [$this, $other];
		}
		return parent::getAffectedBlocks();
	}
}
