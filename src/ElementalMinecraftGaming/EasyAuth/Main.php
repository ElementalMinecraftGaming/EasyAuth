<?php
namespace ElementalMinecraftGaming\EasyAuth;
    
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\utils\Config;
use pocketmine\Server;

class Main extends PluginBase implements Listener
 {
    
    public $db;
    public $config;
    private $loggedIn = [];
    
    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->db = new \SQLite3($this->getDataFolder() . "easyauth.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS security(player TEXT PRIMARY KEY, pw TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS securityy(player TEXT PRIMARY KEY, sw TEXT);");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
     public function auth($username, $pw) {
        $search = $this->db->prepare("SELECT pw FROM security WHERE player = :player;");
        $search->bindValue(":player", $username);
        $start = $search->execute();
	$got = $start->fetchArray(SQLITE3_ASSOC);
        return $got["pw"];
    }
    
    public function authh($username, $sw) {
        $search = $this->db->prepare("SELECT sw FROM securityy WHERE player= :player;");
        $search->bindValue(":player", $username);
        $start = $search->execute();
	$got = $start->fetchArray(SQLITE3_ASSOC);
        return $got["sw"];
    }
    
    public function playerRegistered($username)
	{
		$user = \SQLite3::escapeString($username);
		$search = $this->db->prepare("SELECT * FROM security WHERE player = :player;");
                $search->bindValue(":player", $user);
                $start = $search->execute();
		$delta = $start->fetchArray(SQLITE3_ASSOC);
		return empty($delta) == false;
	}
        
        public function safeRegistered($username)
	{
		$user = \SQLite3::escapeString($username);
		$search = $this->db->prepare("SELECT * FROM securityy WHERE player = :player;");
                $search->bindValue(":player", $user);
                $start = $search->execute();
		$delta = $start->fetchArray(SQLITE3_ASSOC);
		return empty($delta) == false;
	}
    
    public function onMove(PlayerMoveEvent $event): bool {
        if (isset($this->loggedIn[$event->getPlayer()->getName()])) {
            return true;
        }
        $player = $event->getPlayer();
        $player->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Please register or log in with: \n/reg {Password}\n/login {Password}\n/eslogin {SafeWord}\n/essafe {SafeWord}\n/logout\n/quit") * 600;
        $event->setCancelled();
        return false;
    }
    
    public function onQuit(PlayerQuitEvent $event) {
        if (isset($this->loggedIn[$event->getPlayer()->getName()])) {
            unset($this->loggedIn[$event->getPlayer()->getName()]);
        }
    }
        
        public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $player->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Please register or log in with: \n/reg {Password}\n/login {Password}\n/eslogin {SafeWord}\n/essafe {SafeWord}\n/logout\n/quit") * 600;
    }
    
     public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
         if (strtolower($command->getName()) == "signin") {
            if ($sender->hasPermission("easyauth.signin")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (!isset($this->loggedIn[$sender->getPlayer()->getName()])) {
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Logging in...");
                            $player = $sender->getName();
                            $username = strtolower($player);
                            $password = $args[0];
                            $checkname = $this->playerRegistered($username);
                            if ($checkname == true) {
                                $checkpw = $this->auth($username, $password);
                                if (password_verify($password, $checkpw)) {
                                    $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Logged in!");
                                    $this->loggedIn[$sender->getName()] = true;
                                    return true;
                                } else {
                                    $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Invalid password");
                                }
                            } else {
                                $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Username is not registered");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Your already logged in");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Please set the password");
                    }
                } else {
                    $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "IN-GAME ONLY!");
                }
            } else {
                $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Invalid perms");
                return false;
            }
        }

        if (strtolower($command->getName()) == "register") {
            if ($sender->hasPermission("easyauth.signup")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Signing up...");
                        $player = $sender->getName();
                        $username = strtolower($player);
                        $pw = password_hash($args[0], TRUE);
                        $checkname = $this->playerRegistered($username);
                        if ($checkname == false) {
                            $del = $this->db->prepare("INSERT OR REPLACE INTO security (player, pw) VALUES (:player, :pw);");
                            $del->bindValue(":player", $username);
                            $del->bindValue(":pw", $pw);
                            $start = $del->execute();
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Registered!");
                            $this->loggedIn[$sender->getName()] = true;
                            return true;
                        } else {
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Account already registered!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Add password!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "In-Game only!");
                }
            } else {
                $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "No Permissions!");
                return false;
            }
        }

        if (strtolower($command->getName()) == "safeset") {
            if ($sender->hasPermission("easyauth.signup")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (isset($this->loggedIn[$sender->getPlayer()->getName()])) {
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Signing up...");
                            $player = $sender->getName();
                            $username = strtolower($player);
                            $sw = password_hash($args[0], TRUE);
                            $checkname = $this->safeRegistered($username);
                            if ($checkname == false) {
                                $del = $this->db->prepare("INSERT OR REPLACE INTO securityy (player, sw) VALUES (:player, :sw);");
                                $del->bindValue(":player", $username);
                                $del->bindValue(":sw", $sw);
                                $start = $del->execute();
                                $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Registered safe word!");
                                $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "It's recommended to sign out to make sure it worked!");
                                return true;
                            } else {
                                $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Safe word already registered!");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Login/register first!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Add safe word!");
                    }
                } else {
                    $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "In-Game only!");
                }
            } else {
                $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "No Permissions!");
                return false;
            }
        }

        if (strtolower($command->getName()) == "safelogin") {
            if ($sender->hasPermission("easyauth.signin")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (!isset($this->loggedIn[$sender->getPlayer()->getName()])) {
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Logging in...");
                            $player = $sender->getName();
                            $username = strtolower($player);
                            $safeword = $args[0];
                            $checkname = $this->safeRegistered($username);
                            if ($checkname == true) {
                                $checksw = $this->authh($username, $safeword);
                                if (password_verify($safeword, $checksw)) {
                                    $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Logged in!");
                                    $this->loggedIn[$sender->getName()] = true;
                                    return true;
                                } else {
                                    $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Invalid safe word");
                                }
                            } else {
                                $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Username is not registered");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Your already logged in");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Please set the safe word");
                    }
                } else {
                    $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "IN-GAME ONLY!");
                }
            } else {
                $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Invalid perms");
                return false;
            }
        }
        if (strtolower($command->getName()) == "logout") {
            if ($sender->hasPermission("easyauth.signin")) {
                if ($sender instanceof Player) {
                        if (isset($this->loggedIn[$sender->getPlayer()->getName()])) {
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Logging out...");
                            unset($this->loggedIn[$sender->getPlayer()->getName()]);
                                    $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Logged out!");
                                    return true;
                        } else {
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Your not logged in");
                        }
                } else {
                    $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "IN-GAME ONLY!");
                }
            } else {
                $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Invalid perms");
                return false;
            }
        }
        if (strtolower($command->getName()) == "quit") {
            if ($sender->hasPermission("easyauth.signup")) {
                if ($sender instanceof Player) {
                        if (isset($this->loggedIn[$sender->getPlayer()->getName()])) {
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Leave...");
                            unset($this->loggedIn[$sender->getPlayer()->getName()]);
                            $sender->getPlayer()->kick("You quit", false);
                            $player = $sender->getPlayer()->getName();
                                    $this->getServer()->broadcastMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "$player quit the game!");
                                    return true;
                        } else {
                            $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Your must be logged in to use this!");
                        }
                } else {
                    $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "IN-GAME ONLY!");
                }
            } else {
                $sender->sendMessage(TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "Invalid perms");
                return false;
            }
        }
        return false;
    }

}
