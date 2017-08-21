<?php namespace kitpvp\combat\bodies;

use pocketmine\network\mcpe\protocol\{
	AddPlayerPacket,
	MobArmorEquipmentPacket,
	PlayerListPacket,
	//MoveEntityPacket,
	//SetEntityMotionPacket,
	RemoveEntityPacket
};
use pocketmine\utils\UUID;
use pocketmine\entity\{
	Entity,
	Human
};
use pocketmine\item\Item;

use kitpvp\KitPvP;
use kitpvp\combat\Combat;
use kitpvp\combat\bodies\tasks\DespawnBodyTask;

use core\AtPlayer as Player;

class Bodies{

	const BODY_LIFESPAN = 60;

	public $plugin;
	public $combat;

	public $bodies = [];

	public function __construct(KitPvP $plugin, Combat $combat){
		$this->plugin = $plugin;
		$this->combat = $combat;

		$plugin->getServer()->getScheduler()->scheduleRepeatingTask(new DespawnBodyTask($plugin), 20);
	}

	public function addBody($thing, $players = []){
		if($thing instanceof Player){
			$name = $thing->getName();
			$eid = Entity::$entityCount++;
			$uuid = UUID::fromRandom();
			$skinid = $thing->getSkinId();
			$skindata = $thing->getSkinData();
			$item = $thing->getInventory()->getItemInHand();
			$armor = $thing->getInventory()->getArmorContents();
			$x = (int) $thing->x;
			$y = (int) $thing->y;
			$z = (int) $thing->z;
			$yaw = $thing->yaw;
			$pitch = $thing->pitch;
		}else{
			$name = "";
			$eid = $thing;
			$uuid = $this->bodies[$eid]["uuid"];
			$skinid = $this->bodies[$eid]["skinid"];
			$skindata = $this->bodies[$eid]["skindata"];
			$item = $this->bodies[$eid]["item"];
			$armor = $this->bodies[$eid]["armor"];
			$x = (int) $this->bodies[$eid]["x"];
			$y = (int) $this->bodies[$eid]["y"];
			$z = (int) $this->bodies[$eid]["z"];
			$yaw = $this->bodies[$eid]["yaw"];
			$pitch = $this->bodies[$eid]["pitch"];
		}
		$pk = new AddPlayerPacket();
		$pk->uuid = $uuid;
		$pk->username = "dead body " . $eid;
		$pk->entityRuntimeId = $eid;
		$pk->x = $x;
		$pk->y = $y;
		$pk->z = $z;
		$pk->pitch = $pitch;
		$pk->headYaw = $yaw;
		$pk->yaw = $yaw;
		$pk->item = $item;
		$flags = 0;
		$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
		$human_flags = 0;
		$human_flags |= 1 << Human::DATA_PLAYER_FLAG_SLEEP;
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, ""],
			Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
			Human::DATA_PLAYER_FLAGS => [Human::DATA_TYPE_BYTE, $human_flags],
			Human::DATA_PLAYER_BED_POSITION => [Human::DATA_TYPE_POS, [$x, $y, $z]],
		];

		$pk2 = new MobArmorEquipmentPacket();
		$pk2->entityRuntimeId = $eid;
		$pk2->slots = $armor;

		$pk3 = new PlayerListPacket();
		$pk3->type = PlayerListPacket::TYPE_ADD;
		$pk3->entries[] = [$uuid, $eid, $name, $skinid, $skindata];

		$pk4 = new PlayerListPacket();
		$pk4->type = PlayerListPacket::TYPE_REMOVE;
		$pk4->entries[] = [$uuid];

		if(empty($players)){
			foreach($this->plugin->getServer()->getOnlinePlayers() as $pl){
				if($pl->getLevel()->getName() == "KitArena"){
					$pl->dataPacket($pk);
					$pl->dataPacket($pk2);
					$pl->dataPacket($pk3);
					$pl->dataPacket($pk4);
				}
			}
			$this->bodies[$eid] = [
				"time" => time() + self::BODY_LIFESPAN, //heh, lifespan of a dead body
				"uuid" => $uuid,
				"skinid" => $skinid,
				"skindata" => $skindata,
				"item" => $item,
				"armor" => $armor,
				"x" => $x,
				"y" => $y,
				"z" => $z,
				"yaw" => $yaw,
				"pitch" => $pitch,
			];
		}else{
			foreach($players as $pl){
				if($pl->getLevel()->getName() == "KitArena"){
					$pl->dataPacket($pk);
					$pl->dataPacket($pk2);
					$pl->dataPacket($pk3);
					$pl->dataPacket($pk4);
				}
			}
		}
	}

	public function canDestroyBody($eid){
		return $this->bodies[$eid]["time"] - time() <= 0;
	}

	public function destroyBody($eid){
		unset($this->bodies[$eid]);
		$pk = new RemoveEntityPacket();
		$pk->entityUniqueId = $eid;
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player) $player->dataPacket($pk);
	}

	public function removeAllBodies(Player $player){
		foreach($this->bodies as $eid => $data){
			$pk = new RemoveEntityPacket();
			$pk->entityUniqueId = $eid;
			$player->dataPacket($pk);
		}
	}

	public function addAllBodies(Player $player){
		foreach($this->bodies as $eid => $data){
			$this->addBody($eid, [$player]);
		}
	}
}