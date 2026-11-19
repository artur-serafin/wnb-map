# Backlog – mapa przejazdu rolkowego

Stan na 2026-11-18 (po zamknięciu bloku B). Odhaczanie: w GitHubie otwórz plik (ikona ołówka), zmień `[ ]` na `[x]`, zatwierdź zmianę (Commit changes).

Priorytety: **M** = musi być przed wdrożeniem, **S** = powinno być, **N** = do rozważenia, można po wdrożeniu.
„Kto": **C** = Claude przygotowuje pliki, **A** = Artur wgrywa/testuje/decyduje.

## Zasady projektu (nie łamiemy ich w kolejnych zmianach)

- Wszystko w wersjach darmowych; Uczestnik potrzebuje tylko przeglądarki mobilnej, bez logowania.
- Pozycja Uczestnika jest widoczna tylko dla niego i nigdzie nie jest wysyłana ani zapisywana (informacja o prywatności w menu mówi dokładnie to). Dlatego nie używamy zewnętrznych usług wyznaczania tras.
- Sekret adresu `ingest` nie trafia do publicznego repozytorium.
- Wszystkie progi i wartości w `config.js`, wszystkie teksty w `lang/*.json`.
- Dostępność kafelków OpenStreetMap nie jest priorytetem (darmowe wydarzenie dla małej grupy).
- Szacunki (ETA) są orientacyjne, zawsze z tyldą; nie obiecujemy dokładności (światła, postoje, zdarzenia).

## Zrobione

- [x] Repozytorium GitHub, dokumenty startowe (`PROJECT.md`, `DECISIONS.md`), plik testowy `data/WnB.gpx`
- [x] Spike 1: Traccar Client (iPhone) zatwierdzony; ustawienia: High, Distance 25 m, Stop detection i heartbeat domyślne; praca w tle, bateria ok. 7 pp/h
- [x] Spike 2: mapa Leaflet z trasą z GPX (tytuł z `<name>`, usuwanie zdublowanych punktów, trasa 1:1 jak w pliku)
- [x] Spike 3: skrypt `ingest` zapisujący `live/latest.json` (ogon 120 s liczony od ostatniego punktu)
- [x] Testy obciążeniowe OVH Personal (odczyty 150–200 osób, zapisy, skok 400 wejść): 0 błędów, p95 < 200 ms
- [x] Decyzja: kafelki OSM (`tile.openstreetmap.org`); PMTiles na OVH jako ewentualna alternatywa
- [x] UI: przyciski z ikonami, dymek przy WnB, ramka statusów, automatyczna lokalizacja Uczestnika
- [x] `config.js` z ustawieniami i komentarzami
- [x] A1 Tolerancja pojedynczego nieudanego odczytu (`FAIL_AFTER_POLLS`)
- [x] A2 Atrybucja OSM z linkiem
- [x] A3 Pliki językowe PL/EN, wybór języka z ustawień telefonu i przełącznik w menu
- [x] A4 Informacja o prywatności w menu (hamburger); wersja przetestowana na telefonie
- [x] B1–B3: produkcyjny `ingest` (POST, walidacja, przełącznik logu `LOG_REQUESTS`, zapis atomowy, 500 przy błędzie zapisu), wzorzec bez sekretu w repozytorium (`server/ingest-ZMIEN-MNIE.php`), `.htaccess` (brak listingu katalogów, blokada `*.lock`/`*.tmp`/`*.log`, `nosniff`, `no-cache` dla `latest.json`); sprawdzone na serwerze testowym

## Blok B: serwer (zamknięty)

- [x] **B1 (M, C)** Wersja produkcyjna `ingest`: usunięcie surowego logu, walidacja danych, nowy długi sekret (min. 24 znaki)
- [x] **B2 (M, C+A)** Sekret poza repozytorium: w repo tylko wzorzec `server/ingest-ZMIEN-MNIE.php`, prawdziwa nazwa pliku wyłącznie na serwerze
- [x] **B3 (M, C)** `.htaccess`: wyłączenie listingu katalogów, blokada plików roboczych (`.lock`, `.tmp`, `log/`), poprawny typ `application/json` dla `latest.json`
- [ ] **B4 (S, C)** Procedura zmiany sekretu (zmiana nazwy pliku + nowy adres w Traccar) na wypadek wycieku; to dokumentacja, trafia do `RUNBOOK.md` (D1)
- [ ] **B5 (N, C)** Odrzucanie punktów o bardzo słabej dokładności (np. > 100 m), żeby znacznik nie „skakał" na starcie; decyzja po teście terenowym (E2), gdy zobaczymy dane

