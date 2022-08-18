# Host requirements

[docker](https://www.docker.com/) and [docker compose](https://docs.docker.com/compose/install/).

# Installation

Clone repo: `git clone <url> backend`.

Add `.env` file to root folder.

Below commands should be run from root folder.

Use a bootstrapping container to install the application's dependencies:
```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php81-composer:latest \
    composer install --ignore-platform-reqs
```

Build API image: `sail build [--no-cache]`.

Boot containers: `sail up -d`.

Grant all privileges to database user dba:

```bash
docker exec -it backend-db-1 sh
mysql -u root -p
GRANT ALL PRIVILEGES ON *.* TO dba@'%';
FLUSH PRIVILEGES;
```

Run all migrations against central and tenant databases. Seed data:

```bash
sail artisan migrate:fresh --seed
sail artisan tenants:seed
```

# Services

| service | external url |
|---|---|
| phpmyadmin | http://[docker host ip]:8080 |
| mailhog | http://[docker host ip]:8025 |
| api | http://[docker host ip] |

# Use

The application implements a multi-tenanted architecture. The [frontend](https://github.com/mathieu-tulpinck/ehb-ad) connects to the tenant back-end API. 

For demonstration purposes, the central domain is `backend.test` and the demo tenant domain is `demo.backend.test`.

To shut the services down: `sail down`.