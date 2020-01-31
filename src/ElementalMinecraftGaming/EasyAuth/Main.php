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

    public function auth($username) {
        $search = $this->db->prepare("SELECT pw FROM security WHERE player = :player;");
        $search->bindValue(":player", $username);
        $start = $search->execute();
        $got = $start->fetchArray(SQLITE3_ASSOC);
        return $got["pw"];
    }

    public function registerAccount($username, $password) {
        $pw = password_hash($password, TRUE);
        $del = $this->db->prepare("INSERT OR REPLACE INTO security (player, pw) VALUES (:player, :pw);");
        $del->bindValue(":player", $username);
        $del->bindValue(":pw", $pw);
        $start = $del->execute();
    }

    public function editSafeword($username, $safeword) {
        $sww = password_hash($safeword, TRUE);
        $del = $this->db->prepare("INSERT OR REPLACE INTO securityy (player, sw) VALUES (:player, :sw);");
        $del->bindValue(":player", $username);
        $del->bindValue(":sw", $sw);
        $start = $del->execute();
    }

    public function authh($username) {
        $search = $this->db->prepare("SELECT sw FROM securityy WHERE player= :player;");
        $search->bindValue(":player", $username);
        $start = $search->execute();
        $got = $start->fetchArray(SQLITE3_ASSOC);
        return $got["sw"];
    }

    public function playerRegistered($username) {
        $user = \SQLite3::escapeString($username);
        $search = $this->db->prepare("SELECT * FROM security WHERE player = :player;");
        $search->bindValue(":player", $user);
        $start = $search->execute();
        $delta = $start->fetchArray(SQLITE3_ASSOC);
        return empty($delta) == false;
    }

    public function safeRegistered($username) {
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
        $player->sendMessage($this->sResult("Please register or log in with: \n/reg {Password}\n/login {Password}\n/eslogin {SafeWord}\n/essafe {SafeWord}\n/logout")) * 6;
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
        $player->sendMessage($this->sResult("Please register or log in with: \n/reg {Password}\n/login {Password}\n/eslogin {SafeWord}\n/essafe {SafeWord}\n/logout\n/quit")) * 600;
    }

    public function sResult($string) {
        return TextFormat::YELLOW . "[" . TextFormat::RED . "EasyAuth" . TextFormat::YELLOW . "] " . TextFormat::GREEN . "$string";
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (strtolower($command->getName()) == "signin") {
            if ($sender->hasPermission("easyauth.signin")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (!isset($this->loggedIn[$sender->getPlayer()->getName()])) {
                            $sender->sendMessage($this->sResult("Logging in..."));
                            $player = $sender->getName();
                            $username = strtolower($player);
                            $password = $args[0];
                            $checkname = $this->playerRegistered($username);
                            if ($checkname == true) {
                                $checkpw = $this->auth($username, $password);
								$verifyy = password_verify($password, $checkpw);
                                if ($verifyy) {
                                    $sender->sendMessage($this->sResult("Logged in!"));
                                    $this->loggedIn[$sender->getName()] = true;
                                    return true;
                                } else {
                                    $sender->sendMessage($this->sResult("Invalid password"));
                                }
                            } else {
                                $sender->sendMessage($this->sResult("Username is not registered"));
                            }
                        } else {
                            $sender->sendMessage($this->sResult("Your already logged in"));
                        }
                    } else {
                        $sender->sendMessage($this->sResult("Please set the password"));
                    }
                } else {
                    $sender->sendMessage($this->sResult("IN-GAME ONLY!"));
                }
            } else {
                $sender->sendMessage($this->sResult("Invalid perms"));
                return false;
            }
        }

        if (strtolower($command->getName()) == "register") {
            if ($sender->hasPermission("easyauth.signup")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        $sender->sendMessage($this->sResult("Signing up..."));
                        $player = $sender->getName();
                        $username = strtolower($player);
                        $pw = $args[0];
                        $checkname = $this->playerRegistered($username);
                        if ($checkname == false) {
                            $this->registerAccount($username, $pw);
                            $sender->sendMessage($this->sResult("Registered!"));
                            $this->loggedIn[$sender->getName()] = true;
                            return true;
                        } else {
                            $sender->sendMessage($this->sResult("Account already registered!"));
                        }
                    } else {
                        $sender->sendMessage($this->sResult("Add password!"));
                    }
                } else {
                    $sender->sendMessage($this->sResult("In-Game only!"));
                }
            } else {
                $sender->sendMessage($this->sResult("No Permissions!"));
                return false;
            }
        }

        if (strtolower($command->getName()) == "changepw") {
            if ($sender->hasPermission("easyauth.signup")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (isset($this->loggedIn[$sender->getPlayer()->getName()])) {
                            $sender->sendMessage($this->sResult("Changing Paswword..."));
                            $player = $sender->getName();
                            $username = strtolower($player);
                            $pw = $args[0];
                            $checkname = $this->playerRegistered($username);
                            if ($checkname == TRUE) {
                                $this->registerAccount($username, $pw);
                                $sender->sendMessage($this->sResult("Changed password!"));
                                $this->loggedIn[$sender->getName()] = true;
                                return true;
                            } else {
                                $sender->sendMessage($this->sResult("Account not registered!"));
                            }
                        } else {
                            $sender->sendMessage($this->sResult("Log in first!"));
                        }
                    } else {
                        $sender->sendMessage($this->sResult("Add password!"));
                    }
                } else {
                    $sender->sendMessage($this->sResult("In-Game only!"));
                }
            } else {
                $sender->sendMessage($this->sResult("No Permissions!"));
                return false;
            }
        }

        if (strtolower($command->getName()) == "changesw") {
            if ($sender->hasPermission("easyauth.signup")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (isset($this->loggedIn[$sender->getPlayer()->getName()])) {
                            $sender->sendMessage($this->sResult("Signing up..."));
                            $player = $sender->getName();
                            $username = strtolower($player);
                            $sw = $args[0];
                            $checkname = $this->safeRegistered($username);
                            if ($checkname == true) {
                                $this->editSafeword($username, $sw);
                                $sender->sendMessage($this->sResult("Registered safe word!"));
                                $sender->sendMessage($this->sResult("It's recommended to sign out to make sure it worked!"));
                                return true;
                            } else {
                                $sender->sendMessage($this->sResult("Safe word not registered!"));
                            }
                        } else {
                            $sender->sendMessage($this->sResult("Login/register first!"));
                        }
                    } else {
                        $sender->sendMessage($this->sResult("Add safe word!"));
                    }
                } else {
                    $sender->sendMessage($this->sResult("In-Game only!"));
                }
            } else {
                $sender->sendMessage($this->sResult("No Permissions!"));
                return false;
            }
        }

        if (strtolower($command->getName()) == "safeset") {
            if ($sender->hasPermission("easyauth.signup")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (isset($this->loggedIn[$sender->getPlayer()->getName()])) {
                            $sender->sendMessage($this->sResult("Signing up..."));
                            $player = $sender->getName();
                            $username = strtolower($player);
                            $sw = $args[0];
                            $checkname = $this->safeRegistered($username);
                            if ($checkname == false) {
                                $this->editSafeword($username, $sw);
                                $sender->sendMessage($this->sResult("Registered safe word!"));
                                $sender->sendMessage($this->sResult("It's recommended to sign out to make sure it worked!"));
                                return true;
                            } else {
                                $sender->sendMessage($this->sResult("Safe word already registered!"));
                            }
                        } else {
                            $sender->sendMessage($this->sResult("Login/register first!"));
                        }
                    } else {
                        $sender->sendMessage($this->sResult("Add safe word!"));
                    }
                } else {
                    $sender->sendMessage($this->sResult("In-Game only!"));
                }
            } else {
                $sender->sendMessage($this->sResult("No Permissions!"));
                return false;
            }
        }

        if (strtolower($command->getName()) == "safelogin") {
            if ($sender->hasPermission("easyauth.signin")) {
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (!isset($this->loggedIn[$sender->getPlayer()->getName()])) {
                            $sender->sendMessage($this->sResult("Logging in..."));
                            $player = $sender->getName();
                            $username = strtolower($player);
                            $safeword = $args[0];
                            $checkname = $this->safeRegistered($username);
                            if ($checkname == true) {
                                $checksw = $this->authh($username, $safeword);
                                $verifyy = password_verify($safeword, $checksw);
                                if ($verifyy) {
                                    $sender->sendMessage($this->sResult("Logged in!"));
                                    $this->loggedIn[$sender->getName()] = true;
                                    return true;
                                } else {
                                    $sender->sendMessage($this->sResult("Invalid safe word"));
                                }
                            } else {
                                $sender->sendMessage($this->sResult("Username is not registered"));
                            }
                        } else {
                            $sender->sendMessage($this->sResult("Your already logged in"));
                        }
                    } else {
                        $sender->sendMessage($this->sResult("Please set the safe word"));
                    }
                } else {
                    $sender->sendMessage($this->sResult("IN-GAME ONLY!"));
                }
            } else {
                $sender->sendMessage($this->sResult("Invalid perms"));
                return false;
            }
        }
        if (strtolower($command->getName()) == "logout") {
            if ($sender->hasPermission("easyauth.signin")) {
                if ($sender instanceof Player) {
                    if (isset($this->loggedIn[$sender->getPlayer()->getName()])) {
                        unset($this->loggedIn[$sender->getPlayer()->getName()]);
                        $sender->sendMessage($this->sResult("Logged out!"));
                        return true;
                    } else {
                        $sender->sendMessage($this->sResult("Your not logged in"));
                    }
                } else {
                    $sender->sendMessage($this->sResult("IN-GAME ONLY!"));
                }
            } else {
                $sender->sendMessage($this->sResult("Invalid perms"));
                return false;
            }
        }
        return false;
    }

}
