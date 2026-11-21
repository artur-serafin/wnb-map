// Test skoku na starcie: 200 osób otwiera stronę prawie jednocześnie.
// Każda "osoba" pobiera 3 pliki z OVH: index.html, config.js i trasę GPX.
// (Leaflet i kafelki map pochodzą z innych serwerów, więc ich tu nie testujemy.)
//
// Uruchomienie:  k6 run burst.js
import http from 'k6/http';
import { check } from 'k6';

const BASE = 'https://arturserafin.com/wnb/';

export const options = {
  scenarios: {
    // Etap 1: 200 osób w ciągu 20 s (10 nowych osób na sekundę), np. ludzie zbierający się na starcie
    arrival: {
      executor: 'constant-arrival-rate',
      rate: 10, timeUnit: '1s', duration: '20s',
      preAllocatedVUs: 50, maxVUs: 200,
    },
    // Etap 2 (po 40 s): 200 osób w tej samej chwili, np. po wrzuceniu linku na grupę
    instant: {
      executor: 'shared-iterations',
      vus: 200, iterations: 200, maxDuration: '30s',
      startTime: '40s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],          // mniej niż 1% błędów
    http_req_duration: ['p(95)<2000'],       // 95% odpowiedzi szybciej niż 2 s
    checks: ['rate>0.999'],
  },
};

export default function () {
  const res = http.batch([
    ['GET', BASE, null, { tags: { name: 'page' } }],
    ['GET', BASE + 'config.js', null, { tags: { name: 'config' } }],
    ['GET', BASE + 'data/WnB.gpx', null, { tags: { name: 'gpx' } }],
  ]);
  check(res[0], { 'strona: status 200': (r) => r.status === 200 });
  check(res[1], { 'config: status 200': (r) => r.status === 200 });
  check(res[2], { 'trasa: status 200 i niepusta': (r) => r.status === 200 && r.body.length > 1000 });
}
