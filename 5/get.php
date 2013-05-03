<?php
header("Content-type: text/html; charset=utf-8");

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');

$i = 0;
$query = "SELECT * FROM `ff5_id` WHERE name='last'";
$last_id = $mysql->getLine($query);
$ff5id = $last_id['id'];
$more = true;


  $ff = json_decode( file_get_contents('http://5.fanfou.com/exp/ajax/photos'), true );
  $ffphoto = $ff['photos'];
  $first = $ffphoto[0]['id'];

  //print_r($ffphoto);
do {
	foreach ( $ffphoto as $ff5 ) {

		$largeurl = $ff5['largeurl'];
		$user_url = $ff5['user_url'];
		$text = $ff5['text'];
		$id = $ff5['id'];
		echo $id . ' // ';
		$msg_id = $ff5['msg_id'];
		$user_id = $ff5['user_id'];
		$url = $ff5['url'];
		$user_screen_name = $ff5['user_screen_name'];
		$repost_count = $ff5['repost_count'];

          if ($id <= $ff5id ) {
          	$more = false;
			$query = "UPDATE `ff5_photo` SET repost_count='$repost_count' WHERE id='$id'";
			$mysql->runSql($query);
            echo $user_screen_name . ': re'.$repost_count.'<br />';
          }
          else {
			$query = "INSERT INTO `ff5_photo` (largeurl, user_url, text, msg_id, id, user_id, url, user_screen_name, repost_count) VALUES ('$largeurl', '$user_url', '$text', '$msg_id', '$id', '$user_id', '$url', '$user_screen_name', '$repost_count')";
			$mysql->runSql($query);
			echo $user_screen_name . ' added <br />';
          }

	}
    $last = $ffphoto[count($ffphoto)-1]['id'];
    $ff = json_decode( file_get_contents('http://5.fanfou.com/exp/ajax/photos?max_id='.$last), true );
 	$ffphoto = $ff['photos'];
}	
while ( count($ffphoto) );



$query = "UPDATE `ff5_id` SET id='$first' WHERE name='last'";
$mysql->runSql($query);

$timestamp = time();
$query = "UPDATE `ff5_id` SET id='$timestamp' WHERE name='time'";
$mysql->runSql($query);


?>