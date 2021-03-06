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
        $username = $_POST['username'];
        include 'connections.php';
        $sql = "INSERT INTO credential (regstart,  username) VALUES (NOW(),'".$username."')";
        if (mysqli_query($conn, $sql)) {
            $challenge = bin2hex(random_bytes(8));
        } else {
            $challenge = 0;
        }
        mysqli_close($conn);

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

    function regpairstart(){
        $username = $_POST['username'];
        include 'connections.php';
        $sql = "UPDATE credential SET regpairstart = NOW() WHERE username = '".$username."'";
        if (mysqli_query($conn, $sql)) {
            $status = true;
        } else {
            $status = false;
        }
        mysqli_close($conn);

        echo json_encode($status);
        exit;
    }

    function inputUnique(){
        $username = $_POST['username'];
        $unique = $_POST['unique'];
        $status;
        include 'connections.php';

        $sql = "UPDATE credential SET macble='".$unique."', regend= NOW() WHERE username = '".$username."'";
        
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
        case 'generateNewChallenge':
            generateNewChallenge();
            break;
        case 'regpairstart':
            regpairstart();
            break;
        case 'inputUnique':
            inputUnique();
            break;
        case 'checkKey':
            checkKey();
            break;
        default:
            echo "false routing";
            break;
    }
?>