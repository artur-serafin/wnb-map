<?php
// update: weryfikacja sekretu i zapis punktów POI dla trybu edycji na mapie.
//
// SEKRET: nazwa pliku ustawiana wyłącznie na serwerze, np. update-LOSOWY-CIAG.php.
// Prawdziwa nazwa i sekret NIE mogą trafić do repozytorium.

const DATA_DIR = __DIR__ . '/data';
const POI_NAME = 'poi.json';
const MAX_BODY_BYTES = 200000;
const POI_TYPES = ['zabka', 'lody', 'default'];

date_default_timezone_set('Europe/Warsaw');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') reply(405, ['error' => 'Method Not Allowed']);
$length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($length > MAX_BODY_BYTES) reply(413, ['error' => 'Payload Too Large']);

$raw = file_get_contents('php://input');
if ($raw === false) reply(400, ['error' => 'Nie można odczytać żądania']);
$body = json_decode($raw, true);
if (!is_array($body)) reply(400, ['error' => 'Niepoprawny JSON']);

$scriptName = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (!preg_match('/^update-(.+)\.php$/', $scriptName, $nameMatch)) {
    reply(500, ['error' => 'Niepoprawna nazwa endpointu']);
}
$expectedSecret = rawurldecode($nameMatch[1]);
$secret = isset($body['secret']) ? (string) $body['secret'] : '';
if (!preg_match('/^[A-Za-z0-9_-]{1,128}$/', $expectedSecret) ||
    !preg_match('/^[A-Za-z0-9_-]{1,128}$/', $secret) ||
    !hash_equals($expectedSecret, $secret)) {
    reply(401, ['error' => 'Niepoprawny sekret']);
}

$action = (string) ($body['action'] ?? '');
if ($action === 'verify') reply(200, ['ok' => true]);
if ($action !== 'save') reply(400, ['error' => 'Nieznana akcja']);

if (!isset($body['pois']) || !is_array($body['pois']) || !array_is_list($body['pois'])) {
    reply(422, ['error' => 'POI musi być listą']);
}

$pois = [];
foreach ($body['pois'] as $i => $poi) {
    $n = $i + 1;
    if (!is_array($poi)) reply(422, ['error' => "Wpis $n: nie jest obiektem"]);

    $lat = null;
    $lon = null;
    if (isset($poi['lat'], $poi['lon']) && is_numeric($poi['lat']) && is_numeric($poi['lon'])) {
        $lat = (float) $poi['lat'];
        $lon = (float) $poi['lon'];
    } elseif (isset($poi['pos']) && is_string($poi['pos']) &&
              preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)\s*$/', $poi['pos'], $m)) {
        $lat = (float) $m[1];
        $lon = (float) $m[2];
    }
    if ($lat === null || $lon === null || abs($lat) > 90 || abs($lon) > 180) {
        reply(422, ['error' => "Wpis $n: brak poprawnej pozycji"]);
    }

    $type = in_array($poi['type'] ?? null, POI_TYPES, true) ? $poi['type'] : 'default';
    $km = null;
    if (array_key_exists('km', $poi) && $poi['km'] !== null && $poi['km'] !== '') {
        if (is_int($poi['km']) || is_float($poi['km'])) {
            $km = (float) $poi['km'];
        } elseif (is_string($poi['km'])) {
            $text = str_replace(',', '.', trim($poi['km']));
            if (!is_numeric($text)) reply(422, ['error' => "Wpis $n: niepoprawny kilometr"]);
            $km = (float) $text;
        } else {
            reply(422, ['error' => "Wpis $n: niepoprawny kilometr"]);
        }
        if ($km < 0) reply(422, ['error' => "Wpis $n: niepoprawny kilometr"]);
    }

    $out = [
        'type' => $type,
        'name' => is_string($poi['name'] ?? null) ? trim($poi['name']) : '',
        'lat' => round($lat, 7),
        'lon' => round($lon, 7),
        'order' => $n,
    ];
    if ($km !== null) $out['km'] = $km;
    $pois[] = $out;
}

if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0755, true)) reply(500, ['error' => 'Server error']);

$target = DATA_DIR . '/' . POI_NAME;
$lock = $target . '.lock';
$fp = fopen($lock, 'c');
if (!$fp || !flock($fp, LOCK_EX)) reply(500, ['error' => 'Server error']);

$content = json_encode($pois, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$tmp = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';
if (file_put_contents($tmp, $content, LOCK_EX) === false) {
    flock($fp, LOCK_UN); fclose($fp); @unlink($tmp);
    reply(500, ['error' => 'Server error']);
}
if (is_file($target)) {
    if (!copy($target, $target . '.prev')) {
        flock($fp, LOCK_UN); fclose($fp); @unlink($tmp);
        reply(500, ['error' => 'Server error']);
    }
}
if (!rename($tmp, $target)) {
    flock($fp, LOCK_UN); fclose($fp); @unlink($tmp);
    reply(500, ['error' => 'Server error']);
}

flock($fp, LOCK_UN);
fclose($fp);
reply(200, ['ok' => true, 'pois' => $pois]);
