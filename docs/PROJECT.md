# Mapa przejazdu rolkowego – status projektu

## Cel
Darmowa strona mobilna (bez instalacji dla uczestników), na której
uczestnik widzi na jednej mapie: planowaną trasę (GPX), swoją pozycję
i bieżącą pozycję organizatora. Organizator udostępnia pozycję z iPhone'a,
także w tle.

## Stan na dziś
- Faza: 1 zakończona (wymagania wstępne), następna: 2 (spiki techniczne)
- Zrobione: konto GitHub, repozytorium, dokumenty startowe
- Następny krok: spike'i techniczne (tracking iPhone, mapa, hosting)

## Wymagania (MVP)
- Jedna aktualna trasa GPX, wgrywana tylko przez organizatora.
- Tytuł trasy widoczny na stronie (z nazwy w GPX, a w razie braku z nazwy
  pliku); do rozważenia: data wgrania trasy.
- Mapa (nie grafika): zoom, przesuwanie, trasa, pozycja uczestnika,
  pozycja organizatora.
- Ogon organizatora: ostatnie 2 min (parametr konfiguracyjny).
- Brak start/stop w aplikacji: stan wynika z tego, czy dociera pozycja.
  Brak aktualnej pozycji => komunikat dla uczestnika, trasa nadal widoczna.
- Widoczność: każdy widzi organizatora i siebie; nikt nie widzi innych
  uczestników; pozycje uczestników nie są przechowywane.
- Języki: PL i EN, teksty w edytowalnych plikach językowych.
- Skala: do 200 jednoczesnych użytkowników (typowy szczyt ok. 130).
- Trasa ok. 4 h, udostępnianie pozycji do ok. 3 h.
- Wszystko w wersjach darmowych; uczestnik tylko przeglądarka mobilna.
- Organizator: natywna aplikacja do śledzenia w tle na iPhonie.

## Założenia i ryzyka
- Śledzenie w tle na iPhonie nie działa ze strony WWW – potrzebna
  aplikacja natywna (kandydaci: Traccar Client, OwnTracks).
- Ruch od 130–200 osób może przekraczać limity darmowych planów.
- Hosting: do sprawdzenia, w tym istniejący hosting OVH (plan Personal).
- Domena: na razie dowolna, własna do ewentualnego zakupu później.

## Pytania otwarte
- Po ilu minutach bez danych uznać, że organizator nie udostępnia pozycji?
- Co eksportuje MapMyRun do GPX (nazwa trasy, gęstość punktów)?
- Zużycie baterii aplikacji trackingowych przy 3 h jazdy.

## Odnośniki
- DECISIONS.md – dziennik decyzji
