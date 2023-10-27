<?php

require 'config.php';

// die on error: ClientId / ClientSecret or in case OAUTH token alrady set...
if(!isset($strava_client_id) || !isset($strava_client_secret) || !is_integer($strava_client_id) || strlen($strava_client_secret)!==40){
	die('This page is supposed to supply Strava OauthToken. You do not seem to have ClientID and ClientSecret in config.php');
}

// stop on error
if(isset($_GET['error'])){

	$err_message = 'Unknown error...';
	if($_GET['error']=='access_denied'){
		$err_message = 'Well, without authorization this cannot work... Go back and click authorize.';
	}
	die($err_message);
}

// ---

if(isset($_SERVER['REQUEST_SCHEME'])){
	$proto = $_SERVER['REQUEST_SCHEME'].'://';}
else{
	$proto = 'https://';
}

$this_url = $proto.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

// ---

// getting final access token
if(isset($_GET['code']) && strlen($_GET['code'])==40){

	$access_token='';
	$code=preg_replace("/[^a-z0-9]+/i", '', $_GET['code']);

	if(!is_file($store_gpx_files_dir.'/db.sqlite')){
		@copy('db.sqlite.init',$store_gpx_files_dir.'/db.sqlite');
		@chmod($store_gpx_files_dir.'/db.sqlite',0666);
	}

	$ch=curl_init();
	curl_setopt_array($ch,[
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_URL => "https://www.strava.com/oauth/token",
	CURLOPT_POST => 1,
	CURLOPT_POSTFIELDS => http_build_query([
	    "client_id" => $strava_client_id,
	    "client_secret" => $strava_client_secret,
		"code" => $code,
		"grant_type" => "authorization_code"])
	]);

	$res=curl_exec($ch);
	curl_close($ch);

	$res=@json_decode($res,true);
	
	if(isset($res['access_token']) && isset($res['refresh_token']) && isset($res['expires_at']) && isset($res['athlete']['id'])){

		$athlete_id=(int)$res['athlete']['id'];
		$expires_at=(int)$res['expires_at'];
		$access_token=preg_replace("/[^a-z0-9]+/i", '', $res['access_token']);
		$refresh_token=preg_replace("/[^a-z0-9]+/i", '', $res['refresh_token']);

		$db = new SQLite3($store_gpx_files_dir.'/db.sqlite');
		$db->query('BEGIN');
		$db->query('DELETE FROM tokens WHERE athlete = '.$athlete_id);
		$db->query("INSERT INTO tokens VALUES($athlete_id,'$access_token','$refresh_token',$expires_at)");
		$db->query('COMMIT');

		$hash = sha1($athlete_id.$secret_salt_hashing);

		$exec_url=str_replace('getoauth.php','',$this_url).'?'.$athlete_id.'z'.$hash;
		print '<html><body>Execute by running this URL manually or automatically with cronjobs';
		print '<pre>URL: <b>'.$exec_url.'</b></pre></body></html>';
		die();
	}

	die('Strava API returned an error. If you refreshed this page go back and re-authorize.');
}


// ---

$auth_url = 'https://www.strava.com/oauth/authorize?client_id='.$strava_client_id.'&response_type=code&redirect_uri='.urlencode($this_url).'&approval_prompt=force&scope=activity%3Awrite%2Cactivity%3Aread%2Cprofile%3Aread_all';
header('location:'.$auth_url);

?>