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

	//check here there are poll open or not if opened then first need to close poll
	$sqlPoll = "SELECT * from pollmaster WHERE is_deleted='0'";
	$stmtPoll = $conn->prepare($sqlPoll); 
	$stmtPoll->execute(); 
	$rowPoll = $stmtPoll->fetch();

	if($rowPoll){
		//then you can not create new poll unless close current poll
		$payload = array("text"=>"@ ".$_POST['user_name']." You can't create poll unless close current poll \n\nHow do I close poll? `/setpoll close`");

	}else{
		//go ahead you can create new poll
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

		//insert create poll here
	    $gainedVote 	= 0;
	    $pollDetails  	= $request_params[1]." ".$request_params[2]; 
	    $isDeleted 		= 0;
	    $sqlIns 		= "INSERT into pollmaster (gained_vote,poll_details,is_deleted) VALUES ('$gainedVote','$pollDetails','$isDeleted')";
		$result 		= $conn->query($sqlIns);

	}


	
}elseif(trim($request_params[0])=="cast"){
	
	//cast functionality will be started here..

	//first check there are any active poll or not 
	$sqlPoll = "SELECT * from pollmaster WHERE is_deleted='0'";
	$stmtPoll = $conn->prepare($sqlPoll); 
	$stmtPoll->execute(); 
	$rowPoll = $stmtPoll->fetch();

	if($rowPoll){
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
	        $newPollGainedPoints = ($newVote*$newVote);
	        $newPoints = ($row["polling_point"]-($newVote*$newVote)); 
	        $updateSql = "UPDATE users SET vote=$newVote,polling_point=$newPoints WHERE id=$userid";
	        $result = $conn->query($updateSql);
		} else {
			//insert new users here
		    $newVote 	 = 1;
		    $newPoints = (10000-($newVote));
		    $newPollGainedPoints = ($newVote*$newVote); 
		    $sqlIns = "INSERT into users (username,polling_point,vote) VALUES ('$newUserName','$newPoints','$newVote')";
			$result = $conn->query($sqlIns);
		}
	    
	    //here updated or insert gained point in pollmaster
	    $newPollPoints = ($rowPoll["gained_vote"]+$newPollGainedPoints); 
	    $pollId 	   = $rowPoll["id"];		
        $updatePollSql = "UPDATE pollmaster SET gained_vote=$newPollPoints WHERE id=$pollId";
        $result = $conn->query($updatePollSql);
		
		//$conn->close();
		$payload = array("text"=>"Thanks for voting @ ".$_POST['user_name']." your remaining points are $newPoints.");
	}else{
		// sorry you can't vote because ther are not any active poll
		$payload = array("text"=>"You are not able to vote because of no any active poll found @ ".$_POST['user_name']);
	}
	//cast functionality will be end here...

}elseif (trim($request_params[0])=="close") {
	//close poll that means soft delete 
	$is_deleted = 1;
    $updateSql = "UPDATE pollmaster SET is_deleted=1";
    $result = $conn->query($updateSql); 
    $payload = array("text"=>"@ ".$_POST['user_name']."You have successfully closed poll.");
}elseif (trim($request_params[0])=="status") {
	//check there are any open or active poll or not 
	
	$sqlPoll = "SELECT * from pollmaster WHERE is_deleted='0'";
	$stmtPoll = $conn->prepare($sqlPoll); 
	$stmtPoll->execute(); 
	$rowPoll = $stmtPoll->fetch();

	if($rowPoll){
		$gained_vote = $rowPoll["gained_vote"];
		$payload = array("text"=>"@ ".$_POST['user_name']."There is ".$gained_vote." voting balance for current poll.");
	}else{
		$payload = array("text"=>"@ ".$_POST['user_name']."Sorry there are no any active poll.");
	}
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