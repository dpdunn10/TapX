<?php
	include '../dbcreds.php';
	if(!isset($_COOKIE['business_id']) || !isset($_COOKIE['table_id']) || !isset($_COOKIE['user_name'])){
		header('Location: home.php');
	}


	echo "Current Tab<br><br>";

	if(isset($_COOKIE['tab']) && isset($_COOKIE['tab_price']))	///TAB IS LINKED TO QUANTITY OF DRINK
	{
		foreach (json_decode($_COOKIE['tab']) as $name => $quantity) {
			echo $quantity . "  " . $name;
			foreach(json_decode($_COOKIE['tab_price']) as $pname => $price)
			{
				if($name == $pname) {
					echo " for a total of $" . $price;
				}
				
			}
			echo "<br>";
		}
	}

	if(isset($_COOKIE['tab_price'])) 	///TAB PRICE IS PRICE OF QUANTITY OF DRINKS
	{
		foreach (json_decode($_COOKIE['tab_price']) as $name => $price) {
			echo "<br>";
			$totalPrice += $price;
		}
	}

	echo "<br><br><br>";
	echo "Total Price for your tab = $" . number_format($totalPrice, 2);
	
	
	echo "<br><br><br><br><br><br>";
	echo "<a class='btn btn-success btn-lg' href='cust-order-form.php'>Return To Order!</a>";


?>