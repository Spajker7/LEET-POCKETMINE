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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class SetActorDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_ACTOR_DATA_PACKET;

	/** @var int */
	public $entityRuntimeId;
	/**
	 * @var mixed[][]
	 * @phpstan-var array<int, array{0: int, 1: mixed}>
	 */
	public $metadata;

	public $syncedPropertiesInt = [];
	public $syncedPropertiesFloat = [];

	/** @var int */
	public $tick = 0;

	protected function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->metadata = $this->getEntityMetadata();

		for($i = 0; $i < $this->getUnsignedVarInt(); $i++) {
			$this->syncedPropertiesInt[$this->getUnsignedVarInt()] = $this->getVarInt();
		}

		for($i = 0; $i < $this->getUnsignedVarInt(); $i++) {
			$this->syncedPropertiesFloat[$this->getUnsignedVarInt()] = $this->getLFloat();
		}

		$this->tick = $this->getUnsignedVarLong();
	}

	protected function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putEntityMetadata($this->metadata);

		$this->putUnsignedVarInt(count($this->syncedPropertiesInt));
		foreach($this->syncedPropertiesInt as $key => $value){
			$this->putUnsignedVarInt($key);
			$this->putVarInt($value);
		}

		$this->putUnsignedVarInt(count($this->syncedPropertiesFloat));
		foreach($this->syncedPropertiesFloat as $key => $value){
			$this->putUnsignedVarInt($key);
			$this->putLFloat($value);
		}

		$this->putUnsignedVarLong($this->tick);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleSetActorData($this);
	}
}
