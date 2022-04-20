<?php

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;

class TickingAreasLoadStatusPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::TICKING_AREAS_LOAD_STATUS_PACKET;

	private bool $waitingForPreload;

	protected function decodePayload() : void{
		$this->waitingForPreload = $this->getBool();
	}

	protected function encodePayload() : void{
		$this->putBool($this->waitingForPreload);
	}

	public function handle(NetworkSession $handler) : bool{
		return $handler->handleTickingAreasLoadStatus($this);
	}
}