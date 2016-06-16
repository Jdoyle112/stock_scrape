<?php

//TO-DO
	// add 1 day result column
	// % positive vs % negative

	require("config.php");
	include('simple_html_dom.php');

	if($_SERVER['REQUEST_METHOD'] == "POST"){

		$sql = "SELECT * FROM stocks";
		$results=$db->query($sql);
		$results=$results->fetchAll(PDO::FETCH_ASSOC);

		foreach ($results as $value) {
			// call quote function for each record
			$quote = getQuote($value['name'], $value['published'], $value['id']);
		}
		header('Location: results.php');
	}
		

	function getQuote($symbol, $published, $id){
		require("config.php");
		for($i = 1; $i < 6; $i++){
			// get date values
			$date = strtotime($published);
			$month = date("m", $date)-1;
			$day = date('d', $date);
			$year = date('Y', $date);
			$date = date("Y-m-d", $date);

			// 3 days past
			if($i == 2){
				//$newDay = date('d', strtotime($day. '+3 days'));
				$newDay = date('d', strtotime($date. ' +3 days'));
				$newMonth = $month;
			}else if($i == 3){
				$newDay = date('d', strtotime($date. '+7 days'));
				$newMonth = $month;
			} else if($i == 4){
				$newDay = date('d', strtotime($date. '+ 14 days'));
				$newMonth = $month;
			} else if($i == 5){
				$newDay = $day;
				$newMonth = date('m', strtotime($date. '+ 1 months'))-1;
			} else {
				$newDay = $day;
				$newMonth = $month;
			}
			//echo "day: " . $day . " month: " . $month . " year: " . $year . "<br>";
			//echo " new day: " . $newDay . " month: " . $newMonth . "<br>"; 

			$url = "http://ichart.finance.yahoo.com/table.csv?s=$symbol&a=$month&b=$day&c=$year&d=$newMonth&e=$newDay&f=$year&g=d&ignore=.csv";
			$marketUrl = "http://ichart.finance.yahoo.com/table.csv?s=^GSPC&a=$month&b=$day&c=$year&d=$newMonth&e=$newDay&f=$year&g=d&ignore=.csv";
			
			// get stock price data
			$return_data = file_get_contents($url);
			 $parts = explode(",", $return_data);

			  $data['Open']      = $parts[7];
			  $data['High']      = $parts[8];
			  $data['Low']       = $parts[9];
			  $data['Close']     = $parts[10];
			  $data['Volume']    = $parts[11];
			  $data['Adj Close'] = $parts[12];

			// get market data for the day
			$return_market = file_get_contents($marketUrl);
			 $vals = explode(",", $return_market);

			  $market['Open']      = $vals[7];
			  $market['High']      = $vals[8];
			  $market['Low']       = $vals[9];
			  $market['Close']     = $vals[10];
			  $amrket['Volume']    = $vals[11];
			  $market['Adj Close'] = $vals[12];  

	  		//echo '<pre>';
			//print_r($data);
			if(!empty($return_data) AND !empty($return_market)){
				// insert into stats DB
				$sql=$db->prepare("INSERT INTO stats (stock_id, published, day, open, close, high, low)VALUES(:id, :pub, :day, :open, :close, :high, :low)");
				$sql->bindParam(':id', $id);
				$sql->bindParam(':pub', $date);
				$sql->bindParam(':day', $i);
				$sql->bindParam(':open', $data['Open']);
				$sql->bindParam(':close', $data['Close']);
				$sql->bindParam(':high', $data['High']);
				$sql->bindParam(':low', $data['Low']);
				$sql->execute();

				// insert into market db
				$sql=$db->prepare("INSERT INTO market (stats_id, day, open, close, high, low) VALUES (:id, :day, :open, :close, :high, :low)");
				$sql->bindParam(':id', $id);
				$sql->bindParam(':day', $i);
				$sql->bindParam(':open', $market['Open']);
				$sql->bindParam(':close', $market['Close']);
				$sql->bindParam(':high', $market['High']);
				$sql->bindParam(':low', $market['Low']);
				$sql->execute();
			}
  		}
	}

	// formula to calculate ret %'s
	function formula($open, $close, $high, $low, $initOpen, $initClose, $initHigh, $initLow){

		// calc % open
		$openPct = round((($initOpen - $open) / $initOpen) * 100, 3) . "%";
		$closePct = round((($initClose - $close) / $initClose) * 100, 3) . "%";
		$highPct = round((($initHigh - $high) / $initHigh) * 100, 3) . "%";
		$lowPct = round((($initLow - $low) / $initLow) * 100, 3) . "%";
		return array($openPct, $closePct, $highPct, $lowPct);
	}

