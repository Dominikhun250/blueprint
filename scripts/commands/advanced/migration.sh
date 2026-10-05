#!/bin/bash

Command() {
  cd "$FOLDER" || cdhalt

  action="$1"; shift || true

  case "$action" in
    status)   php artisan bp:migration:status ;;
    history)  php artisan bp:migration:history ;;
    show)     php artisan bp:migration:show "$@" ;;
    rollback) php artisan bp:migration:rollback "$@" ;;
    *)
      PRINT FATAL "Unknown migration action '$action'. Use: status|history|show|rollback."
      exit 2
      ;;
  esac
  exit $?
}