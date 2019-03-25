<?php
    function checkUsername(){
        $username = $_POST['username'];
        $status;
        include 'connections.php';

        $sql = 'SELECT * FROM credential WHERE username="'.$username.'"';
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $status = 0;
            
        } else {
            $status = 1;
        }
        mysqli_close($conn);

        echo json_encode($status);
        exit;
    }

    function checkKey(){
        $username = $_POST['username'];
        $status;
        include 'connections.php';

        $sql = 'SELECT publickey FROM credential WHERE username="'.$username.'"';
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            while($row = $result->fetch_assoc()) {
                if($row["publickey"] != NULL){
                    $status = 0;
                }else{
                    $status = 1;
                }
            }
        } else {
            $status = 1;
        }
        mysqli_close($conn);

        echo json_encode($status);
        exit;
    }

    function generateNewChallenge(){
        $challenge = bin2hex(random_bytes(8));

        // $username = $_POST['username'];
        // $status;
        // include 'connections.php';

        // $sql = 'SELECT * FROM credential WHERE username="'.$username.'"';
        // $result = mysqli_query($conn, $sql);
        
        // if (mysqli_num_rows($result) > 0) {
        //     $sql = "UPDATE credential SET challenge='".$challenge."' WHERE username='".$username."'";
        // } else {
        //     $sql = "INSERT INTO credential(username, challenge)VALUES ('".$username."','".$challenge."')";
        // }
        
        // if (mysqli_query($conn, $sql)) {
        //     $status = 1;
        // } else {
        //     $status = 0;
        // }

        // mysqli_close($conn);
        echo json_encode($challenge);

        exit;
    }

    function inputUnique(){
        $username = $_POST['username'];
        $unique = $_POST['unique'];
        $status;
        include 'connections.php';

        $sql = "UPDATE credential SET macble='".$unique."' WHERE username = '".$username."'";
        
        if (mysqli_query($conn, $sql)) {
            $status = 1;
        } else {
            $status = 0;
        }

        mysqli_close($conn);

        echo json_encode($status);
        exit;
    }

    function registrationFromApps(){
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
            $keyHandle=$json->keyHandle;                //to be stored
            $challenge=$json->challenge;                //to be stored
            $counter = 0;
            
            //create salt
            $salt = bin2hex(crypt_random_string(16));   //to be stored

            //TODO: check challenge server and stored challenge

            //store everything (username, kpub, salt, key handle, counter, challenge)

            // prepare sql and insert to db
            $sql = "INSERT INTO credential (usernmae, publickey, salt, keyhandle, counter, challenge, validuntil)
            VALUES ('".$username."', '".$publickey."', '".$salt."', '".$keyhandle."', '".$counter."', '".$challenge."', '".$NOW() + INTERVAL 30 SECOND)."'";
            
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
    }

    $func = $_POST['func']; 

    switch ($func) {
        case 'checkUsername':
            checkUsername();
            break;
        case 'generateNewChallenge':
            generateNewChallenge();
            break;
        case 'inputUnique':
            inputUnique();
            break;
        case 'checkKey':
            checkKey();
            break;
        case 'registrationFromApps':
            registrationFromApps();
            break;
        default:
            echo "false routing";
            break;
    }
?>