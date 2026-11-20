# TESTING: wyniki testów i plan testów

Stan na 2026-11-20. Środowisko testowe: folder `wnb-test` na OVH Personal. Skróty: **K6** = test obciążeniowy (`tools/`), **SYM** = symulator przejazdu (`ride.js`), **TEL** = ręczny test Artura na iPhonie, **PHP** = test skryptu serwerowego w wierszu poleceń, **MAT** = test logiki na przykładach (kod uruchomiony poza przeglądarką).

## 1. Zrobione testy i wyniki

### 1.1 Traccar Client w terenie (iPhone)

| Test | Wynik |
|---|---|
| Bez progu dystansu (2 h 36 min, High) | punkty co 300 s (32 punkty) niezależnie od ruchu: bezużyteczne; wniosek: ustawić Distance. Bateria 75% → 45% (łącznie z innym użyciem telefonu) |
| Distance 25 m, 15 min jazdy | 129 punktów, odstępy zwykle 6–8 s (zakres 5–16 s), ok. 25–32 m między punktami, dokładność 6–14 m, opóźnienie dostarczenia 0–1 s |
| Distance 25 m, 2 h 14 min (jazda, światła, lunch) | 614 punktów, 89% odstępów ≤ 10 s, 17 przerw 30–100 s na światłach (najdłuższa w ruchu ok. 103 s), opóźnienie ≤ 5 s, praca w tle bez przerw, bateria 60% → 45% (ok. 7 pp/h; iOS podaje poziom co 5 pp) |
| Postój (lunch ok. 30 min, krótszy ok. 5 min) | sygnał co ok. 301–304 s (heartbeat domyślny), bez prędkości; pozycja stała w granicach 3–9 m |

### 1.2 Hosting (K6, OVH Personal)

| Test | Wynik |
|---|---|
| Żądanie zakresowe (Range) | 206 Partial Content, `Content-Range` poprawny |
| Nagłówki `latest.json` | `application/json`, `no-cache, must-revalidate`, `nosniff` |
| `smoke.js` (10 użytkowników, 30 s) | 0 błędów, średnio 83 ms |
| `load.js` (150–200 odczytów co 7–9 s + zapis co 5 s, 6 min) | 6970 żądań, 0 błędów HTTP; odczyt p95 53 ms, zapis p95 77 ms, max 176 ms; 1 niepoprawny odczyt na 6898 |
| `burst.js` (200 wejść w 20 s + 200 jednocześnie; strona, `config.js`, GPX) | 1203 żądania, 0 błędów, p95 187 ms, max 364 ms; 200 jednoczesnych wejść obsłużone w ok. 1,4 s |

Niepoprawny odczyt (jeden na ok. 6900): hipoteza to wyścig odczytu z podmianą pliku na dysku sieciowym; strona ignoruje pojedynczy błąd (komunikat dopiero po 2 z rzędu).

### 1.3 Skrypty serwerowe (PHP w wierszu poleceń)

- **`ingest`:** GET odrzucany (405); żądanie bez pozycji ignorowane; poprawny punkt zapisany (9 węzłów = 16,7 km/h); starszy punkt, punkt z przyszłości (+1 h) i szerokość 999 ignorowane; po 30 punktach co 5 s ogon ma 25 punktów i rozpiętość 120 s; domyślnie żaden log nie powstaje.
- **`upload`**, 24 scenariusze: GET; sam GPX; GPX + POI; nowy GPX usuwa POI (z `.prev`); sam POI z BOM (BOM usuwany); POI z kilometrem poza trasą (tylko ostrzeżenie); odrzucenia: tekst zamiast XML, brak `trk`, DOCTYPE, jeden punkt, 5001 punktów, złe współrzędne, plik 2,1 MB, zły typ/pozycja/JSON/nie lista w POI, dobry GPX ze złym POI; brak plików; zbyt duże żądanie; nieznana akcja; usunięcie i usunięcie przy braku plików; tytuł z kodem HTML (poprawnie escapowany). Po wszystkich odrzuconych wgraniach pliki były **identyczne jak przed nimi**; brak pozostawionych plików tymczasowych.
- Na serwerze: **TEL** potwierdza wgrywanie, usuwanie i stan bez trasy.

