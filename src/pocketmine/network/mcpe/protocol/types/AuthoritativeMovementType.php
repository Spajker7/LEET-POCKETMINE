<?php


namespace pocketmine\network\mcpe\protocol\types;


class AuthoritativeMovementType{

	private function __construct(){
		//NOOP
	}

	public const CLIENT = 0;
	public const SERVER = 1;
	public const SERVER_WITH_REWIND = 2;
}