<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\UpdateAbilitiesPacketLayer;
use function count;

/**
 * Updates player abilities and permissions, such as command permissions, flying/noclip, fly speed, walk speed etc.
 * Abilities may be layered in order to combine different ability sets into a resulting set.
 */
class UpdateAbilitiesPacket extends DataPacket {
	public const NETWORK_ID = ProtocolInfo::UPDATE_ABILITIES_PACKET;

	public const NORMAL = 0;
	public const OPERATOR = 1;
	public const AUTOMATION = 2; //command blocks
	public const HOST = 3; //hosting player on LAN multiplayer
	public const OWNER = 4; //server terminal on BDS
	public const INTERNAL = 5;

	private int $commandPermission = self::NORMAL;
	private int $playerPermission = PlayerPermissions::MEMBER;
	private int $targetActorUniqueId; //This is a little-endian long, NOT a var-long. (WTF Mojang)
	/**
	 * @var UpdateAbilitiesPacketLayer[]
	 * @phpstan-var array<int, UpdateAbilitiesPacketLayer>
	 */
	private array $abilityLayers;

	/**
	 * @generate-create-func
	 * @param UpdateAbilitiesPacketLayer[] $abilityLayers
	 * @phpstan-param array<int, UpdateAbilitiesPacketLayer> $abilityLayers
	 */
	public static function create(int $commandPermission, int $playerPermission, int $targetActorUniqueId, array $abilityLayers) : self{
		$result = new self;
		$result->commandPermission = $commandPermission;
		$result->playerPermission = $playerPermission;
		$result->targetActorUniqueId = $targetActorUniqueId;
		$result->abilityLayers = $abilityLayers;
		return $result;
	}

	public function getCommandPermission() : int{ return $this->commandPermission; }

	public function getPlayerPermission() : int{ return $this->playerPermission; }

	public function getTargetActorUniqueId() : int{ return $this->targetActorUniqueId; }

	/** @return UpdateAbilitiesPacketLayer[] */
	public function getAbilityLayers() : array{ return $this->abilityLayers; }

	protected function decodePayload() : void{
		$this->targetActorUniqueId = $this->getLLong(); //WHY IS THIS NON-STANDARD?
		$this->playerPermission = $this->getByte();
		$this->commandPermission = $this->getByte();

		$this->abilityLayers = [];
		for($i = 0, $len = $this->getByte(); $i < $len; $i++){
			$this->abilityLayers[] = UpdateAbilitiesPacketLayer::decode($this);
		}
	}

	protected function encodePayload() : void{
		$this->putLLong($this->targetActorUniqueId);
		$this->putByte($this->playerPermission);
		$this->putByte($this->commandPermission);

		$this->putByte(count($this->abilityLayers));
		foreach($this->abilityLayers as $layer){
			$layer->encode($this);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateAbilities($this);
	}
}