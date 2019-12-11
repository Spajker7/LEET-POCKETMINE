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

namespace pocketmine\entity;

use Ahc\Json\Comment as CommentedJsonDecoder;
use InvalidArgumentException;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\SerializedImage;
use pocketmine\utils\SkinAnimation;
use function hash;
use function json_encode;
use function str_repeat;

class Skin{
	public const ACCEPTED_SKIN_SIZES = [
		64 * 32 * 4,
		64 * 64 * 4,
		128 * 64 * 4,
		128 * 128 * 4,
		256 * 128 * 4
	];

	/** @var string */
	private $skinId;
	/** @var string */
	private $skinResourcePatch;
	/** @var SerializedImage */
	private $skinData;
	/** @var SkinAnimation[] */
	private $animations;
	/** @var SerializedImage */
	private $capeData;
	/** @var string */
	private $geometryData;
	/** @var string */
	private $animationData;
	/** @var bool */
	private $premium;
	/** @var bool */
	private $persona;
	/** @var bool */
	private $capeOnClassic;
	/** @var ?string */
	private $capeId;

	public function __construct(string $skinId, string $skinResourcePatch, SerializedImage $skinData, array $animations = [], SerializedImage $capeData = null, string $geometryData = "", string $animationData = "", bool $premium = false, bool $persona = false, $capeOnClassic = false, string $capeId = null){
		$this->skinId = $skinId;
		$this->skinResourcePatch = $skinResourcePatch;
		$this->skinData = $skinData;
		$this->animations = $animations;
		$this->capeData = $capeData;
		$this->geometryData = $geometryData;
		$this->animationData = $animationData;
		$this->premium = $premium;
		$this->persona = $persona;
		$this->capeOnClassic = $capeOnClassic;
		$this->capeId = $capeId;

		$this->debloatGeometryData();
	}

	public static function null() : Skin {
		$skinData = str_repeat("\x00", 8192);
		return new Skin(hash("md5", $skinData), self::convertLegacyGeometryName("geometry.humanoid.custom"), SerializedImage::fromLegacy($skinData));
	}

	public static function convertLegacyGeometryName(string $geometryName) : string{
		return json_encode(["geometry" => ["default" => $geometryName]]);
	}

	/**
	 * @return string
	 */
	public function getSkinResourcePatch() : string{
		return $this->skinResourcePatch;
	}

	/**
	 * @return SkinAnimation[]
	 */
	public function getAnimations() : array{
		return $this->animations;
	}

	/**
	 * @return string
	 */
	public function getAnimationData() : string{
		return $this->animationData;
	}

	/**
	 * @return bool
	 */
	public function isPremium() : bool{
		return $this->premium;
	}

	/**
	 * @return bool
	 */
	public function isPersona() : bool{
		return $this->persona;
	}

	/**
	 * @return bool
	 */
	public function isCapeOnClassic() : bool{
		return $this->capeOnClassic;
	}

	public function getCapeId() : string{
		return $this->capeId ?? "";
	}

