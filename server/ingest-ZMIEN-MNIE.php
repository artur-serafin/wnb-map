<?php
// ingest: odbiera pozycję WnB z aplikacji Traccar Client i zapisuje live/latest.json.
//
// SEKRET: nazwa tego pliku na serwerze (zamień "ZMIEN-MNIE" na własny losowy ciąg, min. 24 znaki).
// Prawdziwa nazwa pliku NIE może trafić do repozytorium; w repozytorium zostaje ta nazwa wzorcowa.

// ===== USTAWIENIA =====
const TAIL_SECONDS       = 120;    // ogon: ile sekund historii (liczone od ostatniego punktu, nie od "teraz")
const MAX_POINTS         = 80;     // maksymalna liczba punktów w ogonie (zabezpieczenie rozmiaru pliku)
const MAX_FUTURE_SECONDS = 300;    // odrzuć punkty z czasem z przyszłości (błędny zegar telefonu)
const MAX_AGE_SECONDS    = 86400;  // odrzuć punkty starsze niż doba
const MAX_BODY_BYTES     = 4096;   // odrzuć zbyt duże żądania
const LOG_REQUESTS       = false;  // true = zapis surowych żądań do log/requests.log. TYLKO na czas testów;
                                   // log zawiera historię lokalizacji WnB. Po testach ustaw false i usuń log.
// ======================

$dir  = __DIR__ . '/live';
$file = $dir . '/latest.json';

function done(int $code = 200, string $msg = 'OK'): void {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo $msg;
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') done(405, 'Method Not Allowed');
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > MAX_BODY_BYTES) done(413, 'Payload Too Large');

if (LOG_REQUESTS) {
    @file_put_contents(
        __DIR__ . '/log/requests.log',
        json_encode(['time' => date('c'), 'post' => $_POST], JSON_UNESCAPED_UNICODE) . "\n",
        FILE_APPEND | LOCK_EX
    );
}

$lat = filter_var($_POST['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lon = filter_var($_POST['lon'] ?? null, FILTER_VALIDATE_FLOAT);
$ts  = filter_var($_POST['timestamp'] ?? null, FILTER_VALIDATE_INT);
$now = time();

// Żądania bez poprawnej pozycji (np. rejestracja tokenu powiadomień) ignorujemy,
// ale odpowiadamy 200, żeby aplikacja nie ponawiała ich w nieskończoność.
if ($lat === false || $lon === false || $ts === false
    || abs($lat) > 90 || abs($lon) > 180
    || $ts > $now + MAX_FUTURE_SECONDS || $ts < $now - MAX_AGE_SECONDS) {
    done();
}

$num = fn(string $k): ?float => (isset($_POST[$k]) && is_numeric($_POST[$k])) ? (float) $_POST[$k] : null;
$acc = $num('accuracy');
$spd = $num('speed');

if (!is_dir($dir) && !mkdir($dir, 0755, true)) done(500, 'Server error');

$fp = fopen($dir . '/ingest.lock', 'c');
if (!$fp || !flock($fp, LOCK_EX)) done(500, 'Server error');

$state  = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
$tail   = is_array($state['tail'] ?? null) ? $state['tail'] : [];
$lastTs = $tail ? (int) end($tail)[2] : 0;

// Przyjmujemy tylko punkty nowsze od ostatniego (bufor offline może wysłać stare lub zdublowane).
if ($ts > $lastTs) {
    $tail[] = [round($lat, 6), round($lon, 6), $ts];
    $cut    = $ts - TAIL_SECONDS;
    $tail   = array_values(array_filter($tail, fn($p) => $p[2] >= $cut));
    $tail   = array_slice($tail, -MAX_POINTS);

    $out = [
        'last' => [
            'lat'       => round($lat, 6),
            'lon'       => round($lon, 6),
            'ts'        => $ts,
            'acc'       => $acc !== null ? (int) round($acc) : null,
            // Traccar podaje prędkość w węzłach; zapisujemy w km/h
            'speed_kmh' => $spd !== null ? round(max(0.0, $spd) * 1.852, 1) : null,
        ],
        'tail' => $tail,
    ];

    $tmp = $file . '.tmp';
    // Zapis atomowy: czytelnik nigdy nie zobaczy pliku w połowie zapisu.
    if (file_put_contents($tmp, json_encode($out)) === false || !rename($tmp, $file)) {
        flock($fp, LOCK_UN);
        fclose($fp);
        done(500, 'Server error');   // aplikacja spróbuje ponownie (bufor offline)
    }
}

flock($fp, LOCK_UN);
fclose($fp);
done();
