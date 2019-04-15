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

use pocketmine\block\utils\Fallable;
use pocketmine\block\utils\FallableTrait;
use pocketmine\event\block\BlockTeleportEvent;
use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\level\Level;
use pocketmine\level\particle\DragonEggTeleportParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;
use function max;
use function min;
use function mt_rand;

class DragonEgg extends Transparent implements Fallable{
	use FallableTrait;

	public function getHardness() : float{
		return 3;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}

	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_WOODEN;
	}

	public function getLightLevel() : int{
		return 1;
	}

	public function tickFalling() : ?Block{
		return null;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		$this->teleport();
		return true;
	}

	public function onAttack(Item $item, int $face, ?Player $player = null) : bool{
		$this->teleport();
		return true;
	}

	protected function teleport() : void{
		for($tries = 0; $tries < 16; ++$tries){
			$block = $this->level->getBlockAt(
				$this->x + mt_rand(-16, 16),
				max(0, min(Level::Y_MAX - 1, $this->y + mt_rand(-8, 8))),
				$this->z + mt_rand(-16, 16)
			);
			if($block instanceof Air){
				$ev = new BlockTeleportEvent($this, $block);
				$ev->call();
				if($ev->isCancelled()){
					break;
				}else{
					$block = $ev->getTo();
				}
				$this->level->addParticle($this, new DragonEggTeleportParticle($this->x - $block->x, $this->y - $block->y, $this->z - $block->z));
				$this->level->setBlock($this, BlockFactory::get(BlockLegacyIds::AIR));
				$this->level->setBlock($block, $this);
				break;
			}
		}
	}
}
