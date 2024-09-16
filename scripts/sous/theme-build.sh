#!/bin/sh

# Theme directories this script needs to build.
SRC_DIR="web/themes/custom/sous-project/src"
COMPONENTS_DIR="web/themes/custom/sous-project/components"

# Check if either of the directories exists
if [ -d "$SRC_DIR" ] || [ -d "$COMPONENTS_DIR" ]; then
  cd web/themes/custom/sous-project
  npm ci
  npm run storybook-build
else
  echo "Cannot find components to compile within the sous-project theme. Skipping build step."
fi