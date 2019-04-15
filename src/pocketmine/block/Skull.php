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
use pocketmine\block\utils\SkullType;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Skull as ItemSkull;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Skull as TileSkull;
use function assert;
use function floor;

class Skull extends Flowable{
	/** @var SkullType */
	protected $skullType;

	/** @var int */
	protected $facing = Facing::NORTH;

	/** @var int */
	protected $rotation = 0; //TODO: split this into floor skull and wall skull handling

	public function __construct(BlockIdentifier $idInfo, string $name){
		$this->skullType = SkullType::SKELETON(); //TODO: this should be a parameter
		parent::__construct($idInfo, $name);
	}

	protected function writeStateToMeta() : int{
		return $this->facing;
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->facing = $stateMeta === 1 ? Facing::UP : BlockDataValidator::readHorizontalFacing($stateMeta);
	}

	public function getStateBitmask() : int{
		return 0b111;
	}

	public function readStateFromWorld() : void{
		parent::readStateFromWorld();
		$tile = $this->level->getTile($this);
		if($tile instanceof TileSkull){
			$this->skullType = $tile->getSkullType();
			$this->rotation = $tile->getRotation();
		}
	}

	public function writeStateToWorld() : void{
		parent::writeStateToWorld();
		//extra block properties storage hack
		$tile = $this->level->getTile($this);
		assert($tile instanceof TileSkull);
		$tile->setRotation($this->rotation);
		$tile->setSkullType($this->skullType);
	}

	public function getHardness() : float{
		return 1;
	}

	/**
	 * @return SkullType
	 */
	public function getSkullType() : SkullType{
		return $this->skullType;
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB{
		//TODO: different bounds depending on attached face
		return AxisAlignedBB::one()->contract(0.25, 0, 0.25)->trim(Facing::UP, 0.5);
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($face === Facing::DOWN){
			return false;
		}

		$this->facing = $face;
		if($item instanceof ItemSkull){
			$this->skullType = $item->getSkullType(); //TODO: the item should handle this, but this hack is currently needed because of tile mess
		}
		if($player !== null and $face === Facing::UP){
			$this->rotation = ((int) floor(($player->yaw * 16 / 360) + 0.5)) & 0xf;
		}
		return parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function asItem() : Item{
		return ItemFactory::get(Item::SKULL, $this->skullType->getMagicNumber());
	}
}
