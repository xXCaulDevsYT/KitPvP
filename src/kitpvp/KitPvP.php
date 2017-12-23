<?php namespace kitpvp;

use pocketmine\plugin\PluginBase;

use kitpvp\{
	items\Items,

	achievements\Achievements,
	arena\Arena,
	combat\Combat,
	duels\Duels,
	kits\Kits,
	leaderboard\Leaderboard
};

class KitPvP extends PluginBase{

	public static $instance = null;
	public $database;

	public $dir = "/home/data/gamemodes/kitpvp/";

	public $achievements;
	public $arena;
	public $combat;
	public $duels;
	public $kits;
	public $leaderboard;

	public function onEnable(){
		self::$instance = $this;

		$creds = array_merge(file("/home/data/mysqlcreds"), ["kitpvp"]);
		foreach($creds as $key => $cred) $creds[$key] = str_replace("\n", "", $cred);
		try{
			$this->database = new \mysqli(...$creds);
		}catch(\Exception $e){
			$this->getLogger()->error("Database connection failed!");
			$this->getServer()->shutdown();
		}
		$this->getLogger()->notice("Successfully connected to database.");

		$this->getServer()->loadLevel("KitSpawn");
		$this->getServer()->getLevelByName("KitSpawn")->setTime(18000);
		$this->getServer()->getLevelByName("KitSpawn")->stopTime();

		$this->getServer()->loadLevel("KitArena");
		$this->getServer()->getLevelByName("KitArena")->setTime(0);
		$this->getServer()->getLevelByName("KitArena")->stopTime();

		$this->getServer()->loadLevel("duels");
		$this->getServer()->getLevelByName("duels")->setTime(0);
		$this->getServer()->getLevelByName("duels")->stopTime();

		Items::init();

		$this->achievements = new Achievements($this);
		$this->arena = new Arena($this);
		$this->leaderboard = new Leaderboard($this);
		$this->duels = new Duels($this);
		$this->combat = new Combat($this);
		$this->kits = new Kits($this);

		$this->getServer()->getPluginManager()->registerEvents(new MainListener($this), $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new MainTask($this), 20);
	}

	public function onDisable(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($this->getKits()->hasKit($player)){
				$this->getKits()->getPlayerKit($player)->refund($player);
			}
		}
		$this->getCombat()->close();

		$this->database->close();
	}

	public static function getInstance(){
		return self::$instance;
	}

	public function getAchievements(){
		return $this->achievements;
	}

	public function getArena(){
		return $this->arena;
	}

	public function getCombat(){
		return $this->combat;
	}

	public function getDuels(){
		return $this->duels;
	}

	public function getKits(){
		return $this->kits;
	}

	public function getLeaderboard(){
		return $this->leaderboard;
	}

}