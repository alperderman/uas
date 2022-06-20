<?php

class util {
    
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
    
}

?>