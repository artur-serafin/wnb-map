# Dziennik decyzji

Format: data – decyzja – powód – rozważane alternatywy

- 2026-11-14 – GitHub jako repozytorium i źródło prawdy – darmowe,
  pozwala wracać do pracy po przerwach.
- 2026-11-14 – Brak start/stop w aplikacji; stan wynika z napływu pozycji –
  prostsze, bez stanu i autentykacji.
- 2026-11-14 – Ogon organizatora 2 min, parametr konfiguracyjny.
- 2026-11-14 – Interfejs PL+EN z plikami językowymi edytowalnymi przez właściciela.
- 2026-11-14 – Hosting nierozstrzygnięty; OVH Personal jest kandydatem do spike'a.
- 2026-11-15 – Aplikacja trackingowa: Traccar Client (iOS), Distance = 25 m.
  Test 15 min: 129 punktów, co 6–8 s, opóźnienie ≤1 s, dokładność 6–14 m.
  Bez progu dystansu aplikacja wysyłała tylko co 5 min (bezużyteczne).
  Otwarte: postoje (heartbeat), bateria w 3 h, szybsza jazda.
  OwnTracks odłożony jako plan B.
- 2026-11-15 – Traccar Client zatwierdzony (test 2h14m, 614 pkt): jazda co 6–8 s,
  opóźnienie ≤5 s, bateria ok. 7 pp/h, postój = heartbeat co ok. 5 min.
  Ustawienia: High, Distance 25 m, Stop detection wyłączone, Accuracy High, Distance 25 m, heartbeat nieustawiany (domyślny, ok. 5 min).
  Strona: trzy progi komunikatu (2–8 min: „Ostatnia pozycja X min temu, Organizator prawdopodobnie stoi“, 8–15 min: „Brak sygnału od Organizatora“ (znacznik szary), >15 min: „Udostępnianie pozycji zakończone“ + znacznik znika, zostaje trasa).
  Ogon liczony względem ostatniego punktu, nie czasu bieżącego.
  Koniec udostępniania = brak danych >15 min.
- 2026-11-15 – Mapa: Leaflet, trasa z GPX (<trk>), tytuł z <name>, duplikaty
  punktów usuwane przy wczytaniu. Trasa celowo rysowana ręcznie i nie zawsze
  po ulicach: wyświetlamy 1:1 jak w pliku, bez snappingu. Test na telefonie OK.
  Otwarte: źródło kafelków dla 130 osób (OSM standardowe nie nadaje się na produkcję).
- 2026-11-15 – Odłożone: "Postój" potwierdzany heartbeatem (prędkość < progu
  ORAZ odstęp od poprzedniego punktu >= ok. 270 s, liczony w ingest i zapisany
  jako pole gap). Wrócimy do tego po teście terenowym, mając w logu prawdziwe
  heartbeaty. Na razie: Postój = ostatnia prędkość < STILL_SPEED_KMH.
  STOP_AFTER_SECONDS zmieniony na 90.
- 2026-11-15 – Test obciążeniowy OVH Personal (k6, 6 min, 150-200 odczytów co 8 s
  + zapis co 5 s): 0 błędów HTTP, p95 odczytu 53 ms, zapisu 77 ms, max 176 ms.
  Hosting OVH ZATWIERDZONY. Zaobserwowano 1 niepoprawny odczyt na 6898
  (hipoteza: wyścig odczytu z podmianą pliku). Do dodania w stronie:
  tolerancja pojedynczego nieudanego odczytu (komunikat dopiero po 2 z rzędu).
- 2026-11-16 – Test skoku (k6, 400 wejść: 200 w 20 s + 200 jednocześnie):
  0 błędów, p95 187 ms, max 364 ms. Trzy testy obciążeniowe zaliczone,
  OVH Personal zostaje jako hosting produkcyjny. Otwarte: źródło map.
