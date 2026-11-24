# Zadania implementacyjne – tryb edycji POI

Stan: backlog i design przygotowane przed implementacją. Nie oznacza to rozpoczęcia zmian w kodzie.

## Kolejność

1. Dane i normalizacja
2. Serwer i zapis
3. Renderowanie i tryb bez trasy
4. Gesty i UI edycji
5. Testy i dokumentacja

## 1. Dane i normalizacja

### T-01 – Znormalizować model POI
- Dodać `order` jako opcjonalną liczbę całkowitą.
- Normalizować brak/nieznany `type` do `default`.
- Normalizować `km` jako opcjonalny tekst/liczbę.
- Przy zapisie edytora ułożyć `order` w ciągu `1...N`.
- Kryterium: samo POI bez wszystkich nowych pól nadal jest poprawne.

### T-02 – Zaktualizować walidację uploadu i save
- Akceptować `zabka`, `lody`, `odpoczynek`, `default` i brak `type`.
- Odrzucać niepoprawną pozycję i złą kolejność.
- Nie wymagać `km`.
- Dopisać ostrzeżenie o kilometrze większym od trasy.
- Kryterium: 404 trasy nie blokuje walidacji/samodzielnego POI.

## 2. Serwer

### T-03 – Dodać update endpoint
- Utworzyć wzorzec `server/update-ZMIEN-MNIE.php`.
- Dodać akcje `verify` i `save`.
- Weryfikować metodę, rozmiar, JSON i sekret.
- Zapis atomowy i kopia `.prev`.
- Kryterium: `401/422/413/500` są rozróżnione i przy odrzuceniu nie tworzą nowego `poi.json`.

### T-04 – Skorygować upload i usuwanie
- Sam POI zastępuje tylko POI i zostawia GPX.
- Sam GPX zastępuje GPX i usuwa stare POI.
- GPX + POI zastępuje oba pliki.
- Delete usuwa istniejące pliki osobno i nie zwraca błędu przy ich braku.
- Kryterium: testy 24+ scenariuszy zachowują poprzedni stan przy błędzie.

## 3. Mapy i POI

### T-05 – Dodać render typu default
- Kółko bez grafiki, kolor przez CSS/Leaflet.
- Cyfra `order` wyśrodkowana w kółku.
- Ogon oraz popup nadal zachowują dotychczasową logikę.
- Kryterium: punkty `default`, `zabka`, `lody`, `odpoczynek` są różne i czytelne.

### T-06 – Naprawić POI bez trasy i km
- `noRoute()` musi wywołać `loadPoi()`.
- Punkt pojawia się bez GPX.
- Mapa bez trasy wyłącza fit, kilometraż trasy i ETA.
- Kryterium: punkt POI działa na mapie bez trasy i bez kilometra.

### T-07 – Globalnie wyłączyć ETA przy jednym niepełnym km
- Najpierw sprawdzić kompletność wszystkich POI z poprawnymi km.
- Nie pokazywać ETA dla żadnego POI ani mety.
- Kryterium: jeden pusty km usuwa wszystkie etykiety ETA.

## 4. Tryb edycji

### T-08 – Sekret i przejście trybu
- Odczytać `s` z URL i wywołać verify.
- W trybie edycji ukryć menu i normalne fabs.
- Pokazać close i save.
- Kryterium: błędny sekret nie aktywuje trybu.

### T-09 – Stan lokalny i pop-over
- Dodać kopię listy POI w pamięci.
- Pola: nazwa, typ, kolejność, km, usuń.
- Kolejność ma tylko `select`, elementy 1…N.
- Kryterium: zmiany są widoczne natychmiast i nie zapisują się same.

### T-10 – Long-press i przenoszenie
- Długi dotyk na mapie tworzy POI.
- Długi dotyk znacznika otwiera edycję.
- Drag przesuwa po wskaźniku i nie przesuwa mapy.
- Drag przy krawędzi uruchamia map panning.
- Kryterium: gesty działają myszą i na iOS/Android.

### T-11 – Kolejność i normalizacja
- Przenosić edytowany punkt i przenumerowywać pozostałe.
- Odświeżać cyfry i opcje kolejności natychmiast.
- Kryterium: każda lista ma unikalny, ciągły `order`.

### T-12 – Save i błąd
- Save wysyła całość i wymagany sekret.
- Sukces odświeża stan z serwera.
- Porażka zostawia ekran i dane lokalne bez zmian.
- Kryterium: zniknięcie sieci nie usuwa lokalnych zmian.

## 5. Testy i dokumentacja

### T-13 – Testy jednostkowe i matematyczne
- Normalizacja typu i kolejności.
- Walidacja km przecinek/kropka.
- Globalna reguła ETA.
- Kolejność po przesunięciu.

### T-14 – Testy PHP
- verify/save; błędny sekret.
- brak GPX w delete.
- brak POI w nowym GPX.
- atomowość i `.prev`.

### T-15 – Testy przeglądarki
- iPhone Safari, Android Chrome, desktop.
- long press, drag, edge panning, pop-over i close.
- brak trasy i brak km.
- save success/error.

### T-16 – Dokumentacja
- Zaktualizować teksty PL/EN.
- Dopisać runbook edycji i zmianę sekretu.
- Uzupełnić testy akceptacyjne i plan terenu.
