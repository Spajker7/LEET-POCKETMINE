<?php

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\DimensionData;
use pocketmine\network\mcpe\protocol\types\DimensionNameIds;

class DimensionDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::DIMENSION_DATA_PACKET;

	/**
	 * @var DimensionData[]
	 * @phpstan-var array<DimensionNameIds::*, DimensionData>
	 */
	private array $definitions;

	/**
	 * @generate-create-func
	 * @param DimensionData[] $definitions
	 * @phpstan-param array<DimensionNameIds::*, DimensionData> $definitions
	 */
	public static function create(array $definitions) : self{
		$result = new self;
		$result->definitions = $definitions;
		return $result;
	}

	/**
	 * @return DimensionData[]
	 * @phpstan-return array<DimensionNameIds::*, DimensionData>
	 */
	public function getDefinitions() : array{ return $this->definitions; }

	protected function decodePayload() : void{
		$this->definitions = [];

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; $i++){
			$dimensionNameId = $this->getString();
			$dimensionData = DimensionData::read($this);

			if(isset($this->definitions[$dimensionNameId])){
				throw new \RuntimeException("Repeated dimension data for key \"$dimensionNameId\"");
			}
			if($dimensionNameId !== DimensionNameIds::OVERWORLD && $dimensionNameId !== DimensionNameIds::NETHER && $dimensionNameId !== DimensionNameIds::THE_END){
				throw new \RuntimeException("Invalid dimension name ID \"$dimensionNameId\"");
			}
			$this->definitions[$dimensionNameId] = $dimensionData;
		}
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt(count($this->definitions));

		foreach($this->definitions as $dimensionNameId => $definition){
			$this->putString((string) $dimensionNameId); //@phpstan-ignore-line
			$definition->write($this);
		}
	}

	public function handle(NetworkSession $handler) : bool{
		return $handler->handleDimensionData($this);
	}
}