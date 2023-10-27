<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// 119672872zedffafae6d167c7ba0fdc29b6e3d084d55e1e208


require 'config.php';

// Strava ClientID and Strava ClientSecret not set. Die with explanation.
if(!isset($strava_client_id) || !isset($strava_client_secret) || !is_integer($strava_client_id) || strlen($strava_client_secret)!==40){
	print '<!DOCTYPE html><html lang="en"><head><title>...</title></head><body>In order to continue this app requires you to enter Strava ClientID & Strava ClientSecret in config.php.<br>Sign up at <b>http://www.strava.com/</b> and register your own Strava client at <b>https://www.strava.com/settings/api</b><br>Enter Strava ClientID & Strava ClientSecret in config.php and reload this page...</body></html>';
	die();
}


// ---

function curlfgc($u){
$c=curl_init();
curl_setopt($c,CURLOPT_FOLLOWLOCATION,1);
curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
curl_setopt($c,CURLOPT_URL,$u);
$res=curl_exec($c); curl_close($c);
if($res){return $res;}else{return false;}}

// ---

function abc50chars($n){
	$n=preg_replace('/[^\p{L}\p{N} -]/u',' ',$n);
	$n=preg_replace('/([\s])\1+/',' ',$n);
	$n=substr($n,0,50);
	return trim($n);
}

function debug_die($x){
	// @file_put_contents('debug.log',$x);
	die();
}

// ---

// redirect and die if no athlete_token 
if(count($_GET)<1){
	header('location:getoauth.php');
	die();
}

// get athlete_token from URL
foreach($_GET as $key => $val){
	$athlete_token=trim(preg_replace("/[^a-z0-9]+/i", '', $key));
}


// just die if missing or not a valid athlete_token
if(!isset($athlete_token)){debug_die(1);}
$athlete_array = explode('z',$athlete_token); 
$athlete_id = (int)$athlete_array[0];
if(!isset($athlete_array[1]) || $athlete_array[1] !== sha1($athlete_id.$secret_salt_hashing)){debug_die(2);}

// get access_token

$db = new SQLite3($store_gpx_files_dir.'/db.sqlite');
$res=$db->query('SELECT * FROM tokens WHERE athlete = '.$athlete_id);
$ath=$res->fetchArray();

if(!isset($ath['expires'])){debug_die(5);}

$exp=(int)$ath['expires'];
if($exp>time()){
	$access_token = $ath['access'];
}

// refresh access_token if expired

if(!isset($access_token)){

	$ch=curl_init();
	curl_setopt_array($ch,[
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_URL => "https://www.strava.com/api/v3/oauth/token",
	CURLOPT_POST => 1,
	CURLOPT_POSTFIELDS => http_build_query([
	    "client_id" => $strava_client_id,
	    "client_secret" => $strava_client_secret,
		"refresh_token" => $ath['refresh'],
		"grant_type" => "refresh_token"])
	]);

	$res=curl_exec($ch);
	curl_close($ch);

	$res=@json_decode($res,true);

	if(!isset($res['access_token']) || !isset($res['refresh_token'])){debug_die(6);}

		$expires_at=(int)$res['expires_at'];
		$access_token=preg_replace("/[^a-z0-9]+/i", '', $res['access_token']);
		$refresh_token=preg_replace("/[^a-z0-9]+/i", '', $res['refresh_token']);

		$db->query('BEGIN');
		$db->query('DELETE FROM tokens WHERE athlete = '.$athlete_id);
		$db->query("INSERT INTO tokens VALUES($athlete_id,'$access_token','$refresh_token',$expires_at)");
		$db->query('COMMIT'); 
		$db->query('VACUUM');

}

if(!isset($access_token)){debug_die(7);}

$res=$db->query('SELECT * FROM zones WHERE athlete = '.$athlete_id);
$ath=$res->fetchArray();

if(!is_array($ath)){
	header('location:zones.php?auth='.$athlete_token);die();}


// ----------------------------------------------
// put activites in json files

