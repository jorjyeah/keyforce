<?php
    //receive data from apps
    $response=array();
    if($_SERVER['REQUEST_METHOD'] == 'POST' && 
    isset($_POST['username']) &&
    isset($_POST['signedencrypted'])
    ){          
        $username=$_POST['username'];               
        $signedstuff=$_POST['signedencrypted'];

        set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
        include 'phpseclib/Crypt/RSA.php';
        include 'phpseclib/Crypt/Random.php';
        require_once('connections.php');

        //sql get challenge_salt & public key
        $publickey = NULL;
        $challenge_salt_generated = NULL;
        $sql = 'SELECT CONCAT(challenge,salt)  as challenge_salt, publickey, counter FROM credential WHERE username="'.$username.'"';
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            while($row = $result->fetch_assoc()) {
                if($row != NULL){ 
                    $publickey = $row['publickey'];
                    $counter_generated = $row['counter'];
                    $challenge_salt_generated = $row['challenge_salt'];
                }
            }
        }
        mysqli_close($conn);

        //decrypt from apps
        $rsa = new Crypt_RSA();

        $rsa->loadKey($publickey);
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $ciphertext = base64_decode($signedstuff);
        $decrypted = $rsa->decrypt($ciphertext);
        
        //get decrypted data
        $json = json_decode($decrypted);
        $salt = $json->salt;
        $challenge = $json->challenge;
        $counter_received = $json->counter;

        //check generated challenge + salt vs decrypted challenge + salt
        $challenge_salt_decrypted = $challenge.$salt;
        $challenge_salt_generated == $challenge_salt_decrypted ? $auth1 = true : $auth1 = false;
        $counter_received >= $counter_generated ? $auth2 = true : $auth2 = false;

        //if success, update counter, authenticate true
        if ($auth1 && $auth2) {
            //update database (counter, session) and authenticate (create session)
            //$sql = "UPDATE credential SET counter='".$counter."' WHERE username = '".$username."'";
            $status = 1;
            $message = "authentication success";
        } else {
            $status = 0;
            $message = "Something Went Wrong";
        }
        $response["status"] = $status;
        $response["message"] = $message;
        echo json_encode($response);
        exit;
    }
    else{
        $response["status"] = 0;
        $response["message"] = "Something Missing";
        echo json_encode($response);
        exit;
    }
?>
