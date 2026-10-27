#!/usr/bin/env bash

# DDEV may return from a restore before Supervisor's socket/extra daemon is up.
# The caller supplies a 45-second timeout; no Composer mutation is retried.
set -eu
[ "${SOUS_DISPOSABLE_TEST:-}" = 1 ] || { echo 'Use an owned disposable fixture.' >&2; exit 1; }
service=webextradaemons:sous-package-index
log=$(mktemp "${TMPDIR:-/tmp}/sous-index-ready.XXXXXX")
# Invoked by the EXIT trap.
# shellcheck disable=SC2329
finish() {
  status=$?
  trap - EXIT
  if [ "$status" -ne 0 ]; then
    echo 'Candidate index did not become ready before dependency installation.' >&2
    cat "$log" >&2
    supervisorctl status "$service" >&2 || :
    supervisorctl tail "$service" stderr >&2 || :
  fi
  rm -f "$log"
  exit "$status"
}
trap finish EXIT
trap 'exit 143' TERM

for ((attempt = 0; attempt < 30; attempt++)); do
  # Start only this declared fixture daemon. Already-running is harmless; a
  # missing socket is transient and is preserved if readiness ultimately fails.
  supervisorctl start "$service" > "$log" 2>&1 || :
  flags=()
  # Before the build, an HTTP 404 proves the server is listening. Once built,
  # require the actual index to return 200 before any Composer work proceeds.
  [ ! -f /var/www/html/.integration/publication/site/packages.json ] || flags=(--fail)
  if curl ${flags[@]+"${flags[@]}"} --silent --show-error --connect-timeout 1 --max-time 2 \
    --output /dev/null http://127.0.0.1:18737/site/packages.json >> "$log" 2>&1; then
    exit 0
  fi
  sleep 1
done
exit 1
