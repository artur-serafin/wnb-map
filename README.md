# WnB mapa przejazdu

Mapa mobilna dla wspólnych przejazdów Wtorek na Bulwarach. Strona pokazuje aktualną
pozycję WnB, planowanę trasę z GPX, punkty POI, pozycję Uczestnika i przewidywany czas
dotarcia do kolejnych punktów.

## Funkcje

- mapa Leaflet z kafelkami OpenStreetMap,
- trasę GPX z strzałkami kierunku, startem i metą,
- bieżącą pozycję WnB i dwuminutowy ogon,
- punkty POI typu `zabka`, `lody` i `default`,
- opcjonalną kolejność POI i opcjonalny kilometr trasy,
- przejazdy z trasą lub bez trasy,
- tryb edycji POI aktywowany osobnym sekretem,
- upload i usuwanie GPX oraz POI,
- archiwizacja tras do plików GPX z indeksem JSON,
- interfejs PL/EN i powiadomienia mobilne.
- ekran startowy z logo, z konfigurowalnymi czasami pełnego widoku i zanikania.

## Struktura

```text
site/
  index.html       mapa i cała logika przeglądarki
  config.js        konfiguracja i teksty ustawień
  lang/            tłumaczenia PL/EN
  data/            WnB.gpx i poi.json
  gpx-history/     zarchiwizowane GPX i routes.json

server/
  ingest-ZMIEN-MNIE.php
  upload-ZMIEN-MNIE.php
  update-ZMIEN-MNIE.php

docs/              wymagania, architektura, backlog i runbook
tools/             symulatory i testy obciążeniowe
```

## Tryb edycji POI

Stronę edycyjną otwiera się przez parametr sekretu:

```text
https://arturserafin.com/wnb/?s=SEKRET
```

W repozytorium znajduje się wzorzec:

```text
server/update-ZMIEN-MNIE.php
```

Przed wdrożeniem na serwer:

1. Zmień nazwę pliku na `update-<SEKRET>.php`.
2. Wartość sekretu pozostaje zakodowana wyłącznie w nazwie pliku na serwerze.
3. W `site/config.js` pozostaw publiczny szablon `UPDATE_URL_TEMPLATE: 'update-{secret}.php'`.
4. Nie wpisuj sekretu do `config.js`, źródeł stron ani dokumentacji.

Tryb edycji umożliwia dodawanie, przesuwanie, usuwanie i zmianę typu, kolejności oraz
kilometra punktu. Zmiany są lokalne do naciśnięcia save. Zamknięcie panelu punktu pozostawia
tryb edycji; zamknięcie całego trybu usuwa parametr `s` i przeładowuje stronę.

## Model POI

```json
[
  {
    "type": "default",
    "name": "Miejsce spotkania",
    "lat": 52.227726,
    "lon": 20.993112,
    "order": 1,
    "km": "12,524"
  }
]
```

- `type`: `zabka`, `lody` lub `default`; brak lub nieznana wartość oznacza `default`,
- `order`: opcjonalna kolejność; brak cyfry oznacza brak numeru na znaczniku,
- `km`: opcjonalny kilometr trasy; brak któregokolwiek poprawnego kilometra wyłącza ETA,
- `lat` i `lon` albo `pos: "lat, lon"`: wymagane współrzędne punktu.

## Zasady uploadu

- sam GPX zastępuje trasę i usuwa dotychczasowe POI,
- sam POI zastępuje tylko `poi.json` i zostawia GPX,
- GPX i POI zapisane razem zastępują obie dane,
- usunięcie może usunąć trasę i POI, gdy któryś plik nie istnieje.
- archiwizacja przenosi bieżący GPX bez zmiany zawartości do `gpx-history/`,
  zapisuje jego tytuł i długość w `gpx-history/routes.json` oraz usuwa POI,
  zachowując ich poprzednią wersję jako `.prev`.

## Uruchomienie lokalne

Repozytorium nie zawiera skryptu budującego ani zależności Node. Do podglądu strony
można użyć prostego serwera statycznego:

```powershell
Set-Location .\site
python -m http.server 4173
```

Następnie otwórz `http://127.0.0.1:4173/`. Otwórz `http://127.0.0.1:4173/?s=SEKRET`
dopiero po skonfigurowaniu lokalnego endpointu o tej samej nazwie.

Produkcyjne skrypty PHP wymagają hostingu Apache/PHP i plików poza repozytorium.
Szczegóły operacyjne znajdują się w [docs/RUNBOOK.md](docs/RUNBOOK.md).

## Dokumentacja

- [docs/PROJECT.md](docs/PROJECT.md) – cel i stan projektu
- [docs/BACKLOG.md](docs/BACKLOG.md) – lista prac
- [docs/TASKS.md](docs/TASKS.md) – zadania implementacyjne
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) – architektura i przepływy
- [docs/REQUIREMENTS.md](docs/REQUIREMENTS.md) – wymagania
- [docs/DECISIONS.md](docs/DECISIONS.md) – decyzje projektowe
- [docs/RUNBOOK.md](docs/RUNBOOK.md) – procedury Organizatora