## Blok C: środowisko i wdrożenie

- [ ] **C2 (M, C+A)** Struktura produkcyjna katalogów (strona, `data/`, `live/`, `lang/`, `ingest`) i przeniesienie z `wnb-test`
- [ ] **C3 (M, A)** Zmiana adresu w Traccar Client na produkcyjny `ingest`
- [ ] **C4 (M, A)** Kod strony i pliki serwera w repozytorium GitHub (poza sekretem): `index.html`, `config.js`, `lang/`, `server/`, `.htaccess`
- [ ] **C5 (M, A)** Sprzątanie środowiska testowego: usunięcie `log-….php`, `requests.log`, folderu `wnb-test`
- [ ] **C6 (N, A)** Automatyczne wdrażanie z GitHuba na OVH (GitHub Actions); na MVP wystarczy ręczny FTP
- [ ] **C1 (M, A) – NA KONIEC** Wybór adresu docelowego (domena/subdomena/katalog) i certyfikat HTTPS; decyzja po poprawkach, dyskusjach i testach

## Blok D: dokumentacja

- [ ] **D1 (M, C)** `RUNBOOK.md`: instrukcja dla Organizatora (przed przejazdem: wgranie GPX i `poi.json` wraz z odczytem kilometrów POI z mapy, test; ładowanie telefonu; ustawienia Traccar: High, Distance 25 m, lokalizacja „Zawsze" i „Precise"; w trakcie; po przejeździe; co robić, gdy znika sygnał)
- [ ] **D2 (S, C)** `ARCHITECTURE.md` (telefon → ingest → `latest.json` → strona) i `REQUIREMENTS.md` zebrane z decyzji
- [ ] **D3 (S, C)** `TESTING.md`: wyniki testów obciążeniowych i checklista testów akceptacyjnych
- [ ] **D4 (M, A)** Aktualizacja `PROJECT.md` (stan, następny krok, odnośnik do `BACKLOG.md`)

## Blok E: testy i go-live

- [ ] **E1 (M, A)** Testy akceptacyjne po wdrożeniu: iPhone (Safari), Android (Chrome), komputer; odmowa lokalizacji, brak sygnału WnB, koniec udostępniania, podmiana pliku GPX (zmiana tytułu), wolne łącze, oba języki
- [ ] **E2 (M, A)** Test terenowy na prawdziwym przejeździe (po wdrożeniu), Glympse równolegle jako plan zapasowy; zachować log do analizy
- [ ] **E3 (M, A)** Lista kontrolna go-live: adres, kod QR na zbiórkę, post na grupę, zebranie opinii po pierwszym przejeździe

## Blok F: rozwój (pomysły Artura), punkt po punkcie

Kolejność: F1 → F7. F4 jest podstawą dla F5. Brak zmian po stronie serwera: ogon 2-minutowy wystarcza do uśredniania prędkości.