?>
<!DOCTYPE hmtl>
<html>
<head>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
	<link rel="stylesheet" type="text/css" href="style.css">
<body>
	<h1>Stock Results</h1>
	<a href="index.php">Back to Input</a>
	<form method="post">
		<input type="submit" value="Generate Results">
	</form>
	<div class="container-fluid">
		<div class="row">
			<h2 style="text-align: center">Stock Price Returns</h2>
			<div class="col-md-2 col-md-offset-1">
				<h4>Stock % Ret. Pub. Date</h4>
				<?php

					// set starting point 
					$sql="SELECT open, close, high, low FROM stats WHERE day = 1";
					$results=$db->query($sql);
					$results=$results->fetchAll(PDO::FETCH_ASSOC);
					foreach ($results as $value) {
						// add all day1 values together
						$initOpen += $value['open'];
						$initClose += $value['close'];
						$initHigh += $value['high'];
						$initLow += $value['low'];

						$open += $value['open'];
						$close += $value['close'];
						$high += $value['high'];
						$low += $value['low'];						
					}
					
					$pct = formula($open, $close, $high, $low, $initOpen, $initClose, $initHigh, $initLow);
					marketRet(1, $pct);

					echo '<ul class="stock_prices">';
					echo '<li>Open: ' . $pct[0] . '</li>';
					echo '<li>Close: ' . $pct[1] . '</li>';
					echo '<li>High: ' . $pct[2] . '</li>';
					echo '<li>Low: ' . $pct[3] . '</li>';		
					echo '</ul>';								

				?>
			</div>
			<div class="col-md-2">
				<h4>Stock % Ret. +3 Days</h4>
				<?php
					$open = 0;
					$close = 0;
					$high = 0;
					$low = 0;
					$pct = 0;				

					$sql="SELECT open, close, high, low FROM stats WHERE day = 2";
					$results=$db->query($sql);
					$results=$results->fetchAll(PDO::FETCH_ASSOC);
					foreach ($results as $value) {
						// add all day2 values together
						$open += $value['open'];
						$close += $value['close'];
						$high += $value['high'];
						$low += $value['low'];
					}

					$pct = formula($open, $close, $high, $low, $initOpen, $initClose, $initHigh, $initLow);
					marketRet(2, $pct);

					echo '<ul class="stock_prices">';
					echo '<li>Open: ' . $pct[0] . '</li>';
					echo '<li>Close: ' . $pct[1] . '</li>';
					echo '<li>High: ' . $pct[2] . '</li>';
					echo '<li>Low: ' . $pct[3] . '</li>';		
					echo '</ul>';					
				?>
			</div>
			<div class="col-md-2">
				<h4>Stock % Ret. +1 Week</h4>
				<?php
					// reset vars
					$open = 0;
					$close = 0;
					$high = 0;
					$low = 0;
					$pct = 0;

					$sql="SELECT open, close, high, low FROM stats WHERE day = 3";
					$results=$db->query($sql);
					$results=$results->fetchAll(PDO::FETCH_ASSOC);
					foreach ($results as $value) {
						// add all day2 values together
						$open += $value['open'];
						$close += $value['close'];
						$high += $value['high'];
						$low += $value['low'];
					}

					$pct = formula($open, $close, $high, $low, $initOpen, $initClose, $initHigh, $initLow);
					marketRet(3, $pct);

					echo '<ul class="stock_prices">';
					echo '<li>Open: ' . $pct[0] . '</li>';
					echo '<li>Close: ' . $pct[1] . '</li>';
					echo '<li>High: ' . $pct[2] . '</li>';
					echo '<li>Low: ' . $pct[3] . '</li>';		
					echo '</ul>';		
				?>
			</div>
			<div class="col-md-2">
				<h4>Stock % Ret. +2 Weeks</h4>
				<?php
					// reset vars
					$open = 0;
					$close = 0;
					$high = 0;
					$low = 0;
					$pct = 0;

					$sql="SELECT open, close, high, low FROM stats WHERE day = 4";
					$results=$db->query($sql);
					$results=$results->fetchAll(PDO::FETCH_ASSOC);
					foreach ($results as $value) {
						// add all day2 values together
						$open += $value['open'];
						$close += $value['close'];
						$high += $value['high'];
						$low += $value['low'];
					}

					$pct = formula($open, $close, $high, $low, $initOpen, $initClose, $initHigh, $initLow);
					marketRet(4, $pct);

					echo '<ul class="stock_prices">';
					echo '<li>Open: ' . $pct[0] . '</li>';
					echo '<li>Close: ' . $pct[1] . '</li>';
					echo '<li>High: ' . $pct[2] . '</li>';
					echo '<li>Low: ' . $pct[3] . '</li>';		
					echo '</ul>';		
				?>
			</div>
			<div class="col-md-2">
				<h4>Stock % Ret. +1 Month</h4>
				<?php
					// reset vars
					$open = 0;
					$close = 0;
					$high = 0;
					$low = 0;
					$pct = 0;

					$sql="SELECT open, close, high, low FROM stats WHERE day = 5";
					$results=$db->query($sql);
					$results=$results->fetchAll(PDO::FETCH_ASSOC);
					foreach ($results as $value) {
						// add all day2 values together
						$open += $value['open'];
						$close += $value['close'];
						$high += $value['high'];
						$low += $value['low'];
					}

					$pct = formula($open, $close, $high, $low, $initOpen, $initClose, $initHigh, $initLow);
					marketRet(5, $pct);
					echo '<ul class="stock_prices">';
					echo '<li>Open: ' . $pct[0] . '</li>';
					echo '<li>Close: ' . $pct[1] . '</li>';
					echo '<li>High: ' . $pct[2] . '</li>';
					echo '<li>Low: ' . $pct[3] . '</li>';		
					echo '</ul>';		
				?>
			</div>									
		</div>
		<div class="row">
			<h2 style="text-align: center">Stock Returns vs. Market</h2>
						<?php
						function marketRet($i, $pct){
							require("config.php");

							echo '<div class="market">';

							// reset market vars if not day1
							if($i > 1){
								$marketOpen = 0;
								$marketClose = 0;
								$marketHigh = 0;
								$marketLow = 0;
							}

							// print title
							if($i == 1){
								echo '<h4>Day 1</h4>';
							}else if($i == 2){
								echo '<h4>+3 Days</h4>';
							}else if($i == 3){
								echo '<h4>+1 Week</h4>';
							}else if($i == 4){
								echo '<h4>+2 Weeks</h4>';
							}else if($i == 5){
								echo '<h4>+1 Month</h4>';
							}

							// set market initial values
							$sql="SELECT * FROM market WHERE day = 1";
							$results=$db->query($sql);
							$results=$results->fetchAll(PDO::FETCH_ASSOC);
							foreach ($results as $value) {
								$marketInitOpen += $value['open'];
								$marketInitClose += $value['close'];
								$marketInitHigh += $value['high'];
								$marketInitLow += $value['low'];
							}								

							// calculate market returns for each day
							$sql="SELECT * FROM market WHERE day = $i";
							$results=$db->query($sql);
							$results=$results->fetchAll(PDO::FETCH_ASSOC);
							foreach ($results as $value) {
								$marketOpen += $value['open'];
								$marketClose += $value['close'];
								$marketHigh += $value['high'];
								$marketLow += $value['low'];
							}

							$marketPct = formula($marketOpen, $marketClose, $marketHigh, $marketLow, $marketInitOpen, $marketInitClose, $marketInitHigh, $marketInitLow);

							// compare vs stock returns for given times
							echo '<ul class="market_prices">';
							for($x = 0; $x < 4; $x++){
								$val = $pct[$x] - $marketPct[$x];
								if($x == 0){
									echo '<li>Open:  ' . $val . '%</li>';
								}else if($x == 1){
									echo '<li>Close:  ' . $val . '%</li>';
								}else if($x == 2){
									echo '<li>High:  ' . $val . '%</li>';
								}else if($x == 3){
									echo '<li>Low:  ' . $val . '%</li>';
								}
							}	
							echo '</ul>';
							echo '</div>';
						}
						?>
					</div>
			
		</div>
	</div>
</body>
</html>



