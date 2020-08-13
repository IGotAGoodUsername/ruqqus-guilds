<?php
include( "private.php" );

$out = "<html><head><style>
table.blueTable {
  border: 1px solid #8D17A4;
  background-color: #EEEEEE;
  width: 100%;
  text-align: left;
  border-collapse: collapse;
}
table.blueTable td, table.blueTable th {
  border: 1px solid #AAAAAA;
  padding: 3px 2px;
}
table.blueTable tbody td {
  font-size: 13px;
}
table.blueTable tr:nth-child(even) {
  background: #BFABEA;
}
table.blueTable thead {
  background: #8D17A4;
  background: -moz-linear-gradient(top, #a951bb 0%, #982ead 66%, #8D17A4 100%);
  background: -webkit-linear-gradient(top, #a951bb 0%, #982ead 66%, #8D17A4 100%);
  background: linear-gradient(to bottom, #a951bb 0%, #982ead 66%, #8D17A4 100%);
  border-bottom: 2px solid #444444;
}
table.blueTable thead th {
  font-size: 15px;
  font-weight: bold;
  color: #FFFFFF;
  border-left: 2px solid #D0E4F5;
}
table.blueTable thead th:first-child {
  border-left: none;
}

table.blueTable tfoot {
  font-size: 14px;
  font-weight: bold;
  color: #FFFFFF;
  background: #D0E4F5;
  background: -moz-linear-gradient(top, #dcebf7 0%, #d4e6f6 66%, #D0E4F5 100%);
  background: -webkit-linear-gradient(top, #dcebf7 0%, #d4e6f6 66%, #D0E4F5 100%);
  background: linear-gradient(to bottom, #dcebf7 0%, #d4e6f6 66%, #D0E4F5 100%);
  border-top: 2px solid #444444;
}
table.blueTable tfoot td {
  font-size: 14px;
}
table.blueTable tfoot .links {
  text-align: right;
}
table.blueTable tfoot .links a{
  display: inline-block;
  background: #1C6EA4;
  color: #FFFFFF;
  padding: 2px 8px;
  border-radius: 5px;
}
</style>
</head>
<title>Ruqqus Guild Stats</title>
<meta charset='utf-8'>
<script src='sorttable.js'></script>
<body>";

function getUA( ) {
	$useragents = array( 
		'Mozilla/5.0 (Windows NT 5.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
		'Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
		'Mozilla/5.0 (iPhone; CPU iPhone OS 13_5_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.1 Mobile/15E148 Safari/604.1',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit/605.1.15 (KHTML, like Gecko)',
		'Mozilla/5.0 (iPad; CPU OS 9_3_5 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Mobile/13G36',
		'Mozilla/5.0 (iPhone; CPU iPhone OS 13_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1 Mobile/15E148 Safari/604.1',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36'
	);
	shuffle( $useragents );
	return( $useragents[0] );
}

function renderTF( $test ) {
	$checkmark = "<span style='color:green'>&#x2713;</span>";
	$x = "<span style='color:red'>&#x2717;</span>";
	if( $test === TRUE ) {
		$rendered = $checkmark;
	} else {
		$rendered = $x;
	}
	return( $rendered );
}
	
function getGuild( $guild ) {
	global $cookies;
	$guild = urlencode( $guild );
	$options  = array( 
	'http' => array(
	'method'=>"GET",
    'header'=>"Accept-language: en\r\n" .
              "Cookie: $cookies\r\n",
	'user_agent' => getUA() ) );
	$context = stream_context_create( $options );
	$rurl = "https://ruqqus.com/api/v1/guild/$guild";
	$guildInfo = json_decode( file_get_contents( $rurl, false, $context ), TRUE );
	return( $guildInfo );
}
$guilds = array();
$guildregex = '/<a class="card-title stretched-link mb-0" href="\/\+(.*?)">/i';
$more = TRUE;
$i = 0;
$time_start = microtime( TRUE );
while( $more === TRUE ) {
	$i++;

	$gurl = "https://ruqqus.com/browse?sort=subs&page=$i";
	$options  = array( 
	'http' => array(
	'method'=>"GET",
    'header'=>"Accept-language: en\r\n" .
              "Cookie: $cookies\r\n",
	'user_agent' => getUA() ) );
	$context = stream_context_create( $options );
	$guildPage = file_get_contents( $gurl, false, $context );
	if( $i == 1 ) {
		preg_match( '/<div class="text-small font-weight-bold">(.*?)<\/div>/i', $guildPage, $mm );
		if( isset( $mm[1] ) ) {
			echo "Logged in as " . $mm[1] . "\n";
			$out .= "<!-- Logged in - expanded results returned -->\n";
		} else {
			echo "**********************************************************\n";
			echo "***** NOT LOGGED IN - RESULTS MAY BE LIMITED! ************\n";
			echo "**********************************************************\n";
			$out .= "<!-- Not logged in - limited results returned! -->\n";
		}
	}
	echo "Processing page $i\n";
	preg_match_all( $guildregex, $guildPage, $matches );
	if( count( $matches[1] ) > 0 ) {
		$more = TRUE;
	} else {
		$more = FALSE;
	}
	foreach( $matches[1] as $m ) {
		echo "  Getting guild $m\n";
		$g = getGuild( $m );
		$guilds[$m] = array();
		$guilds[$m]['name'] = $m;
		$guilds[$m]['mods'] = $g['mods_count'];
		$guilds[$m]['subs'] = $g['subscriber_count'];
		$guilds[$m]['banned'] = $g['is_banned'];
		$guilds[$m]['private'] = $g['is_private'];
		$guilds[$m]['restricted'] = $g['is_restricted'];
		$guilds[$m]['over_18'] = $g['over_18'];
	}
}
$time_stop = microtime( TRUE );
$num = 0;
$out .= '
<table id="guilds" class="blueTable sortable">
<thead>
  <tr>
    <th>Number</th>
    <th>Guild Name</th>
    <th>Mod Count</th>
    <th>Sub Count</th>
	<th>Banned</th>
	<th>Private</th>
	<th>Restricted</th>
	<th>Over 18</th>
  </tr>
  </thead>' . "\n";
foreach( $guilds as $guild ) {

	$num++;
	$name = $guild['name'];
	$mods = $guild['mods'];
	$subs = $guild['subs'];
	$banned = renderTF( $guild['banned'] );
	$private = renderTF( $guild['private'] );
	$restricted = renderTF( $guild['restricted'] );
	$over_18 = renderTF( $guild['over_18'] );
	if( $mods == 0 ) { $mods = "None!"; }
	$uname = urlencode( $name );
	$out .= "	<tr>
	<td>$num</td>
    <td><a href='https://ruqqus.com/+$uname'>+$name</a></td>
    <td>$mods</td>
    <td>$subs</td>
	<td>$banned</td>
	<td>$private</td>
	<td>$restricted</td>
	<td>$over_18</td>
  </tr>\n";
}
$out .= "</table>\n";
$elapsed = $time_stop - $time_start;
$out .= "<!-- Loop completed, $i pages in $elapsed seconds! -->\n";
$out .= "</body></html>\n";
file_put_contents( "index.html", $out );
?>
