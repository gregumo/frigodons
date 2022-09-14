# Aliases

Some aliases suggestions:

```sh
# Docker
alias dcu="docker compose up -d"
alias dcd="docker compose down"
alias dce="docker compose exec"
alias dps="docker ps"

# Composer
alias cmp="docker compose exec php composer";

# Symfony
alias sf="docker compose exec php bin/console";
alias cc="docker compose exec php bin/console cache:clear";
alias dsu="docker compose exec php bin/console doctrine:schema:update";
alias dml="docker compose exec php bin/console doctrine:migrations:list";
alias dms="docker compose exec php bin/console doctrine:migrations:status";
alias dme="docker compose exec php bin/console doctrine:migrations:execute";
```
