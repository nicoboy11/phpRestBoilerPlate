<?php
    error_reporting( E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED) );
    header('Access-Control-Allow-Origin: *');  
    header('Access-Control-Allow-Headers: *');

    require_once 'route.class.php';
    require_once 'vendor/firebase/php-jwt/src/JWT.php';
    require_once 'config.php';
    require_once 'helper.php';

    $GLOBALS['db'] = new Database( DB_HOST,DB_NAME,DB_USERNAME,DB_PASSWORD );

    /** VERIFY TOKEN
     *   
     */
        $header = apache_request_headers();
        $token = null;
        
        $authorization = "";

        if(isset($header['Authorization'])){
            $authorization = $header['Authorization'];
        } else {
            $authorization = $header['authorization'];
        }

        if(isset($authorization) && $authorization !== "") {
            $bearer = explode(' ', $authorization);
            $decoded = JWT::decode($bearer[1], SECRET);
            if(!isset($decoded)) {
                http_response_code(422);
                exit('{"error":"Invalid token"}');
            }

            $token = $bearer[1];
        }


    /** DECLARE ROUTING
     *
     */
        // $route is for routes which need authentication
        $route = new Route(getRoute(), $db, true, $token, $decoded);

        // $freeRoute is for routes that do not need authentication (e.g. Login)
        $freeRoute = new Route(getRoute(), $db);


    /** Create User
     * 
     */    
        $freeRoute->post('createUser', function($req, $param, $db){

            $hash = password_hash($req['password'], PASSWORD_DEFAULT);
            $sqlParams = Array(
                ":username" => $req["username"],             ":telefono" => $req["telefono"],                        ":celular" => $req["celular"],          ":whatsapp" => $req["whatsapp"],        ":tipoUsuario" => $req["tipoUsuario"],  ":calle" => $req["calle"],
                ":entreCalles" => $req["entreCalles"],       ":numero" => $req["numero"],                            ":codigoPostal" => $req["codigoPostal"],":colonia" => $req["colonia"],          ":nivel" => $req["nivel"],              ":casasVendidas" => $req["casasVendidas"],
                ":casasCompradas" => $req["casasCompradas"], ":invitacionesEnviadas" => $req["invitacionesEnviadas"],":email" => $req["email"],              ":passwordHash" => $req["passwordHash"],":externalAuth" => $req["externalAuth"],":externalId"  => $req["externalId"] 
            );

            $result = $db->execute("CALL createUser(:username,      :telefono,              :celular,       :whatsapp,      :tipoUsuario,   :calle,
                                                    :entreCalles,   :numero,                :codigoPostal,  :colonia,       :nivel,         :casasVendidas,
                                                    :casasCompradas,:invitacionesEnviadas,  :email,         :passwordHash,  :externalAuth,  :externalId)",$sqlParams);

            echo json_encode(utf8ize($result));
        });

    /** Log In
     *
     */
        $freeRoute->post('loginUser', function($req, $param, $db) {
            if(!isset($req['email']) || !isset($req['password'])) {
                header("HTTP/1.1 401 Unauthorized ");
                $errArray = Array();
                $errArray["message"] = "Proporcione un email ó password";
                echo json_encode($errArray);
                return;
            }

            $result = $db->execute("CALL GetLogin(:EMAIL)",array( ":EMAIL" => fpVarchar($req['email']) ));

            //Compare the provided password with the hashed database password
            if (password_verify($req['password'], $result[0]["password"])) {
                //Add all data I may use from the token
                $payload = array(   
                    "email" => $req['email'],
                    "userName" => $result[0]['userName']
                );

                //Generate token
                $jwt = JWT::encode($payload,SECRET);

                //Return query results (userData) with token
                if($result <> null){
                    $result[0]['token'] = $jwt;
                    //Remove password from array
                    unset($result[0]["password"]);
                    echo json_encode($result);
                    return;
                } else {
                    $errArray = Array();
                    $errArray["message"] = "Login validation failed.";
                    echo json_encode($errArray);                
                }                
            }
            else {
                $errArray = Array();
                $errArray["message"] = "Login failed.";
                echo json_encode($errArray);   
            }             

        });

    /** Get endpoint example [domainName]/api/v1/getData/[params]
     * 
     */
        $route->get('getData', function($req, $param, $db) {
           /* $result = $db->execute("CALL SP_USERS(" . fpVarchar($param) . ",'')");
            echo json_encode( utf8ize($result));*/
        });


    /** Post Endpoint Example  [domainName]/api/v1/postData/[params]
     * 
     */
        $route->post('postData', function($req, $param, $db){
            /*
            if(isset($req['txt_proyecto'])) {
                $result = $db->execute("CALL EditProyecto(" . fpInt($param) . "," . fpVarchar($req['txt_proyecto']) . "," . 
                fpInt($req["id_usuario"]) . "," . fpInt($req["id_status"]) . "," . fpDate($req['fec_inicio']) ."," . 
                fpDate($req['fec_limite']) . ");");

            if($result <> null){
                echo json_encode( utf8ize($result));
            }
            }*/
        });


    /** Log Errores
     * 
     */
        $freeRoute->post('ErrorLog',function($req, $param, $db){
           /* $result = $db->execute("INSERT INTO esnLog(errorDescription,dateOfError,id_usuario) VALUE(:error,NOW(),:id_usuario);",
                                        array(":error"=>$req["error"], ":id_usuario"=>$req["id_usuario"]));

            echo json_encode(utf8ize($result));*/
        });

    function getCurrentUri() {
        $basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
        $uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));
        if (strstr($uri, '?')) $uri = substr($uri, 0, strpos($uri, '?'));
        $uri = '/' . trim($uri, '/');
        return $uri;
    }

    function getRoute() {
        $base_url = getCurrentUri();
        $routes = array();
        $routes = explode('/', $base_url);

        foreach($routes as $route)
        {
            if(trim($route) != '')
                array_push($routes, $route);
        }

        return $routes;
    }

    function upload($id_usuario){

        ob_start();

        $fileTipo = "imagen";
        if($_POST["ruta"] == "archivos"){
            $ruta = "archivos";
        }

        if($_POST["ruta"] == "usuarios"){
            $ruta = "usr/thumbs/small";
        }
        
        $fileName = $_FILES[$fileTipo]["name"]; // Nombre del archivo
        $fileTmpLoc = $_FILES[$fileTipo]["tmp_name"]; // Nombre temporal del archivo 
        $fileErrorMsg = $_FILES[$fileTipo]["error"]; // numero de error si esque hay
        $fileType = $_FILES[$fileTipo]["type"]; // numero de error si esque hay

        //Obtengo la extensión del archivo (incluyendo casos con punto en el nombre)
        $ext = findexts($fileName, $fileType);

        //Si es blob le agrego la extension al nombre
        if($fileName == 'blob'){
            $fileName = $fileName.'.'.$ext;
        }

        //Obtengo la fecha actual
        $strdate = date('YmdHi');

        //Nombre del archivo y su extensión
        $fileArr = explode(".",$fileName);
                    
        //Nombre final del archivo
        if($_POST["ruta"] == "archivos"){
            $archivo_subir = $fileArr[0].'-'.$strdate.'.'.$ext;
        }

        if($_POST["ruta"] == "usuarios"){
            $archivo_subir = $id_usuario . '.' . $ext;
        }

        // Checar si hubo algún error
        if (!$fileTmpLoc) { 
            switch( $fileErrorMsg ) {
                case UPLOAD_ERR_OK:
                    $response["error"] = false;;
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    $response["error"] .= ' - Archivo demasiado grande (limite de 100Mb).';
                    break;				
                case UPLOAD_ERR_FORM_SIZE:
                    $response["error"] .= ' - Archivo demasiado grande (limite de 100Mb).';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $response["error"] .= ' - El archivo no se subió completo.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $response["error"] .= ' - No se seleccionó ningún archivo.';
                    break;
                default:
                    $response["error"] .= ' - Error interno #'.$fileErrorMsg;
                    break;
            }		
            return $response;
            exit();

        } else if (!preg_match("/.(gif|jpg|jpeg|png|pdf|xls|xlsx|doc|docx|zip|rar|dmg|mp3|zip|wav)$/i", $fileName) ) {  
            // This condition is only if you wish to allow uploading of specific file types    
            $response["error"] = "ERROR: El archivo no es valido";
            unlink($fileTmpLoc);
            return $response;
            exit();
        }            

        //Si todo salió bien muevo el archivo temporal a la nueva ruta
        move_uploaded_file($fileTmpLoc, $ruta."/$archivo_subir");

        //Si no se subió un archivo esque algo salió mal
        if (!file_exists($ruta."/$archivo_subir")) {
            $response["error"] = 'No se subió el archivo';	
            return $response;
            exit();
        } else {


			if(strtolower( ($fileArr[1]) == "jpg" || $fileArr[1] == "jpeg")  && ISSET($_FILES['fileupPerfil'])){
                $reducedfile = str_replace("_pre","",$archivo_subir);
                
                resizeImage($ruta."/".$archivo_subir, $ruta."/".$reducedfile, 120, 200, $quality = 60);
                unlink($ruta."/$archivo_subir");			
            }			
            
            if(strtolower($fileArr[1]) == "png" && ISSET($_FILES['fileupPerfil'])){
                //echo 'entró';
                $newfile = str_replace("png","jpg",$archivo_subir);
                
                png2jpg(file_get_contents($ruta."/".$archivo_subir),$ruta."/".$newfile,80);
                unlink($ruta."/".$archivo_subir);
                
                $reducedfile = str_replace("_pre","",$newfile);

                resizeImage($ruta."/".$newfile, $ruta."/".$reducedfile, 120, 200, $quality = 60);
                unlink($ruta."/".$newfile);
            }            

            //Regresar archivo subido
            $response["archivo"] = $archivo_subir;
            return $response;
        }

    }

    function findexts ($fileName, $fileType) { 
        if($fileName == "blob"){
            $fileArr = explode("/",$fileType);
            if($fileArr[1] == "jpeg"){
                return 'jpg';
            }
            return $fileArr[1];
        }

        $fileName = strtolower($fileName) ; 
        $exts = preg_split("[/\\.]", $fileName) ; 
        $n = count($exts)-1; 
        $exts = $exts[$n]; 
        return $exts; 
    }    

    function png2jpg($originalFile, $outputFile, $quality) {
        $image = imagecreatefromstring($originalFile);
        imagejpeg($image, $outputFile, $quality);
        imagedestroy($image);
        
    }	    

    function resizeImage($sourceImage, $targetImage, $maxWidth, $maxHeight, $quality = 80){
        
    
        // Obtain image from given source file.
        if (!$image = @imagecreatefromjpeg($sourceImage))
        {
            echo $sourceImage;
            return false;
        }
    
        // Get dimensions of source image.
        list($origWidth, $origHeight) = getimagesize($sourceImage);
    
        if ($maxWidth == 0)
        {
            $maxWidth  = $origWidth;
        }
    
        if ($maxHeight == 0)
        {
            $maxHeight = $origHeight;
        }
    
        // Calculate ratio of desired maximum sizes and original sizes.
        $widthRatio = $maxWidth / $origWidth;
        $heightRatio = $maxHeight / $origHeight;
    
        // Ratio used for calculating new image dimensions.
        $ratio = min($widthRatio, $heightRatio);
    
        // Calculate new image dimensions.
        $newWidth  = (int)$origWidth  * $ratio;
        $newHeight = (int)$origHeight * $ratio;
    
        // Create final image with new dimensions.
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        imagejpeg($newImage, $targetImage, $quality);
    
        // Free up the memory.
        imagedestroy($image);
        imagedestroy($newImage);
    
        return true;
    }    
        
    function jsonEscape($str)  {
        return str_replace("\t","\\t",str_replace("\r","\\r",str_replace("\n", "\\n", $str)));
    }

?>