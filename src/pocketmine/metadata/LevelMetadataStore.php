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

namespace pocketmine\metadata;

use pocketmine\level\Level;
use pocketmine\plugin\Plugin;
use function strtolower;

class LevelMetadataStore extends MetadataStore{

	private function disambiguate(Level $level, string $metadataKey) : string{
		return strtolower($level->getFolderName()) . ":" . $metadataKey;
	}

	/**
	 * @return MetadataValue[]
	 */
	public function getMetadata(Level $subject, string $metadataKey){
		return $this->getMetadataInternal($this->disambiguate($subject, $metadataKey));
	}

	public function hasMetadata(Level $subject, string $metadataKey) : bool{
		return $this->hasMetadataInternal($this->disambiguate($subject, $metadataKey));
	}

	/**
	 * @return void
	 */
	public function removeMetadata(Level $subject, string $metadataKey, Plugin $owningPlugin){
		$this->removeMetadataInternal($this->disambiguate($subject, $metadataKey), $owningPlugin);
	}

	/**
	 * @return void
	 */
	public function setMetadata(Level $subject, string $metadataKey, MetadataValue $newMetadataValue){
		$this->setMetadataInternal($this->disambiguate($subject, $metadataKey), $newMetadataValue);
	}
}
