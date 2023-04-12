#!/bin/bash

lando start
lando npm emulsify init test-sous-remove --platform drupal
lando npm --prefix ./web/themes/custom/test-sous-remove install
lando npm --prefix ./web/themes/custom/test-sous-remove emulsify system install compound
