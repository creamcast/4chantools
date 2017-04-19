<!doctype html>
<html lang="en">
<head>
<style>
table, th, td {
	padding: 8px;
    border: 1px solid black;
    border-collapse: collapse;
}
</style>
<meta charset="utf-8">
<title>Thread Speed Rank</title>
<meta name="description" content="None">
<meta name="author" content="None">
</head>
<body>
<?php

ini_set("allow_url_fopen", 1);
//error_reporting(0);//turn of errors

class ThreadEntry{
	public $num;
	public $timec;	//created time
	public $timea;	//alive time
	public $repc;	//reply count
	public $speed;
	
	public $com;
	public $sub;
	public $url_thread;
	public $url_thumb;
}

function cmp($a, $b){
	if ($a->speed == $b->speed) {
		return 0;
	}
	return ($a->speed < $b->speed) ? 1 : -1;
}


//get json
function GetBoardCatalog($board,&$catalog){
	$api_catalog_url="http://a.4cdn.org/$board/catalog.json";
	$json = file_get_contents($api_catalog_url);
	if ($json === false) {
		//handle error
		echo "Could not retreive board catalog: " . $board;
		return false;
	}
	$catalog = json_decode($json,true);	//decode as array
	return true;
}

function GetThumbnailUrl($board,$tim){
	return "http://t.4cdn.org/".$board."/".$tim."s.jpg";
}

function GetThreadUrl($board,$tnum){
	return "http://boards.4chan.org/$board/thread/$tnum";
}

function GetThreadsData($board, $catalog, &$threads){
	$current_time = floatval(time());
	foreach ($catalog as &$page) {
		foreach($page["threads"] as &$thread){
			$tmp = new ThreadEntry();
			$tmp->num = $thread["no"];
			$tmp->timec = floatval($thread["time"]);
			$tmp->timea = floatval(($current_time - $tmp->timec)/60); //convert to minutes
			$tmp->repc = floatval($thread["replies"]);
			$tmp->speed = floatval($tmp->repc / $tmp->timea * 60 * 24);
			$tmp->com = $thread["com"];
			$tmp->sub = $thread["sub"];
			if (is_string($tmp->sub)===FALSE){
				$tmp->sub = "No subject";
			}
			$tmp->url_thread = GetThreadUrl($board,$thread["no"]);
			$tmp->url_thumb = GetThumbnailUrl($board,$thread["tim"]);
			array_push($threads, $tmp);
		}
	}
}

function CreateRankTable(&$threads){
	$count = 0;
	usort($threads, "cmp");
	echo '<table>';
	echo '<tr><th>Rank</th><th>Image</th><th>Speed</th><th>Replies</th><th>Text</th><th>Link</th></tr>';
	foreach ($threads as $thread){
		$count = $count + 1;
		echo '<tr>';
		//rank
		echo '<td>';
		echo $count;
		echo '</td>';
		//image
		echo '<td>';
		echo '<img src="' . $thread->url_thumb . '">';
		//echo $thread->url_thumb;
		echo '</td>';
		//speed
		echo '<td>';
		echo number_format($thread->speed,2,'.','');
		echo '</td>';
		//replies
		echo '<td>';
		echo number_format($thread->repc);
		echo '</td>';
		//text
		echo '<td>';
		echo "<b><u>".$thread->sub."</u></b><p>".$thread->com;
		echo '</td>';
		//link
		echo '<td>';
		echo '<a href="'.$thread->url_thread.'" target="_blank">LINK</a>';
		echo '</td>';
		echo '</tr>';
		//echo $thread->url_thread . ":" . $thread->num . ":" . $thread->speed . "<br>";
	}
	echo '</table>';
}

//--main program--
echo '<center><h2>Thread Speed Ranking</h2></center>';
echo '<form method="post" action="./ikioi.php">';
echo 'Enter board: <input type="text" name="board">';
echo '<input type="submit" name="submit" value="Submit">';
echo '</form>';

if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
      $nameErr = "Only letters and white space allowed"; 
    }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if (empty($_POST["board"])) {
		echo "board name missing";
		return 0;
    } else if (!preg_match("/^[a-zA-Z0-9 ]*$/",$_POST["board"])){
    	echo "Invalid board name";
    	return 0;
    }else{
    	$board = $_POST["board"];
    	$catalog = array();
		$threads = array();
    	if (GetBoardCatalog($board, $catalog)){
			echo "retreiving and calculating thread rank...<br>";
			GetThreadsData($board,$catalog,$threads);
			echo "printing table...";
			CreateRankTable($threads);
		}
		return 1;
    }
}
?>
</body>
</html>