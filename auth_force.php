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
        session_start();
        session_regenerate_id();
        session_unset();
        $challenge = bin2hex(random_bytes(8));
        $sessionid=session_id();

        // if updateChallenge + updateSession true then do reply data
        updateChallenge($challenge);
        updateSession($sessionid);
        
        $_SESSION['username']=$_POST['username'];

        $key = array(
            'action' => "authentication",
            'username' => $_POST['username'],
            'keyhandle' => getSecret($_POST['username'],"keyhandle"),
            'challenge' => getSecret($_POST['username'],"challenge"),
            'sessionid' => getSecret($_POST['username'],"sessionid"),
            'appId' => "http://192.168.0.177/invicikey",
            'auth_portal' => "http://192.168.0.177/keyforce/",
            'encryptedsalt' => encryptSalt(getSecret($_POST['username'],"salt"), getSecret($_POST['username'],"publickey"))
        );        
        // encrypt salt ddengan kpub;
        echo json_encode($key);
        exit;
    }

    function updateChallenge($challenge){
        $username = $_POST['username'];
        include 'connections.php';
        $sql = "UPDATE credential SET challenge='".$challenge."' WHERE username='".$username."'";
        
        if (mysqli_query($conn, $sql)) {
            mysqli_close($conn);
            return true;
        } else {
            updateChallenge($challenge);
        }
    }

    function updateSession($sessionid){
        $username = $_POST['username'];
        include 'connections.php';
        $sql = "UPDATE credential SET sessionid='".$sessionid."', validUntil= NOW() + INTERVAL 30 SECOND WHERE username='".$username."'";
        
        if (mysqli_query($conn, $sql)) {
            mysqli_close($conn);
            return true;
        } else {
            updateSession($sessionid);
        }
    }

    function checkAuthenticated(){
        session_start();
        $_SESSION['authenticated']=false;
        if(isset($_SESSION['username']))
        {
            $username=$_SESSION['username'];
            include 'connections.php';
            include 'constants.php';
            set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
            include 'phpseclib/Crypt/Hash.php';
            
            $sql = 'SELECT authenticated, sessionid, salt FROM credential WHERE username="'.$username.'"';
            $result = mysqli_query($conn, $sql);
            
            if (mysqli_num_rows($result) > 0) {
                while($row = $result->fetch_assoc()) {
                    $saltDatabase = $row['salt'];
                    $authenticatedDatabase = $row['authenticated'];
                    $sessionidDatabase = $row['sessionid'];
                }
            } else {
                $saltDatabase = NULL;
                $authenticatedDatabase = NULL;
                $sessionidDatabase = NULL;
            }

            $hash = new Crypt_Hash('sha1');
            $authenticated = bin2hex($hash->hash($STRINGTRUE.$saltDatabase.$sessionidDatabase.$PEPPER));

            $sessionidDatabase    == session_id()          ? $auth1=true : $auth1=false;
            $authenticated        == $authenticatedDatabase? $auth2=true : $auth2=false;
            
            mysqli_close($conn);

            $_SESSION['authenticated_id_system']=$authenticated;
            $_SESSION['authenticated_id_database']=$authenticatedDatabase;

            if($auth1 && $auth2){
                //TODO: authenticated set as hash value
                $_SESSION['authenticated']=true;
                //header('Location:/2fysh/hello.php');
            }else{
                $_SESSION['authenticated']=false;
            }
            // $auth_array = array($_SESSION['authenticated'], $_SESSION['authenticated_id_system'], $_SESSION['authenticated_id_database']);
            echo json_encode($_SESSION['authenticated']);
            // echo json_encode($auth_array);
            exit;
        }
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

    function checkIdBle(){
        $username = $_POST['username'];
        $idBleReceived = $_POST['idBle'];
        include 'connections.php';

        $sql = 'SELECT macble FROM credential WHERE username="'.$username.'"';
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            while($row = $result->fetch_assoc()) {
                if($row['macble'] != NULL){ 
                    $idBleGenerated = $row['macble'];
                }else{
                    $idBleGenerated = NULL;
                }
            }
        } else {
            $idBleGenerated = 0;
        }
        mysqli_close($conn);

        if ($idBleGenerated == $idBleReceived){
            $status = true;
        }else{
            $status = false;
        }
        
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
        case 'checkAuthenticated':
            checkAuthenticated();
            break;
        case 'checkIdBle':
            checkIdBle();
            break;
        default:
            echo "false routing";
            break;
    }
?>