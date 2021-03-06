<?php namespace kitpvp\arena\predators\entities;

use pocketmine\level\Level;
use pocketmine\nbt\tag\{
	CompoundTag,
	ListTag,
	FloatTag,
	DoubleTag,
	ShortTag,
	StringTag
};
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Boss extends Predator{

	public $reinforcements = [];

	public $rTick = 300;
	public $healCooldown = 300;

	public $startingHealth = 300;

	public $untilSpecial = 160;
	public $specialTicks = 0;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->setScale(1.5);
	}

	public function getNametag() : string{
		return TextFormat::YELLOW . TextFormat::BOLD . "[BOSS] " .  TextFormat::RESET . parent::getNametag();
	}

	public function isBoss(){
		return true;
	}

	public function tickReinforcements(){
		if($this->rTick > 0 && $this->hasTarget()){
			$this->rTick--;
		}
		if($this->rTick %100 == 0){
			foreach($this->reinforcements as $eid){
				if(Server::getInstance()->findEntity($eid, $this->getLevel()) == null) unset($this->reinforcements[$eid]);
			}
		}
		if($this->rTick <= 0 && $this->hasTarget() && count($this->reinforcements) <= 7){
			$count = mt_rand(1,3);
			for($i = 0; $i <= $count; $i++){
				$x = $this->getX() + mt_rand(-3,3);
				$y = $this->getY();
				$z = $this->getZ() + mt_rand(-3,3);

				$nbt = new CompoundTag(" ", [
					new ListTag("Pos", [
						new DoubleTag("", $x),
						new DoubleTag("", $y),
						new DoubleTag("", $z)
					]),
					new ListTag("Motion", [
						new DoubleTag("", 0),
						new DoubleTag("", 0),
						new DoubleTag("", 0)
					]),
					new ListTag("Rotation", [
						new FloatTag("", 0),
						new FloatTag("", 0),
					]),
					new ShortTag("Health", 20),
					new CompoundTag("Skin", [
						new StringTag("Data", file_get_contents("/home/data/skins/holding_chest.dat")),
						new StringTag("Name", "Standard_Custom")
					]),
				]);
				$entity = $this->getReinforcement($this->getLevel(), $nbt);
				$entity->target = $this->target;
				$entity->spawnToAll();

				$this->reinforcements[] = $entity->getId();
			}
			$this->rTick = 300;
		}
	}

	public function getReinforcement(Level $level, CompoundTag $nbt){
		return null;
	}

	public function tickHealer(){
		if($this->healCooldown > 0){
			$this->healCooldown--;
		}

		if($this->healCooldown === 0){
			if($this->getHealth() < $this->getMaxHealth()){
				$this->setHealth($this->getHealth() + 1);
			}
		}
	}

	public function tickSpecial(){
		if($this->untilSpecial > 0 && $this->hasTarget()){
			$this->untilSpecial--;
		}
		if($this->untilSpecial === 0){
			$this->performSpecial();
		}
	}

	public function performSpecial(){
		$this->specialTicks++;
	}

	public function resetSpecial(){
		$this->untilSpecial = 160;
		$this->specialTicks = 0;
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$this->tickReinforcements();
		$this->tickSpecial();
		$this->tickHealer();

		return parent::entityBaseTick($tickDiff);
	}

	public function attack(EntityDamageEvent $source) : void{
		$this->healCooldown = 300;
		parent::attack($source);
	}

	public function whistle(){
		foreach($this->getLevel()->getNearbyEntities($this->getBoundingBox()->expandedCopy(15, 15, 15)) as $entity){
			if($entity instanceof Predator && $entity->canWhistle){
				$entity->target = $this->target;
				$entity->canWhistle = false;
			}
		}
	}

	public function spawnToAll() : void{
		parent::spawnToAll();
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			$player->sendMessage(TextFormat::RED . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GRAY . "A " . TextFormat::DARK_PURPLE . $this->getType() . " Boss" . TextFormat::GRAY . " has spawned! Kill it for a big prize.");
		}
	}

}