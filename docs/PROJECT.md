# Mapa przejazdu rolkowego – status projektu

## Cel
Darmowa strona mobilna (bez instalacji dla uczestników), na której
uczestnik widzi na jednej mapie: planowaną trasę (GPX), swoją pozycję
i bieżącą pozycję organizatora. Organizator udostępnia pozycję z iPhone'a,
także w tle.

## Stan na dziś
- Status: działające MVP z trasą, POI i śledzeniem pozycji WnB
- Hosting: OVH Personal, domena docelowa `https://arturserafin.com/wnb/`
- Zrobione: ingest pozycji, mapa GPX, POI, ETA, języki PL/EN, upload danych,
  tryb edycji POI i atomowy zapis przez osobny endpoint
- Następny krok: końcowe testy przeglądarkowe i terenowe, aktualizacja środowiska
  produkcyjnego i procedury sekretów

## Wymagania (MVP)
- Jedna aktualna trasa GPX lub przejazd bez trasy; oba stany obsługuje mapa.
- Punkty POI mogą istnieć bez trasy i bez kilometra trasy.
- Tryb edycji POI aktywowany sekretem w parametrze `?s=SEKRET`.
- Tytuł trasy widoczny na stronie (z nazwy w GPX, a w razie braku z nazwy
  pliku); do rozważenia: data wgrania trasy.
- Mapa (nie grafika): zoom, przesuwanie, trasa, pozycja uczestnika,
  pozycja organizatora.
- Ogon organizatora: ostatnie 2 min (parametr konfiguracyjny).
- Brak start/stop w aplikacji: stan wynika z tego, czy dociera pozycja.
  Komunikacja rozróżnia trasę i dostępność znacznika WnB; POI nie wpływają na stan.
- Tytuły i statusy są tłumaczone; statusy wyświetlają się w stosie z animacją,
  a czas zanikania i czas statusów tymczasowych są ustawiane w `config.js`.
- Widoczność: każdy widzi organizatora i siebie; nikt nie widzi innych
  uczestników; pozycje uczestników nie są przechowywane.
- Języki: PL i EN, teksty w edytowalnych plikach językowych.
- Skala: do 200 jednoczesnych użytkowników (typowy szczyt ok. 130).
- Trasa ok. 4 h, udostępnianie pozycji do ok. 3 h.
- Wszystko w wersjach darmowych; uczestnik tylko przeglądarka mobilna.
- Organizator: natywna aplikacja do śledzenia w tle na iPhonie.

## Założenia i ryzyka
- Śledzenie w tle działa przez aplikację Traccar Client; strona jest tylko widokiem.
- Ruch od 130–200 osób musi być monitorowany pod kątem limitów hostingu i kafelków.
- Hosting OVH Personal został zatwierdzony na podstawie testów obciążeniowych.
- Kafelki OpenStreetMap nie mają gwarancji dostępności i mogą zostać zmienione.
- Sekrety ingest, upload i trybu edycji są utrzymywane poza publicznym repozytorium.

## Pytania otwarte
- Czy progi utraty sygnału potwierdzają się w pełnym przejeździe terenowym?
- Co eksportuje MapMyRun do GPX (nazwa trasy, gęstość punktów)?
- Zużycie baterii aplikacji trackingowych przy 3 h jazdy.

## Odnośniki
- DECISIONS.md – dziennik decyzji
- BACKLOG.md – lista zadań i stan prac
- ARCHITECTURE.md – komponenty i przepływy danych
- REQUIREMENTS.md – wymagania i kryteria weryfikacji
- RUNBOOK.md – operacje Organizatora
