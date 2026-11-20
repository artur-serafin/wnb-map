<?php
// upload: wgrywanie i usuwanie trasy (data/WnB.gpx) oraz punktów POI (data/poi.json).
//
// SEKRET: nazwa tego pliku na serwerze (zamień "ZMIEN-MNIE" na własny losowy ciąg, min. 24 znaki,
// inny niż w pliku ingest). Prawdziwa nazwa NIE może trafić do repozytorium.

// ===== USTAWIENIA =====
const DATA_DIR      = __DIR__ . '/data';
const GPX_NAME      = 'WnB.gpx';
const POI_NAME      = 'poi.json';
const MAX_GPX_BYTES = 2000000;           // maksymalny rozmiar pliku GPX
const MAX_POI_BYTES = 200000;            // maksymalny rozmiar pliku poi.json
const MAX_POINTS    = 5000;              // maksymalna liczba punktów trasy
const POI_TYPES     = ['zabka', 'lody']; // musi zgadzać się z typami ikon na stronie
// ======================

date_default_timezone_set('Europe/Warsaw');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');          // adres tej strony zawiera sekret
header('X-Robots-Tag: noindex, nofollow');

function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function distKm(array $a, array $b): float {
    $r = M_PI / 180;
    $dLat = ($b[0] - $a[0]) * $r;
    $dLon = ($b[1] - $a[1]) * $r;
    $x = sin($dLat / 2) ** 2 + cos($a[0] * $r) * cos($b[0] * $r) * sin($dLon / 2) ** 2;
    return 2 * 6371 * asin(sqrt($x));
}

// Sprawdza plik GPX tak, jak czyta go strona: pierwszy ślad (trk), kolejne identyczne punkty pomijamy.
function parseGpx(string $xml): array {
    if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
        return ['error' => 'Plik GPX zawiera niedozwolone elementy (DOCTYPE lub ENTITY).'];
    }
    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $ok = $doc->loadXML($xml, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$ok || !$doc->documentElement || $doc->documentElement->localName !== 'gpx') {
        return ['error' => 'To nie jest poprawny plik GPX.'];
    }
    $trk = $doc->getElementsByTagName('trk')->item(0);
    if (!$trk) {
        return ['error' => 'W pliku GPX nie ma śladu (znacznik trk). Wyeksportuj trasę jako ślad.'];
    }
    $nameEl = $trk->getElementsByTagName('name')->item(0);
    $pts = [];
    foreach ($trk->getElementsByTagName('trkpt') as $p) {
        $lat = $p->getAttribute('lat');
        $lon = $p->getAttribute('lon');
        if (!is_numeric($lat) || !is_numeric($lon) || abs((float) $lat) > 90 || abs((float) $lon) > 180) {
            return ['error' => 'Punkt trasy ma niepoprawne współrzędne.'];
        }
        $pt = [(float) $lat, (float) $lon];
        $last = end($pts);
        if ($last === false || $last[0] !== $pt[0] || $last[1] !== $pt[1]) $pts[] = $pt;
    }
    if (count($pts) < 2) return ['error' => 'Ślad musi mieć co najmniej 2 różne punkty.'];
    if (count($pts) > MAX_POINTS) {
        return ['error' => 'Za dużo punktów trasy (' . count($pts) . ', maksymalnie ' . MAX_POINTS . '). Uprość trasę.'];
    }
    $km = 0.0;
    for ($i = 1; $i < count($pts); $i++) $km += distKm($pts[$i - 1], $pts[$i]);
    return ['error' => null, 'title' => $nameEl ? trim($nameEl->textContent) : '', 'km' => $km, 'points' => count($pts)];
}

function poiPos(array $e): ?array {
    if (isset($e['pos']) && is_string($e['pos'])) {
        if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)\s*$/', $e['pos'], $m)) return null;
        $lat = (float) $m[1]; $lon = (float) $m[2];
    } elseif (isset($e['lat'], $e['lon']) && is_numeric($e['lat']) && is_numeric($e['lon'])) {
        $lat = (float) $e['lat']; $lon = (float) $e['lon'];
    } else {
        return null;
    }
    return (abs($lat) <= 90 && abs($lon) <= 180) ? [$lat, $lon] : null;
}

function poiKm($v): ?float {   // liczba 12.524 albo tekst "12,524"
    if (is_int($v) || is_float($v)) return (float) $v;
    if (is_string($v)) {
        $s = str_replace(',', '.', trim($v));
        if (is_numeric($s)) return (float) $s;
    }
    return null;
}

