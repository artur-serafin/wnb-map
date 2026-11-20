# REQUIREMENTS: mapa przejazdu rolkowego

Stan na 2026-11-20. **Organizator (WnB)** udostępnia pozycję i wgrywa trasę, **Uczestnik** ogląda stronę. Weryfikacja: **K6** = test obciążeniowy, **SYM** = symulator przejazdu (`ride.js`), **TEL** = ręczny test Artura na telefonie, **TEREN** = do potwierdzenia na prawdziwym przejeździe.

## 1. Wymagania funkcjonalne

### Trasa
| ID | Wymaganie | Weryfikacja |
|---|---|---|
| F-01 | Organizator wgrywa jeden aktualny plik GPX; nowy plik zastępuje stary | TEL |
| F-02 | Organizator może usunąć trasę; przejazd bez trasy jest dopuszczalny i nie pokazuje błędu, POI ani ETA | TEL |
| F-03 | Tytuł trasy pochodzi z nazwy w pliku GPX, razem z długością | TEL |
| F-04 | Trasa jest rysowana na mapie 1:1 jak w pliku (nie przyciągana do ulic), ze strzałkami kierunku oraz oznaczeniem startu i mety | TEL |
| F-05 | Dotknięcie trasy pokazuje jej kilometr (np. „12 km 524 m"); w miejscach przecięcia trasy kilka wartości | TEL |
| F-06 | Uczestnik może powiększać, przesuwać mapę i wycentrować ją na trasie | TEL |

### Pozycja WnB
| ID | Wymaganie | Weryfikacja |
|---|---|---|
| F-10 | Strona pokazuje bieżącą pozycję WnB i ogon z ostatnich 2 minut | TEL, SYM |
| F-11 | Dymek przy WnB: prędkość, a przy danych starszych niż 15 s także wiek („35 s temu") | TEL, SYM |
| F-12 | Stany: „Postój", „Prawdopodobny postój" (po 90 s), „Brak sygnału" (po 8 min), „Udostępnianie zakończone" (po 15 min); progi konfigurowalne | TEL, SYM |
| F-13 | Brak start/stop w aplikacji: stan wynika z napływu pozycji | TEL |
| F-14 | Wiek pozycji liczony według czasu serwera, nie zegara telefonu | K6, TEL |

### Pozycja i kierunek Uczestnika
| ID | Wymaganie | Weryfikacja |
|---|---|---|
| F-20 | Uczestnik na jednej mapie widzi trasę, siebie i WnB | TEL |
| F-21 | Pozycja Uczestnika włącza się automatycznie; przy odmowie zgody mapa i trasa działają z komunikatem | TEL |
| F-22 | Wskaźnik kierunku ruchu (GPS) w jeździe i kierunku patrzenia (kompas) na postoju | TEL (matematyka), TEREN |
| F-23 | Przyciski: przybliż, oddal, cała trasa, WnB, moja pozycja | TEL |
| F-24 | Gdy WnB jest poza ekranem, poświata przy brzegu wskazuje kierunek | TEL |

### Punkty POI i ETA
| ID | Wymaganie | Weryfikacja |
|---|---|---|
| F-30 | Organizator definiuje punkty (Żabka, Lody) w `poi.json`: nazwa, pozycja (także wklejona z Google Maps), kilometr trasy | TEL |
| F-31 | Znacznik POI nie zasłania trasy; ogon wskazuje dokładną pozycję punktu | TEL |
| F-32 | ETA WnB do najbliższego punktu przed nim i do mety (z tyldą, zaokrąglone), prędkość z ostatnich 2 minut | SYM, TEREN |
| F-33 | Stany: „WnB jest tutaj" (stoi przy punkcie), „WnB stoi"; ETA do kolejnego dopiero po odjechaniu poza kilometr punktu | SYM |
| F-34 | Bez trasy lub poza trasą (> 200 m) brak ETA | SYM |

### Obsługa
| ID | Wymaganie | Weryfikacja |
|---|---|---|
| F-40 | Strona `upload` (z sekretem w adresie): wgranie GPX i `poi.json`, usunięcie, walidacja, potwierdzenie | TEL |
| F-41 | Nowy GPX bez `poi.json` usuwa stare punkty; przechowywana jest jedna poprzednia wersja (`.prev`) | TEL |
| F-42 | Interfejs po polsku i angielsku, teksty w plikach językowych; menu z wyborem języka i informacją o prywatności | TEL |
| F-43 | Wszystkie progi i ustawienia w jednym pliku `config.js` z komentarzami | TEL |

## 2. Wymagania niefunkcjonalne i ograniczenia

| ID | Wymaganie | Weryfikacja |
|---|---|---|
| N-01 | Wyłącznie darmowe narzędzia i usługi | przegląd |
| N-02 | Uczestnik nie instaluje aplikacji (strona mobilna wystarcza), bez logowania, dostęp publiczny | TEL |
| N-03 | Obciążenie: do 200 jednoczesnych Uczestników (typowy szczyt ok. 130), z zapasem | K6 |
| N-04 | Organizator udostępnia pozycję z iPhone'a, w tle (także podczas filmowania i rozmów), z rozsądnym zużyciem baterii | TEL (2 h 14 min), TEREN (3 h) |
| N-05 | Pozycja Uczestnika nigdzie nie jest wysyłana ani zapisywana | przegląd kodu |
| N-06 | Tylko Organizator może wgrywać trasę i udostępniać pozycję (kontrola przez sekret w adresie, bez uwierzytelniania w aplikacji) | przegląd |
| N-07 | Przeglądarki: iPhone (Safari), Android (Chrome) | TEL (iPhone), do sprawdzenia: Android |
| N-08 | Dostępność kafelków OSM nie jest priorytetem; plan awaryjny przez zmianę `TILE_URL` | przegląd |
| N-09 | Czas odpowiedzi serwera: p95 poniżej 1 s dla odczytu i zapisu | K6 (53 ms i 77 ms) |
| N-10 | Atrybucja OpenStreetMap z linkiem i poszanowanie polityki kafelków (Referer, brak prefetchu/offline) | przegląd |

## 3. Poza zakresem (świadomie odrzucone lub odłożone)

- wyznaczanie trasy do WnB po ulicach i punkt przechwycenia (prywatność, limity darmowych usług),
- wykrywanie zmiany trasy na otwartej stronie (nowa trasa po odświeżeniu),
- wiele tras i archiwum, statystyki, tryb offline (wymaga własnych map),
- „Postój" potwierdzany heartbeatem (po teście terenowym).

## 4. Otwarte do potwierdzenia na przejeździe (E2)

Progi czasowe (90 s, 8 min), zużycie baterii przez 3 godziny, kierunek GPS i kompas na iPhonie, zachowanie na Androidzie, wygląd i czytelność na słońcu.
