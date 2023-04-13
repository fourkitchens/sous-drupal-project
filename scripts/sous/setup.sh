#!/bin/bash

lando start
lando emulsify init test-sous-remove --platform drupal
lando npm --prefix ./web/themes/custom/test-sous-remove install
lando emulsify --prefix ./web/themes/custom/test-sous-remove system install compound
lando info
