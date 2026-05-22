# Production Readiness Checklist

## Security
- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Set `SESSION_SECURE_COOKIE=true` and serve only over HTTPS.
- Keep `ALLOW_LEGACY_MUTATIONS=false`.
- Rotate secrets and set strong `APP_KEY`.
- Enforce least-privilege DB credentials.

## Database
- Run migrations in a controlled release window.
- Take backup before schema changes.
- Verify `applicants.email` canonical index exists.

## Application
- Cache config/routes/views:
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
- Ensure queue/cron workers are monitored.

## Testing
- Run contract/feature tests before deploy.
- Run preflight command:
  - `php artisan system:preflight --strict`
- Smoke-test critical flows:
  - login/logout
  - google onboarding
  - draft save + submit registration
  - admin user/vehicle/report mutations

## Observability
- Set centralized log collection and alerting.
- Track 4xx/5xx rates and auth failure spikes.
- Monitor DB CPU/slow queries and queue latency.
