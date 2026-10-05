#!/bin/bash

Command() {
  cd "$FOLDER" || cdhalt

  args=()
  for arg in "$@"; do
    case "$arg" in
      --force|-f) args+=("--force") ;;
      --from=*) args+=("$arg") ;;
      --to=*) args+=("$arg") ;;
      *) ;;
    esac
  done

  php artisan bp:migrate "${args[@]}"
  exit $?
}