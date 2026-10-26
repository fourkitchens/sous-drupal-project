#!/bin/sh

set -eu
# Project-owned: write a complete backup to the sole supplied temporary path.
# Configure an explicit source here; do not rename or import the file yourself.
echo 'No database download source is configured. Supply a backup or configure devops/hooks/download-db.sh.' >&2
exit 1
