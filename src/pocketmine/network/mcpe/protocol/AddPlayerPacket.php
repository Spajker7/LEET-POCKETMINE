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

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\UpdateAbilitiesPacketLayer;
use pocketmine\utils\UUID;
use function count;

class AddPlayerPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_PLAYER_PACKET;

	/** @var UUID */
	public $uuid;
	/** @var string */
	public $username;
	/** @var int|null */
	public $entityUniqueId = null; //TODO
	/** @var int */
	public $entityRuntimeId;
	/** @var string */
	public $platformChatId = "";
	/** @var Vector3 */
	public $position;
	/** @var Vector3|null */
	public $motion;
	/** @var float */
	public $pitch = 0.0;
	/** @var float */
	public $yaw = 0.0;
	/** @var float|null */
	public $headYaw = null; //TODO
	/** @var ItemStackWrapper */
	public $item;
	public $gameMode = GameMode::SURVIVAL;
	/**
	 * @var mixed[][]
	 * @phpstan-var array<int, array{0: int, 1: mixed}>
	 */
	public $metadata = [];
	public $syncedPropertiesInt = [];
	public $syncedPropertiesFloat = [];

	//TODO: adventure settings stuff
	/** @var int */
	public $playerPermissions = PlayerPermissions::VISITOR;
	/** @var int */
	public $commandPermissions = UpdateAbilitiesPacket::NORMAL;
	/** @var array<UpdateAbilitiesPacketLayer> */
	public $layers;
	/** @var int */

	/** @var EntityLink[] */
	public $links = [];

	/** @var string */
	public $deviceId = ""; //TODO: fill player's device ID (???)
	/** @var int */
	public $buildPlatform = DeviceOS::UNKNOWN;

	protected function decodePayload(){
		$this->uuid = $this->getUUID();
		$this->username = $this->getString();
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->platformChatId = $this->getString();
		$this->position = $this->getVector3();
		$this->motion = $this->getVector3();
		$this->pitch = $this->getLFloat();
		$this->yaw = $this->getLFloat();
		$this->headYaw = $this->getLFloat();
		$this->item = ItemStackWrapper::read($this);
		$this->gameMode = $this->getVarInt();
		$this->metadata = $this->getEntityMetadata();

		for($i = 0; $i < $this->getUnsignedVarInt(); $i++) {
			$this->syncedPropertiesInt[$this->getUnsignedVarInt()] = $this->getVarInt();
		}

		for($i = 0; $i < $this->getUnsignedVarInt(); $i++) {
			$this->syncedPropertiesFloat[$this->getUnsignedVarInt()] = $this->getLFloat();
		}

		$this->entityUniqueId = $this->getLLong();

		$this->playerPermissions = $this->getByte();
		$this->commandPermissions = $this->getByte();

		$layerCount = $this->getByte();
		$this->layers = [];
		for($i = 0; $i < $layerCount; ++$i){
			$this->layers[] = UpdateAbilitiesPacketLayer::decode($this);
		}

		$linkCount = $this->getUnsignedVarInt();
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[$i] = $this->getEntityLink();
		}

		$this->deviceId = $this->getString();
		$this->buildPlatform = $this->getLInt();
	}

	protected function encodePayload(){
		$this->putUUID($this->uuid);
		$this->putString($this->username);
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putString($this->platformChatId);
		$this->putVector3($this->position);
		$this->putVector3Nullable($this->motion);
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);
		$this->putLFloat($this->headYaw ?? $this->yaw);
		$this->item->write($this);
		$this->putVarInt($this->getVarInt());
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

		$this->putLLong($this->entityUniqueId ?? $this->entityRuntimeId);

		$this->putByte($this->playerPermissions);
		$this->putByte($this->commandPermissions);

		$layers = $this->layers ?? [new UpdateAbilitiesPacketLayer(
				UpdateAbilitiesPacketLayer::LAYER_BASE,
				array_fill(0, UpdateAbilitiesPacketLayer::NUMBER_OF_ABILITIES, false),
				0.0,
				0.0
			)];

		$this->putByte(count($layers));
		foreach($layers as $layer){
			$layer->encode($this);
		}

		$this->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$this->putEntityLink($link);
		}

		$this->putString($this->deviceId);
		$this->putLInt($this->buildPlatform);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddPlayer($this);
	}
}
