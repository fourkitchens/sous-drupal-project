#!/bin/sh

set -eu
. "$(dirname "$0")/../scripts/_runtime.sh"

# Project-owned, local/staging only. Reassert and verify the same external
# integration/worker safeguards used by post-import.sh. Configuration deployment
# must not silently restore their production state. This also runs after failed
# deployment; return nonzero if a required safeguard cannot be established.
