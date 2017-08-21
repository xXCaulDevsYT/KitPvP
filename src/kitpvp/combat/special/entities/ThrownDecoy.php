<?php namespace kitpvp\combat\special\entities;

use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use kitpvp\KitPvP;

class ThrownDecoy extends Projectile{

	const NETWORK_ID = 87;

	public $width = 0.25;
	public $length = 0.25;
	public $height = 0.25;

	protected $gravity = 0.03;
	protected $drag = 0.01;

	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null){
		parent::__construct($level, $nbt, $shootingEntity);
	}

	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::onUpdate($currentTick);

		$owner = $this->getOwningEntity();
		if(!$owner instanceof Player){
			$this->close();
			return true;
		}
		if($this->isCollided or $this->onGround){
			if(KitPvP::getInstance()->getArena()->inArena($owner)){
				KitPvP::getInstance()->getKits()->setInvisible($owner, true);
				KitPvP::getInstance()->getCombat()->getSpecial()->special[$owner->getName()]["decoy"] = time();
			}
			$this->close();
			$hasUpdate = true;
		}
		if($this->age > 1200){
			$this->close();
			$hasUpdate = true;
		}
		return $hasUpdate;
	}

	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->type = ThrownDecoy::NETWORK_ID;
		$pk->entityRuntimeId = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);
		parent::spawnTo($player);
	}

}