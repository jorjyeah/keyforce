<?php
    function checkUsername(){
        $username = $_POST['username'];
        $status;
        include 'connections.php';

        $sql = 'SELECT * FROM credential WHERE username="'.$username.'"';
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $status = 1;
        } else {
            $status = 0;
        }
        mysqli_close($conn);
        echo json_encode($status);
        exit;
    }

    function getKey(){
        $key = array(
            'action' => "authentication",
            'username' => $_POST['username'],
            'keyhandle' => getSecret($_POST['username'],"keyhandle"),
            'challenge' => getSecret($_POST['username'],"challenge"),
            'appId' => "http://192.168.0.177/invicikey",
            'auth_portal' => "http://192.168.0.177/keyforce/",
            'encryptedsalt' => encryptSalt(getSecret($_POST['username'],"salt"), getSecret($_POST['username'],"publickey"))
        );        
        // encrypt salt ddengan kpub;
        echo json_encode($key);
        exit;
    }

    function encryptSalt($salt, $keypub){
        set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
        include 'phpseclib/Crypt/RSA.php';
        include 'phpseclib/Crypt/Random.php';
        $publickey = getSecret($_POST['username'],"publickey");
        $salt = getSecret($_POST['username'],"salt");

        $rsa = new Crypt_RSA();
        $rsa->loadKey($publickey);
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $encryptedsalt = $rsa->encrypt($salt);
        return base64_encode($encryptedsalt);
    }

    function getSecret($usname,$identifier){
        include 'connections.php';
        $sql = 'SELECT '.$identifier.' FROM credential WHERE username="'.$usname.'"';
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            while($row = $result->fetch_assoc()) {
                if($row[$identifier] != NULL){ 
                    $key = $row[$identifier];
                }else{
                    $key = NULL;
                }
            }
        } else {
            $key = NULL;
        }
        mysqli_close($conn);
        return $key;
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
    
    $func = $_POST['func'];
    switch ($func) {
        case 'checkUsername':
            checkUsername();
            break;
        case 'getKey':
            getKey();
            break;
        case 'inputUnique':
            inputUnique();
            break;
        default:
            echo "false routing";
            break;
    }
?>