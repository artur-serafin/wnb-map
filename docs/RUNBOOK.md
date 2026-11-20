# RUNBOOK: instrukcja dla Organizatora (WnB)

Wersja robocza z 2026-11-20. Adresy i sekrety uzupełniasz w sekcji 1, **poza repozytorium** (jest publiczne).

## Szybka ściąga

1. **Dzień przed:** wyeksportuj trasę z MapMyRun (GPX), przygotuj `poi.json`, wgraj oba przez stronę `upload`, sprawdź mapę.
2. **Przed wyjściem:** naładuj telefon, sprawdź ustawienia Traccar Client, wyślij testową pozycję.
3. **Na starcie przejazdu:** włącz „Continuous tracking" (śledzenie włączasz dopiero na przejeździe), sprawdź na drugim telefonie, że WnB widać na mapie, wyślij link Uczestnikom.
4. **W trakcie:** nie zamykaj Traccar Client. Gdy znacznik WnB znika, patrz sekcja 5.
5. **Po przejeździe:** wyłącz „Continuous tracking". Opcjonalnie usuń trasę przez stronę `upload`.

## 1. Adresy i sekrety (uzupełnij, nie wpisuj tego do repozytorium)

| Co | Adres |
|---|---|
| Strona dla Uczestników | (do uzupełnienia po wyborze domeny) |
| Wgrywanie trasy (`upload`) | `https://…/upload-<SEKRET>.php` |
| Odbiór pozycji (`ingest`), wpisywany w Traccar Client | `https://…/ingest-<SEKRET>.php` |

Sekrety przechowuj w menedżerze haseł. To jedyne zabezpieczenie: kto zna adres `ingest`, może podszywać się pod WnB, a kto zna adres `upload`, może podmienić trasę.

## 2. Struktura plików na serwerze

```
(katalog główny strony)
├── index.html, config.js          strona i jej ustawienia
├── lang/pl.json, en.json          teksty interfejsu
├── data/WnB.gpx, poi.json         bieżąca trasa i punkty (zmieniasz przez stronę upload)
├── data/*.prev                    poprzednia wersja każdego pliku (zablokowane z internetu)
├── live/latest.json               bieżąca pozycja WnB (zapisuje ingest)
├── ingest-<SEKRET>.php            odbiór pozycji z Traccar Client
├── upload-<SEKRET>.php            wgrywanie i usuwanie trasy
└── .htaccess                      zabezpieczenia (brak listingu katalogów, blokada .lock/.tmp/.log/.prev)
```

## 3. Przed przejazdem

