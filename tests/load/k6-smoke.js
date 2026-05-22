import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 10,
  duration: '30s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<800'],
  },
};

const BASE = __ENV.BASE_URL || 'http://127.0.0.1:8080/backend';

export default function () {
  const endpoints = [
    '/login.php',
    '/admin-login.php',
  ];

  endpoints.forEach((path) => {
    const res = http.get(`${BASE}${path}`, {
      headers: { Accept: 'text/html' },
    });
    check(res, {
      'status is 200 or 302': (r) => r.status === 200 || r.status === 302,
    });
  });

  sleep(1);
}

