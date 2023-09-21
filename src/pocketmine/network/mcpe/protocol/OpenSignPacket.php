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

class OpenSignPacket extends DataPacket/* implements ServerboundPacket*/{
	public const NETWORK_ID = ProtocolInfo::OPEN_SIGN_PACKET;

	private Vector3 $signPosition;
	private bool $front;

	public static function create(Vector3 $signPosition, bool $front) : self{
		$result = new self;
		$result->signPosition = $signPosition;
		$result->front = $front;
		return $result;
	}

	public function getSignPosition() : Vector3 { return $this->signPosition; }

	public function isFront() : bool{ return $this->front; }

	protected function decodePayload() : void{
		$x = 0;
		$y = 0;
		$z = 0;
		$this->getBlockPosition($x, $y, $z);
		$this->signPosition = new Vector3($x, $y, $z);
		$this->front = $this->getBool();
	}

	protected function encodePayload() : void{
		$this->putBlockPosition($this->signPosition->getX(), $this->signPosition->getY(), $this->signPosition->getZ());
		$this->putBool($this->front);
	}

	public function handle(NetworkSession $handler) : bool{
		return $handler->handleOpenSign($this);
	}
}
