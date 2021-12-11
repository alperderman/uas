<?php

class util {

    function limitData($data, $limit) {
        $countData = count($data);
        $countLimit = count($limit);
        $result = [];
        if($countData == $countLimit){
            for($i=0;$i < $countData;$i++){
            if($data[$i] == null || $data[$i] == ""){
                $result = false;
                break;
            }else{
                if($limit[$i] != false && strlen($data[$i]) > $limit[$i]){
                array_push($result, substr($data[$i], 0, $limit[$i]));
                }else{
                array_push($result, $data[$i]);
                }
            }
            }
        }else{
            $result = false;
        }
        return $result;
    }
    
    function multiPregMatch($regs, $args) {
        $result = true;
        if (!empty($args) && !empty($regs)) {
            if (is_array($args)) {
                if (is_array($regs)) {
                    for($i=0;$i < count($args);$i++){
                        if (!preg_match($regs[$i], $args[$i])) {
                            $result = false;
                            break;
                        }
                    }
                } else {
                    foreach($args as $arg){
                        if (!preg_match($regs, $arg)) {
                            $result = false;
                            break;
                        }
                    }
                }
            } else {
                if (!preg_match($regs, $args)) {
                    $result = false;
                }
            }
        } else {
            $result = false;
        }
        return $result;
    }

    function crypto_rand_secure($min, $max) {
        $range = $max - $min;
        if ($range < 1) return $min;
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1;
        $bits = (int) $log + 1;
        $filter = (int) (1 << $bits) - 1;
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter;
        } while ($rnd > $range);
        return $min + $rnd;
    }

    function newToken($length){
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet);
        for ($i=0; $i < $length; $i++) {
        $token .= $codeAlphabet[$this->crypto_rand_secure(0, $max-1)];
        }
        return $token;
    }
    
}

?>