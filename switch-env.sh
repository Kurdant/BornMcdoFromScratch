#!/bin/bash
ENV=${1:-dev}
if [[ "$ENV" == "prod" ]]; then
  cp .env.prod .env
  docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
elif [[ "$ENV" == "dev" ]]; then
  cp .env.dev .env
  docker-compose up -d
else
  echo "Usage: $0 [dev|prod]"
  exit 1
fi
echo "✓ Environnement basculé sur : $ENV"
