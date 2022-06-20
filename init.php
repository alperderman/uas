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

    private function __clone() {}
    private function __sleep() {}
    private function __wakeup() {}

    private function __construct() {
        require(dirname(__FILE__).'/config.php');
        $this->config = new config();
        require(dirname(__FILE__).'/util.php');
        $this->util = new util();
    }
    
    public $config;
    public $util;
    public $conn;

    function execQuery($query, $params = null, $dbname = null) {
        if (empty($dbname)) {
            $dbname = $this->config->defaultDb;
        }
        if (empty($this->conn[$dbname])) {
            $this->conn[$dbname] = new PDO($this->config->db[$dbname]);
        }
        $connQuery = $this->conn[$dbname]->prepare($query);
        if (!empty($params)) {
            if (!is_array($params)) {
                $params = array($params);
            }
            $connQuery->execute($params);
        } else {
            $connQuery->execute();
        }
        return $connQuery->fetchAll();
    }

    function checkRank($args = [], $dbname = null) { //args: username, ranks
        $result = false;
        $ranksCount = null;
        $params = $args['ranks'];
        $sql = $this->config->querypartCheckRank;
        
        if (is_array($args['ranks'])) {
            $ranksCount = count($args['ranks']);
            for ($i = 0;$i < $ranksCount;$i++) {
                if ($i < 1) {
                    $sql .= 'r.rank = ?';
                } else {
                    $sql .= 'or r.rank = ?';
                }
            }
            array_unshift($params, $args['username']);
        }else {
            $ranksCount = 1;
            $sql .= 'r.rank = ?';
            $params = [$args['username'], $args['ranks']];
        }
    
        $sql .= ')';
        $response = $this->execQuery($sql, $params, $dbname);
    
        if (!empty($response) && $response[0]['count'] >= $ranksCount) {
            $result = true;
        }
    
        return $result;
    }

    function checkPerm($args = [], $dbname = null) { //args: username, perms
        $result = false;
        $permsCount = null;
        $params = $args['perms'];
        $sql = $this->config->querypartCheckPerm;
    
        if (is_array($args['perms'])) {
            $permsCount = count($args['perms']);
            for ($i = 0;$i < $permsCount;$i++) {
                if ($i < 1) {
                    $sql .= 'p.perm = ?';
                }else {
                    $sql .= 'or p.perm = ?';
                }
            }
            array_unshift($params, $args['username']);
        }else {
            $permsCount = 1;
            $sql .= 'p.perm = ?';
            $params = [$args['username'], $args['perms']];
        }
    
        $sql .= ')';
        $response = $this->execQuery($sql, $params, $dbname);
        
        if (!empty($response) && $response[0]['count'] >= $permsCount) {
            $result = true;
        }
        
        return $result;
    }

    function addPermToRank($args = [], $dbname = null) { //args: perm, rank
        if (!empty($args['perm']) && !empty($args['rank'])) {
            $params = [$args['perm'], $args['rank']];
            $this->execQuery($this->config->queryAddPermToRank, $params, $dbname);
        }
    }

    function addRankToUser($args = [], $dbname = null) { //args: rank, username, expire
        if (empty($args['expire'])) {$args['expire'] = null;}

        if (!empty($args['rank']) && !empty($args['username'])) {
            $params = [$args['rank'], $args['username'], $args['expire']];
            $this->execQuery($this->config->queryAddRankToUser, $params, $dbname);
        }
    }

    function remPermFromRank($args = [], $dbname = null) { //args: perm, rank
        if (!empty($args['perm']) && !empty($args['rank'])) {
            $params = [$args['perm'], $args['rank']];
            $this->execQuery($this->config->queryRemPermFromRank, $params, $dbname);
        }
    }

    function remRankFromUser($args = [], $dbname = null) { //args: rank, username
        if (!empty($args['rank']) && !empty($args['username'])) {
            $params = [$args['rank'], $args['username']];
            $this->execQuery($this->config->queryRemRankFromUser, $params, $dbname);
        }
    }

    function delPerm($args = [], $dbname = null) { //args: perm
        if (!empty($args['perm'])) {
            $this->execQuery($this->config->queryDelPerm, $args['perm'], $dbname);
        }
    }

    function delRank($args = [], $dbname = null) { //args: rank
        if (!empty($args['rank'])) {
            $this->execQuery($this->config->queryDelRankFromRanks, $args['rank'], $dbname);
            $this->execQuery($this->config->queryDelRankFromPerms, $args['rank'], $dbname);
        }
    }

    function delUser($args = [], $dbname = null) { //args: username
        if (!empty($args['username'])) {
            $this->execQuery($this->config->queryDelUserFromUsers, $args['username'], $dbname);
            $this->execQuery($this->config->queryDelUserFromRanks, $args['username'], $dbname);
        }
    }

    function newUser($args = [], $dbname = null) { //args: username, password
        $result = false;
        $params = [$args['username'], $args['password']];
        $regs = [$this->config->usernameRegex, $this->config->passwordRegex];
        if ($this->util->multiPregMatch($regs, $params)) {
            $response = $this->execQuery($this->config->queryCheckUser, $params, $dbname);
            if (empty($response)) {
                $passwordHash = password_hash($args['password'], PASSWORD_BCRYPT);
                $params = [$args['username'], $passwordHash];
                $this->execQuery($this->config->queryNewUser, $params, $dbname);
                $result = true;
            }
        }
        return $result;
    }

    function login($args = [], $dbname = null) { //args: username, password
        $result = false;
        $params = [$args['username'], $args['password']];
        $regs = [$this->config->usernameRegex, $this->config->passwordRegex];
        if ($this->util->multiPregMatch($regs, $params)) {
            $response = $this->execQuery($this->config->queryGetPass, $args['username'], $dbname);
            if (!empty($response)) {
                $passwordHash = $response[0]['password'];
                if (password_verify($args['password'], $passwordHash)) {
                    $result = true;
                    $_SESSION[$this->config->defaultAuth] = array('username'=>$args['username']);
                }
            }
        }
        return $result;
    }

    function logout() {
        session_unset();
        session_destroy();
    }

    function confirmSession() {
        $result = false;
        if (!empty($_SESSION[$this->config->defaultAuth])) {
            $result = $_SESSION[$this->config->defaultAuth]['username'];
        }
        return $result;
    }

    function getUser($args = [], $dbname = null) { //args: username
        $result = false;
        if (empty($args['username'])) {$args['username'] = null;}

        if (!empty($args['username'])) {
            $response = $this->execQuery($this->config->queryGetUser, $args['username'], $dbname);
            if (!empty($response)) {
                $result = $response;
            }
        } else {
            $response = $this->execQuery($this->config->queryGetUsers, $args['username'], $dbname);
            $result = $response;
        }
        return $result;
    }

    function getPermsOfRank($args = [], $dbname = null) { //args: rank
        $result = false;
        if (empty($args['rank'])) {$args['rank'] = null;}

        if (!empty($args['rank'])) {
            $response = $this->execQuery($this->config->queryGetPermsOfRank, $args['rank'], $dbname);
            if (!empty($response)) {
                $result = array();
                foreach ($response as $row) {
                    array_push($result, $row['perm']);
                }
            }
        } else {
            $result = array();
            $response = $this->execQuery($this->config->queryGetPerms, $args['rank'], $dbname);
            foreach ($response as $row) {
                if (!isset($result[$row['rank']])) {
                    $result[$row['rank']] = array();
                }
                array_push($result[$row['rank']], $row['perm']);
            }
        }
        return $result;
    }

    function updPass($args = [], $dbname = null) { //args: username, password, newpassword
        $result = false;
        if (!empty($args['username']) && !empty($args['password']) && !empty($args['newpassword'])) {
            $response = $this->execQuery($this->config->queryGetPass, $args['username'], $dbname);
            if (!empty($response)) {
                $passwordHash = $response[0]['password'];
                if (password_verify($args['password'], $passwordHash)) {
                    $result = true;
                    $newPasswordHash = password_hash($args['newpassword'], PASSWORD_BCRYPT);
                    $params = [$newPasswordHash, $args['username']];
                    $this->execQuery($this->config->queryUpdPass, $params, $dbname);
                }
            }
        }
        return $result;
    }

}

return uas::instance();

?>