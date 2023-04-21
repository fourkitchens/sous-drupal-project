#!/bin/sh

cd web/themes/custom/sous-project
lando npm ci
lando npm run storybook:build
