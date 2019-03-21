<?php
$response=array();
if($_SERVER['REQUEST_METHOD'] == 'POST' && 
   isset($_POST['username']) &&
   isset($_POST['signedStuff'])
  ){
    $message="";
    $username=$_POST['username'];
    $signedstuff=$_POST['signedStuff'];

    set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
    include 'phpseclib/Crypt/RSA.php';
    include 'phpseclib/Crypt/Random.php';
    //include 'phpseclib/Crypt/Hash.php'; //declared in RSA
    include ('constants.php');
    require_once('dbinc.php');

    //get CONCAT(challenge,salt) as 'challenge_salt', counter... 
    $stmt = $conn->prepare("SELECT CONCAT(challenge,salt)  as 'challenge_salt',counter,publicKey,salt,sessionid FROM users WHERE username= :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $row=$stmt->fetch(PDO::FETCH_ASSOC);

    $publickey=$row['publicKey'];
    $rsa = new Crypt_RSA();
    $rsa->loadKey($publickey);
    $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
    $ciphertext = base64_decode($signedstuff);
    $decrypted = $rsa->decrypt($ciphertext);

    $json = json_decode($decrypted);
    $received_challenge_salt=$json->received_challenge_salt;
    $received_counter = $json->received_counter;
    
    $row['challenge_salt'] == $received_challenge_salt ? $auth1 = true : $auth1 = false;
    //$row['counter']        == $received_counter        ? $auth2 = true : $auth2 = false;
    $received_counter        >=  $row['counter']       ? $auth2 = true : $auth2 = false;



    /*    
    echo "received_challenge_salt: ".$received_challenge_salt;
    echo "received_counter: ".$received_counter;
    echo "username: ".$username."<br/>";
    echo "challenge_salt: ".$row['challenge_salt']."<br/>";
    echo "counter: ".$row['counter']."<br/>";
    echo "publicKey: ".$row['publicKey']."<br/>";
    echo "auth1: ".$auth1."<br/>";
    echo "auth2: ".$auth2."<br/>";  
    */
    
    
    //if yes -> update counter
    //if yes -> authenticated set true
    if($auth1 && $auth2){
        //update counter
        $x=$received_counter+1;
        $stmt = $conn->prepare("UPDATE users SET counter=:counter WHERE username=:username;");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':counter', $x , PDO::PARAM_INT);
        try{
            $success = $stmt->execute();
            $message = "Counter Added";
        }
        catch(PDOException $e){
            error_log("Update failed: ".$e->getMessage());
            $success = 0;
            $message = "Something went wrong.".$e->getMessage();
            die($message);
        }

        //update authenticated
        $hash = new Crypt_Hash('sha1');
        $authenticated = bin2hex($hash->hash($STRINGTRUE.$row['salt'].$row['sessionid'].$PEPPER));
        $stmt = $conn->prepare("UPDATE users SET authenticated=:authenticated WHERE username=:username;");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':authenticated', $authenticated);


        try{
            $success = $stmt->execute();
            $message = "Authenticate success!";
        }
        catch(PDOException $e){
            error_log("Update failed: ".$e->getMessage());
            $success = 0;
            $message = "Something went wrong.".$e->getMessage();
            die($message);
        }
    }
    else{
        $success = 0;
        $message = "Authentication failed";
    }
    if ($success) $success=1;
    $response["success"] = $success;
    $response["message"] = $message;

	header('Content-Type: application/json');
	echo json_encode($response);    //remember the backslash of the response because of json_encode!

}
else{
    // required field is missing
    $response["success"] = 0;
    $response["message"] = "Required field(s) is missing";

    // echoing JSON response
	//echo json_encode($response);
	header('Content-Type: application/json');
	echo json_encode($response, JSON_UNESCAPED_SLASHES);
}
    
?>