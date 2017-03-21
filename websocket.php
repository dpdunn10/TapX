<?php
$host = 'localhost'; //host
$port = '9998'; //port
$null = NULL; //null var

//Create TCP/IP sream socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//reuseable port
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

//bind socket to specified host
socket_bind($socket, 0, $port);

//listen to port
socket_listen($socket);

//create & add listening socket to the list
$clients = array($socket);

$businesses = array();

//start endless loop, so that our script doesn't stop
while (true) {
	//manage multiple connections
	$changed = $clients;
	//returns the socket resources in $changed array
	socket_select($changed, $null, $null, 0, 10);

	//check for new socket
	if (in_array($socket, $changed)) {
		$socket_new = socket_accept($socket); //accept new socket
		$clients[] = $socket_new; //add socket to client array

		$header = socket_read($socket_new, 1024); //read data sent by the socket
		perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake

		socket_getpeername($socket_new, $ip); //get ip address of connected socket
		//$response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' connected'))); //prepare json data
		//send_message($response); //notify all users about new connection

		//make room for new socket
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}

	//loop through all connected sockets
	foreach ($changed as $changed_socket) {

		//check for any incoming data
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
		{
			$received_text = unmask($buf); //unmask data
			$tst_msg = json_decode($received_text); //json decode
			// print("Test Message:" . $received_text . "\n");
			// print_r($tst_msg);
			$user_business_id = $tst_msg->business_id; //business sender is at
			$user_type = $tst_msg->type; //type of message (order or get or close)
			$user_table_id = $tst_msg->table_id; //sender table
			$user_quantity = $tst_msg->quantity;
			$user_item = $tst_msg->item; //message text
			// print("Business ID: ".$user_business_id);
			// print("\nUser Type: ".$user_type);
			// print("\nTable ID: ".$user_table_id);
			// print("\nQuantity: ".$user_quantity);
			// print("\nItem: ".$user_item."\n");

			//Add new business to businessses arrary with socket and business_id for sending
			if ($user_type == 'customer'){
				print("I'm a customer\n");
			} else if ($user_type == 'business'){
				array_push($businesses, array('business_id'=>$user_business_id, 'socket'=>$changed_socket));
			}

			//prepare data to be sent to client
			//$response_text = mask(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color)));
			$response_text = mask(json_encode(array('business_id'=>$user_business_id, 'type'=>$user_type, 'table_id'=>$user_table_id, 'quantity'=>$user_quantity, 'item'=>$user_item)));
			send_message($response_text, $user_business_id); //send data
			break 2; //exit this loop
		}

		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) { // check disconnected client
			// remove client for $clients array
			$found_socket = array_search($changed_socket, $clients);
			socket_getpeername($changed_socket, $ip);
			unset($clients[$found_socket]);

			//notify all users about disconnected connection
			//$response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
			//send_message($response);
		}
	}
}
// close the listening socket
socket_close($socket);

//Find correct business and send only to that business
function send_message($msg, $business_id)
{
	global $businesses;
	foreach($businesses as $business)
	{
		if($business['business_id'] == $business_id){
			@socket_write($business['socket'],$msg,strlen($msg));
		}
	}
	return true;

	/*
	global $clients;
	foreach($clients as $changed_socket)
	{
		@socket_write($changed_socket,$msg,strlen($msg));
	}
	return true;
	*/
}


//Unmask incoming framed message
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

//Encode message for transfer to client.
function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);

	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}

//handshake new client.
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\n" .
	"Upgrade: websocket\n" .
	"Connection: Upgrade\n" .
	"WebSocket-Origin: $host\n" .
	"WebSocket-Location: ws://$host:$port/demo/shout.php\n".
	"Sec-WebSocket-Accept:$secAccept\n\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}
