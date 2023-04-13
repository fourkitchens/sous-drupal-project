#!/bin/bash

lando start
lando emulsify init sous-project --platform drupal
lando npm --prefix ./web/themes/custom/sous-project install
lando emulsify --prefix ./web/themes/custom/sous-project system install compound
