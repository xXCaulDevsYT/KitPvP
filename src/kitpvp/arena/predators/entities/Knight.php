<?php namespace kitpvp\arena\predators\entities;

use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Skin;

class Knight extends Predator{

	public $attackDamage = 4;
	public $speed = 0.35;
	public $startingHealth = 20;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->setSkin(new Skin("Standard_Custom", file_get_contents("/home/data/skins/knight.dat")));
	}

	public function getType(){
		return "Knight";
	}

}