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

namespace pocketmine\command;

use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\plugin\Plugin;

class PluginAnnotatedCommand extends Command implements PluginIdentifiableCommand{

	/** @var Plugin */
	private $owningPlugin;
	/** @var AnnotatedCommandListener|Plugin */
	private $listener;
	/** @var string */
	private $method;

	public function __construct(string $name, Plugin $owner, $listener, string $method){
		parent::__construct($name);
		$this->owningPlugin = $owner;
		$this->listener = $listener;
		$this->method = $method;
		$this->usageMessage = "";
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){

		if(!$this->owningPlugin->isEnabled()){
			return false;
		}

		if(!$this->testPermission($sender)){
			return false;
		}

		$success = $this->listener->{$this->method}($sender, $args);

		if(!$success and $this->usageMessage !== ""){
			throw new InvalidCommandSyntaxException();
		}

		return $success;
	}

	public function getExecutor() : CommandExecutor{
		return $this->executor;
	}

	/**
	 * @return void
	 */
	public function setExecutor(CommandExecutor $executor){
		$this->executor = $executor;
	}

	public function getPlugin() : Plugin{
		return $this->owningPlugin;
	}
}
