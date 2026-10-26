#!/bin/sh

set -eu
. "$(dirname "$0")/../scripts/_runtime.sh"

# Project-owned, local/nonproduction only. Disable and verify external sync,
# queues and scheduled workers here using the project's existing facilities.
# Keep this idempotent: it also runs after a partially failed database import.
# Put always-on configuration kill switches in runtime settings so importing
# configuration cannot reactivate them. Sous verifies native mail/cron policy.
