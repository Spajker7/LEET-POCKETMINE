<?php

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;

class AgentActionEventPacket extends DataPacket {
	public const NETWORK_ID = ProtocolInfo::AGENT_ACTION_EVENT_PACKET;

	private string $requestId;
	private int $action;
	private string $responseJson;

	/**
	 * @generate-create-func
	 */
	public static function create(string $requestId, int $action, string $responseJson) : self{
		$result = new self;
		$result->requestId = $requestId;
		$result->action = $action;
		$result->responseJson = $responseJson;
		return $result;
	}

	public function getRequestId() : string{ return $this->requestId; }

	public function getAction() : int{ return $this->action; }

	public function getResponseJson() : string{ return $this->responseJson; }

	protected function decodePayload() : void{
		$this->requestId = $this->getString();
		$this->action = $this->getLInt();
		$this->responseJson = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putString($this->requestId);
		$this->putLInt($this->action);
		$this->putString($this->responseJson);
	}

	public function handle(NetworkSession $handler) : bool{
		return $handler->handleAgentActionEvent($this);
	}
}