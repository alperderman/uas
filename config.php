<?php

class config {
    public $sessionLimits = ["min"=>24, "max"=>32];
    public $chainLimits = ["min"=>32, "max"=>48];
    public $usernameRegex = '/^[a-zA-Z0-9]{4,16}$/';
    public $passwordRegex = '/^[a-zA-Z0-9]{4,24}$/';
    public $defaultAuth = 'auth';

    public $db = ["uas"=>"sqlite:".__DIR__."/db/uas.db"];
    public $defaultDb = "uas";

    public $queryGetUser = 'select username, group_concat(rank, ";") as ranks from ranks where username = ? group by username';
    public $queryGetUsers = 'select username, group_concat(rank, ";") as ranks from ranks group by username';
    public $queryGetPermsOfRank = 'select perm from perms where rank = ?';
    public $queryGetPerms = 'select * from perms';
    public $querypartCheckRank = 'select count(*) as count from users as u, ranks as r where r.username = u.username and (r.expire > strftime("%s", "now") or r.expire is null or r.expire = "") and u.username = ? and (';
    public $querypartCheckPerm = 'select count(*) as count from users as u, ranks as r, perms as p where r.username = u.username and (r.expire > strftime("%s", "now") or r.expire is null or r.expire = "") and r.rank = p.rank and u.username = ? and (';
    public $queryAddRankToUser = 'insert into ranks (rank, username, expire) values (?, ?, ?)';
    public $queryAddPermToRank = 'insert into perms (perm, rank) values (?, ?)';
    public $queryRemPermFromRank = 'delete from perms where perm = ? and rank = ?';
    public $queryRemRankFromUser = 'delete from ranks where rank = ? and username = ?';
    public $queryDelPerm = 'delete from perms where perm = ?';
    public $queryDelRankFromRanks = 'delete from ranks where rank = ?';
    public $queryDelRankFromPerms = 'delete from perms where rank = ?';
    public $queryDelUserFromUsers = 'delete from users where username = ?';
    public $queryDelUserFromRanks = 'delete from ranks where username = ?';
    public $queryCheckUser = 'select count(*) from users where username = ? limit 0, 1';
    public $queryNewUser = 'insert into users (username, password) values (?, ?)';
    public $queryGetPass = 'select password from users where username = ? limit 0, 1';
    public $queryUpdPass = 'update users set password = ? where username = ?';
}

?>