#!/bin/sh

cd web/themes/custom/sous-project
# echo "clear npm cache"
npm cache clear --force
# echo "install packages"
npm install --prefer-offline --no-audit --loglevel verbose
echo "build the theme assets"
npm run build
# Uncomment when *.stories.js are available in the theme.
# npm run storybook-build
