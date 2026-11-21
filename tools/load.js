// Właściwy test obciążeniowy (6 minut):
//  - "readers": użytkownicy odpytujący latest.json co ok. 8 s (rośnie do 150, potem do 200)
//  - "writer":  jeden wirtualny WnB wysyłający pozycję co 5 s do skryptu ingest
//
// Uruchomienie (adres ingest podajesz w poleceniu, nie w pliku):
//   k6 run -e INGEST_URL="https://arturserafin.com/wnb/ingest-TWOJ-CIAG.php" load.js
//
// UWAGA: test zapisze sztuczne pozycje do live/latest.json i log/requests.log.
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE = 'https://arturserafin.com/wnb/';
const LIVE_URL = BASE + 'live/latest.json';
const INGEST_URL = __ENV.INGEST_URL;

export const options = {
  scenarios: {
    readers: {
      executor: 'ramping-vus',
      exec: 'reader',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 150 },  // narastanie do 150 osób
        { duration: '3m',  target: 150 },  // typowy przejazd (ok. 130 osób)
        { duration: '1m',  target: 200 },  // narastanie do górnej granicy
        { duration: '1m',  target: 200 },  // 200 osób
        { duration: '30s', target: 0 },
      ],
      gracefulRampDown: '10s',
    },
    writer: {
      executor: 'constant-vus',
      exec: 'writer',
      vus: 1,
      duration: '6m',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],                      // mniej niż 1% błędów
    'http_req_duration{name:read}': ['p(95)<1000'],      // odczyty: 95% szybciej niż 1 s
    'http_req_duration{name:write}': ['p(95)<1000'],     // zapisy: 95% szybciej niż 1 s
    checks: ['rate>0.999'],                              // praktycznie wszystkie kontrole OK
  },
};

export function setup() {
  if (!INGEST_URL) throw new Error('Podaj adres ingest: k6 run -e INGEST_URL="..." load.js');
}

export function reader() {
  const r = http.get(LIVE_URL + '?t=' + Date.now(), { tags: { name: 'read' } });
  check(r, {
    'odczyt: status 200': (x) => x.status === 200,
    'odczyt: poprawny JSON z pozycją i ogonem': (x) => {
      try { const d = x.json(); return !!d.last && Array.isArray(d.tail); } catch (e) { return false; }
    },
    'odczyt: jest nagłówek Date': (x) => !!x.headers['Date'],
  });
  sleep(7 + Math.random() * 2);   // 7-9 s, jak odpytywanie co ok. 8 s z rozrzutem
}

let n = 0;
export function writer() {
  n += 1;
  const r = http.post(INGEST_URL, {
    id: 'loadtest',
    lat: (52.2095 + n * 0.00001).toFixed(6),
    lon: (21.0200 + n * 0.00036).toFixed(6),       // ok. 25 m na krok, jak na prawdziwym przejeździe
    timestamp: Math.floor(Date.now() / 1000),
    accuracy: 10,
    speed: 9,                                      // węzły, ok. 17 km/h
    batt: 80,
  }, { tags: { name: 'write' } });
  check(r, {
    'zapis: status 200': (x) => x.status === 200,
    'zapis: odpowiedź OK': (x) => x.body === 'OK',
  });
  sleep(5);
}
