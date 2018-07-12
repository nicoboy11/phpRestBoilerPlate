<?php
	$fileTipo = "";
	$response = array();

	if(ISSET($_FILES["up_file_even"])){
		
		$fileTipo = "up_file_even";
		$ruta = "archivos";
		
	}		
	elseif(ISSET($_FILES['fileupPerfil'])){
		$fileTipo = "fileupPerfil";
		$ruta = "usr";
	}
	else{
		return;
	}
		ob_start();
		// Set local PHP vars from the POST vars sent from our form using the array
		// of data that the $_FILES global variable contains for this uploaded file

		$fileName = $_FILES[$fileTipo]["name"]; // The file name
		$fileTmpLoc = $_FILES[$fileTipo]["tmp_name"]; // File in the PHP tmp folder
		$fileType = $_FILES[$fileTipo]["type"]; // The type of file it is
		$fileSize = $_FILES[$fileTipo]["size"]; // File size in bytes
		$fileErrorMsg = $_FILES[$fileTipo]["error"]; // 0 for false... and 1 for true

		$ext = findexts($fileName);

		$strdate = date('YmdHi');
		$fileArr = explode(".",$fileName);
					
		$archivo_subir = $fileArr[0].'-'.$strdate.'.'.$fileArr[1];
		$archivo_subir = $archivo_subir;
		
		if(ISSET($_FILES['fileupPerfil'])){
			$archivo_subir = $_POST['id_usuario'].'_pre.'.strtolower($fileArr[1]);
		}
		
		// Specific Error Handling if you need to run error checking
		if (!$fileTmpLoc) { // if file not chosen
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

			echo json_encode($response);
			exit();

		} else if (!preg_match("/.(gif|jpg|jpeg|png|pdf|xls|xlsx|doc|docx|zip|rar|dmg|mp3|zip|wav)$/i", $fileName) ) {
	
			 // This condition is only if you wish to allow uploading of specific file types    
			 $response["error"] = "ERROR: El archivo no es valido";
			 unlink($fileTmpLoc);
			 echo json_encode($response);
			 exit();
		}
//echo "4,";		
		// Place it into your "uploads" folder mow using the move_uploaded_file() function
		move_uploaded_file($fileTmpLoc, $ruta."/$archivo_subir");
				
		//echo $ruta."/$archivo_subir";
//echo "5,";		
		// Check to make sure the uploaded file is in place where you want it
		if (!file_exists($ruta."/$archivo_subir")) {
//echo "6,";		
			$response["error"] = 'noexiste';	
			echo json_encode($response);
			/*echo "ERROR: File not uploaded<br /><br />";
			echo "Check folder permissions on the target uploads folder is 0755 or looser.<br /><br />";
			echo "Check that your php.ini settings are set to allow over 2 MB files, they are 2MB by default.";*/
			exit();
		}
		else{
			
			if(strtolower($fileArr[1]) == "jpg" && ISSET($_FILES['fileupPerfil'])){
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
			echo json_encode($response);
		}
		
		unset($_FILES[$fileTipo]);
	
	


function findexts ($filename) { 
	$filename = strtolower($filename) ; 
	$exts = preg_split("[/\\.]", $filename) ; 
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
	
	echo '1';
    // Obtain image from given source file.
    if (!$image = @imagecreatefromjpeg($sourceImage))
    {
		echo $sourceImage;
        return false;
    }
	echo '2';
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
	echo '3';
    // Calculate ratio of desired maximum sizes and original sizes.
    $widthRatio = $maxWidth / $origWidth;
    $heightRatio = $maxHeight / $origHeight;

    // Ratio used for calculating new image dimensions.
    $ratio = min($widthRatio, $heightRatio);

    // Calculate new image dimensions.
    $newWidth  = (int)$origWidth  * $ratio;
    $newHeight = (int)$origHeight * $ratio;
	echo '4';
    // Create final image with new dimensions.
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
    imagejpeg($newImage, $targetImage, $quality);

    // Free up the memory.
    imagedestroy($image);
    imagedestroy($newImage);
	echo '5';
    return true;
}


	 //This applies the function to our file $ext = findexts ($_FILES['uploaded']['name']) ;
?>