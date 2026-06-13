#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
COMPOSE=(docker compose -f "$SCRIPT_DIR/API/docker-compose.yml")

# Charge nvm pour aligner la version de Node (le blog .nvmrc demande Node 20).
if [ -s "${NVM_DIR:-$HOME/.nvm}/nvm.sh" ]; then
  # shellcheck disable=SC1091
  . "${NVM_DIR:-$HOME/.nvm}/nvm.sh"
  if [ -f "$SCRIPT_DIR/blog/.nvmrc" ]; then
    (cd "$SCRIPT_DIR/blog" && nvm use >/dev/null) || nvm use 20 >/dev/null
  fi
fi

cmd="${1:-start}"

case "$cmd" in
  start)
    BLOG_PID=""
    ADMIN_PID=""
    cleanup() {
      echo
      if [ -n "$BLOG_PID" ] && kill -0 "$BLOG_PID" 2>/dev/null; then
        echo "==> Arrêt du blog (pid $BLOG_PID)..."
        kill "$BLOG_PID" 2>/dev/null || true
        wait "$BLOG_PID" 2>/dev/null || true
      fi
      if [ -n "$ADMIN_PID" ] && kill -0 "$ADMIN_PID" 2>/dev/null; then
        echo "==> Arrêt du back-office admin (pid $ADMIN_PID)..."
        kill "$ADMIN_PID" 2>/dev/null || true
        wait "$ADMIN_PID" 2>/dev/null || true
      fi
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

    echo "==> Démarrage du blog sur http://localhost:4321/blog/ ..."
    if [ ! -d "$SCRIPT_DIR/blog/node_modules" ]; then
      echo "==> Installation des dépendances du blog..."
      (cd "$SCRIPT_DIR/blog" && npm install)
    fi
    (cd "$SCRIPT_DIR/blog" && npm run dev) &
    BLOG_PID=$!

    echo "==> Démarrage du back-office admin sur http://localhost:3001 ..."
    if [ ! -d "$SCRIPT_DIR/admin/node_modules" ]; then
      echo "==> Installation des dépendances du back-office admin..."
      (cd "$SCRIPT_DIR/admin" && npm install)
    fi
    (cd "$SCRIPT_DIR/admin" && npm run dev) &
    ADMIN_PID=$!

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
