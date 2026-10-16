#!/bin/bash
set -e

deploy_php_api() {
  service_name=php-api
  old_container_id=$(docker ps -f name=$service_name -l -q)

  # Bring a new container online, running new code,
  # (Caddy starts routing to both the old container and new container)
  docker compose up -d --no-deps --scale $service_name=2 --no-recreate $service_name --wait

  # Run migrations in the new container
  new_container_id=$(docker ps -f name=$service_name -l -q)
  docker exec "$new_container_id" bin/console doctrine:migrations:migrate --all-or-nothing --allow-no-migration --no-interaction

  # Take the old container offline (if $old_container is not empty).
  if [ -n "${old_container_id}" ]; then
    docker stop "$old_container_id"
    docker rm "$old_container_id"
  fi

  docker compose up -d --no-deps --scale $service_name=1 --no-recreate $service_name --wait
}

docker compose up database -d --wait;
deploy_php_api;
docker compose up -d --remove-orphans --wait;
