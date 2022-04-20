<?php

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\NetworkBinaryStream;

final class DimensionData{

	public function __construct(
		private int $maxHeight,
		private int $minHeight,
		private int $generator
	){}

	public function getMaxHeight() : int{ return $this->maxHeight; }

	public function getMinHeight() : int{ return $this->minHeight; }

	public function getGenerator() : int{ return $this->generator; }

	public static function read(NetworkBinaryStream $stream) : self{
		$maxHeight = $stream->getVarInt();
		$minHeight = $stream->getVarInt();
		$generator = $stream->getVarInt();

		return new self($maxHeight, $minHeight, $generator);
	}

	public function write(NetworkBinaryStream $stream) : void{
		$stream->putVarInt($this->maxHeight);
		$stream->putVarInt($this->minHeight);
		$stream->putVarInt($this->generator);
	}
}