<?php

class handler {

  public $util;
  public $config;

  function __construct() {
    $this->util = new util();
    $this->config = new config();
  }

  function initDB($f3) {
    if (empty($f3->get("dbUas"))) {
      $f3->set('dbUas', new DB\SQL('sqlite:'.dirname(__FILE__).'/../db/uas.db'));
    }
  }

  function checkRank($f3, $username, $ranks) {
    $result = false;
    $args = $ranks;
    $sql = 'select count(*) as count from users as u, ranks as r where r.username = u.username and (r.expire < strftime("%s", "now") or r.expire is null) and u.username = ? and (';

    if (is_array($ranks)) {
      $ranksCount = count($ranks);
      for ($i = 0;$i < count($ranks);$i++) {
        if ($i < 1) {
          $sql .= 'r.rank = ?';
        }else {
          $sql .= 'or r.rank = ?';
        }
      }
      array_unshift($args, $username);
    }else {
      $ranksCount = 1;
      $sql .= 'r.rank = ?';
      $args = [$username, $ranks];
    }

    $sql .= ')';
    $this->initDB($f3);
    $response = $f3->get("dbUas")->exec($sql, $args);

    if (!empty($response) && $response[0]['count'] >= $ranksCount) {
      $result = true;
    }

    return $result;
  }

  function checkPerm($f3, $username, $perms) {
    $result = false;
    $args = $perms;
    $sql = 'select count(*) as count from users as u, ranks as r, perms as p where r.username = u.username and (r.expire < strftime("%s", "now") or r.expire is null) and r.rank = p.rank and u.username = ? and (';

    if (is_array($perms)) {
      $permsCount = count($perms);
      for ($i = 0;$i < count($perms);$i++) {
        if ($i < 1) {
          $sql .= 'p.perm = ?';
        }else {
          $sql .= 'or p.perm = ?';
        }
      }
      array_unshift($args, $username);
    }else {
      $permsCount = 1;
      $sql .= 'p.perm = ?';
      $args = [$username, $perms];
    }

    $sql .= ')';
    $this->initDB($f3);
    $response = $f3->get("dbUas")->exec($sql, $args);
    
    if (!empty($response) && $response[0]['count'] >= $permsCount) {
      $result = true;
    }
    
    return $result;
  }

  function addPermToRank($f3, $perm, $rank) {
    if (!empty($perm) && !empty($rank)) {
      $this->initDB($f3);
      $args = [$perm, $rank];
      $f3->get("dbUas")->exec('insert into perms (perm, rank) values (?, ?)', $args);
    }
  }

  function addRankToUser($f3, $rank, $username, $expire = null) {
    if (!empty($rank) && !empty($username)) {
      $this->initDB($f3);
      $args = [$rank, $username, $expire];
      $f3->get("dbUas")->exec('insert into ranks (rank, username, expire) values (?, ?, ?)', $args);
    }
  }

  function remPermFromRank($f3, $perm, $rank) {
    if (!empty($perm) && !empty($rank)) {
      $this->initDB($f3);
      $args = [$perm, $rank];
      $f3->get("dbUas")->exec('delete from perms where perm = ? and rank = ?', $args);
    }
  }

  function remRankFromUser($f3, $rank, $username) {
    if (!empty($rank) && !empty($username)) {
      $this->initDB($f3);
      $args = [$rank, $username];
      $f3->get("dbUas")->exec('delete from ranks where rank = ? and username = ?', $args);
    }
  }

  function delPerm($f3, $perm) {
    if (!empty($perm)) {
      $this->initDB($f3);
      $f3->get("dbUas")->exec('delete from perms where perm = ?', $perm);
    }
  }

  function delRank($f3, $rank) {
    if (!empty($rank)) {
      $this->initDB($f3);
      $f3->get("dbUas")->exec(
        array(
          'delete from ranks where rank = ?',
          'delete from perms where rank = ?'
        ),
        $rank
      );
    }
  }

  function login($f3, $username, $password) {
    $result = false;
    $args = [$username, $password];
    $regs = [$this->config->usernameRegex, $this->config->passwordRegex];
    if ($this->util->multiPregMatch($regs, $args)) {
      $this->initDB($f3);
      $response = $f3->get("dbUas")->exec('select password from users where username = ? limit 0, 1', $username);
      if (!empty($response)) {
        $passwordHash = $response[0]['password'];
        if (password_verify($password, $passwordHash)) {
          $result = true;
          $f3->set('SESSION.'.$this->config->defaultAuth.'', array('username'=>$username));
        }
      }
    }
    return $result;
  }

  function logout($f3) {
    $f3->clear('SESSION');
  }

