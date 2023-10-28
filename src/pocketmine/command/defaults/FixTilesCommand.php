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

namespace pocketmine\command\defaults;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\level\format\Chunk;
use pocketmine\math\Vector3;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class FixTilesCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct(
			$name,
			"Fixes tile entities at given position",
			"/fixtiles <world> <x> <y> <z>"
		);
		$this->setPermission("pocketmine.command.fixtiles");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if (count($args) !== 4) {
			return false;
		}

		$level = Server::getInstance()->getLevelByName($args[0]);

		if ($level === null) {
			$sender->sendMessage("World $args[0] not found!");
			return true;
		}

		$pos = new Vector3(intval($args[1]), intval($args[2]), intval($args[3]));

		/** @var Chunk[] $chunks */
		$chunks = [];

		$viewDistance = Server::getInstance()->getMemoryManager()->getViewDistance(Server::getInstance()->getViewDistance());
		$centerX = $pos->getFloorX() >> 4;
		$centerZ = $pos->getFloorZ() >> 4;

		for($subRadius = 0; $subRadius < $viewDistance; $subRadius++){
			$subRadiusSquared = $subRadius ** 2;
			$nextSubRadiusSquared = ($subRadius + 1) ** 2;
			$minX = (int) ($subRadius / M_SQRT2);

			$lastZ = 0;

			for($x = $subRadius; $x >= $minX; --$x){
				for($z = $lastZ; $z <= $x; ++$z){
					$distanceSquared = ($x ** 2 + $z ** 2);
					if($distanceSquared < $subRadiusSquared){
						continue;
					}elseif($distanceSquared >= $nextSubRadiusSquared){
						break; //skip to next X
					}

					$lastZ = $z;
					//If the chunk is in the radius, others at the same offsets in different quadrants are also guaranteed to be.

					/* Top right quadrant */
					$chunks[] = $level->getChunk($centerX + $x, $centerZ + $z);
					/* Top left quadrant */
					$chunks[] = $level->getChunk($centerX - $x - 1, $centerZ + $z);
					/* Bottom right quadrant */
					$chunks[] = $level->getChunk($centerX + $x, $centerZ - $z - 1);
					/* Bottom left quadrant */
					$chunks[] = $level->getChunk($centerX - $x - 1, $centerZ - $z - 1);

					if($x !== $z){
						/* Top right quadrant mirror */
						$chunks[] = $level->getChunk($centerX + $z, $centerZ + $x);
						/* Top left quadrant mirror */
						$chunks[] = $level->getChunk($centerX - $z - 1, $centerZ + $x);
						/* Bottom right quadrant mirror */
						$chunks[] = $level->getChunk($centerX + $z, $centerZ - $x - 1);
						/* Bottom left quadrant mirror */
						$chunks[] = $level->getChunk($centerX - $z - 1, $centerZ - $x - 1);
					}
				}
			}
		}

		$count = count($chunks);

		$sender->sendMessage("Checking $count chunks!");

		$chunksToCheck = [];

		foreach($chunks as $chunk) {
			$chunksToCheck[] = $chunk->fastSerialize();
		}

		Server::getInstance()->getAsyncPool()->submitTask(new FixTilesTask($chunksToCheck, $sender->getName(), $level->getFolderName()));

		return true;
	}
}

class FixTilesTask extends AsyncTask {

	const TILE_IDS = array(
		BlockIds::SHULKER_BOX => true,
		BlockIds::JUKEBOX => true,
		BlockIds::HOPPER_BLOCK => true,
		BlockIds::CAULDRON_BLOCK => true,
		BlockIds::BREWING_STAND_BLOCK => true,
		BlockIds::BEACON => true,
		BlockIds::TRAPPED_CHEST => true,
		BlockIds::COMMAND_BLOCK => true,
		BlockIds::DAYLIGHT_SENSOR => true,
		BlockIds::DROPPER => true,
		BlockIds::DISPENSER => true,
		BlockIds::NOTE_BLOCK => true,
		BlockIds::OBSERVER => true,
		BlockIds::PISTON => true,
		BlockIds::COMPARATOR_BLOCK => true,
		BlockIds::ITEM_FRAME_BLOCK => true,
	);

	private $chunks;
	/** @var string */
	private $senderName;
	private $levelName;

	public function __construct($chunks, $senderName, $levelName)
	{
		$this->chunks = $chunks;
		$this->senderName = $senderName;
		$this->levelName = $levelName;
	}

	public function onRun()
	{
		$result = [];

		foreach($this->chunks as $serializedChunk) {
			$chunk = Chunk::fastDeserialize($serializedChunk);

			for($x = 0; $x < 16; $x++) {
				for($z = 0; $z < 16; $z++) {
					for($y = 0; $y < 256; $y++) {
						$blockId = $chunk->getBlockId($x, $y, $z);

						if ($blockId === BlockIds::AIR || $blockId === BlockIds::STONE || $blockId === BlockIds::DIRT || $blockId === BlockIds::GRASS) {
							continue;
						}

						if (isset(self::TILE_IDS[$blockId])) {
							$result[] = new Vector3(($chunk->getX() << 4) + $x, $y, ($chunk->getZ() << 4) + $z);
						}
					}
				}
			}
		}

		$this->setResult($result);
	}

	public function onCompletion(Server $server): void
	{
		$sender = $server->getPlayerExact($this->senderName) ?? new ConsoleCommandSender();

		$level = $server->getLevelByName($this->levelName);

		if ($level === null) {
			$sender->sendMessage("Level unloaded while running.");
		}

		$result = $this->getResult();
		$count = count($result);
		$sender->sendMessage("Found $count tiles. Fixing...");

		$fixed = 0;

		foreach($result as $pos) {
			if ($level->getTile($pos) === null) {
				// ladies and gentlemen, we got him!
				$level->setBlock($pos, BlockFactory::get(BlockIds::AIR));
				$fixed++;
			}
		}

		$sender->sendMessage("Fixed $fixed tiles!");
	}
}
