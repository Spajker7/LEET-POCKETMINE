<?php


namespace pocketmine\network\mcpe\protocol\types;


class Experiment{
	/** @var string */
	private $name;
	/** @var bool */
	private $enabled;

	public function __construct(string $name, bool $enabled){
		$this->name = $name;
		$this->enabled = $enabled;
	}

	public function getName(): string{
		return $this->name;
	}

	public function isEnabled(): bool{
		return $this->enabled;
	}
}