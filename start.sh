#!/usr/bin/env bash
set -euo pipefail

# Whole stack in Docker — no local Node/PHP required, identical build on every
# machine. Services (see docker-compose.yml):
#   API    php / nginx / mysql   http://localhost:8080
#   front  React + Vite          http://localhost:3000
#   admin  React + Vite          http://localhost:3001  (ROLE_ADMIN)
#   blog   Astro                 http://localhost:4321/blog/

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
COMPOSE=(docker compose -f "$SCRIPT_DIR/docker-compose.yml")

cmd="${1:-start}"

case "$cmd" in
  start)
    echo "==> Build + démarrage de toute la stack (docker compose)..."
    "${COMPOSE[@]}" up -d --build

    echo "==> Attente de l'API sur http://localhost:8080 ..."
    ready=false
    for _ in $(seq 1 90); do
      if curl -fsS http://localhost:8080/api/docs >/dev/null 2>&1; then
        ready=true
        break
      fi
      sleep 1
    done
    if [ "$ready" = false ]; then
      echo "!! L'API n'a pas répondu après 90s. Logs :"
      "${COMPOSE[@]}" logs --tail=50 php nginx
      exit 1
    fi

    cat <<EOF
==> Stack prête :
      API    http://localhost:8080
      front  http://localhost:3000
      admin  http://localhost:3001
      blog   http://localhost:4321/blog/

    Logs en direct : $0 logs
    Arrêt          : $0 stop
EOF
    ;;

  stop)
    echo "==> Arrêt et suppression des conteneurs..."
    "${COMPOSE[@]}" down
    ;;

  logs)
    shift || true
    "${COMPOSE[@]}" logs -f "$@"
    ;;

  status)
    "${COMPOSE[@]}" ps
    ;;

  *)
    cat <<EOF
Usage: $0 [start|stop|logs|status]

  start   (défaut) build + démarre toute la stack en arrière-plan (docker compose up -d --build).
  stop    arrête et supprime les conteneurs.
  logs    suit les logs (optionnel : nom de service, ex. "$0 logs front").
  status  affiche l'état des conteneurs.
EOF
    exit 1
    ;;
esac
