# Backlog – mapa przejazdu rolkowego

Stan na 2026-11-17. Odhaczanie: w GitHubie otwórz plik (ikona ołówka), zmień `[ ]` na `[x]`, zatwierdź zmianę (Commit changes).

Priorytety: **M** = musi być przed wdrożeniem, **S** = powinno być, **N** = do rozważenia, można po wdrożeniu.
„Kto": **C** = Claude przygotowuje pliki, **A** = Artur wgrywa/testuje/decyduje.

## Zasady projektu (nie łamiemy ich w kolejnych zmianach)

- Wszystko w wersjach darmowych; Uczestnik potrzebuje tylko przeglądarki mobilnej, bez logowania.
- Pozycja Uczestnika jest widoczna tylko dla niego i nigdzie nie jest wysyłana ani zapisywana (informacja o prywatności w menu mówi dokładnie to).
- Sekret adresu `ingest` nie trafia do publicznego repozytorium.
- Wszystkie progi i wartości w `config.js`, wszystkie teksty w `lang/*.json`.
- Dostępność kafelków OpenStreetMap nie jest priorytetem (darmowe wydarzenie dla małej grupy).

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
- [x] A4 Informacja o prywatności w menu (hamburger)

## Blok B: serwer (następny do zrobienia)

- [ ] **B1 (M, C)** Wersja produkcyjna `ingest`: usunięcie surowego logu, walidacja danych, nowy długi sekret (min. 24 znaki)
- [ ] **B2 (M, C+A)** Sekret poza repozytorium: w repo tylko wzorzec `server/ingest-ZMIEN-MNIE.php`, prawdziwa nazwa pliku wyłącznie na serwerze
- [ ] **B3 (M, C)** `.htaccess`: wyłączenie listingu katalogów, blokada plików roboczych (`.lock`, `.tmp`, `log/`), poprawny typ `application/json` dla `latest.json`
- [ ] **B4 (S, C)** Procedura zmiany sekretu (zmiana nazwy pliku + nowy adres w Traccar) na wypadek wycieku
- [ ] **B5 (N, C)** Odrzucanie punktów o bardzo słabej dokładności (np. > 100 m), żeby znacznik nie „skakał" na starcie

## Blok C: środowisko i wdrożenie

- [ ] **C2 (M, C+A)** Struktura produkcyjna katalogów (strona, `data/`, `live/`, `lang/`, `ingest`) i przeniesienie z `wnb-test`
- [ ] **C3 (M, A)** Zmiana adresu w Traccar Client na produkcyjny `ingest`
- [ ] **C4 (M, A)** Kod strony i pliki serwera w repozytorium GitHub (poza sekretem): `index.html`, `config.js`, `lang/`, `server/`
- [ ] **C5 (M, A)** Sprzątanie środowiska testowego: usunięcie `log-….php`, `requests.log`, folderu `wnb-test`
- [ ] **C6 (N, A)** Automatyczne wdrażanie z GitHuba na OVH (GitHub Actions); na MVP wystarczy ręczny FTP
- [ ] **C1 (M, A) – NA KONIEC** Wybór adresu docelowego (domena/subdomena/katalog) i certyfikat HTTPS; decyzja po poprawkach, dyskusjach i testach

## Blok D: dokumentacja

- [ ] **D1 (M, C)** `RUNBOOK.md`: instrukcja dla Organizatora (przed przejazdem: wgranie GPX i test, ładowanie telefonu; ustawienia Traccar: High, Distance 25 m, lokalizacja „Zawsze" i „Precise"; w trakcie; po przejeździe; co robić, gdy znika sygnał)
- [ ] **D2 (S, C)** `ARCHITECTURE.md` (telefon → ingest → `latest.json` → strona) i `REQUIREMENTS.md` zebrane z decyzji
- [ ] **D3 (S, C)** `TESTING.md`: wyniki testów obciążeniowych i checklista testów akceptacyjnych
- [ ] **D4 (M, A)** Aktualizacja `PROJECT.md` (stan, następny krok, odnośnik do `BACKLOG.md`)

## Blok E: testy i go-live

- [ ] **E1 (M, A)** Testy akceptacyjne po wdrożeniu: iPhone (Safari), Android (Chrome), komputer; odmowa lokalizacji, brak sygnału WnB, koniec udostępniania, podmiana pliku GPX (zmiana tytułu), wolne łącze, oba języki
- [ ] **E2 (M, A)** Test terenowy na prawdziwym przejeździe (po wdrożeniu), Glympse równolegle jako plan zapasowy; zachować log do analizy
- [ ] **E3 (M, A)** Lista kontrolna go-live: adres, kod QR na zbiórkę, post na grupę, zebranie opinii po pierwszym przejeździe

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
- [ ] Odległość i kierunek do WnB, najkrótszy dojazd do grupy
- [ ] Punkty zbiórki i postojów (POI)
- [ ] Statystyki przejazdów

## Pomysły Artura na rozwinięcie (do uzupełnienia)

- [ ] (wpisz pomysły tutaj, po jednym w linii)
