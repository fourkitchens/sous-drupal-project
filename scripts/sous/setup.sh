#!/bin/bash

lando start
lando npm install
lando npm --prefix ./web/themes/custom/sous-project install
