<?php

$servername = "vnnovate.com";
$username = "vnnovate_poll";
$password = "poll@123";
$dbname   = "vnnovate_custom_poll";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Connected successfully"; 
}catch(PDOException $e){
    //echo "Connection failed: " . $e->getMessage();
}

			

// $newUserName = "gettest";
// $sql = "SELECT * from users WHERE username='$newUserName'";
	
// $stmt = $conn->prepare($sql); 
// $stmt->execute(); 
// $row = $stmt->fetch();

// if(!$row){

// 			$newVote 	 = 1;
// 		    $newPoints = (10000-5); 
// 		    $sql = "INSERT into users (username,polling_point,vote) VALUES ('$newUserName','$newPoints','$newVote')";
// 			$result = $conn->query($sql);


// }else{
// 	echo "found";
// }
// echo "<pre/>";
// print_r($row);
// die;

$text  = "Hello, World!";
if(isset($_POST['text']) && !empty($_POST['text'])){
	$text = $_POST['text'];
}
$url = "https://hooks.slack.com/services/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";
$ch = curl_init();

//set options 
//$data = array("text"=>$text);
//$payload = json_encode($data);

//$text = 'cast 1';
$request_params = explode('"',$text);

if(trim($request_params[0])=="create"){
	$request_params[2] = trim($request_params[2]);
	$optionsArry = explode("--",$request_params[2]);
	array_shift($optionsArry);

	//create poll functionality will be started here...
	$payload = [
 	"channel"=>$_POST['channel_name'],
 	"text"=>"@".$_POST['user_name']." created a new poll! Vote in it!",
    "link_names"=> 1,
    "attachments"=> [
            [
                "fallback"=> "@".$_POST['user_name']." created a new poll! Vote in it!",

                "color"=> "good",
                "mrkdwn_in"=> ["fields", "text"],
                "fields"=> [
                    [
                        "title"=> $request_params[1],
                        "value"=> ""
                    ]
                ]
            ]
        ]
    ];


	foreach ($optionsArry as $key => $value) {
		$payload["attachments"][0]["fields"][0]["value"] .= "(".($key + 1).")".$value."\n";
	}

	$payload["attachments"][0]["fields"][0]["value"] .= "\n\nHow do I vote? `/setpoll cast [option number]`";	
}elseif(trim($request_params[0])=="cast"){
	
	//cast functionality will be started here..

	//here first check user is exist or not if not then create and assign some default point 10000
	$newUserName = $_POST['user_name'];
	
	$sql = "SELECT * from users WHERE username='$newUserName'";
	$stmt = $conn->prepare($sql); 
	$stmt->execute(); 
	$row = $stmt->fetch();

	if($row){
    	// output data of each row
	    $userid = $row["id"];
        $newVote = $row["vote"]+1;
        $newPoints = ($row["polling_point"]-($newVote*$newVote)); 
        $updateSql = "UPDATE users SET vote=$newVote,polling_point=$newPoints WHERE id=$userid";
        $result = $conn->query($updateSql);
	} else {
		//insert new users here
	    $newVote 	 = 1;
	    $newPoints = (10000-($newVote)); 
	    $sqlIns = "INSERT into users (username,polling_point,vote) VALUES ('$newUserName','$newPoints','$newVote')";
		$result = $conn->query($sqlIns);
	}
    
	
	//$conn->close();
	$payload = array("text"=>"Thanks for voting @ ".$_POST['user_name']." your remaining points are $newPoints.");
	//cast functionality will be end here...

}



$resPayload = json_encode($payload);

curl_setopt( $ch, CURLOPT_POSTFIELDS, $resPayload );
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
# Send request.
$result = curl_exec($ch);
curl_close($ch);
# Print response.

?>