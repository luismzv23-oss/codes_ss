<?php
$apiKey = 'f0c0a08561154df8d34044b07ae65838a5a77a5d09272a36b7ba0768af5423ce';
$si = 'AL3DRZGZ2vwK6UBPF13OpsdyzVxaN6Frqc7KM1Wv9CjAqfP7aFROfL377Ab1oyZjUNj8DvoKWpUUe9_zyHQz7FQ4kz-129CeEL_MX5u-qi0UjZTuNu2caZO-xdtfOy9vGWuHFTTJUaZQ_OdQqxJIPz7ivsgLJ3afGU84iKCZ3KamGEtTAHoNKBWj7jWi4mSImOMhtkK2pSZKhUiwm-lGdXronQaWIFCG6mBuHVGYaw8pJIPw5Kn2JwirG5KAIq2efxnNRdpt9DNET8Hy-eeilQixbb3euiNw4w==';
$query = urlencode('Amistosos Internacionales Hoy');
$url = "https://serpapi.com/search.json?engine=google&q={$query}&api_key={$apiKey}&hl=es&gl=ar&si=" . urlencode($si);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response !== false) {
    file_put_contents(__DIR__ . '/serpapi_si_result.json', $response);
    echo "SUCCESS: Fetched and wrote " . strlen($response) . " bytes to serpapi_si_result.json.";
} else {
    echo "ERROR: HTTP code {$httpCode}, response: " . substr($response, 0, 500);
}
