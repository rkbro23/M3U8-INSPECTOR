<?php
header('Content-Type: application/json');

$url = $_GET['url'] ?? '';
$auth = $_GET['auth'] ?? '';
if (!$url) exit(json_encode(['error' => 'No URL provided.']));

$headers = [
    "User-Agent: rkboi"
];
if ($auth) {
    $headers[] = "Authorization: $auth";
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if (!$response) exit(json_encode(['error' => 'Failed to fetch: ' . $err]));

$lines = explode("\n", $response);
$streams = [];
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], '#EXT-X-STREAM-INF:') !== false) {
        $info = $lines[$i];
        $urlLine = trim($lines[$i+1] ?? '');
        preg_match('/RESOLUTION=(\d+x\d+)/', $info, $res);
        preg_match('/BANDWIDTH=(\d+)/', $info, $bw);
        preg_match('/CODECS="([^"]+)"/', $info, $codec);
        $streams[] = [
            'res' => $res[1] ?? 'N/A',
            'bw' => $bw[1] ?? '0',
            'codec' => $codec[1] ?? 'N/A',
            'url' => $urlLine
        ];
    }
}

// If no variants found, treat as direct stream (show segments)
if (empty($streams)) {
    $segments = [];
    foreach ($lines as $line) {
        if (trim($line) && !str_starts_with($line, '#')) {
            $segments[] = $line;
        }
    }
    echo json_encode(['error' => 'No master variants found. Showing ' . count($segments) . ' segments.', 'segments' => $segments]);
} else {
    echo json_encode(['streams' => $streams]);
}
