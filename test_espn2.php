<?php
$url = "https://site.api.espn.com/apis/site/v2/sports/soccer/all/scoreboard";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (isset($data['events'])) {
    echo "Found " . count($data['events']) . " events.\n";
    if (count($data['events']) > 0) {
        $ev = $data['events'][0];
        echo "First event: " . $ev['name'] . " at " . $ev['date'] . "\n";
    }
} else {
    echo "No events found.\n";
    echo substr($response, 0, 500);
}
