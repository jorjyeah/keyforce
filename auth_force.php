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
        $username = $_POST['username'];
        include 'connections.php';

        $sql = 'SELECT keyhandle FROM credential WHERE username="'.$username.'"';
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            while($row = $result->fetch_assoc()) {
                if($row["keyhandle"] != NULL){ 
                    $key = $row["keyhandle"];
                }else{
                    $key = NULL;
                }
            }
        } else {
            $key = NULL;
        }
        mysqli_close($conn);

        echo json_encode($key);
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

    $func = $_POST['func']; //remember to escape it
    // $func = 'inputUnique';
    switch ($func) {
        case 'checkUsername':
            checkUsername();
            break;
        case 'getKey':
            getKey();
            break;
        // case 'getChallengeKey':
        //     getChallengeKey();
        //     break;
        case 'inputUnique':
            inputUnique();
            break;
        default:
            echo "false routing";
            break;
    }
?>