# Requirements

- php v7.4 or higher
- [composer](https://getcomposer.org/)
- [docker](https://www.docker.com/) and [docker compose](https://docs.docker.com/compose/install/)

# Installation

Clone repo: `git clone <url> backend`.

Add `.env` file to root folder.

Below commands should be run from root folder.

Install php extensions:
```bash
sudo apt install \
    php7.4-bcmath \
    php7.4-common \
    php7.4-curl \
    php7.4-json \
    php7.4-mbstring \
    php7.4-xml
```

Install dependencies: `composer update`.

Build api image: `sail build [--no-cache]`.

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

The application implements a multi-tenanted architecture. The [frontend](https://github.com/mathieu-tulpinck/ehb-ad) connects to the tenant back-end. 

For demonstration purposes, the central domain is `backend.test` and the demo tenant domain is `demo.backend.test`.

To shut the services down: `sail down`.