- 2026-11-16 – Mapa: zostajemy przy kafelkach OSM (tile.openstreetmap.org),
  skala do ok. 50 osób jednocześnie. Wymagania polityki OSM: atrybucja z linkiem,
  Referer, brak prefetchu/offline, brak SLA i możliwa blokada bez uprzedzenia.
  Plan awaryjny: zmiana TILE_URL w config.js (PMTiles na OVH jako gotowa
  alternatywa; hosting potwierdzony na żądania zakresowe). "Tryb offline" z fazy 10
  wymaga własnych kafelków.
- 2026-11-17 – Strona: menu (hamburger) z wyborem języka PL/EN i informacją o
  prywatności; teksty w lang/pl.json i lang/en.json; tolerancja pojedynczego
  błędu odczytu (FAIL_AFTER_POLLS=2). Leaflet zostaje na cdnjs (własna kopia
  odłożona). Dostępność kafelków OSM nie jest priorytetem. Wybór adresu
  docelowego na sam koniec.
- 2026-11-20 – Adres docelowy: https://arturserafin.com/wnb/ (podfolder istniejącej domeny,
  HTTPS, bez "test"). Nowe sekrety dla ingest i upload. wnb-test do usunięcia po testach
  akceptacyjnych. index.html przekierowuje http na https (lokalizacja wymaga HTTPS).
- 2026-11-22 – Tryb edycji POI jest osobnym stanem sesji strony, bez logowania i bazy danych.
  Wejście i zapis wymagają sekretu. Sekret jest przesłany jawnie do dedykowanego endpointu
  `update-<SEKRET>.php`, ale tylko prawdziwa nazwa pliku i nieujawniony sekret trafiają na
  serwer; `index.html` i dokumentacja nie zawierają prawdziwego sekretu.
  Alternatywa z logowaniem administratora została odrzucona jako zbyt duży koszt i zależność
  dla jednorazowego narzędzia organizatora.
- 2026-11-22 – Pola POI: `order` jest opcjonalnym dodatnim całkowitoliczbowym indeksem,
  `type` przyjmuje `zabka`, `lody` lub `default`, a brak/nieznana wartość jest normalizowana
  do `default`. UI przenosi element i normalizuje pozostałe wartości do ciągu 1…N.
  Sam JSON może zawierać brakujące `order` i `km`; plik zapisany przez edytor normalizuje
  kolejność wszystkich istniejących punktów.
- 2026-11-22 – Brak któregokolwiek poprawnego kilometra wyłącza ETA dla całego zestawu.
  Punkt bez kilometra może istnieć na mapie bez trasy i nie uniemożliwia jej odczytu
  przez pozostałe punkty. Jest to bezpieczniejsze niż domysł, który punkt jest wcześniejszy.
- 2026-11-22 – Edycja POI korzysta z Pointer Events i lokalnej kopii listy. Długi dotyk
  mapy/znacznika i press+move są obsługiwane w jednej warstwie edytora; normalny widok
  Leafleta zostaje bez zmian. Save jest atomowym zastąpieniem całego `poi.json` z kopią
  `.prev`, a zamknięcie trybu odrzuca niesaved changes bez pytania.
- 2026-11-23 – `?s=SEKRET` wybiera nazwę endpointu z publicznego szablonu
  `update-{secret}.php`; prawdziwy sekret nie trafia do `config.js`. Skrypt sprawdza, że
  sekret z nazwy pliku jest zgodny z sekretem przesłanym w JSON.
- 2026-11-23 – Nagłówek strony pozostaje widoczny w trybie edycji. Hamburger jest ukryty,
  a jego miejsce zajmują przyciski save i zamknięcia trybu. Zamykanie panelu punktu nie
  zamyka całego trybu. Zamknięcie trybu usuwa `s` z adresu i przeładowuje stronę.
- 2026-11-23 – Powiadomienie o udanym save jest osobnym wpisem pod istniejącymi
  powiadomieniami i znika po 2 sekundach. Pola klikalne POI ograniczono do kółka/ikony,
  bez ogona; w trybie edycji ogon jest dwukrotnie dłuższy niż zwykle.