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