- [x] **F1 (S, C)** Stała szerokość pola statusu przy WnB (cyfry o stałej szerokości, `tabular-nums`), żeby pole nie „pływało" przy zmianie sekund (np. 31 → 32)
- [x] **F2 (S, C)** Znaczniki kierunku jazdy na trasie (strzałki co stały odstęp w pikselach, widoczne przy każdym przybliżeniu) oraz wyraźne oznaczenie startu i mety (trasa jest pętlą, początek i koniec dzieli ok. 40 m)
- [x] **F3 (S, C+A)** Punkty POI: plik `data/poi.json` edytowany ręcznie. Pola: `type` (`zabka` = planowy postój w sklepie, `lody` = planowy postój na lody), `name`, pozycja jako `pos` (tekst „lat, lon" wklejony wprost z Google Maps) albo `lat` i `lon`, **`km`** (kilometr trasy, z dokładnością do 3 miejsc po przecinku, czyli do metra; liczony wzdłuż śladu GPX od startu, w tych samych kilometrach co długość w tytule strony). Dymek po dotknięciu, ikony ogólne bez logo marek. Bez planowanego czasu postoju. Przy zmianie trasy aktualizujesz GPX i `poi.json`. **Kilometr trasy w dymku:** dotknięcie dowolnego punktu trasy (każdy Uczestnik, bez trybu specjalnego) pokazuje dymek z kilometrem trasy, np. „km 12,345" (dokładność do metra, liczony wzdłuż śladu GPX od startu); w miejscach skrzyżowania trasy z samą sobą pokazuje oba kilometry; klikalna strefa szersza niż linia, żeby łatwo trafić palcem. Dzięki temu Artur odczytuje `km` do `poi.json` bezpośrednio z mapy; bez ostrzeżeń o odległości POI od trasy
- [x] **F4 (S, C)** Postęp WnB wzdłuż planowanej trasy w kilometrach (rzut pozycji na trasę, rozstrzyganie 3 skrzyżowań trasy kierunkiem i ogonem). Gdy WnB jest dalej niż ok. 150–200 m od trasy (ustawienie w `config.js`), nie liczymy postępu ani ETA
- [x] **F5 (S, C)** ETA WnB do najbliższego POI przed nim (według `km`) oraz do mety (meta = ostatni punkt z prognozą), np. „ok. 38 min", zawsze z tyldą i zaokrąglone. Prędkość uśredniana z ostatnich 2 minut. Logika stanów POI: **(1) WnB w punkcie:** jest blisko POI (próg odległości `POI_AT_METERS` w `config.js`) ORAZ prędkość jest bliska zeru lub zerowa albo brak prędkości (heartbeat w tej lokalizacji) = postój w punkcie, bez ETA, krótki komunikat; **(2) WnB ruszył:** ETA do kolejnego POI (lub mety) pokazujemy dopiero, gdy ogon WnB wyjedzie poza kilometr POI, w którym stał; **(3) punkt minięty:** pokazujemy kolejny. Gdy WnB stoi poza POI: bez liczby, komunikat zamiast ETA
- [x] **F6 (S, C)** Wskaźnik kierunku do WnB, gdy jego znacznik jest poza ekranem: poświata przy brzegu/rogu mapy w stronę WnB, w kolorze znacznika WnB (szara przy braku sygnału, brak po zakończeniu udostępniania); znika, gdy znacznik WnB jest widoczny na ekranie. Do ustalenia po podglądzie na telefonie: intensywność, ewentualny napis z odległością (np. „1,2 km")
- [x] **F7 (S, C)** Kierunek ruchu Uczestnika: stożek/strzałka przy niebieskiej kropce na podstawie kierunku ruchu z lokalizacji (`heading`), pokazywana tylko podczas jazdy (próg prędkości w `config.js`); bez kompasu telefonu, bo wymaga dodatkowej zgody i nie działa, gdy telefon jest w kieszeni. Dostępność kierunku na iPhonie do potwierdzenia w terenie

## Świadomie odłożone (wracamy po wdrożeniu lub po teście terenowym)

- [ ] **Postój potwierdzany heartbeatem:** „Postój" tylko gdy prędkość < progu ORAZ odstęp od poprzedniego punktu ≥ ok. 270 s (pole `gap` liczone w `ingest`, nowe ustawienie `HEARTBEAT_MIN_GAP_SECONDS`); wrócić po teście terenowym z prawdziwymi heartbeatami
- [ ] Weryfikacja progów: `LOST_AFTER_SECONDS` (480 s) i `STOP_AFTER_SECONDS` (90 s) na prawdziwym przejeździe
- [ ] Pomiar baterii przy pełnych 3 godzinach udostępniania
- [ ] A5: własna kopia Leafleta na OVH zamiast cdnjs (ewentualnie suma kontrolna SRI przy cdnjs)
- [ ] A6: szybsze odświeżenie po powrocie do karty i blokada wygaszania ekranu (Wake Lock)
- [ ] A7: podgląd linku przy wklejeniu na grupie (tytuł, obrazek), favicon
- [ ] Własne mapy (PMTiles na OVH) jako alternatywa dla kafelków OSM; wymagane też dla trybu offline
- [ ] Tryb offline (wymaga własnych map; na kafelkach OSM zabroniony)
- [ ] Wiele tras / archiwum tras
- [ ] Statystyki przejazdów

## Odrzucone (z powodu prywatności i limitów darmowych usług)

- „Prowadź do WnB" z wyznaczaniem trasy po ulicach (wymagałoby wysyłania pozycji Uczestnika do zewnętrznej usługi trasowania) oraz wyliczanie punktu przechwycenia. Zastąpione wskaźnikiem kierunku do WnB (F6) i stałym pokazywaniem pozycji WnB.
