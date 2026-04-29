#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
COMPOSE=(docker compose -f "$SCRIPT_DIR/API/docker-compose.yml")

cmd="${1:-start}"

case "$cmd" in
  start)
    cleanup() {
      echo
      echo "==> Arrêt du backend..."
      "${COMPOSE[@]}" stop
    }
    trap cleanup EXIT INT TERM

    echo "==> Démarrage du backend (docker compose)..."
    "${COMPOSE[@]}" up -d

    echo "==> Attente de l'API sur http://localhost:8080 ..."
    ready=false
    for _ in $(seq 1 60); do
      if curl -fsS http://localhost:8080/api/docs >/dev/null 2>&1; then
        ready=true
        break
      fi
      sleep 1
    done
    if [ "$ready" = false ]; then
      echo "!! L'API n'a pas répondu après 60s. Logs :"
      "${COMPOSE[@]}" logs --tail=50
      exit 1
    fi
    echo "==> API prête."

    echo "==> Démarrage du frontend sur http://localhost:3000 ..."
    cd "$SCRIPT_DIR/front"
    npm start
    ;;

  stop)
    echo "==> Arrêt et suppression des conteneurs backend..."
    "${COMPOSE[@]}" down
    ;;

  logs)
    "${COMPOSE[@]}" logs -f
    ;;

  status)
    "${COMPOSE[@]}" ps
    ;;

  *)
    cat <<EOF
Usage: $0 [start|stop|logs|status]

  start   (défaut) lance le backend (docker) puis le frontend (npm start).
          Ctrl+C arrête le frontend et stoppe les conteneurs backend.
  stop    arrête et supprime les conteneurs backend.
  logs    suit les logs des conteneurs backend.
  status  affiche l'état des conteneurs backend.
EOF
    exit 1
    ;;
esac
