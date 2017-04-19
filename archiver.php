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
<title>Archiver</title>
<meta name="description" content="None">
<meta name="author" content="None">
</head>

<script language="javascript">
var int = self.setInterval("window.scrollBy(0,1000);", 200);
</script>

<body>
<?php

class ThreadEntry{
	public $board;
	public $no;
	public $last_modified;
}

function GetApiUrlThreadListFor($board){
	return "http://a.4cdn.org/$board/threads.json";
}
function GetApiUrlThread($board,$no){
	return "http://a.4cdn.org/$board/thread/$no.json";
}

function GetTCreateJSONSTR($json){
	$dthread = json_decode($json,true);
	return $dthread["posts"][0]["time"];
}

function FindMatchInList(&$threadentry, &$threadlist){
	foreach ($threadlist as $thread){
		if (
		$thread->no == $threadentry->no &&
		$thread->last_modified == $threadentry->last_modified &&
		$thread->board == $threadentry->board
		){
			return TRUE;
		}
	} 
	return FALSE;
}

function RemNonThreadNames(&$threadnames){
	$tmp = array();
	foreach ($threadnames as $tname){	
		if (preg_match("/^[0-9]*$/",$tname)==TRUE){
			array_push($tmp, $tname);
		}
	}
	$threadnames=array();
	$threadnames=$tmp;
}

function ArchiveThreads($board){
	//retreive board list json and cache if it doesn't exist on local
	$local_boardlist_url="boardlist.json";
	$api_boardlist_url="http://a.4cdn.org/boards.json";
	$json = file_get_contents($local_boardlist_url);
	if ($json === FALSE){
		echo "no board list file retreiving from 4chan...";
		$json = file_get_contents($api_boardlist_url);
		if ($json === FALSE) { echo "Could not retreive board list"; return false;}
		$wresult = file_put_contents($local_boardlist_url,$json);
		if ($wresult === FALSE) {echo "Could not cache board list to disk";}
	}
	
	//get board names and store in array
// 	$dboardlist = json_decode($json,true);
// 	$boards = array();
// 	foreach ($dboardlist["boards"] as &$board) {	
// 		array_push($boards, $board["board"]);
// 	}

	$boards = array();
	array_push($boards,$board);
	
	//create board folders
	foreach($boards as &$board){
		if (!file_exists( $board )){
			mkdir($board);
		}
	}
	
	//get all thread numbers
	$tl_filename = "threadlist.json";
	
	$threads = array();
	$threads_full = array();
	foreach ($boards as &$board){
		echo "retreiving thread list for $board <br>";
		$json = file_get_contents(GetApiUrlThreadListFor($board) , false, stream_context_create(array('http'=> array('timeout'=>10))));
		if ($json===FALSE){
			echo "could not retreive thread list for $board <br>";
			return 0;
		}else{
			//get copy of old threadlist and save to tmp array
			$threads_old = array();
			$oldtlistjson = file_get_contents("$board/$tl_filename");
			if ($oldtlistjson != FALSE){
				$doldtlist = json_decode($oldtlistjson,true);
				foreach ($doldtlist as &$page) {
					foreach($page["threads"] as &$thread){
						$tmp = new ThreadEntry();
						$tmp->no = $thread["no"];
						$tmp->last_modified = $thread["last_modified"];
						$tmp->board = $board;
						array_push($threads_old, $tmp);
					}
				}
			}
			
			//save copy of new threadlist
			if (file_put_contents("$board/$tl_filename",$json)){
				echo "thread list saved $board/$tl_filename<br>";
			}else{
				echo "could not save thread list for $board<br>";
			}
			
			//push thread numbers into array
			echo "getting thread numbers for $board...<br>";
			$dthreadlist = json_decode($json,true);
			foreach ($dthreadlist as &$page) {
				foreach($page["threads"] as &$thread){
					$tmp = new ThreadEntry();
					$tmp->no = $thread["no"];
					$tmp->last_modified = $thread["last_modified"];
					$tmp->board = $board;
					//push only modified threads
					if (FindMatchInList($tmp,$threads_old)){
						echo "found local match for $tmp->no of $tmp->board, thread will not be downloaded<br>";
					}else{
						array_push($threads, $tmp);	
					}
					array_push($threads_full,$tmp);	//full thread list for deleting old threads
				}
			}
			echo "-----------------------------------------<br>";
		}
	}
	
	//DL threads
	$maxthreads = sizeof($threads);
	$count = 0;
	foreach ($threads as $thread){		
		$file = $thread->board . "/" . $thread->no;	//get filename
		$json = file_get_contents(GetApiUrlThread($thread->board,$thread->no) , false, stream_context_create(array('http'=> array('timeout'=>10))));
		$count = $count + 1;
		if ($json!=FALSE){
			echo "wrote $thread->no to $file ($count/$maxthreads) <br>";
			$wresult = file_put_contents($file,$json);
		}else{
			echo "could not retreive thread $thread->no : $thread->board <br>";
		}	
	}
	
	//delete old threads
	echo "clearing dead threads...<br>";
	$count=0;
//	$scanned_dir = array_diff(scandir($board),array('..', '.',$tl_filename));	
	$scanned_dir = scandir($board);
	RemNonThreadNames($scanned_dir);
	
	foreach($scanned_dir as $filename){
		$exists=0;
		foreach ($threads_full as $te){
			if($filename == strval($te->no)){
				//echo "$filename exists<br>";
				$exists=1;
				break;
			}
		}
		if ($exists==0){
			$delfile="$board/$filename";
			unlink($delfile);
			$count++;
			echo "deleting $board/$filename<br>";
		}
	}
	echo "deleted $count threads.<br>";
	//get list of thread filenames in folder
	//compare with _full_ (create another array) thread list array	
	echo "done.<br>";
	echo '<script language="javascript">int = window.clearInterval(int);</script>';
}

ini_set("allow_url_fopen", 1);
//error_reporting(0);//turn of errors

echo '<center><h2>Archiver</h2></center>';
echo "Click to refresh boards data";
echo '<form method="post" action="./archiver.php">';
echo 'Enter board: <input type="text" name="board">';
echo '<input type="submit" name="refresh" value="Refresh">';
echo '</form>';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$board = $_POST["board"];
	echo $board."<br>";
	ArchiveThreads($board);
}

?>
</body>
</html>