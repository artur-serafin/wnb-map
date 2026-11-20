// Próbny test (smoke): 10 wirtualnych użytkowników odpytuje latest.json co 8 s przez 30 s.
// Cel: sprawdzić, czy narzędzie działa i czy serwer odpowiada poprawnie.
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 10,           // liczba wirtualnych użytkowników
  duration: '30s',   // czas trwania testu
  thresholds: {
    http_req_failed: ['rate<0.01'],      // mniej niż 1% błędów
    http_req_duration: ['p(95)<1000'],   // 95% odpowiedzi szybciej niż 1 s
  },
};

const URL = 'https://arturserafin.com/wnb-test/live/latest.json';

export default function () {
  const r = http.get(URL + '?t=' + Date.now());
  check(r, {
    'status 200': (x) => x.status === 200,
    'poprawny JSON z pozycją': (x) => {
      try { return !!x.json().last; } catch (e) { return false; }
    },
    'jest nagłówek Date': (x) => !!x.headers['Date'],
  });
  sleep(8);
}
