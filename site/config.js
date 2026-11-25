// ============================================================
//  config.js: wszystkie ustawienia strony w jednym miejscu.
//  Edytujesz tylko wartości po dwukropku. Po zmianie wgraj plik
//  na serwer (czasem trzeba odświeżyć stronę mocniej, np. zamknąć
//  i otworzyć kartę, żeby zobaczyć zmianę).
//  Progi podajemy w sekundach, czasy statusów/animacji w milisekundach, prędkości w km/h.
//  Teksty (PL/EN) są w osobnych plikach: lang/pl.json i lang/en.json.
// ============================================================
const CONFIG = {

  // ---------- Pliki ----------
  GPX_URL: 'data/WnB.gpx',          // plik z trasą (ścieżka względem strony)
  POI_URL: 'data/poi.json',         // plik z punktami POI
  UPDATE_URL_TEMPLATE: 'update-{secret}.php', // szablon; {secret} zastępuje wartość ?s=SEKRET
  NO_ROUTE_TITLE: 'Wtorek na Bulwarach', // tytuł trasy, gdy nie ma pliku GPX (np. w trakcie tworzenia trasy)
  IDLE_USE_SEASONAL_NOTICE: false,    // true = komunikat dla okresu zimowego
  LIVE_URL: 'live/latest.json',     // plik z pozycją WnB, tworzony przez skrypt ingest
  LANG_DIR: 'lang/',                // folder z plikami językowymi
  STATUS_FADE_MS: 250,              // czas pojawiania i zanikania statusu (ms)
  STATUS_TEMPORARY_MS: 2000,        // czas widoczności statusu tymczasowego (ms)
  SPLASH_HOLD_MS: 1500,              // ile ms ekran startowy pozostaje na pełnym ekranie
  SPLASH_FADE_MS: 1000,             // czas zanikania ekranu startowego
  // UWAGA: długość ogona (ile sekund historii) ustawiasz po stronie serwera,
  // w skrypcie ingest-....php (stała TAIL_SECONDS), nie tutaj.

  // ---------- Menu: odnośniki (pusty adres ukrywa dany odnośnik) ----------
  LINKS: {
    messenger: 'https://m.me/cm/AbYKXGHWim8ADPrD/',   // grupa / społeczność na Messengerze
    youtube: 'https://www.youtube.com/@wtoreknabulwarach',     // kanał na YouTube
    facebook: 'https://www.facebook.com/wtoreknabulwarach',    // profil na Facebooku
    instagram: 'https://www.instagram.com/wtoreknabulwarach/'    // profil na Instagramie
  },

  // ---------- Języki ----------
  LANGS: { pl: 'Polski', en: 'English' },  // dostępne języki: kod pliku -> nazwa na przycisku
  DEFAULT_LANG: 'en',               // język, gdy telefon ma inny język niż powyższe
                                    // (gdy telefon jest po polsku, strona startuje po polsku)
									
  // ----------- POI --------------
  POI_ICON_SIZE_PX: 34,				// rozmiar ikony POI
  POI_TAIL_PX: 35,                  // długość ogona znacznika POI (px): odległość kółka od wskazywanego punktu
  SHOW_ETA: true,                   // etykiety ETA przy punktach i mecie (false = ukryj)
  ROUTE_MAX_OFFSET_METERS: 20,      // dalej niż tyle metrów od trasy WnB uznajemy za "poza trasą" (bez ETA)
  POI_AT_METERS: 100,               // blisko punktu = w odległości do tylu metrów
  ETA_ROUND_MINUTES: 5,              // ETA powyżej 10 min zaokrąglamy do tylu minut (1 = bez zaokrąglania)
  SHOW_GLOW: true,                  // false = wyłącz poświatę
  GLOW_SIZE_PX: 220,                // wielkość poświaty (px)
  GLOW_OPACITY: 0.8,                // intensywność przy samym brzegu (0 do 1)
  GLOW_VISIBLE_MARGIN_PX: 28,       // WnB bliżej brzegu ekranu niż tyle px liczymy jako "poza ekranem"
  GLOW_SHOW_DISTANCE: false,         // true = obok poświaty napis z odległością od Uczestnika
  
  // ---------- Kierunek ruchu Uczestnika (wiązka przy niebieskiej kropce) ----------
  SHOW_HEADING: true,               // false = bez wskaźnika kierunku ruchu
  HEADING_MIN_SPEED_MS: 0.5,        // wskaźnik widać, gdy Uczestnik porusza się szybciej niż tyle m/s (1,5 m/s = 5,4 km/h)
  HEADING_HOLD_SECONDS: 3,          // po tylu s bez ważnego kierunku wskaźnik znika (GPS czasem chwilowo nie podaje kierunku)
  HEADING_SMOOTHING: 0.5,            // wygładzanie obrotu: 0.1 = bardzo gładko, 1 = bez wygładzania
  SHOW_COMPASS: true,               // gdy Uczestnik stoi, wiązka pokazuje kierunek patrzenia z kompasu telefonu (false = wyłącz)
  COMPASS_DECLINATION_DEG: 7,        // poprawka północy magnetycznej na geograficzną: Warszawa ok. +7 stopni (przybliżenie; 0 = bez poprawki)
  
  // ---------- Nazwa ----------
  ORGANIZER_NAME: 'WnB',            // jak strona nazywa osobę udostępniającą pozycję

  // ---------- Odświeżanie ----------
  POLL_SECONDS: 8,                  // co ile sekund strona pobiera pozycję WnB z serwera
                                    // (mniej = świeższe dane, ale większy ruch na serwerze)
  FAIL_AFTER_POLLS: 2,              // ile nieudanych pobrań z rzędu, zanim pojawi się
                                    // "Brak połączenia z serwerem" (1 = od razu po pierwszym błędzie)

  // ---------- Progi czasowe: ile sekund minęło od ostatniej pozycji WnB ----------
  FRESH_SECONDS: 15,                // do tylu s dymek pokazuje tylko prędkość;
                                    // powyżej dodaje "X sekund temu"
  STOP_AFTER_SECONDS: 90,           // tyle s bez nowych danych (gdy WnB jechał) = "Prawdopodobny postój"
  LOST_AFTER_SECONDS: 480,          // tyle s bez danych znacznik i przycisk WnB są szare; 480 = 8 min
  END_AFTER_SECONDS: 900,           // tyle s bez danych znacznik znika; 900 = 15 min
  STILL_SPEED_KMH: 2,               // poniżej tej prędkości (albo gdy brak prędkości) = "Postój"

  // ---------- Mapa ----------
  TILE_URL: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', // źródło kafelków mapy
  TILE_ATTRIBUTION: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors', //kafelki od OSM

  //TILE_URL: 'https://basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png?key=[carto-key]',  // kafelki mapy Carto Voyager)
  //TILE_ATTRIBUTION: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>, © <a href="https://carto.com/attributions">CARTO</a>',  // kafelki od CARTO
  MAX_ZOOM: 19,                     // największe dozwolone przybliżenie
  FOCUS_ZOOM: 16,                   // przybliżenie po naciśnięciu przycisku "Moja pozycja" / "WnB"
  FIT_PADDING: 20,                  // margines (px) wokół trasy przy "Cała trasa"

  // ---------- Lokalizacja Uczestnika ----------
  GEO_HIGH_ACCURACY: true,          // true = dokładny GPS (więcej baterii), false = oszczędniej
  GEO_MAX_AGE_SECONDS: 5,           // jak stare dane o pozycji telefon może ponownie użyć
  GEO_TIMEOUT_SECONDS: 20,          // po ilu s bez pozycji telefon zgłasza błąd (strona próbuje dalej)

  // ---------- Wygląd ----------
  COLORS: {
    route: '#d1495b',               // linia trasy
    start: '#2a9d8f',               // punkt startu
    organizer: '#ffb703',           // znacznik WnB i jego ogon
    organizerLost: '#9a9a9a',       // znacznik WnB przy braku sygnału
    outline: '#14213d',             // ciemny obrys znacznika i ogona
    me: '#1d6fdc',                   // pozycja Uczestnika
	arrow: '#8f1d2c'                // strzałki kierunku jazdy na trasie
  },
  ROUTE_WIDTH: 4,                   // grubość linii trasy (px)
  TAIL_WIDTH: 4,                    // grubość ogona WnB (px)
  MARKER_RADIUS: 9,                  // wielkość znacznika WnB (px)
  SHOW_ARROWS: true,                // strzałki kierunku jazdy wzdłuż trasy (false = ukryj)
  ARROW_SPACING_PX: 110,            // odstęp między strzałkami na ekranie (px; większy = rzadziej)
  ARROW_SIZE_PX: 20,                // wielkość strzałki (px)
  LABELS_MIN_ZOOM: 16               // od jakiego przybliżenia widać napisy "Start" i "Meta"
};