### 1.4 Logika strony

| Obszar | Test | Wynik |
|---|---|---|
| Postęp WnB na trasie | MAT: symulacja całej trasy 31 km, szum GPS ok. 5 m, ogon 2 min (1484 próbki) | średni błąd 4 m, maks. 57 m, brak pomyłek na 3 skrzyżowaniach; start i meta (39 m od siebie) rozróżnione także z jednego punktu |
| ETA i stany punktów | MAT + SYM | stany: ETA, „WnB jest tutaj", „WnB stoi", wstrzymanie po minięciu punktu, utrata sygnału, zaokrąglanie; potwierdzone też **TEL** |
| Kilometr po dotknięciu trasy | MAT na prawdziwej geometrii | zygzak ze zrzutu daje jedną wartość przy zoom 12–18; 3 skrzyżowania dają dwie wartości od zoom 16 (skrzyżowanie 1 i 3 już od 12) |
| Znaczniki POI | MAT | kółko ustawia się poza trasą (trasa pionowa, pozioma, ukośna, brak trasy) |
| Poświata | MAT | kierunek i krawędź dla WnB z prawej, z góry, w rogach, w środku ekranu |
| Strzałki | MAT | rozmieszczenie i kierunek na odcinkach prostych i z zakrętem |
| Kierunek Uczestnika, kompas | MAT | wygładzanie kąta (w tym przejście przez północ), wzory kompasu dla telefonu płasko i pionowo |
| Stan „bez trasy" | MAT | 404 daje ciszę, 500 daje komunikat błędu |
| Interfejs | **TEL** (Artur) | menu i języki, strzałki, start/meta, ikony POI, kilometr, poświata, kierunek ruchu i kompas, brak trasy, wgrywanie: potwierdzone jako działające |

## 2. Lista testów akceptacyjnych (E1)

Wykonaj najpierw w `wnb-test`, a po przeniesieniu na adres docelowy powtórz skróconą wersję (punkty oznaczone ★).

**Urządzenia:** iPhone (Safari) ✔ podstawowo, Android (Chrome) ☐, komputer ☐.

Szybkie sprawdzenie progów czasowych bez czekania: w `config.js` ustaw tymczasowo `STOP_AFTER_SECONDS: 20`, `LOST_AFTER_SECONDS: 40`, `END_AFTER_SECONDS: 60`, a po teście przywróć 90, 480, 900.

