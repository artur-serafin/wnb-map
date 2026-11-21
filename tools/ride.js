// Symulator przejazdu: wysyła do skryptu ingest pozycje wzdłuż trasy z pliku WnB.gpx,
// jakby WnB jechał (punkt co 5 s), z opcjonalnymi postojami. Służy do testów strony bez wychodzenia z domu.
//
// Plik WnB.gpx musi leżeć w tym samym folderze co ten skrypt.
// Uruchomienie (jedna linia):
//   k6 run -e INGEST_URL="https://arturserafin.com/wnb/ingest-TWOJ-SEKRET.php" ride.js
// Opcje (dodaj kolejne -e):
//   -e SPEED_KMH=18          prędkość jazdy (domyślnie 18)
//   -e START_KM=5            od którego kilometra trasy zacząć (domyślnie 0)
//   -e STOPS="7.985:90"      postoje: kilometr:sekundy, oddzielone przecinkami, np. "7.985:90,15.461:120"
//
// UWAGA: skrypt zapisuje sztuczne pozycje do live/latest.json. Nie uruchamiaj go podczas prawdziwego przejazdu.
import http from 'k6/http';
import { sleep } from 'k6';

const INGEST_URL = __ENV.INGEST_URL;
const SPEED = parseFloat(__ENV.SPEED_KMH || '18');
const START_KM = parseFloat(__ENV.START_KM || '5');
const STOPS = (__ENV.STOPS || '').split(',').filter(Boolean).map((s) => {
  const [km, sec] = s.split(':');
  return { km: parseFloat(km.replace(',', '.')), sec: parseFloat(sec), done: false };
});

const gpx = open('WnB.gpx');
const route = [];
for (const m of gpx.matchAll(/<trkpt lat="([\d.]+)" lon="([\d.]+)"/g)) {
  const ll = [parseFloat(m[1]), parseFloat(m[2])];
  const last = route[route.length - 1];
  if (!last || last[0] !== ll[0] || last[1] !== ll[1]) route.push(ll);   // jak na stronie: bez zdublowanych punktów
}

function distKm(a, b) {   // ta sama formuła co na stronie, żeby kilometry się zgadzały
  const R = 6371, rad = Math.PI / 180, dLat = (b[0] - a[0]) * rad, dLon = (b[1] - a[1]) * rad;
  const h = Math.sin(dLat / 2) ** 2 + Math.cos(a[0] * rad) * Math.cos(b[0] * rad) * Math.sin(dLon / 2) ** 2;
  return 2 * R * Math.asin(Math.sqrt(h));
}
const cum = [0];
for (let i = 1; i < route.length; i++) cum.push(cum[i - 1] + distKm(route[i - 1], route[i]));
const TOTAL = cum[cum.length - 1];

function posAt(d) {
  let i = 1;
  while (i < cum.length - 1 && cum[i] < d) i++;
  const t = (d - cum[i - 1]) / (cum[i] - cum[i - 1]);
  return [route[i - 1][0] + (route[i][0] - route[i - 1][0]) * t, route[i - 1][1] + (route[i][1] - route[i - 1][1]) * t];
}

function send(d, speedKmh) {
  const p = posAt(d);
  const body = {
    id: 'simulator', lat: p[0].toFixed(6), lon: p[1].toFixed(6),
    timestamp: Math.floor(Date.now() / 1000), accuracy: 10, batt: 80,
  };
  if (speedKmh != null) body.speed = (speedKmh / 1.852).toFixed(2);   // Traccar podaje prędkość w węzłach
  http.post(INGEST_URL, body);
}

export const options = { vus: 1, iterations: 1 };

export default function () {
  if (!INGEST_URL) throw new Error('Podaj adres ingest: k6 run -e INGEST_URL="..." ride.js');
  const stepKm = (SPEED / 3600) * 5;   // dystans w ciągu 5 s
  let d = START_KM, tick = 0;
  console.log('Trasa: ' + TOTAL.toFixed(2) + ' km, start od km ' + START_KM + ', prędkość ' + SPEED + ' km/h');
  while (d <= TOTAL) {
    send(d, SPEED);
    const stop = STOPS.find((s) => !s.done && d >= s.km);
    if (stop) {
      stop.done = true;
      console.log('Postój na km ' + d.toFixed(3) + ' przez ' + stop.sec + ' s');
      send(d, 0);          // punkt z prędkością zero, jak przy zatrzymaniu
      sleep(stop.sec);     // w czasie postoju aplikacja nic nie wysyła
    }
    if (tick % 12 === 0) console.log('km ' + d.toFixed(2));
    tick += 1;
    d += stepKm;
    sleep(5);
  }
  console.log('Koniec trasy');
}
