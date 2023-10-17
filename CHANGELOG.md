# [5.1.0](https://github.com/fourkitchens/sous-drupal-project/compare/5.0.0...5.1.0) (2023-04-27)


### Features

* add npm scripts to lando so ci and lando can run both seperately ([#102](https://github.com/fourkitchens/sous-drupal-project/issues/102)) ([412d607](https://github.com/fourkitchens/sous-drupal-project/commit/412d6073ea7b010c814748fc9cd6eadaa24db8d1))

# [5.0.0](https://github.com/fourkitchens/sous-drupal-project/compare/4.4.0...5.0.0) (2023-04-21)


### Bug Fixes

* major version bump ([b2fd228](https://github.com/fourkitchens/sous-drupal-project/commit/b2fd22871b13233a8b25ac59ebd9f040bc7c0a09))


### BREAKING CHANGES

* Remove the distro

# [4.4.0](https://github.com/fourkitchens/sous-drupal-project/compare/4.3.0...4.4.0) (2023-04-21)


### Bug Fixes

* Chris' original approach with append in composer but targeting default.settings.php ([913cad3](https://github.com/fourkitchens/sous-drupal-project/commit/913cad3e9250a26137d8b876e5320c0218e777f7))
* chromium not chromium-browser ([a6574d6](https://github.com/fourkitchens/sous-drupal-project/commit/a6574d63a63e5445289bcbe78c0e467442b5234c))
* composer version and bash to shell update ([c5e56ac](https://github.com/fourkitchens/sous-drupal-project/commit/c5e56ac895ed05aa4ce3b6a516ca1bc76579aa94))
* emulsify/cli version ([4aef72e](https://github.com/fourkitchens/sous-drupal-project/commit/4aef72e0077544aeb597dabf9038d33f07225d83))
* file replace paths ([b3234b7](https://github.com/fourkitchens/sous-drupal-project/commit/b3234b7a5760adf64c21ee5985a747f43cb77eaa))
* file replace paths ([a52bbcc](https://github.com/fourkitchens/sous-drupal-project/commit/a52bbcc715a43e61f70c5d2c5750992578fdd0c3))
* find and replace command for lando file ([ae327cc](https://github.com/fourkitchens/sous-drupal-project/commit/ae327ccc72f42ff124492f70183f28c28649851d))
* missing development.services.yml file ([861aeac](https://github.com/fourkitchens/sous-drupal-project/commit/861aeac4345ef45e929374d1ebd775197d1d529c))
* project theme in lando ([56c8908](https://github.com/fourkitchens/sous-drupal-project/commit/56c8908189e10ca49cce83a12dc5a736321a41d6))
* revert .nvmrc changes ([6da14a9](https://github.com/fourkitchens/sous-drupal-project/commit/6da14a917e0ade5e4a7796629e3f27fabed40992))
* scaffolding wip ([b1066a4](https://github.com/fourkitchens/sous-drupal-project/commit/b1066a4f4efbc31467bf24592effb9fd17d4c948))
* theme replace references ([c81b74a](https://github.com/fourkitchens/sous-drupal-project/commit/c81b74a64367f1a8873bef59694d4873f5f5ba9d))
* update config and lando to allow site install from config ([ae1bab6](https://github.com/fourkitchens/sous-drupal-project/commit/ae1bab65527ed1335b98466f01c0792779aa6d76))


### Features

* add chromedriver adjustment for arm macs ([d21db1a](https://github.com/fourkitchens/sous-drupal-project/commit/d21db1ac0353fb638f49af512d18fedc2276e411))
* add chromedriver docker service override ([5ae95b9](https://github.com/fourkitchens/sous-drupal-project/commit/5ae95b9856ab4adfc44ba74696eec1c4fb893f59))
* add config for superuser role ([a8e8cc6](https://github.com/fourkitchens/sous-drupal-project/commit/a8e8cc680fd8b589d7b61433d5b4c47fc82d8943))
* add setup/theme/build scripts ([98a81ce](https://github.com/fourkitchens/sous-drupal-project/commit/98a81ce43d14b9d3ac532fe92280be8e2a236709))
* add site install command to setup ([974b3ab](https://github.com/fourkitchens/sous-drupal-project/commit/974b3ab6ccf2e83557bfe4ea8c2714f469a94947))
* add sous-builder install command to lando ([60d8f75](https://github.com/fourkitchens/sous-drupal-project/commit/60d8f75513065ee70eb1f3688fa3913561243717))
* add sousdemo theme for testing ([9b1a7ec](https://github.com/fourkitchens/sous-drupal-project/commit/9b1a7ec97f2c7ea9893f8d3b4bd26ddb529bfa39))
* attempt to simplify output of setup script ([addfa24](https://github.com/fourkitchens/sous-drupal-project/commit/addfa24603dbab9ca8d52d3018b69b58dc1f5245))
* attempt to simplify output of setup script ([a01935c](https://github.com/fourkitchens/sous-drupal-project/commit/a01935c78126d2e9c0f54a3b7b510abd35a21b6f))
* auto-confirm sousdemo install when prompt ([84ba2a1](https://github.com/fourkitchens/sous-drupal-project/commit/84ba2a1ac6e1bffcb38a6fcd2002f29c67b4d2f6))
* better script status verbiage ([79aacc2](https://github.com/fourkitchens/sous-drupal-project/commit/79aacc2d9883b4bd517e83b4e205c4868e875a9b))
* block user one and give user a temp login for sous_chef ([7f6e420](https://github.com/fourkitchens/sous-drupal-project/commit/7f6e420d8a4573a9145529b91db5a7c095ab4ebf))
* change composer project name in json file ([ef8b559](https://github.com/fourkitchens/sous-drupal-project/commit/ef8b559ac6b8279964b4f06596b91da9cbb7a3a4))
* enable crop module by default ([8dc3890](https://github.com/fourkitchens/sous-drupal-project/commit/8dc3890c91cb9bc7b05cab24cb8e10cdeed3d97d))
* git init the project so husky doesnt fail ([4112daf](https://github.com/fourkitchens/sous-drupal-project/commit/4112daf4442658e67b88ee739e56b982c01417ed))
* include crop type config ([9ef7e39](https://github.com/fourkitchens/sous-drupal-project/commit/9ef7e390f307828c6c656d1c66d3de67c923a8a3))
* include crop type config ([7512b28](https://github.com/fourkitchens/sous-drupal-project/commit/7512b289cd6bbcd8307db6266a1af84f92ce4dde))
* install chromium browser manually for arm64 ([e34043b](https://github.com/fourkitchens/sous-drupal-project/commit/e34043bde66add0ecafc4656d36552ead50a2ceb))
* install emulsifycli during lando setup ([4851ab7](https://github.com/fourkitchens/sous-drupal-project/commit/4851ab75c72bc9e1cb6b7c26641cb6e15b877c65))
* migrate filter config to sous proper ([530b6b7](https://github.com/fourkitchens/sous-drupal-project/commit/530b6b76f1dc0b5bf7f48b281f61dda6e310e83e))
* more find-and-replace four sous-project setup ([2eb98be](https://github.com/fourkitchens/sous-drupal-project/commit/2eb98be4f1a94ae9e34d1258356889d234b09b96))
* move --silent option for npm ([3ba1f6b](https://github.com/fourkitchens/sous-drupal-project/commit/3ba1f6b1e84bfa06d2945d673f44d744b7178245))
* move compound install to lando ([cfd16da](https://github.com/fourkitchens/sous-drupal-project/commit/cfd16da5d06b92e768c019d719fba275846895f0))
* move setup to post-create ([805f1f3](https://github.com/fourkitchens/sous-drupal-project/commit/805f1f3b0e7c7847c852a73a5d22e3aa9f88ed52))
* move setup to post-create ([7230896](https://github.com/fourkitchens/sous-drupal-project/commit/7230896b9909601e54d63cbd825e3f7db4a72264))
* no npm when running emulsify through lando ([02a5cc3](https://github.com/fourkitchens/sous-drupal-project/commit/02a5cc37f2d073467756ff2a712d567f7be3c173))
* quiet apt-get setup commands ([8234461](https://github.com/fourkitchens/sous-drupal-project/commit/8234461c12847247b41b5584e4baa0994b616979))
* quiet option for apt-get commands ([5075efc](https://github.com/fourkitchens/sous-drupal-project/commit/5075efce90d7d67339859ff9fb7a5b4d9a3df5f0))
* remove config export ([3a85d83](https://github.com/fourkitchens/sous-drupal-project/commit/3a85d83b53e44d562aa1ade88784c4bc56429f10))
* remove drupal profile ([e97ab51](https://github.com/fourkitchens/sous-drupal-project/commit/e97ab519ecf6b06e62f85dcf1f72699741ded3e9))
* remove emulisfy-ds dependency as the cli pulls this in ([2649f4b](https://github.com/fourkitchens/sous-drupal-project/commit/2649f4b8a19fa81877c86a89979de3f900306c52))
* remove nvm from setup script and migrate to lando ([164db6e](https://github.com/fourkitchens/sous-drupal-project/commit/164db6eea60cb54f4194f235c8679e150001690c))
* remove old profile references ([0136619](https://github.com/fourkitchens/sous-drupal-project/commit/013661949dce93e0c9bfad4b0c4190ecd83442e6))
* remove old theme name ([9bddf25](https://github.com/fourkitchens/sous-drupal-project/commit/9bddf25fe19cc06f4d6b810c503e5548648af0ae))
* rename setup to prep ([1b38d4c](https://github.com/fourkitchens/sous-drupal-project/commit/1b38d4cf6c3d6cba4fe63f03359b37c11cd3f0d3))
* replace the theme before the sous-project in lando ([267e5e0](https://github.com/fourkitchens/sous-drupal-project/commit/267e5e0bd9a6f490b429e89a9d753db8dbab5cc5))
* replace the theme before the sous-project in lando ([c0c106b](https://github.com/fourkitchens/sous-drupal-project/commit/c0c106b755b1d85a9c4a84f7902a1f72f8525a39))
* rerun config import post install ([7ab945d](https://github.com/fourkitchens/sous-drupal-project/commit/7ab945d16e8b879d0f523198105ccc470f06bd36))
* restore editor config ([bceba11](https://github.com/fourkitchens/sous-drupal-project/commit/bceba114c819220b272e8abaa3da664b24fa58b5))
* restore entity_embed enabling ([1f190c7](https://github.com/fourkitchens/sous-drupal-project/commit/1f190c71fa221d3458121316473b539b2b0a51ff))
* restore old profile module activations ([52abae0](https://github.com/fourkitchens/sous-drupal-project/commit/52abae00eb704d24a3628dbbb8d7c149909aafbb))
* rework emulsify/node/npm install for lando ([b49aa19](https://github.com/fourkitchens/sous-drupal-project/commit/b49aa19653640c0ad3ae8fdebc171bca158e84ef))
* set editor config for cke5 ([feae73d](https://github.com/fourkitchens/sous-drupal-project/commit/feae73df73b978d1893d96a0aa4f284256679dfb))
* setup sous_chef user and superuser role ([a1af951](https://github.com/fourkitchens/sous-drupal-project/commit/a1af9519b565b6ca5f45ff284446010e4f64b213))
* setup sous_chef user and superuser role ([d9248da](https://github.com/fourkitchens/sous-drupal-project/commit/d9248da65f4c00dad08d9cb193fe03a2037b5409))
* simplify setup script ([869cdb6](https://github.com/fourkitchens/sous-drupal-project/commit/869cdb6d862596bd40cbe86f86d80a6ae221eec1))
* start lando post-create project in order to setup the rest ([5442990](https://github.com/fourkitchens/sous-drupal-project/commit/5442990c99534fcc03e53969574322aade584c20))
* use emulsify name for various commands ([9f68e0f](https://github.com/fourkitchens/sous-drupal-project/commit/9f68e0fee8cc2d85a536ebffcebc94ecd34c8a19))
* use https for sous-builder url ([60e50b1](https://github.com/fourkitchens/sous-drupal-project/commit/60e50b1bc524b12a1e0d42f30e52b23d8c288a0f))

# [4.3.0](https://github.com/fourkitchens/sous-drupal-project/compare/4.2.2...4.3.0) (2023-03-15)


### Features

* set up docs structure ([1f58022](https://github.com/fourkitchens/sous-drupal-project/commit/1f5802200cfeb80f20995aa26f98328388e91b15))

## [4.2.2](https://github.com/fourkitchens/sous-drupal-project/compare/4.2.1...4.2.2) (2022-12-19)


### Bug Fixes

* distro version and find-and-replace command for linux ([13ae175](https://github.com/fourkitchens/sous-drupal-project/commit/13ae175ab4b6dbd277b4e77775be65faeeae5fdd))
* match emulisfycli naming convension ([29e1154](https://github.com/fourkitchens/sous-drupal-project/commit/29e11541b6feca27ddbbb786fc813b558ee76150))
* no need for underscore project name ([bc79220](https://github.com/fourkitchens/sous-drupal-project/commit/bc79220e372b4142dec7bfe7c8a23c98f2d23883))
* use dashed project name for lando and underscore everything else ([f18a398](https://github.com/fourkitchens/sous-drupal-project/commit/f18a398bb8be886bba99408b3452731832bd4e2a))

## [4.2.1](https://github.com/fourkitchens/sous-drupal-project/compare/4.2.0...4.2.1) (2022-12-07)


### Bug Fixes

* add database password instructions ([3458456](https://github.com/fourkitchens/sous-drupal-project/commit/3458456a53675b25d27c0e7d862ad2154bece51b))
* added database install instruction ([d0a0f2e](https://github.com/fourkitchens/sous-drupal-project/commit/d0a0f2e913914d8f0d2239c4f3981714ef0b1e6c))

# [4.2.0](https://github.com/fourkitchens/sous-drupal-project/compare/4.1.3...4.2.0) (2022-12-02)


### Bug Fixes

* php version update and rename master to main ([d269388](https://github.com/fourkitchens/sous-drupal-project/commit/d269388b0f8f3c76308a8ac9dbae3e61aae30ba2))
* readme, ignore ide generated files ([2f2808e](https://github.com/fourkitchens/sous-drupal-project/commit/2f2808efcdf6c00b9008108c55193978f7db8d99))
* update node orb ([fb9179f](https://github.com/fourkitchens/sous-drupal-project/commit/fb9179fdadf447faa8eb8993b82d11f5032ccd37))
* update package-lock.json ([809093e](https://github.com/fourkitchens/sous-drupal-project/commit/809093e06dfdfadf9d03dc0f79a0979811f8d8a5))
* update readme ([10efa52](https://github.com/fourkitchens/sous-drupal-project/commit/10efa52a2cbd89597b1fdb4ceed57e9bed29d8a6))


### Features

* pulled main and fixed conflicts ([9df602d](https://github.com/fourkitchens/sous-drupal-project/commit/9df602dcd40b9450aca11f462476af5b9944f6f1))
* resolved conflicts and updated all packages ([402a753](https://github.com/fourkitchens/sous-drupal-project/commit/402a753f322e6bd456d5d67aa2521cae24aa9e69))

## [4.1.3](https://github.com/fourkitchens/sous-drupal-project/compare/4.1.2...4.1.3) (2022-08-24)


### Bug Fixes

* adds oomphinc to allow-plugins ([d5af40a](https://github.com/fourkitchens/sous-drupal-project/commit/d5af40a072a541412bf68f6ae99cf48333b7c707))
* syntax error ([5783323](https://github.com/fourkitchens/sous-drupal-project/commit/5783323738ed8eb30433f5431b23f3e98a955508))

## [4.1.2](https://github.com/fourkitchens/sous-drupal-project/compare/4.1.1...4.1.2) (2022-08-23)


### Bug Fixes

* remove change that is in a different PR ([5d5f3cc](https://github.com/fourkitchens/sous-drupal-project/commit/5d5f3ccc57e05308fa11d5f15935975b67caa83f))
* removed drupal console ([ff52a72](https://github.com/fourkitchens/sous-drupal-project/commit/ff52a725b49ca193fe714f537ad541c5ae19ad87))

## [4.1.1](https://github.com/fourkitchens/sous-drupal-project/compare/4.1.0...4.1.1) (2022-05-03)


### Bug Fixes

* **docs:** add semantic release angular badge to readme ([5be1e76](https://github.com/fourkitchens/sous-drupal-project/commit/5be1e762d8adabb9dad9b5d013afb1b2665508ab))

# [4.1.0](https://github.com/fourkitchens/sous-drupal-project/compare/4.0.1...4.1.0) (2022-03-22)


### Bug Fixes

* **ci:** fix beta branch ci setup ([965d640](https://github.com/fourkitchens/sous-drupal-project/commit/965d640ecd0bb170a038d620b83f81b8a098c6f7))


### Features

* **changelog:** add changelog feature ([67929f3](https://github.com/fourkitchens/sous-drupal-project/commit/67929f363942de56cd5b1a8b36d40dc09fbb823f))