function parsePoi(string $json, ?float $routeKm): array {
    $json = preg_replace('/^\xEF\xBB\xBF/', '', $json);   // Notatnik z Windows dopisuje znacznik BOM
    $list = json_decode($json, true);
    if (!is_array($list) || !array_is_list($list)) {
        return ['errors' => ['Plik poi.json musi być poprawnym JSON-em w postaci listy: [ {...}, {...} ].'], 'warnings' => [], 'count' => 0, 'content' => ''];
    }
    $errors = []; $warnings = [];
    foreach ($list as $i => $e) {
        $n = $i + 1;
        if (!is_array($e)) { $errors[] = "Wpis $n: nie jest obiektem."; continue; }
        if (!in_array($e['type'] ?? null, POI_TYPES, true)) {
            $errors[] = "Wpis $n: nieznany typ (dozwolone: " . implode(', ', POI_TYPES) . ').';
        }
        if (poiPos($e) === null) $errors[] = "Wpis $n: brak poprawnej pozycji (pole pos albo pola lat i lon).";
        if (isset($e['km']) && $e['km'] !== '') {
            $km = poiKm($e['km']);
            if ($km === null || $km < 0) $errors[] = "Wpis $n: niepoprawny kilometr (km).";
            elseif ($routeKm !== null && $km > $routeKm + 0.05) {
                $warnings[] = "Wpis $n: kilometr " . number_format($km, 3, ',', '') . ' jest większy niż długość trasy (' . number_format($routeKm, 3, ',', '') . ' km).';
            }
        }
    }
    return ['errors' => array_slice($errors, 0, 10), 'warnings' => $warnings, 'count' => count($list), 'content' => $json];
}

function storeFile(string $name, string $content): void {
    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0755, true)) throw new RuntimeException('Nie można utworzyć folderu data.');
    $target = DATA_DIR . '/' . $name;
    $tmp = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($tmp, $content, LOCK_EX) === false) throw new RuntimeException("Nie można zapisać pliku $name.");
    if (is_file($target)) copy($target, $target . '.prev');   // jedna kopia poprzedniej wersji
    if (!rename($tmp, $target)) {                              // podmiana atomowa
        @unlink($tmp);
        throw new RuntimeException("Nie można podmienić pliku $name.");
    }
}

function removeFile(string $name): bool {   // "usunięcie" = przeniesienie do .prev
    $target = DATA_DIR . '/' . $name;
    if (!is_file($target)) return false;
    return rename($target, $target . '.prev');
}

// Wynik: ['none'] gdy pole nie zostało wypełnione, ['ok', treść] albo ['error', komunikat].
function readUpload(string $field, int $maxBytes): array {
    $f = $_FILES[$field] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return ['none'];
    if ($f['error'] !== UPLOAD_ERR_OK) return ['error', "Nie udało się wgrać pliku (kod {$f['error']}); plik może być za duży."];
    if ($f['size'] > $maxBytes) return ['error', 'Plik jest za duży (maksymalnie ' . round($maxBytes / 1000) . ' KB).'];
    $tmp = $f['tmp_name'];
    if (!is_uploaded_file($tmp) && !(PHP_SAPI === 'cli' && is_file($tmp))) return ['error', 'Niepoprawne wgranie pliku.'];
    $data = file_get_contents($tmp);
    return $data === false ? ['error', 'Nie można odczytać pliku.'] : ['ok', $data];
}

function currentState(): string {
    $parts = [];
    $gpx = DATA_DIR . '/' . GPX_NAME;
    if (is_file($gpx)) {
        $g = parseGpx((string) file_get_contents($gpx));
        $when = date('Y-m-d H:i', (int) filemtime($gpx));
        $parts[] = $g['error'] === null
            ? 'Trasa: <b>' . h($g['title'] !== '' ? $g['title'] : '(bez nazwy)') . '</b>, ' . number_format($g['km'], 1, ',', '') . ' km, ' . $g['points'] . ' punktów, wgrana ' . h($when)
            : 'Trasa: plik istnieje, ale jest niepoprawny (' . h($g['error']) . ')';
    } else {
        $parts[] = 'Trasa: brak';
    }
    $poi = DATA_DIR . '/' . POI_NAME;
    if (is_file($poi)) {
        $list = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($poi)), true);
        $parts[] = 'Punkty POI: ' . (is_array($list) ? count($list) : 'plik niepoprawny') . ', wgrane ' . h(date('Y-m-d H:i', (int) filemtime($poi)));
    } else {
        $parts[] = 'Punkty POI: brak';
    }
    return implode('<br>', $parts);
}

