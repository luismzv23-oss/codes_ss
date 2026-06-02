<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://serpapi.com/search.json?engine=google&q=mls+schedule&api_key=f0c0a08561154df8d34044b07ae65838a5a77a5d09272a36b7ba0768af5423ce");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);
echo $result;
