<?php

// TO-DO
	// crawl through multiple pages automatically (pick pages with a # in them)
	// set max execution time
	ini_set('max_execution_time', 1000);
	include('simple_html_dom.php');

	// set the initial URL
	if($_SERVER['REQUEST_METHOD'] == 'POST'){
		$page = $_POST['url'];
		pageLoop($page);
		//getArticles($page);
	}

	// loop through pages
	function pageLoop($page){
		require("config.php");

		// counter determines amount of times (pages) functions runs
		static $count = 0;
		$count++;

		$html = new simple_html_dom();

		$html->load_file($page);

		// get all article links on page
		$links = $html->find('.entry-title a');

		//navigate into article
		foreach ($links as $value) {
			$link = $value->href;
			// run getArticles function
			getArticles($link);
		}

		// get next page link
		$next = $html->find('div.nav-previous a', 0);
		echo $next->href. '<br>';

		// check if html and next exist
		if(!empty($html)){
			if(!empty($next)){

				// click next page link
				$url = $next->href;
				$html->clear();
				unset($html);

				// limit # of loops
				if($count < 100){
					// restart function with new URL
					pageLoop($url);	
				}else{
					return;
				}
			
			}
		}	
	}

	// get individual articles
	function getArticles($page){
		require("config.php");

		$html = new simple_html_dom();

		$html->load_file($page);

		// get all pagination id's
		$next = $html->find('.vapp', 0);
		
		// check if html has contents
		if(!empty($html)){
			// check if pagination exists
			if(!empty($next)){
				// click view all button
				$url = $next->href;
				$html->clear();
				unset($html);
				echo 'page: ' . $url . '<br>';

				// restart function with new URL
				getArticles($url);

			}else {
				echo 'page: ' . $page . '<br>';
				// get article date
				$pub = $html->find('.published');
				$pub = $pub[0]->innertext;
				$pub = date('y-m-d', strtotime($pub));

				// get all tickers 
				$quotes = $html->find('h3');
				foreach ($quotes as $value) {
					//$name = $value->next_sibling(1)->find('u', 0)->innertext;
					$name = $value->next_sibling(1)->find('u', 0);
					$name = strip_tags($name);
					echo 'name 1: ' . $name . '<br>';
					if(!empty($name)){
						// upload all tickers to DB (u is directly in 1st sibling p)
						$sql = $db->prepare("INSERT INTO stocks(name, published, url)VALUES(:name, :pub, :url)");
						$sql->bindParam(':name', $name);
						$sql->bindParam(':pub', $pub);
						$sql->bindParam(':url', $page);
						$sql->execute();
					}else{
						// u is in 2nd p
						$name = $value->next_sibling(1)->next_sibling(1)->find('u', 0);
						$name = strip_tags($name);
						echo 'name 2: ' . $name . '<br>';		
						if(!empty($name)){
							$sql = $db->prepare("INSERT INTO stocks(name, published, url)VALUES(:name, :pub, :url)");
							$sql->bindParam(':name', $name);
							$sql->bindParam(':pub', $pub);
							$sql->bindParam(':url', $page);
							$sql->execute();							
						}else {
							// quote inside a instead of u
							$name = $value->next_sibling(1)->children(2);
							echo 'name 3: ' . $name . '<br>';
							//$name = $name->innertext;
							if(!empty($name) AND strlen($name) < 6){
								echo 'name 3: ' . $name . '<br>';
								$sql = $db->prepare("INSERT INTO stocks(name, published, url)VALUES(:name, :pub, :url)");
								$sql->bindParam(':name', $name);
								$sql->bindParam(':pub', $pub);
								$sql->bindParam(':url', $page);
								$sql->execute();												
							}
						}				
					}
				}
			}		
		}	
	}	// end function

?>
<!DOCTYPE html>
<html>
<head>
</head>
<body>
	<h1>Stock Price Scraper</h1>
	<div>
		<form method="post">
			<input type="text" name="url" placeholder="Enter a Valid Url">
			<input type="submit" value="Submit">
		</form>
		<a href="results.php">View Results</a>
	</div>
</body>
</html>






