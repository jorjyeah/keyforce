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
        // $username = $_POST['username'];
        $username = "pak";
        $keyhandle = getSecret($username,"keyhandle");
        $publickey = getSecret($username,"publickey");
        $salt = getSecret($username,"salt");
        echo "<br>";
        echo " | keyhandle = ";
        var_dump($keyhandle);
        echo "<br>";
        echo " | publickey = ";
        var_dump($publickey);
        echo "<br>";
        echo " | salt = ";
        var_dump($salt);
        echo "<br>";
        echo crypt($salt,$publickey);
        // encrypt salt ddengan kpub;

        // echo json_encode($key);
        // exit;
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

    //$func = $_POST['func'];
    $func = 'getKey';
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