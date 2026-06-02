<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.football-data.org/v4/competitions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Auth-Token: 99a866451b0746d3a903f9564cab1b9b'));
$result = curl_exec($ch);
curl_close($ch);
echo $result;
