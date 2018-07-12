<?php

    function fpVarchar($param){
        if(!isset($param)){
            return "NULL";
        }

        return "'" . $param . "'";
    }

    function fpInt($param){
        if(!is_numeric($param)){
            return "NULL";
        }

        if($param === ""){
            return "NULL";
        }

        return $param;
    }

    function fpDate($param){
        if(!isset($param)){
            return "NULL";
        }

        if($param == ""){
            return "NULL";
        }

        return "'" . $param . "'";
    }

    function utf8ize($d) {
        if (is_array($d)) {
                foreach ($d as $k => $v) {
                $d[$k] = utf8ize($v);
                }
        } else if (is_string ($d)) {
                return utf8_encode($d);
        }
        return $d;
    }       