$ok = []; $err = []; $warn = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete') {
            $a = removeFile(GPX_NAME);
            $b = removeFile(POI_NAME);
            $ok[] = ($a || $b) ? 'Usunięto trasę i punkty POI (poprzednie wersje zostały zachowane jako .prev).' : 'Nie było czego usuwać.';
        } elseif ($action === 'upload') {
            $gpx = readUpload('gpx', MAX_GPX_BYTES);
            $poi = readUpload('poi', MAX_POI_BYTES);
            if ($gpx[0] === 'none' && $poi[0] === 'none') $err[] = 'Wybierz plik trasy (GPX) i/lub plik punktów (poi.json).';
            if ($gpx[0] === 'error') $err[] = 'GPX: ' . $gpx[1];
            if ($poi[0] === 'error') $err[] = 'POI: ' . $poi[1];

            $g = null; $p = null; $routeKm = null;
            if ($gpx[0] === 'ok') {
                $g = parseGpx($gpx[1]);
                if ($g['error'] !== null) $err[] = 'GPX: ' . $g['error']; else $routeKm = $g['km'];
            } elseif (is_file(DATA_DIR . '/' . GPX_NAME)) {
                $cur = parseGpx((string) file_get_contents(DATA_DIR . '/' . GPX_NAME));
                if ($cur['error'] === null) $routeKm = $cur['km'];
            }
            if ($poi[0] === 'ok') {
                $p = parsePoi($poi[1], $routeKm);
                foreach ($p['errors'] as $e) $err[] = 'POI: ' . $e;
                foreach ($p['warnings'] as $w) $warn[] = $w;
            }

            if (!$err) {   // zapisujemy dopiero, gdy wszystko jest poprawne
                if ($g !== null) {
                    storeFile(GPX_NAME, $gpx[1]);
                    $ok[] = 'Wgrano trasę: ' . ($g['title'] !== '' ? $g['title'] : '(bez nazwy)') . ', ' . number_format($g['km'], 1, ',', '') . ' km, ' . $g['points'] . ' punktów.';
                    if ($p === null && removeFile(POI_NAME)) $ok[] = 'Dotychczasowe punkty POI usunięto, bo nie dołączono nowych.';
                }
                if ($p !== null) {
                    storeFile(POI_NAME, $p['content']);
                    $ok[] = 'Wgrano punkty POI: ' . $p['count'] . '.';
                    if ($g === null && !is_file(DATA_DIR . '/' . GPX_NAME)) $warn[] = 'Nie ma trasy, więc punkty nie będą widoczne na stronie, dopóki jej nie wgrasz.';
                }
            }
        } elseif ($action === '' && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $err[] = 'Żądanie jest za duże. Wgraj mniejsze pliki.';
        } else {
            $err[] = 'Nieznana akcja.';
        }
    } catch (Throwable $e) {
        $err[] = 'Błąd serwera: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
<title>Trasa: wgrywanie</title>
<style>
  body { font: 16px/1.45 system-ui, sans-serif; max-width: 34rem; margin: 0 auto; padding: 1rem; color: #14213d; }
  h1 { font-size: 1.25rem; margin: .2rem 0 1rem; }
  .box { border: 1px solid #ccd; border-radius: 12px; padding: .8rem 1rem; margin: 0 0 1rem; background: #f7f8fb; }
  .ok { border-color: #2a9d8f; background: #e9f6f4; } .err { border-color: #c0392b; background: #fbeceb; } .warn { border-color: #e0a100; background: #fff7df; }
  .box ul { margin: .2rem 0; padding-left: 1.2rem; }
  label { display: block; margin: .8rem 0 .2rem; font-weight: 600; }
  input[type=file] { width: 100%; font: inherit; }
  .hint { font-size: .85rem; opacity: .75; margin: .3rem 0; }
  button { width: 100%; font: inherit; font-weight: 700; padding: .8rem; border: 0; border-radius: 10px; background: #14213d; color: #fff; margin-top: 1rem; }
  button.danger { background: #c0392b; }
</style>
</head>
<body>
<h1>Trasa i punkty: wgrywanie</h1>
<?php if ($ok): ?><div class="box ok"><ul><?php foreach ($ok as $m) echo '<li>' . h($m) . '</li>'; ?></ul></div><?php endif; ?>
<?php if ($warn): ?><div class="box warn"><b>Uwaga</b><ul><?php foreach ($warn as $m) echo '<li>' . h($m) . '</li>'; ?></ul></div><?php endif; ?>
<?php if ($err): ?><div class="box err"><b>Nic nie zostało zmienione</b><ul><?php foreach ($err as $m) echo '<li>' . h($m) . '</li>'; ?></ul></div><?php endif; ?>

<div class="box"><b>Stan obecny</b><br><?php echo currentState(); ?></div>

<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="action" value="upload">
  <label for="gpx">Plik trasy (GPX)</label>
  <input type="file" id="gpx" name="gpx" accept=".gpx,application/gpx+xml,text/xml,application/xml">
  <label for="poi">Punkty POI (poi.json), opcjonalnie</label>
  <input type="file" id="poi" name="poi" accept=".json,application/json">
  <p class="hint">Nowa trasa bez pliku POI usuwa dotychczasowe punkty. Sam plik POI zmienia tylko punkty. Poprzednia wersja każdego pliku zostaje zachowana jako .prev.</p>
  <button type="submit">Wgraj</button>
</form>

<form method="post" onsubmit="return confirm('Usunąć trasę i punkty POI?');">
  <input type="hidden" name="action" value="delete">
  <button type="submit" class="danger">Usuń trasę i punkty</button>
</form>
</body>
</html>
