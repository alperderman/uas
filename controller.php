<?php
session_start();

class uas {
    public static function instance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new uas();
        }
        return $instance;
    }

    private function __construct() {
        $this->f3=require(dirname(__FILE__).'/f3/base.php');
        $this->f3->set('AUTOLOAD', dirname(__FILE__).'/models/');
    }
    private function __clone() {}
    private function __sleep() {}
    private function __wakeup() {}

    public $f3;

    public function call_model($class, $args = null, $prepend = true) {
        if ($args === null) {
            $args = $this->f3;
        } else {
            if ($prepend) {
                if (is_array($args)) {
                    array_unshift($args, $this->f3);
                } else {
                    $args = [$this->f3, $args];
                }
            }
        }
        return $this->f3->call($class, $args);
    }

    private function runCallback($args, $result = null) {
        if ($result !== null) {
            if ($result === false && !empty($args['fail'])) {
                $args['fail']($result);
            }
            if ($result !== false && !empty($args['success'])) {
                $args['success']($result);
            }
            if (!empty($args['after'])) {
                $args['after']($result);
            }
        } else {
            if (!empty($args['after'])) {
                $args['after']();
            }
        }
    }

    public function confirmSession($args = []) {
        $result = false;
        $result = $this->call_model('handler->confirmSession');
        $this->runCallback($args, $result);
        return $result;
    }

    public function confirmChain($args = []) {
        $result = false;
        $result = $this->call_model('handler->confirmChain', [$args['session'], $args['chain']]);
        $this->runCallback($args, $result);
        return $result;
    }

    public function checkRank($args = []) {
        $result = false;
        if (!empty($args['username']) && !empty($args['ranks'])) {
            $result = $this->call_model('handler->checkRank', [$args['username'], $args['ranks']]);
        }
        $this->runCallback($args, $result);
        return $result;
    }

    public function checkPerm($args = []) {
        $result = false;
        if (!empty($args['username']) && !empty($args['perms'])) {
            $result = $this->call_model('handler->checkPerm', [$args['username'], $args['perms']]);
        }
        $this->runCallback($args, $result);
        return $result;
    }

    public function updChain($args = []) {
        $result = false;
        $result = $this->call_model('handler->updChain', [$args['session']]);
        $this->runCallback($args, $result);
        return $result;
    }

    public function loginUser($args = []) {
        $result = false;
        if (!empty($args['username']) && !empty($args['password'])) {
            $result = $this->call_model('handler->login', [$args['username'], $args['password']]);
        }
        $this->runCallback($args, $result);
        return $result;
    }

    public function newUser($args = []) {
        $result = false;
        if (!empty($args['username']) && !empty($args['password'])) {
            $result = $this->call_model('handler->newUser', [$args['username'], $args['password']]);
        }
        $this->runCallback($args, $result);
        return $result;
    }

    public function delUser($args = []) {
        if (!empty($args['username'])) {
            $this->call_model('handler->delUser', $args['username']);
        }
        $this->runCallback($args);
    }

    public function getUser($args = []) {
        $result = false;
        $result = $this->call_model('handler->getUser', $args['username']);
        $this->runCallback($args, $result);
        return $result;
    }

    public function updPassword($args = []) {
        $result = false;
        if (!empty($args['username']) && !empty($args['password']) && !empty($args['newpassword'])) {
            $result = $this->call_model('handler->updPassword', [$args['username'], $args['password'], $args['newpassword']]);
            $this->runCallback($args, $result);
        }
        return $result;
    }

    public function getPermsOfRank($args = []) {
        $result = false;
        $result = $this->call_model('handler->getPermsOfRank', $args['rank']);
        $this->runCallback($args, $result);
        return $result;
    }

    public function addPermToRank($args = []) {
        if (!empty($args['perm']) && !empty($args['rank'])) {
            $this->call_model('handler->addPermToRank', [$args['perm'], $args['rank']]);
        }
        $this->runCallback($args);
    }

    public function remPermFromRank($args = []) {
        if (!empty($args['perm']) && !empty($args['rank'])) {
            $this->call_model('handler->remPermFromRank', [$args['perm'], $args['rank']]);
        }
        $this->runCallback($args);
    }

    public function addRankToUser($args = []) {
        if (!empty($args['rank']) && !empty($args['username'])) {
            $this->call_model('handler->addRankToUser', [$args['rank'], $args['username'], $args['expire']]);
        }
        $this->runCallback($args);
    }

    public function remRankFromUser($args = []) {
        if (!empty($args['rank']) && !empty($args['username'])) {
            $this->call_model('handler->remRankFromUser', [$args['rank'], $args['username']]);
        }
        $this->runCallback($args);
    }

    public function delRank($args = []) {
        if (!empty($args['rank'])) {
            $this->call_model('handler->delRank', $args['rank']);
        }
        $this->runCallback($args);
    }

    public function delPerm($args = []) {
        if (!empty($args['perm'])) {
            $this->call_model('handler->delPerm', $args['perm']);
        }
        $this->runCallback($args);
    }
}

return uas::instance();

?>