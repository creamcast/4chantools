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
<title>Many Replies Finder</title>
<meta name="description" content="None">
<meta name="author" content="None">
</head>
<body>
<?php

class ThreadEntry{
	
}

class PostEntry{
	public $com;
	public $no;
	public $rcount;
	public $threadno;
	public $url_thumb;
}

function cmp($a, $b){
	if ($a->rcount == $b->rcount) {
		return 0;
	}
	return ($a->rcount < $b->rcount) ? 1 : -1;
}

function RepCount($no, &$posts){
	$count = 0;
	foreach($posts as $post){
		$sstr="&gt;&gt;".$no;
		if(strpos($post->com, $sstr)!=FALSE){
			$count++;
		}
	}
	return $count;
}

function GetThumbnailUrl($board,$tim){
	return "http://t.4cdn.org/".$board."/".$tim."s.jpg";
}

function TallyRepsThread($board,$thread_jsonfile,&$posts){
	$json = file_get_contents($thread_jsonfile);
	$dthread = json_decode($json,true);
	$nowthread = $dthread["posts"][0]["no"];
	foreach ($dthread["posts"] as $post){
		$tmp = new PostEntry();
		$tmp->no  = $post["no"];
		$tmp->com = $post["com"];
		$tmp->threadno=$post["resto"];
		if ($post["tim"] != 0){
			$tmp->url_thumb=GetThumbnailUrl($board,$post["tim"]);
		}
		if ($tmp->threadno==0){
			$tmp->threadno=$nowthread;	
		}
		array_push($posts, $tmp);
	}
	
	foreach ($posts as &$post){
		$post->rcount=RepCount($post->no,$posts);
	}
}

function TallyRepsBoard($board){
	$boardposts=array();
	$threadnames = scandir($board);
	RemNonThreadNames($threadnames);
	foreach($threadnames as $tname){
		$tmp=array();
		TallyRepsThread($board,"$board/$tname",$tmp);
		$boardposts=array_merge($boardposts,$tmp);
	}
	
	usort($boardposts, "cmp");
		
	foreach($boardposts as $post){
		if ($post->rcount >= 3){
			echo "<h2>$post->rcount Replies</h2>";
			echo '<a href="http://boards.4chan.org/' . $board . "/thread/" . $post->threadno . "#p" . $post->no . '">LINK</a><br>';
			echo '<img src="' . $post->url_thumb . '"> <br>' ;
			echo "Thread number: $post->threadno<br>";
			echo "Post number: $post->no<br>";
			echo "$post->com<br>";
			echo "<hr>";
		}
	}
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
//usort($threads, "cmp");
//$scanned_dir = array_diff(scandir($board),array('..', '.',$tl_filename));
//$json = file_get_contents()

ini_set("allow_url_fopen", 1);
//error_reporting(0);//turn of errors

echo '<center><h2>Many Replies Finder</h2></center>';
echo "Enter board name";
echo '<form method="post" action="./reps.php">';
echo 'Enter board: <input type="text" name="board">';
echo '<input type="submit" name="submit" value="Submit">';
echo '</form>';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$board = $_POST["board"];
	echo $board."<br>";
	TallyRepsBoard($board);
}

?>
</body>
</html>