$html_data=[];
$html_template='<!DOCTYPE html><html lang="en"><head><title>...</title><meta charset="utf-8"><meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"><script src="script.js"></script><script>base=\'%BASE%\';</script><style>body{font-family:monospace}</style></head><body><div style="margin:auto;width:360px;max-width:100%;background-color:#fff;padding:10px 0"><h1 style="text-align:right">ACTIVITIES</h1><table style="width:100%" cellpadding="5">%DATA%</table><textarea id="tx" style="width:90%;height:40px;padding:5%;border-width:0;background-color:#f4f4f4" placeholder="Description rewrite + Weather - MAF - Zones"></textarea><input type="button" value="Shoes: NONE" style="width:100%;border-width:0;color:#fff;background-color:#444;margin:5px 0;padding:10px 0" onclick="res_butts();this.style.color=\'#fff\';this.style.backgroundColor=\'#444\';shoes=0">%SHOES%<br><br><button type="button" style="width:100%;border-width:0;color:#fff;background-color:#D81B60;margin:5px 0;padding:10px 0" onclick="self.location.href=\'zones.php?auth=%BASE%\'">MAF &amp; HR Zones</button></div></body></html>';

if(!isset($athlete_array[2]) || !is_numeric($athlete_array[2])){


$ch=curl_init();
curl_setopt($ch, CURLOPT_URL,'https://www.strava.com/api/v3/athlete/activities?page=1&per_page=5');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $access_token]);
$res=curl_exec($ch);
curl_close($ch);

$res=@json_decode($res,true); $list_pos=[];

for($i=0;$i<5;$i++){

$tmp2json=[];
$tmp2json['id']=$res[$i]['id'];
$tmp2json['distance']=round($res[$i]['distance']/1000,2);
$tmp2json['elapsed_time']=gmdate("H:i",$res[$i]['elapsed_time']);
$tmp2json['start_latlng']=$res[$i]['start_latlng'];
$tmp2json['start_date']=$res[$i]['start_date'];
$tmp2json['average_speed']=$res[$i]['average_speed'];
$tmp2json['hr_data']=[];

$aid=$res[$i]['id']; 
$list_pos[]=[$res[$i]['id'],$res[$i]['name'],$res[$i]['start_date']];
$json_file=$store_gpx_files_dir.'/'.$aid.'.json';

if(!is_file($json_file) && $res[$i]['has_heartrate']>0){
$ch=curl_init();
curl_setopt($ch, CURLOPT_URL,'https://www.strava.com/api/v3/activities/'.$aid.'/streams?keys=heartrate&key_by_type=true');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $access_token]);

$hrd=curl_exec($ch);
curl_close($ch);

$hrd=@json_decode($hrd,true);
$tmp2json['hr_data']=$hrd['heartrate']['data'];

}

if(!is_file($json_file) && count($tmp2json['hr_data'])>0){
	$fle=json_encode($tmp2json);
	file_put_contents($json_file, $fle);}

}

$shoes='';
$html_data[]='<tr><td colspan="3" style="border-bottom:1px solid #D81B60"></td></tr><tr><td colspan="3"></td></tr>';
$spacer_row=$html_data[0];

foreach ($list_pos as $val) {
	$html_data[]='<tr><td style="width:80%"><input style="border-width:0;padding:10px" id="inp'.$val[0].'" type="text" value="'.$val[1].'"></td><td>'.substr($val[2],0,10).'</td><td><input type="button" style="color:#fff;background-color:#1976D2;border-width:0;padding:5px 15px" onclick="go(\''.$val[0].'\')" value="&nbsp; GO &nbsp;"></td></tr>'.$spacer_row;
	$spacer_row='';
}


$ch=curl_init();
curl_setopt($ch, CURLOPT_URL,'https://www.strava.com/api/v3/athlete/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $access_token]);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
$result = curl_exec($ch);
$info = curl_getinfo($ch); //Some information on the fetch
curl_close($ch);

$result=json_decode($result,true);

foreach ($result['shoes'] as $val) {
	$shoes.='<input type="button" value="'.htmlspecialchars($val['name']).'" style="width:100%;margin:5px 0;border-width:0;background-color:#eee;padding:10px 0" onclick="res_butts();this.style.color=\'#fff\';this.style.backgroundColor=\'#444\';shoes=\''.htmlspecialchars($val['id']).'\'"> ';
}

$html_data=implode('', $html_data); 
print str_replace(['%DATA%','%BASE%','%SHOES%'], [$html_data,$athlete_token,$shoes], $html_template);

}

?>