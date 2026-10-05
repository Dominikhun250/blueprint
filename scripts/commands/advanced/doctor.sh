#!/bin/bash

Command() {
  cd "$FOLDER" || cdhalt

  # Forward arguments directly to the artisan command.
  php artisan bp:doctor "$@"
  exit $?
}