# Install

We use Docker to manage web server, database and extra services.

To install Docker and Docker Compose: https://docs.docker.com/compose/install/

First build:
```sh
docker compose build --pull --no-cache
```

Launch containers:
```sh
docker compose up -d
```

App is now available at https://localhost