| # | Scenariusz | iPhone | Android | Komputer |
|---|---|---|---|---|
| 1 ★ | Otwarcie strony: tytuł z długością, trasa, strzałki, start/meta, punkty POI; widać w czasie poniżej 5 s | ☐ | ☐ | ☐ |
| 2 ★ | Zgoda na lokalizację: niebieska kropka, wiązka kierunku; odmowa: komunikat, mapa i trasa działają | ☐ | ☐ | ☐ |
| 3 ★ | SYM: WnB jedzie, ogon, dymek z prędkością, przesuwanie bez odświeżania strony | ☐ | ☐ | ☐ |
| 4 | SYM z postojem: „Postój", „Prawdopodobny postój", „Brak sygnału", „Udostępnianie zakończone" (progi skrócone) | ☐ | ☐ | ☐ |
| 5 | Brak danych od WnB: komunikat „Brak danych od WnB" przy świeżym pliku bez pozycji | ☐ | ☐ | ☐ |
| 6 | Tryb samolotowy 20 s: komunikat o braku połączenia po drugim błędzie, powrót sieci znosi komunikat | ☐ | ☐ | ☐ |
| 7 | POI i ETA (SYM z postojem na punkcie): ETA, „WnB jest tutaj", ETA do kolejnego po odjeździe, ETA do mety | ☐ | ☐ | ☐ |
| 8 | Dotknięcie trasy daje kilometr, dotknięcie punktu daje nazwę i kilometr | ☐ | ☐ | ☐ |
| 9 | Poświata w stronę WnB poza ekranem i jej zanik | ☐ | ☐ | ☐ |
| 10 ★ | Podmiana trasy przez `upload` i odświeżenie strony: nowy tytuł i POI | ☐ | ☐ | ☐ |
| 11 | Usunięcie trasy: sama pozycja WnB, bez błędów, bez przycisku „Cała trasa" | ☐ | ☐ | ☐ |
| 12 | Języki PL/EN: przełączanie w menu, zapamiętanie po odświeżeniu, język telefonu przy pierwszej wizycie | ☐ | ☐ | ☐ |
| 13 | Menu: tekst o prywatności czytelny w obu językach | ☐ | ☐ | ☐ |
| 14 | Kompas po zatrzymaniu: iPhone prosi o zgodę po dotknięciu „Moja pozycja"; Android działa bez pytania | ✔ | ☐ | — |
| 15 | Po wygaszeniu ekranu i powrocie do strony odświeża się sama | ☐ | ☐ | — |
| 16 ★ | Atrybucja OpenStreetMap widoczna i klikalna | ☐ | ☐ | ☐ |
| 17 | Wolne łącze (np. ograniczenie sieci w narzędziach przeglądarki): strona ładuje się i działa | — | — | ☐ |

## 3. Plan testu terenowego (E2)

Cel: sprawdzić to, czego nie da się zasymulować.

**Przed:** w `ingest` ustaw `LOG_REQUESTS = true` (zapis surowych pozycji); wgraj trasę i punkty z prawdziwymi kilometrami; naładuj telefon do 100% i zanotuj poziom.

**W trakcie i po przejeździe zapisz:**
- poziom baterii na początku, co godzinę i na końcu (łączne udostępnianie ok. 3 h),
- czy znacznik ani razu nie zniknął (zasięg, tło, filmowanie, rozmowy),
- przy każdym punkcie POI: **prognozowane ETA w chwili odjazdu z poprzedniego punktu i faktyczny czas dotarcia**,
- zachowanie stanów (90 s, 480 s) na światłach i postojach,
- czy kierunek z GPS i kompas na iPhonie działają i czy zgadzają się z rzeczywistością,
- uwagi Uczestników (czytelność na słońcu, zrozumiałość komunikatów).

**Po:** pobierz `log/requests.log` i wyślij do analizy (odstępy, luki, dokładność, heartbeaty, ocena progów); wyłącz `LOG_REQUESTS` i usuń log; wnioski wpisz do `DECISIONS.md`.

## 4. Jak powtórzyć testy (z folderu `tools/`)

```
k6 run smoke.js
k6 run -e INGEST_URL="https://…/ingest-<SEKRET>.php" load.js
k6 run burst.js
k6 run -e INGEST_URL="https://…/ingest-<SEKRET>.php" -e START_KM=9 -e STOPS="11.064:90" ride.js
```

`load.js` i `ride.js` zapisują sztuczne pozycje do `latest.json` (nie uruchamiaj ich podczas prawdziwego przejazdu); po `load.js` usuń `log/requests.log`, jeśli logowanie było włączone. Adresy w skryptach wskazują środowisko testowe: po przeniesieniu na adres docelowy zmień stałą `BASE`/`URL` na początku pliku.

## 5. Niezweryfikowane

Android i komputer, zachowanie w tle na iPhonie przez pełne 3 godziny, kierunek z GPS i kompas w prawdziwym ruchu, dokładność ETA, progi czasowe na prawdziwych światłach, wygląd na jasnym słońcu, tryb niskiego zużycia energii w iOS oraz limity wgrywania plików na hostingu przy dużych plikach.
