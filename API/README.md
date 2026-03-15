# API

Symfony 8.0 API with hexagonal architecture and vertical slicing.

## Requirements

- Docker & Docker Compose

## Getting started

```bash
docker compose up -d
```

The API is available at `http://localhost:8080`.

## Quality tools

### PHP CS Fixer

Check code style:

```bash
docker exec api-php vendor/bin/php-cs-fixer fix --dry-run --diff
```

Fix code style:

```bash
docker exec api-php vendor/bin/php-cs-fixer fix
```

### PHPArkitect

Check architecture rules:

```bash
docker exec api-php vendor/bin/phparkitect check
```