### 3.1 Trasa
- [ ] Narysuj trasę w MapMyRun i wyeksportuj jako **GPX**. Nazwa trasy w MapMyRun (np. „Wtorek na Bulwarach #254") staje się tytułem na stronie. Plik musi zawierać **ślad** (`trk`); sama „trasa" (`rte`) jest odrzucana.
- [ ] Przygotuj `poi.json` (sekcja 3.2) albo zdecyduj, że punktów nie będzie.
- [ ] Otwórz stronę `upload`, wybierz **GPX i `poi.json` razem**, naciśnij „Wgraj". Sprawdź potwierdzenie (tytuł, kilometry, liczba punktów).
- [ ] **Uwaga:** nowy GPX **bez** pliku POI usuwa wszystkie dotychczasowe punkty. Poprzednie zostają w `poi.json.prev`.
- [ ] Otwórz stronę Uczestników i sprawdź: tytuł z długością, trasa ze strzałkami, start i meta, punkty POI.

### 3.2 Punkty POI (`poi.json`)

```json
[
  { "type": "zabka", "name": "Żabka – przykład", "pos": "52.2184058, 21.0151354", "km": "12,524" },
  { "type": "lody",  "name": "Lody – przykład",  "lat": 52.2301, "lon": 21.0122, "km": 17.480 }
]
```

- `type`: `zabka` (planowy postój w sklepie) albo `lody` (planowy postój na lody).
- Pozycja: `pos` wklejone z Google Maps (prawy przycisk na mapie, pierwsza linia) **albo** `lat` i `lon`.
- `km`: kilometr trasy z dokładnością do metra. **Odczytaj go z mapy**: dotknij trasy w miejscu punktu, a dymek pokaże np. „12 km 524 m". Zapisz jako `"12,524"` (z przecinkiem tylko w cudzysłowie) albo `12.524` (z kropką, bez cudzysłowu).
- Bez `km` punkt będzie widoczny, ale nie dostanie ETA. Skrypt `upload` odrzuci plik z błędami i wypisze, co poprawić.
- W miejscach, gdzie trasa przecina samą siebie, dymek pokazuje kilka kilometrów. Wybierz ten, który odpowiada Twojemu kierunkowi jazdy.

### 3.3 Telefon z Traccar Client (iPhone)
- [ ] Naładowany telefon. Z testów: śledzenie zużywa ok. 7 punktów procentowych baterii na godzinę, plus Twoje filmowanie i rozmowy. Na 3 godziny zaplanuj zapas.
- [ ] Ustawienia aplikacji: **Server URL** = adres `ingest`, **Location accuracy: High**, **Distance: 25**, **Stop detection: wyłączony** (tak było we wszystkich testach), Stationary heartbeat domyślny (podczas postoju aplikacja sama wysyła sygnał co ok. 5 minut), **Offline buffering** włączony.
- [ ] Lokalizacja dla aplikacji: **„Zawsze"** i **dokładna lokalizacja** włączona (Ustawienia → Traccar Client → Lokalizacja).
- [ ] Zalecane (nie testowaliśmy każdego z osobna): włączone odświeżanie aplikacji w tle dla Traccar Client i wyłączony tryb niskiego zużycia energii.
- [ ] Naciśnij **Send location** i sprawdź, że znacznik WnB pojawia się na stronie.

## 4. W trakcie przejazdu

- **Start:** włącz **Continuous tracking** dopiero na przejeździe, nie wcześniej. Na drugim telefonie sprawdź, że znacznik i ogon się przesuwają.
- Nie zamykaj Traccar Client i nie odpinaj telefonu od ładowarki w trakcie (jeśli jest podłączony, dobrze).
- Pozycja jest wysyłana co ok. 25 m ruchu (w jeździe co 5–8 s). Na postoju aplikacja milknie i wysyła sygnał co ok. 5 minut.
- **Co widzą Uczestnicy:**

| Dymek przy WnB | Znaczenie |
|---|---|
| „17 km/h" | jedzie, dane świeże |
| „17 km/h, 35 s temu" | ostatnia pozycja ma kilkanaście sekund |
| „Postój" | ostatnia pozycja pokazuje, że stoi |
| „Prawdopodobny postój, 2 min temu" | brak nowych danych od ponad 90 s, choć jechał (np. światła) |
| szary znacznik, „Brak sygnału od WnB" | brak danych ponad 8 minut |
| „Udostępnianie pozycji zakończone" | brak danych ponad 15 minut |

- **Etykiety przy punktach i mecie:** „ETA ~38 min" (orientacyjnie, prędkość z ostatnich 2 minut), „WnB jest tutaj" (stoi przy punkcie), „WnB stoi" (stoi poza punktem). ETA do kolejnego punktu pojawia się dopiero, gdy WnB odjedzie od poprzedniego.
- **Poświata** przy brzegu ekranu wskazuje, w którą stronę jest WnB, gdy jego znacznika nie widać.

## 5. Gdy coś nie działa w trakcie

| Objaw | Co sprawdzić |
|---|---|
| Znacznik WnB zniknął albo jest szary | Czy Traccar Client działa (nie został zamknięty) i „Continuous tracking" jest włączony. Zasięg sieci. Bateria i tryb oszczędzania. Naciśnij **Send location**. Jeśli to nie pomaga, wyłącz i włącz „Continuous tracking". |
| Pozycja się spóźnia | Zasięg. Aplikacja ma bufor offline i dośle zaległe punkty, gdy zasięg wróci. |
| Znacznik „skacze" na starcie | Zła dokładność GPS tuż po włączeniu. Poczekaj 1–2 minuty na zewnątrz. |
| Brak komunikatu o postoju, a stoisz długo | Normalne: po stronie strony „Postój" zależy od ostatniej prędkości. Co ok. 5 minut przychodzi sygnał z aplikacji. |
| Plan awaryjny | **Glympse** (darmowy), z którego korzystałeś dotychczas, możesz udostępnić równolegle jako zapasowy. |

## 6. Po przejeździe

- [ ] Wyłącz **Continuous tracking** w Traccar Client. Po 15 minutach strona sama pokaże „Udostępnianie pozycji zakończone".
- [ ] Opcjonalnie usuń trasę przez stronę `upload` („Usuń trasę i punkty"). Poprzednie wersje zostaną jako `.prev`.
- [ ] Jeśli włączałeś zapis logu (`LOG_REQUESTS` w `ingest`), wyłącz go i usuń `log/requests.log`.

## 7. Przejazd bez trasy

Usuń trasę przez stronę `upload` (albo nie wgrywaj żadnej). Strona pokaże wtedy tylko mapę i pozycję WnB, bez punktów, ETA i przycisku „Cała trasa", i nie zgłosi żadnego błędu.

## 8. Wgrywanie trasy: najczęstsze błędy

| Komunikat | Przyczyna i rozwiązanie |
|---|---|
| „W pliku GPX nie ma śladu (trk)" | Wyeksportowano samą trasę (`rte`). Wyeksportuj w MapMyRun jako GPX ze śladem. |
| „To nie jest poprawny plik GPX" | Zły plik lub uszkodzony eksport. |
| „Ślad musi mieć co najmniej 2 różne punkty" | Za krótka lub pusta trasa. |
| „Za dużo punktów trasy" | Trasa nagrana zamiast narysowanej. Uprość lub narysuj ręcznie (limit 5000 punktów). |
| POI: „nieznany typ", „brak poprawnej pozycji", „niepoprawny kilometr" | Popraw wskazany wpis w `poi.json` (numer wpisu jest w komunikacie). |
| POI: ostrzeżenie o kilometrze większym niż trasa | Plik został wgrany, ale sprawdź `km` w tym wpisie. |

Przy błędzie nic nie jest zmieniane: trasa na stronie zostaje taka jak była.

## 9. Przywrócenie poprzedniej wersji (z `.prev`)

Przez FTP w folderze `data`: zmień nazwę `WnB.gpx.prev` na `WnB.gpx` (nadpisz istniejący) i, jeśli trzeba, `poi.json.prev` na `poi.json`. Przechowywana jest tylko jedna poprzednia wersja.

## 10. Zmiana sekretu (gdy adres mógł wyciec)

Podejrzenie: ktoś zmienił trasę bez Twojej wiedzy albo pojawia się obca pozycja WnB.

1. Wygeneruj nowy sekret (PowerShell: `[guid]::NewGuid().ToString('N')`).
2. Zmień nazwę pliku na serwerze (FTP): `ingest-<STARY>.php` na `ingest-<NOWY>.php` (i/lub `upload-…`).
3. **Ingest:** wpisz nowy adres w Traccar Client (Server URL).
4. Sprawdź, że wszystko działa (Send location albo wejście na stronę `upload`). Zapisz nowy sekret w menedżerze haseł.
5. Sprawdź, czy trasa i punkty są prawidłowe. W razie podmiany: sekcja 9.

## 11. Ustawienia strony (`config.js`)

Wszystkie ustawienia mają komentarze w pliku. Najczęściej zmieniane:

| Ustawienie | Domyślnie | Co zmienia |
|---|---|---|
| `STOP_AFTER_SECONDS` | 90 | po ilu sekundach bez danych pokazać „Prawdopodobny postój" |
| `LOST_AFTER_SECONDS` | 480 | po ilu sekundach „Brak sygnału" |
| `END_AFTER_SECONDS` | 900 | po ilu sekundach „Udostępnianie zakończone" |
| `POLL_SECONDS` | 8 | jak często strona pobiera pozycję WnB |
| `POI_AT_METERS` | 100 | jak blisko punktu WnB jest „w punkcie" |
| `ETA_ROUND_MINUTES` | 5 | zaokrąglenie ETA powyżej 10 minut |
| `ARROW_SPACING_PX`, `ARROW_SIZE_PX` | 110, 14 | gęstość i wielkość strzałek na trasie |
| `SHOW_GLOW`, `GLOW_SHOW_DISTANCE` | true, false | poświata w stronę WnB i napis z odległością |
| `SHOW_COMPASS`, `COMPASS_DECLINATION_DEG` | true, 7 | kompas Uczestnika, poprawka na północ geograficzną |
| `TILE_URL` | OSM | źródło map (zmiana, gdy OSM zablokuje dostęp) |

Po zmianie wgraj `config.js` na serwer i zamknij oraz ponownie otwórz kartę (przeglądarka mogła zapamiętać stary plik).

## 12. Diagnostyka i testy w domu

- **Symulator przejazdu** (`ride.js` w k6): wysyła sztuczne pozycje wzdłuż `WnB.gpx`. Przykład: `k6 run -e INGEST_URL="…" -e START_KM=9 -e STOPS="11.064:90" ride.js` (sztuczne pozycje trafiają do `latest.json`, nie używaj podczas prawdziwego przejazdu).
- **Test obciążeniowy** (`load.js`, `burst.js`): wyniki w `TESTING.md`.
- **Surowy log pozycji:** w skrypcie `ingest` ustaw `LOG_REQUESTS = true` (zapis do `log/requests.log`, zawiera historię lokalizacji, po testach wyłącz i usuń).

## 13. Znane ograniczenia

- Podczas postoju pozycja WnB odświeża się co ok. 5 minut. Stan „Postój" opiera się na ostatniej prędkości.
- ETA jest szacunkiem (nie uwzględnia świateł i niespodzianek) i wymaga `km` w `poi.json`.
- Mapy OpenStreetMap nie mają gwarancji dostępności i mogą zostać zablokowane. Wtedy zmień `TILE_URL` w `config.js` albo użyj własnych kafelków.
- Kierunek z kompasu Uczestnika zależy od telefonu i kalibracji; na iPhonie wymaga zgody po dotknięciu „Moja pozycja".
- Przeglądarka Uczestnika wstrzymuje stronę po wygaszeniu ekranu; po powrocie odświeża się sama.
