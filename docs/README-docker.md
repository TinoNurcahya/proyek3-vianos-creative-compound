# Docker (development) for proyek3-vianos-creative-compound

Quick start:

1. Copy environment file:

   - Linux/macOS: `cp .env.example .env`
   - Windows CMD: `copy .env.example .env`

2. Build and start containers:

```bash
docker compose up -d --build
```

3. (Optional) Enter the app container:

```bash
docker compose exec app sh
```

App will be available at `http://localhost` (served by nginx).

Notes:
- If your Docker uses older compose, run `docker-compose` instead of `docker compose`.
- Run `composer install` inside the container if needed.
