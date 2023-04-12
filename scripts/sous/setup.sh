#!/bin/bash

lando start
lando npm install
lando npm emulsify init sous-project --platform drupal
lando npm --prefix ./web/themes/custom/sous-project install
lando npm --prefix ./web/themes/custom/sous-project emulsify system install compound
