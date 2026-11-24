// Symulator przejazdu: wysyła do skryptu ingest pozycje wzdłuż trasy z pliku GPX,
// jakby WnB jechał (punkt co 5 s), z opcjonalnymi postojami. Służy do testów strony bez wychodzenia z domu.
//
// Uruchomienie (jedna linia):
//   k6 run -e INGEST_URL="https://arturserafin.com/wnb/ingest-TWOJ-SEKRET.php" ride.js
// Opcje (dodaj kolejne -e):
//   -e SPEED_KMH=18          prędkość jazdy (domyślnie 18)
//   -e START_KM=5            od którego kilometra trasy zacząć (domyślnie 0)
//   -e GPX_VARIATION=15      maksymalne boczne odchylenie od GPX w metrach (domyślnie 0)
//   -e STOPS="7.985:90"      postoje: kilometr:sekundy, oddzielone przecinkami, np. "7.985:90,15.461:120"
//   -e ROUTE="WnB.gpx"       nazwa pliku z trasą
//
// UWAGA: skrypt zapisuje sztuczne pozycje do live/latest.json. Nie uruchamiaj go podczas prawdziwego przejazdu.
import http from 'k6/http';
import { sleep } from 'k6';

const INGEST_URL = __ENV.INGEST_URL;
const SPEED = parseFloat(__ENV.SPEED_KMH || '18');
const START_KM = parseFloat(__ENV.START_KM || '5');
const GPX_VARIATION = Math.max(0, parseFloat(__ENV.GPX_VARIATION || '0'));
const STOPS = (__ENV.STOPS || '').split(',').filter(Boolean).map((s) => {
  const [km, sec] = s.split(':');
  return { km: parseFloat(km.replace(',', '.')), sec: parseFloat(sec), done: false };
});
const ROUTE = (__ENV.ROUTE || 'WnB.gpx');

const gpx = open(`${ROUTE}`);
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

function posWithVariation(d, meters) {
  const i = posAtIndex(d);
  const a = route[i - 1], b = route[i];
  const t = (d - cum[i - 1]) / (cum[i] - cum[i - 1]);
  const lat = a[0] + (b[0] - a[0]) * t;
  const lon = a[1] + (b[1] - a[1]) * t;
  const cosLat = Math.cos(lat * Math.PI / 180);
  const eastMeters = (b[1] - a[1]) * cosLat * 111320;
  const northMeters = (b[0] - a[0]) * 111320;
  const length = Math.hypot(eastMeters, northMeters);
  if (!length) return [lat, lon];
  const perpendicularEast = -northMeters / length;
  const perpendicularNorth = eastMeters / length;
  return [
    lat + perpendicularNorth * meters / 111320,
    lon + perpendicularEast * meters / (111320 * cosLat),
  ];
}

function posAtIndex(d) {
  let i = 1;
  while (i < cum.length - 1 && cum[i] < d) i++;
  return i;
}

function clamp(v, min, max) {
  return Math.max(min, Math.min(max, v));
}

function send(d, speedKmh, variationMeters) {
  const p = posWithVariation(d, variationMeters);
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
  if (!Number.isFinite(SPEED) || SPEED <= 0) throw new Error('SPEED_KMH musi być dodatnią liczbą');
  if (!Number.isFinite(START_KM) || START_KM < 0) throw new Error('START_KM musi być liczbą większą lub równą zero');
  if (!Number.isFinite(GPX_VARIATION) || GPX_VARIATION < 0) throw new Error('GPX_VARIATION musi być liczbą większą lub równą zero');
  let d = START_KM, tick = 0;
  let speedFactor = 1;
  let speedTarget = 1;
  let variationMeters = 0;
  let variationTarget = 0;
  console.log('Trasa: ' + TOTAL.toFixed(2) + ' km, start od km ' + START_KM +
    ', prędkość ' + SPEED + ' km/h +/- 20%, odchylenie od GPX +/- ' + GPX_VARIATION + ' m');
  while (d <= TOTAL) {
    // Płynne tempo: co 20 s wybieramy nowe tempo i odchylenie, a stan dąży do celu
    // z dodatkowymi niewielkimi falami. Ograniczenia zawsze pozostają ±20% i ±GPX_VARIATION.
    if (tick % 4 === 0) {
      speedTarget = 0.8 + Math.random() * 0.4;
      variationTarget = (Math.random() * 2 - 1) * GPX_VARIATION;
    }
    speedFactor += (speedTarget - speedFactor) * 0.35;
    speedFactor = clamp(speedFactor + Math.sin(tick / 7) * 0.008, 0.8, 1.2);
    variationMeters += (variationTarget - variationMeters) * 0.25;
    variationMeters = clamp(variationMeters + Math.sin(tick / 5) * 0.25, -GPX_VARIATION, GPX_VARIATION);

    const speedKmh = SPEED * speedFactor;
    send(d, speedKmh, variationMeters);
    const stop = STOPS.find((s) => !s.done && d >= s.km);
    if (stop) {
      stop.done = true;
      console.log('Postój na km ' + d.toFixed(3) + ' przez ' + stop.sec + ' s');
      send(d, 0, variationMeters); // punkt z prędkością zero, jak przy zatrzymaniu
      sleep(stop.sec);     // w czasie postoju aplikacja nic nie wysyła
    }
    if (tick % 12 === 0) console.log('km ' + d.toFixed(2));
    tick += 1;
    d += (speedKmh / 3600) * 5;
    sleep(5);
  }
  console.log('Koniec trasy');
}
