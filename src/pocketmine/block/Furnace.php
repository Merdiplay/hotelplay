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
use pocketmine\item\TieredTool;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Furnace as TileFurnace;

class Furnace extends Solid{
	/** @var BlockIdentifierFlattened */
	protected $idInfo;

	/** @var int */
	protected $facing = Facing::NORTH;
	/** @var bool */
	protected $lit = false; //this is set based on the blockID

	public function getId() : int{
		return $this->lit ? $this->idInfo->getSecondId() : parent::getId();
	}

	protected function writeStateToMeta() : int{
		return $this->facing;
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->facing = BlockDataValidator::readHorizontalFacing($stateMeta);
		$this->lit = $id === $this->idInfo->getSecondId();
	}

	public function getStateBitmask() : int{
		return 0b111;
	}

	public function getHardness() : float{
		return 3.5;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}

	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_WOODEN;
	}

	public function getLightLevel() : int{
		return $this->lit ? 13 : 0;
	}

	public function isLit() : bool{
		return $this->lit;
	}

	/**
	 * @param bool $lit
	 *
	 * @return $this
	 */
	public function setLit(bool $lit = true) : self{
		$this->lit = $lit;
		return $this;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($player !== null){
			$this->facing = Facing::opposite($player->getHorizontalFacing());
		}

		return parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($player instanceof Player){
			$furnace = $this->getLevel()->getTile($this);
			if($furnace instanceof TileFurnace and $furnace->canOpenWith($item->getCustomName())){
				$player->addWindow($furnace->getInventory());
			}
		}

		return true;
	}
}
