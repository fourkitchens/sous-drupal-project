#!/bin/sh
# Save a timestamped local database backup under reference/.
# Run npm run local-data-bak from the project root.
set -eu

ddev export-db --file="reference/db-$(date +%Y-%m-%d-%H%M).sql.gz" "$@"
