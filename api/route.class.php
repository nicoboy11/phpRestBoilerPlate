<?php

    class Route{
        private $route;
        private $needsToken;
        private $token;
        private $db;

        function Route($routes, $db, $needsToken = false, $token = null, $decoded = null) {
            $this->route = $routes;
            $this->needsToken = $needsToken;
            $this->token = $token;
            $this->db = $db;
            $this->decoded = $decoded;
        }

        function post($route, $callback){
            $archivo = "";

            if($route == $this->route[1] && $_SERVER['REQUEST_METHOD'] == 'POST') {
                
                $payload = file_get_contents('php://input');
                $data = json_decode($payload, true);

                if($data == null){
                    $data = $_POST;
                }

                if( $this->needsToken && !isset($this->token) ) {
                    echo 'No token provided';
                } else {
                    $data['token'] = $this->token;
                    $data['txt_usuario'] = $this->decoded->txt_usuario;
                    $callback($data, $this->route[2], $this->db);
                }

            } 

        }

        function get($route, $callback){
            if($route == $this->route[1]) {
                if( $this->needsToken && !isset($this->token) ) {
                    echo 'No token provided';
                } else {
                    $_GET['token'] = $this->token;
                    $_GET['token'] = $this->decoded->txt_usuario;
                    $callback($_GET, $this->route[2], $this->db);
                }

            } 

        }     
        
    }