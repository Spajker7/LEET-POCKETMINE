<?php


namespace pocketmine\entity;


use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

class PersonaPieceTintColor
{
	public const PIECE_TYPE_PERSONA_EYES = "persona_eyes";
	public const PIECE_TYPE_PERSONA_HAIR = "persona_hair";
	public const PIECE_TYPE_PERSONA_MOUTH = "persona_mouth";

	/** @var string */
	private $pieceType;
	/** @var string[] */
	private $colors;

	/**
	 * @param string $pieceType
	 * @param string[] $colors
	 */
	public function __construct(string $pieceType, array $colors){
		$this->pieceType = $pieceType;
		$this->colors = $colors;
	}

	public function getPieceType() : string{
		return $this->pieceType;
	}

	/**
	 * @return string[]
	 */
	public function getColors() : array{
		return $this->colors;
	}

	public static function deserializeNBT(CompoundTag $pieceTintColorTag) : PersonaPieceTintColor{

		$colors = [];

		/** @var StringTag $color */
		foreach ($pieceTintColorTag->getListTag("Colors") as $color) {
			$colors[] = $color->getValue();
		}

		return new PersonaPieceTintColor(
			$pieceTintColorTag->getString("PieceType"),
			$colors
		);
	}

	public function serializeNBT() : CompoundTag{

		$colorTags = [];

		foreach ($this->getColors() as $color) {
			$colorTags[] = new StringTag("Color", $color);
		}

		return new CompoundTag("PieceTintColor", [
			new StringTag("PieceType", $this->getPieceType()),
			new ListTag("Colors", $colorTags)
		]);
	}
}