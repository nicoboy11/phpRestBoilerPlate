<?php

    class Database extends PDO {
        function __construct() {
            try {
                parent::__construct('mysql:host='.DB_HOST.';dbname='.DB_NAME,DB_USERNAME,DB_PASSWORD);
                $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                $this->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");
                $this->query("SET character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
            } catch(PDOException $e) {
                header("HTTP/1.1 401 Unauthorized ");
                $errArray = Array();
                $message = $e->getMessage();
                $errArray["message"] = str_replace("SQLSTATE[ERROR]: ", "", $message);
                echo json_encode($errArray);                
            }
        }

        function execute($sql, $arr = array()) {
            try {
                $stmt = $this->prepare($sql);

                foreach($arr as $key => $value){
                    $stmt->bindValue($key, $value);
                }

                $stmt->execute();        

                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);   
                $stmt->closeCursor();
                //return array
                return $result;
            }
            catch(PDOException $e){
                //return error
                //return error
                header("HTTP/1.1 401 Unauthorized ");
                $errArray = Array();
                $message = $e->getMessage();
                $errArray["message"] = str_replace("SQLSTATE[ERROR]: <<Unknown error>>: 1644 ", "", $message);
                echo json_encode($errArray);
                
            }         
        }
    }

    set_exception_handler('exceptionHandler');

    function exceptionHandler($exception) {
        echo $exception->getMessage();
    }