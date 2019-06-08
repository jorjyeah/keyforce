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

            include 'constants.php';
            require_once('connections.php');

            //sql get challenge_salt & public key
            $publickey_generated = NULL;
            $challenge_salt_generated = NULL;
            $sql = 'SELECT CONCAT(challenge,salt)  as challenge_salt, publickey, counter, sessionid, salt FROM credential WHERE username="'.$username.'"';
            $result = mysqli_query($conn, $sql);
            if (mysqli_num_rows($result) > 0) {
                while($row = $result->fetch_assoc()) {
                    if($row != NULL){ 
                        $publickey_generated= $row['publickey'];
                        $sessionid_generated = $row['sessionid'];
                        $salt_generated = $row['salt'];
                        $counter_generated = $row['counter'];
                        $challenge_salt_generated = $row['challenge_salt'];
                    }
                }
            }
            mysqli_close($conn);

            //decrypt from received apps data
            $rsa = new Crypt_RSA();
            $rsa->loadKey($publickey_generated);
            $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
            $ciphertext = base64_decode($signedstuff);
            $decrypted = $rsa->decrypt($ciphertext);
            
            //get decrypted data
            $json = json_decode($decrypted);
            $salt_received= $json->salt;
            $challenge_received = $json->challenge;
            $counter_received = $json->counter;

            //check generated challenge + salt vs decrypted challenge + salt
            $challenge_salt_decrypted = $challenge_received.$salt_received;
            $challenge_salt_generated == $challenge_salt_decrypted ? $auth1 = true : $auth1 = false;
            $counter_received >= $counter_generated ? $auth2 = true : $auth2 = false;

            //if success, update counter, authenticate true
            if ($auth1 && $auth2) {
                //update counter
                $biggercounter = $counter_received + 1;
                $username = $_POST['username'];
                include 'connections.php';
                $sql = "UPDATE credential SET counter='".$biggercounter."' WHERE username='".$username."'";
                if (mysqli_query($conn, $sql)) {
                    $status = 1;
                    $message = "counter updated";
                } else {
                    $status = 0;
                    $message = "counter can't be updated";
                }

                //update authenticated
                $hash = new Crypt_Hash('sha1');
                $authenticated = bin2hex($hash->hash($STRINGTRUE.$salt_generated.$sessionid_generated.$PEPPER));
                $sql = "UPDATE credential SET authenticated='".$authenticated."' WHERE username='".$username."'";
                if (mysqli_query($conn, $sql)) {
                    $status = 1;
                    $message = "authentication success";
                } else {
                    $status = 0;
                    $message = "counter can't be updated";
                }
                mysqli_close($conn);
            } else {
                $status = 0;
                $message = "authentication failed";
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
