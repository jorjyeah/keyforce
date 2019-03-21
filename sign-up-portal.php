<?php
$response=array();
if($_SERVER['REQUEST_METHOD'] == 'POST' && 
   isset($_POST['publicKey']) && 
   isset($_POST['username']) &&
   isset($_POST['signedStuff'])
  ){
    
    $publickey=$_POST['publicKey'];             //to be stored
    $username=$_POST['username'];               //to be stored
    $signedstuff=$_POST['signedStuff'];

    set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
    include 'phpseclib/Crypt/RSA.php';
    include 'phpseclib/Crypt/Random.php';
    require_once('dbinc.php');

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
    //check the db first, username etc.
    $stmt = $conn->prepare("SELECT * FROM users WHERE username= :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $row=$stmt->fetch(PDO::FETCH_ASSOC);
    if($row){
        $success = 0;
        $message="USERNAME taken!";
    }
    else{
        // prepare sql and bind parameters
        $stmt = $conn->prepare("INSERT INTO users (userName, publicKey, salt, keyHandle, counter, challenge, validUntil)
        VALUES (:username, :publickey, :salt, :keyhandle, :counter, :challenge, NOW() + INTERVAL 30 SECOND)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':publickey', $publickey);
        $stmt->bindParam(':salt', $salt);
        $stmt->bindParam(':keyhandle', $keyHandle);
        $stmt->bindParam(':counter', $counter);
        $stmt->bindParam(':challenge', $challenge);
        
        try{
            $success = $stmt->execute();
            $message = "Registration success";
        }
        catch(PDOException $e){
            error_log("Insert DB failed: ".$e->getMessage());
            $success = 0;
            $message = "Something went wrong.";
        }

    }

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