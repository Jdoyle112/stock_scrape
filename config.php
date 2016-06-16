<?php

// cennect to db

	$servername = "localhost";
	$username = "root";
	$password = "root";

	try {
	    $db = new PDO("mysql:host=$servername;dbname=StockScrape", $username, $password);
	    // set the PDO error mode to exception
	    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	    //echo "Connected successfully"; 
	    }
	catch(PDOException $e)
	    {
	    echo "Connection failed: " . $e->getMessage();
	}
   //	define("BASE_URL", "http://funninja.byethost31.com/Fun_Ninja_Games/");

?>