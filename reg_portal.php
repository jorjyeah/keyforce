<?php
    //receive data from apps
    $response=array();
    if($_SERVER['REQUEST_METHOD'] == 'POST' && 
    isset($_POST['keypub']) && 
    isset($_POST['username']) &&
    isset($_POST['signedencrypted'])
    ){
        
        $publickey=$_POST['keypub'];             //to be stored
        $username=$_POST['username'];               //to be stored
        $signedstuff=$_POST['signedencrypted'];

        set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
        include 'phpseclib/Crypt/RSA.php';
        include 'phpseclib/Crypt/Random.php';
        require_once('connections.php');

        $rsa = new Crypt_RSA();

        $rsa->loadKey($publickey);
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $ciphertext = base64_decode($signedstuff);
        $decrypted = $rsa->decrypt($ciphertext);

        $json = json_decode($decrypted);
        $keyhandle=$json->keyhandle;                //to be stored
        $challenge=$json->challenge;                //to be stored
        $counter = 0;
        
        //create salt
        $salt = bin2hex(crypt_random_string(16));   //to be stored

        //TODO: check challenge server and stored challenge

        //store everything (username, kpub, salt, key handle, counter, challenge)

        // prepare sql and insert to db
        $sql = "INSERT INTO credential (username, publickey, salt, keyhandle, counter, challenge)
        VALUES ('".$username."', '".$publickey."', '".$salt."', '".$keyhandle."', '".$counter."', '".$challenge."')";
        
        if (mysqli_query($conn, $sql)) {
            $status = 1;
            $message = "registered";
        } else {
            $status = 0;
            $message = "Something Went Wrong";
        }

        $response["status"] = $status;
        $response["message"] = $message;
        echo json_encode($response);
        mysqli_close($conn);
        exit;
    }
    else{
        $response["status"] = 0;
        $response["message"] = "Something Missing";
        echo json_encode($response);
        exit;
    }
?>
