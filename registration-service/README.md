# Diaspora registration prototype

A development prototype of the separately protected registration service
proposed in [ADR 0010](../docs/adr/0010-registration-as-separate-service.md).
**It accepts synthetic data only.** It must never receive real personal or
passport information, and it is not approved for deployment.

## Run it locally

Requires PHP 8.3 with `pdo_pgsql`, `intl` and `sodium`, Composer 2 and
PostgreSQL 16.

```bash
cd registration-service
composer install
createuser mofa_registry --pwprompt --createdb
createdb mofa_registry --owner=mofa_registry
cat > .env.local <<ENV
APP_SECRET=$(php -r 'echo bin2hex(random_bytes(16));')
DATABASE_URL="postgresql://mofa_registry:YOUR_PASSWORD@127.0.0.1:5432/mofa_registry?serverVersion=16&charset=utf8"
REGISTRY_FIELD_KEY=$(php -r 'echo base64_encode(random_bytes(32));')
REGISTRY_INDEX_KEY=$(php -r 'echo base64_encode(random_bytes(32));')
ENV
php bin/console doctrine:migrations:migrate
php bin/console registry:seed-synthetic --count=20   # optional
cd public && php -S 127.0.0.1:8081
```

Open http://127.0.0.1:8081/register. To link the Drupal site's diaspora
registration page to it, start Drupal with
`MOFA_REGISTRATION_PROTOTYPE_URL=http://127.0.0.1:8081/register` and
rebuild caches.

## Tests

```bash
createdb mofa_registry_test --owner=mofa_registry
echo 'DATABASE_URL="postgresql://mofa_registry:YOUR_PASSWORD@127.0.0.1:5432/mofa_registry?serverVersion=16&charset=utf8"' > .env.test.local
php bin/console doctrine:migrations:migrate --env=test
php bin/phpunit
cd ../tests/e2e && REGISTRY_URL=http://127.0.0.1:8081 npx playwright test specs/registry.spec.js
```

## Synthetic data rules

- Passport numbers: `SYN` followed by six digits, such as `SYN123456`.
- Email addresses: example.com, example.org, example.net, `.example` or `.test`.
- Phone numbers: use the fictional +1 555-01xx range, such as `+15550100123`.

See [ADR 0015](../docs/adr/0015-synthetic-only-guards.md) for every guard.

## Layout

| Path | What it is |
| --- | --- |
| `src/Registration/` | Form data, reference numbers, the service that stores records |
| `src/Security/FieldCipher.php` | Field encryption and blind indexes |
| `src/Entity/` | Registration, encrypted details, audit log |
| `src/EventSubscriber/PrototypeGuardSubscriber.php` | Synthetic-mode guard and security headers |
| `migrations/` | Database schema, constraints and audit trigger |
| `public/css/shared/` | Copies of the website theme's CSS; CI checks they match |