	/**
	 * @deprecated
	 * @return bool
	 */
	public function isValid() : bool{
		try{
			$this->validate();

			return true;
		}catch(InvalidArgumentException $e){
			return false;
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function validate() : void{
		if($this->skinId === ""){
			throw new InvalidArgumentException("Skin ID must not be empty");
		}
		//TODO: validate geometry
	}

	/**
	 * @return string
	 */
	public function getSkinId() : string{
		return $this->skinId;
	}

	/**
	 * @return SerializedImage
	 */
	public function getSkinData() : SerializedImage{
		return $this->skinData;
	}

	/**
	 * @return SerializedImage
	 */
	public function getCapeData() : SerializedImage{
		if($this->capeData === null){
			return new SerializedImage(0, 0, '');
		}

		return $this->capeData;
	}

	/**
	 * @return string
	 */
	public function getGeometryData() : string{
		return $this->geometryData;
	}

	/**
	 * Hack to cut down on network overhead due to skins, by un-pretty-printing geometry JSON.
	 *
	 * Mojang, some stupid reason, send every single model for every single skin in the selected skin-pack.
	 * Not only that, they are pretty-printed.
	 * TODO: find out what model crap can be safely dropped from the packet (unless it gets fixed first)
	 */
	public function debloatGeometryData() : void{
		if($this->geometryData !== ""){
			$this->geometryData = (string) json_encode((new CommentedJsonDecoder())->decode($this->geometryData));
		}
	}

	public function getFullSkinId() : string{
		return $this->skinId . "_" . $this->getCapeId();
	}

	/**
	 * @param CompoundTag $skinTag
	 *
	 * @return Skin
	 * @throws InvalidArgumentException
	 */
	public static function deserializeSkinNBT(CompoundTag $skinTag) : Skin{

		$skin = null;

		// old skin format
		if($skinTag->hasTag("GeometryName")) {
			$capeData = $skinTag->getByteArray("CapeData", "");
			$cape = $capeData === "" ? new SerializedImage(0, 0, "") : new SerializedImage(32, 64, $capeData);

			$skin = new Skin(
				$skinTag->getString("Name"),
				json_encode(["geometry" => ["default" => $skinTag->getString("GeometryName", "")]]),
				SerializedImage::fromLegacy($skinTag->hasTag("Data", StringTag::class) ? $skinTag->getString("Data") : $skinTag->getByteArray("Data")), //old data (this used to be saved as a StringTag in older versions of PM)
				[],
				$cape,
				$skinTag->getByteArray("GeometryData", "")
			);
		}
		else {
			$animations = [];

			if($skinTag->hasTag("Animations", ListTag::class)) {
				/** @var CompoundTag $animationTag */
				foreach ($skinTag->getListTag("Animations") as $animationTag) {
					$animations[] = new SkinAnimation(
						new SerializedImage(
							$animationTag->getInt("ImageWidth"),
							$animationTag->getInt("ImageHeight"),
							$animationTag->getByteArray("Data")),
						$animationTag->getInt("Type"), $animationTag->getFloat("Frames"));
				}
			}

			$skin = new Skin(
				$skinTag->getString("Name"),
				$skinTag->getString("SkinResourcePatch", ""),
				new SerializedImage($skinTag->getInt("SkinImageWidth"), $skinTag->getInt("SkinImageHeight"), $skinTag->getByteArray("Data")),
				$animations,
				new SerializedImage($skinTag->getInt("CapeImageWidth"), $skinTag->getInt("CapeImageHeight"), $skinTag->getByteArray("CapeData")),
				$skinTag->getByteArray("GeometryData", ""),
				$skinTag->getByteArray("AnimationData", ""),
				$skinTag->getByte("PremiumSkin") === 1,
				$skinTag->getByte("PersonaSkin") === 1,
				$skinTag->getByte("CapeOnClassic") === 1,
				$skinTag->getString("CapeId", "")
			);
		}

		$skin->validate();
		return $skin;
	}

	public function serializeSkinNBT() : CompoundTag{
		$animations = [];

		foreach($this->getAnimations() as $animation) {
			$animationTag = new CompoundTag("Animation", [
				new ByteArrayTag("Data", $animation->getImage()->getData()),
				new IntTag("ImageHeight", $animation->getImage()->getHeight()),
				new IntTag("ImageWidth", $animation->getImage()->getWidth()),
				new IntTag("Type", $animation->getType()),
				new FloatTag("Frames", $animation->getFrames())
			]);

			$animations[] = $animationTag;
		}

		return new CompoundTag("Skin", [
			new StringTag("Name", $this->getSkinId()),
			new StringTag("SkinResourcePatch", $this->getSkinResourcePatch()),
			new ByteArrayTag("Data", $this->getSkinData()->getData()),
			new IntTag("SkinImageHeight", $this->getSkinData()->getHeight()),
			new IntTag("SkinImageWidth", $this->getSkinData()->getWidth()),
			new ByteArrayTag("CapeData", $this->getCapeData()->getData()),
			new IntTag("CapeImageHeight", $this->getCapeData()->getHeight()),
			new IntTag("CapeImageWidth", $this->getCapeData()->getWidth()),
			new ByteArrayTag("GeometryData", $this->getGeometryData()),
			new ByteArrayTag("AnimationData", $this->getAnimationData()),
			new ByteTag("PremiumSkin", $this->isPremium() ? 1 : 0),
			new ByteTag("PersonaSkin", $this->isPersona() ? 1 : 0),
			new ByteTag("CapeOnClassic", $this->isCapeOnClassic() ? 1 : 0),
			new StringTag("CapeId", $this->getCapeId()),
			new ListTag("Animations", $animations)
		]);
	}
}
