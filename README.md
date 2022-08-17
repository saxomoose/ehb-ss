# Host requirements

php version on docker host: 7 or 8.

[composer](https://getcomposer.org/). Required php extensions can be checked by running `composer check-platform-reqs`. Install missing extensions if any.

[docker](https://www.docker.com/) and [docker compose](https://docs.docker.com/compose/install/).

# Installation

Clone repo: `git clone <url> backend`.

Add `.env` file to root folder.

Below commands should be run from top folder.

Install dependencies: `composer update`.

Build API image: `sail build [--no-cache]`.

Boot containers: `sail up -d`.

Upgrade dependencies within container: `sail composer update`.

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