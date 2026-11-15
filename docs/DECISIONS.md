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
  Ustawienia: High, Distance 25 m, Stop detection i heartbeat włączone.
  Strona: dwa progi komunikatu (>2 min „stoi?", >8 min „brak sygnału").
  Ogon liczony względem ostatniego punktu, nie czasu bieżącego.
