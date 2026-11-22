# Backlog – mapa przejazdu rolkowego

Stan na 2026-11-22. Odhaczanie: w GitHubie otwórz plik (ikona ołówka), zmień `[ ]` na `[x]`, zatwierdź zmianę (Commit changes).

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

- [ ] **C2 (M, C+A)** Struktura produkcyjna katalogów (strona, `data/`, `live/`, `lang/`, `ingest`) i przeniesienie z `wnb`
- [ ] **C3 (M, A)** Zmiana adresu w Traccar Client na produkcyjny `ingest`
- [x] **C4 (M, A)** Kod strony i pliki serwera w repozytorium GitHub (poza sekretem): `index.html`, `config.js`, `lang/`, `server/`, `.htaccess`
- [ ] **C5 (M, A)** Sprzątanie środowiska testowego: usunięcie `log-….php`, `requests.log`, folderu `wnb`
- [ ] **C6 (N, A)** Automatyczne wdrażanie z GitHuba na OVH (GitHub Actions); na MVP wystarczy ręczny FTP
- [ ] **C1 (M, A) – NA KONIEC** Wybór adresu docelowego (domena/subdomena/katalog) i certyfikat HTTPS; decyzja po poprawkach, dyskusjach i testach

## Blok D: dokumentacja

- [x] **D1 (M, C)** `RUNBOOK.md`: instrukcja dla Organizatora (przed przejazdem: wgranie GPX i `poi.json` wraz z odczytem kilometrów POI z mapy, test; ładowanie telefonu; ustawienia Traccar: High, Distance 25 m, lokalizacja „Zawsze" i „Precise"; w trakcie; po przejeździe; co robić, gdy znika sygnał)
- [x] **D2 (S, C)** `ARCHITECTURE.md` (telefon → ingest → `latest.json` → strona) i `REQUIREMENTS.md` zebrane z decyzji
- [x] **D3 (S, C)** `TESTING.md`: wyniki testów obciążeniowych i checklista testów akceptacyjnych
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
- [x] **F7 (S, C)** Kierunek ruchu Uczestnika: stożek/strzałka przy niebieskiej kropce na podstawie kierunku ruchu z lokalizacji (`heading`), pokazywana tylko podczas jazdy (próg prędkości w `config.js`); bez kompasu telefonu, bo wymaga dodatkowej zgody i nie działa, gdy telefon jest w kieszeni. Dostępność kierunku na iPhonie do potwierdzenia w terenie. Kierunek patrzenia z kompasu telefonu, gdy Uczestnik stoi (iPhone: zgoda po dotknięciu „Moja pozycja"; poprawka deklinacji w `config.js`); do potwierdzenia w terenie

## Blok G: wgrywanie i usuwanie trasy

- [x] **G1 (M, C)** Strona: brak trasy to stan celowy (404): bez komunikatu i błędu, bez trasy i ETA, ale POI nadal może działać; kilometr trasy i przycisk „Cała trasa" są ukryte; pozycja WnB działa; opcjonalny `NO_ROUTE_TITLE`
- [x] **G3 (M, C+A)** `upload-<sekret>.php`: formularz (GPX + opcjonalnie `poi.json`, przycisk usunięcia), nowe GPX usuwa stare POI, sam plik POI nie usuwa GPX, usunięcie może usunąć oba pliki, walidacja przed podmianą, zapis atomowy, jedna kopia poprzedniej wersji (`.prev`), potwierdzenie (tytuł, długość, liczba punktów); wzorzec w repozytorium `server/upload-ZMIEN-MNIE.php`, sekret tylko na serwerze
- [x] **G4 (S, C)** `.htaccess`: blokada plików `.prev`
- [ ] **G5 (S, C)** Procedura wgrywania trasy w `RUNBOOK.md` (eksport z MapMyRun, wgranie z telefonu, kontrola, przywrócenie poprzedniej wersji)

## Blok H: tryb edycji punktów POI

Kolejność: H1–H4 dane i serwer, H5–H10 mapa i gesty, H11–H13 zapis i testy, H14–H16 dokumentacja i wdrożenie.

- [ ] **H1 (M, C)** Rozszerzyć model POI o opcjonalne pola `order` i `km`; typy: `zabka`, `lody`, `default`. Brak pola `type` i nieznany/nowy typ są traktowane jako `default` w przeglądarce i normalizowane do `default` przed zapisem.
- [ ] **H2 (M, C)** Zmienić renderowanie POI: `default` to kółko bez grafiki z opcjonalną cyfrą kolejności; punkty bez `order` nie pokazują cyfry. Puste lub nieistniejące `km` nie powodują widocznego kilometra w dymku.
- [ ] **H3 (M, C+A)** Poprawić stan bez trasy: POI są ładowane i pokazywane również wtedy, gdy `WnB.gpx` zwraca 404. Przy braku trasy nie pokazuje się kilometr trasy ani przycisk „Cała trasa", a ETA jest całkowicie wyłączone.
- [ ] **H4 (M, C)** Zmienić regułę ETA: jeżeli chociaż jeden punkt POI ma puste lub niepoprawne `km`, ETA nie jest liczone dla żadnego punktu ani mety. Gdy wszystkie punkty mają poprawny `km`, zachować dotychczasową logikę ETA.
- [ ] **H5 (M, C)** Dodać wejście w tryb edycji przez sekret w parametrze URL `?s=SEKRET` oraz osobny endpoint `update-<SEKRET>.php`. Endpoint najpierw weryfikuje sekret przed operacją verify/save i zwraca właściwy kod HTTP.
- [ ] **H6 (M, C)** Napisać `update-<SEKRET>.php` z walidacją `poi.json`, atomowym zapisem do `data/poi.json`, kopią dotychczasowego pliku do `poi.json.prev`, limitem rozmiaru i odpowiedziami `JSON` dla sprawdzenia sekretu i błędów.
- [ ] **H7 (M, C)** W trybie edycji ukryć normalne menu i przyciski, pokazując prawy górny przycisk zamknięcia (krzyżyk) oraz przycisk save ponad pozostałymi prawymi przyciskami.
- [ ] **H8 (M, C)** Zaimplementować long-press na pustej części mapy: po ~650 ms utworzyć jeden POI `default` bez nazwy i kilometra, z ostatnim numerem kolejności. Punkt lokalizować w dokładnym miejscu dotknięcia.
- [ ] **H9 (M, C)** Zaimplementować long-press na znaczniku POI: otworzyć edycję punktu. Kliknięcie zwykłe nadal otwiera zwykły dymek poza trybem edycji; strzałka/ogon znacznika nie może być elementem klikalnym ani uruchamiać edycji.
- [ ] **H10 (M, C)** Zaimplementować press+move na znaczniku POI: zablokować przesuwanie mapy przez ten gest, przesuwać lokalny marker pod wskaźnikiem i przesuwać mapę brzegową, gdy wskaźnik dotrze do krawędzi ekranu. Po puszczeniu zapisać lokalną nową współrzędną, bez natychmiastowego zapisu.
- [ ] **H11 (M, C)** W pop-overze edycji dodać pola: nazwa, typ (`default`, `zabka`, `lody`), kolejność (lista 1…N, bez wpisywania), kilometr trasy (tekst, przecinek lub kropka, opcjonalnie pusty) i usunięcie punktu.
- [ ] **H12 (M, C)** Zmienić kolejność w trybie edycji przez przesunięcie punktu w nową pozycję i automatyczne przenumerowanie pozostałych punktów do ciągu 1…N. Zmiana kolejności musi być natychmiast widoczna na wszystkich znacznikach.
- [ ] **H13 (M, C+A)** Wszystkie dodania, przeniesienia, edycje i usunięcia trzymać tylko w lokalnej kopii `poi.json` w pamięci przeglądarki aż do „save". Zamknięcie trybu odrzuca zmiany bez pytania. Save wysyła całą listę i po sukcesie odświeża lokalny stan z odpowiedzi serwera.
- [ ] **H14 (S, C+A)** Obsłużyć błędy sekretu i zapisu: poprawny kod HTTP dla niepoprawnego sekretu, błąd walidacji, błąd serwera i brak zmiany pliku przy odrzuceniu zapisu. Lokalny stan POI pozostaje nietknięty po błędzie save.
- [ ] **H15 (M, C+A)** Rozszerzyć upload trasy i usuwanie: sam POI zastępuje tylko POI i nie dotyka GPX; sam GPX zastępuje trasę i usuwa istniejące POI, bo stare punkty nie pasują do nowej mapy; usunięcie usuwa GPX i POI bez błędu, gdy któryś plik już nie istnieje.
- [ ] **H16 (S, C+A)** Zaktualizować języki, `RUNBOOK.md`, `TESTING.md` i procedurę zmiany sekretu. Dodać testy na iOS/Android, brak trasy, brak kilometra, kolejność i utratę połączenia podczas save.

Odrzucone: wykrywanie zmiany trasy na otwartych stronach (nowa trasa po odświeżeniu wystarcza).

## Świadomie odłożone (wracamy po wdrożeniu lub po teście terenowym)

- [ ] **Postój potwierdzany heartbeatem:** „Postój" tylko gdy prędkość < progu ORAZ odstęp od poprzedniego punktu ≥ ok. 270 s (pole `gap` liczone w `ingest`, nowe ustawienie `HEARTBEAT_MIN_GAP_SECONDS`); wrócić po teście terenowym z prawdziwymi heartbeatami
- [ ] Weryfikacja progów: `LOST_AFTER_SECONDS` (480 s) i `STOP_AFTER_SECONDS` (90 s) na prawdziwym przejeździe
- [ ] Pomiar baterii przy pełnych 3 godzinach udostępniania
- [ ] A5: własna kopia Leafleta na OVH zamiast cdnjs (ewentualnie suma kontrolna SRI przy cdnjs)
- [ ] A6: szybsze odświeżenie po powrocie do karty i blokada wygaszania ekranu (Wake Lock)
- [x] A7: podgląd linku przy wklejeniu na grupie (tytuł, obrazek), favicon; po wyborze domeny ustawić absolutny adres obrazka i sprawdzić podgląd w docelowej grupie
- [ ] Własne mapy (PMTiles na OVH) jako alternatywa dla kafelków OSM; wymagane też dla trybu offline
- [ ] Tryb offline (wymaga własnych map; na kafelkach OSM zabroniony)
- [ ] Wiele tras / archiwum tras
- [ ] Statystyki przejazdów

## Odrzucone (z powodu prywatności i limitów darmowych usług)

- „Prowadź do WnB" z wyznaczaniem trasy po ulicach (wymagałoby wysyłania pozycji Uczestnika do zewnętrznej usługi trasowania) oraz wyliczanie punktu przechwycenia. Zastąpione wskaźnikiem kierunku do WnB (F6) i stałym pokazywaniem pozycji WnB.