  function newUser($f3, $username, $password) {
    $result = false;
    $args = [$username, $password];
    $regs = [$this->config->usernameRegex, $this->config->passwordRegex];
    if ($this->util->multiPregMatch($regs, $args)) {
      $this->initDB($f3);
      $response = $f3->get("dbUas")->exec('select count(*) from users where username = ? limit 0, 1', $username);
      if (empty($response)) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $args = [$username, $passwordHash];
        $f3->get("dbUas")->exec('insert into users (username, password) values (?, ?)', $args);
        $result = true;
      }
    }
    return $result;
  }

  function delUser($f3, $username) {
    if (!empty($username)) {
      $this->initDB($f3);
      $f3->get("dbUas")->exec(
        array(
          'delete from users where username = ?', 
          'delete from ranks where username = ?'
        ),
        $username
      );
    }
  }

  function getUser($f3, $username = null) {
    $result = false;
    $this->initDB($f3);
    if (!empty($username)) {
      $response = $f3->get("dbUas")->exec('select username, group_concat(rank, ";") as ranks from ranks where username = ? group by username', $username);
      if (!empty($response)) {
        $result = $response;
      }
    } else {
      $response = $f3->get("dbUas")->exec('select username, group_concat(rank, ";") as ranks from ranks group by username');
      $result = $response;
    }
    return $result;
  }

  function getPermsOfRank($f3, $rank = null) {
    $result = false;
    $this->initDB($f3);
    if (!empty($rank)) {
      $response = $f3->get("dbUas")->exec('select distinct p.perm from ranks as r, perms as p where r.rank = p.rank and r.rank = ?', $rank);
      if (!empty($response)) {
        $result = array();
        foreach ($response as $row) {
          array_push($result, $row['perm']);
        }
      }
    } else {
      $result = array();
      $response = $f3->get("dbUas")->exec('select distinct p.perm, r.rank from ranks as r, perms as p where r.rank = p.rank');
      foreach ($response as $row) {
        if (!is_array($result[$row['rank']])) {
          $result[$row['rank']] = array();
        }
        array_push($result[$row['rank']], $row['perm']);
      }
    }
    return $result;
  }

  function updPassword($f3, $username, $password, $newPassword) {
    $result = false;
    if (!empty($username) && !empty($password) && !empty($newPassword)) {
      $this->initDB($f3);
      $response = $f3->get("dbUas")->exec('select password from users where username = ? limit 0, 1', $username);
      if (!empty($response)) {
        $passwordHash = $response[0]['password'];
        if (password_verify($password, $passwordHash)) {
          $result = true;
          $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
          $args = [$newPasswordHash, $username];
          $f3->get("dbUas")->exec('update users set password = ? where username = ?', $args);
        }
      }
    }
    return $result;
  }

  function newToken($f3, $username = null) {
    if ($username === null && $this->confirmSession($f3)) {
      $username = $f3->get('SESSION.'.$this->config->defaultAuth.'.username');
    }

    $sessionRand = rand($this->config->sessionLimits['min'], $this->config->sessionLimits['max']);
    $chainRand = rand($this->config->chainLimits['min'], $this->config->chainLimits['max']);
    $sessionToken = $this->util->newToken($sessionRand);
    $chainToken = password_hash($this->util->newToken($chainRand), PASSWORD_BCRYPT);
    $args = [$sessionToken, $chainToken, $username];
    $this->initDB($f3);
    $f3->get("dbUas")->exec('insert into auth (session, chain, username) values (?, ?, ?)', $args);
  }

  function delToken($f3, $sessionToken) {
    if (!empty($sessionToken)) {
      $this->initDB($f3);
      $f3->get("dbUas")->exec('delete from auth where session = ?', $sessionToken);
    }
  }

  function updChain($f3, $sessionToken = null) {
    $result = false;
    if (empty($sessionToken)) {$f3->get($this->config->defaultSession);}
    if (!empty($sessionToken)) {
      $chainRand = rand($this->config->chainLimits['min'], $this->config->chainLimits['max']);
      $chainToken = password_hash($this->util->newToken($chainRand), PASSWORD_BCRYPT);
      $args = [$chainToken, $sessionToken];
      $this->initDB($f3);
      $f3->get("dbUas")->exec('update auth set chain = ? where session = ?', $args);
      $result = $chainToken;
    }
    return $result;
  }

  function getToken($f3, $username = null) {
    $result = false;
    if ($username === null && $this->confirmSession($f3)) {
      $username = $f3->get('SESSION.'.$this->config->defaultAuth.'.username');
    }

    $this->initDB($f3);
    $response = $f3->get("dbUas")->exec('select session from auth where username = ?', $username);
    if (!empty($response)) {
      $result = $response;
    }
    
    return $result;
  }

  function confirmSession($f3) {
    $result = false;
    if (!empty($f3->get('SESSION.'.$this->config->defaultAuth.''))) {
      $result = $f3->get('SESSION.'.$this->config->defaultAuth.'.username');
    }
    return $result;
  }

  function confirmChain($f3, $sessionToken = null, $chainToken = null) {
    $result = false;

    if (empty($sessionToken)) {$f3->get($this->config->defaultSession);}
    if (empty($chainToken)) {$f3->get($this->config->defaultChain);}

    if (!empty($sessionToken) && !empty($chainToken)) {
      $this->initDB($f3);
      $response = $f3->get("dbUas")->exec('select chain, username from auth as a where (a.expire < strftime("%s", "now") or a.expire is null) and a.session = ? limit 0, 1', $sessionToken);
      if (!empty($response)) {
        $chainHash = $response[0]['chain'];
        if (password_verify($chainToken, $chainHash)) {
          $result = $response[0]['username'];
        }
      }
    }

    return $result;
  }

}

?>