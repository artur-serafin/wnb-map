<?php
declare(strict_types=1);

// Zmień nazwę pliku na generate-routes-<losowy-sekret>.php przed wgraniem.
// Uruchamiaj przez HTTPS i usuń skrypt po wygenerowaniu indeksu.

const MAX_GPX_BYTES = 2000000;
const MAX_POINTS = 5000;

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function distanceKm(array $a, array $b): float {
    $r = M_PI / 180;
    $dLat = ($b[0] - $a[0]) * $r;
    $dLon = ($b[1] - $a[1]) * $r;
    $x = sin($dLat / 2) ** 2 + cos($a[0] * $r) * cos($b[0] * $r) * sin($dLon / 2) ** 2;
    return 2 * 6371 * asin(sqrt($x));
}

function parseGpx(string $xml): array {
    if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
        return ['error' => 'zawiera niedozwolone DOCTYPE lub ENTITY'];
    }

    $previousErrorMode = libxml_use_internal_errors(true);
    $document = new DOMDocument();
    $loaded = $document->loadXML($xml, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrorMode);

    if (!$loaded || !$document->documentElement || $document->documentElement->localName !== 'gpx') {
        return ['error' => 'nie jest poprawnym plikiem GPX'];
    }

    $track = $document->getElementsByTagName('trk')->item(0);
    if (!$track) return ['error' => 'nie zawiera śladu trk'];

    $name = $track->getElementsByTagName('name')->item(0);
    $points = [];
    foreach ($track->getElementsByTagName('trkpt') as $point) {
        $lat = $point->getAttribute('lat');
        $lon = $point->getAttribute('lon');
        if (!is_numeric($lat) || !is_numeric($lon) ||
            abs((float) $lat) > 90 || abs((float) $lon) > 180) {
            return ['error' => 'zawiera niepoprawne współrzędne'];
        }

        $coordinates = [(float) $lat, (float) $lon];
        $last = end($points);
        if ($last === false || $last[0] !== $coordinates[0] || $last[1] !== $coordinates[1]) {
            $points[] = $coordinates;
        }
    }

    if (count($points) < 2) return ['error' => 'musi zawierać co najmniej 2 różne punkty'];
    if (count($points) > MAX_POINTS) return ['error' => 'przekracza limit ' . MAX_POINTS . ' punktów'];

    $distance = 0.0;
    for ($i = 1; $i < count($points); $i++) {
        $distance += distanceKm($points[$i - 1], $points[$i]);
    }

    return [
        'error' => null,
        'title' => $name ? trim($name->textContent) : '',
        'distanceKm' => round($distance, 1)
    ];
}

function scanRoutes(): array {
    $files = glob(__DIR__ . '/*.gpx');
    if ($files === false || !$files) {
        return ['routes' => [], 'errors' => ['Nie znaleziono plików GPX w tym folderze.']];
    }
    sort($files, SORT_STRING);

    $routes = [];
    $errors = [];
    foreach ($files as $path) {
        $file = basename($path);
        if (!preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.gpx\z/', $file) || is_link($path)) {
            $errors[] = $file . ': nieobsługiwana nazwa pliku.';
            continue;
        }

        $size = filesize($path);
        if ($size === false || $size > MAX_GPX_BYTES) {
            $errors[] = $file . ': plik przekracza limit 2 MB albo nie można odczytać jego rozmiaru.';
            continue;
        }

        $xml = file_get_contents($path);
        if ($xml === false) {
            $errors[] = $file . ': nie można odczytać pliku.';
            continue;
        }

        $parsed = parseGpx($xml);
        if ($parsed['error'] !== null) {
            $errors[] = $file . ': ' . $parsed['error'] . '.';
            continue;
        }

        $routes[] = [
            'file' => $file,
            'title' => $parsed['title'] !== '' ? $parsed['title'] : '(bez nazwy)',
            'distanceKm' => $parsed['distanceKm']
        ];
    }

    return ['routes' => $routes, 'errors' => $errors];
}

function writeRoutes(array $routes): ?string {
    $json = json_encode($routes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) return 'Nie można zakodować indeksu JSON: ' . json_last_error_msg();
    $json .= "\n";

    $target = __DIR__ . '/routes.json';
    $temporary = __DIR__ . '/routes.' . bin2hex(random_bytes(8)) . '.tmp';
    $written = file_put_contents($temporary, $json, LOCK_EX);
    if ($written !== strlen($json)) {
        @unlink($temporary);
        return 'Nie udało się zapisać pliku tymczasowego.';
    }
    if (!rename($temporary, $target)) {
        @unlink($temporary);
        return 'Nie udało się podmienić routes.json.';
    }
    return null;
}

function renderPage(string $heading, string $message, array $errors = [], bool $showForm = false): void {
    echo '<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . h($heading) . '</title><style>';
    echo 'body{font:16px/1.5 system-ui,sans-serif;max-width:48rem;margin:2rem auto;padding:0 1rem;color:#14213d}';
    echo 'button{padding:.7rem 1rem;border:0;border-radius:.5rem;background:#ffb703;color:#14213d;font:inherit;font-weight:700}';
    echo 'li{margin:.4rem 0;color:#9b2c2c}code{overflow-wrap:anywhere}';
    echo '</style></head><body><h1>' . h($heading) . '</h1><p>' . h($message) . '</p>';
    if ($errors) {
        echo '<ul>';
        foreach ($errors as $error) echo '<li>' . h($error) . '</li>';
        echo '</ul>';
    }
    if ($showForm) {
        echo '<form method="post" onsubmit="return confirm(\'Wygenerować routes.json ponownie?\')">';
        echo '<input type="hidden" name="confirm" value="yes"><button type="submit">Wygeneruj routes.json</button></form>';
    }
    echo '</body></html>';
}

$filename = basename(__FILE__);
if (!preg_match('/\Agenerate-routes-([A-Za-z0-9_-]{24,})\.php\z/', $filename)) {
    http_response_code(404);
    exit('Zmień nazwę skryptu, dodając po generate-routes- losowy sekret o długości co najmniej 24 znaków.');
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'GET') {
    renderPage(
        'Generator indeksu tras',
        'Po potwierdzeniu skrypt sprawdzi wszystkie pliki .gpx w tym folderze i zastąpi routes.json. Jeśli którykolwiek plik będzie niepoprawny, indeks nie zostanie zmieniony.',
        [],
        true
    );
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    renderPage('Niedozwolona metoda', 'Użyj formularza na stronie generatora.');
    exit;
}

if (!isset($_POST['confirm']) || !is_string($_POST['confirm']) || $_POST['confirm'] !== 'yes') {
    http_response_code(400);
    renderPage('Brak potwierdzenia', 'Nie zmieniono routes.json. Otwórz formularz i potwierdź generowanie.');
    exit;
}

$result = scanRoutes();
if ($result['errors']) {
    http_response_code(422);
    renderPage('Nie wygenerowano indeksu', 'Popraw poniższe pliki. Istniejący routes.json pozostał bez zmian.', $result['errors']);
    exit;
}

$error = writeRoutes($result['routes']);
if ($error !== null) {
    http_response_code(500);
    renderPage('Nie zapisano indeksu', $error);
    exit;
}

renderPage('Indeks tras wygenerowany', 'Zapisano ' . count($result['routes']) . ' tras w routes.json. Usuń skrypt generatora z serwera.');
