#!/bin/sh
set -eu
. "$(dirname "$0")/../scripts/_runtime.sh"

# Put project cleanup here so imported data is ready for sanitization.
# runtime_drush config:set system.site name "Local site" --yes
:
