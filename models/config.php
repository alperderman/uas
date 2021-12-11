<?php

class config {
    public $sessionLimits = ["min"=>24, "max"=>32];
    public $chainLimits = ["min"=>32, "max"=>48];
    public $usernameLimits = [4, 16];
    public $passwordLimits = [4, 24];
    public $usernameRegex = '/^[a-zA-Z0-9]{4,16}$/';
    public $passwordRegex = '/^[a-zA-Z0-9]{4,24}$/';
    public $defaultSession = 'POST.s';
    public $defaultChain = 'POST.c';
    public $defaultAuth = 'auth';
}

?>