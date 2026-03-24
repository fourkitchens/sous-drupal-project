#!/bin/sh

# If this is an existing cloned repository, rebuild against local data.
if [ -d .git ]; then
  ddev start
  ./devops/scripts/rebuild.sh
else
  # Fresh create-project install path.
  ./devops/scripts/sous-build.sh
fi
