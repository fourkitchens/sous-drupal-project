![Sous featuring Emulsify](themes/sous_admin/assets/images/Sous.png "Sous featuring Emulsify")

# Sous Distribution Package

A base Drupal distribution profile with a theme based on Emulsify Design System.

## Install and Use

Follow the documentation found on the [Sous Drupal project](https://github.com/fourkitchens/sous-drupal-project) page.


## Contributing

1. Clone this project, create a new branch make your change and push the branch.
2. In another directory, clone the [sous-drupal-project](https://github.com/fourkitchens/sous-drupal-project) repo.
3. Change the `"fourkitchens/sous-drupal-distro": "dev-master",` line to match the name of the branch you wish to test or change into the `web/profiles/contrib/sous/` directory and checkout the branch you are looking for if you have already setup the project.
4. `composer install`
5. `lando start`
6. Open the url that lando prints
7. Follow the setup instructions
8. Database connection information can be found with `lando info` in the database section.
9. Finish setup and test as appropriate.


## Semantic Versioning

Setup
-----

  1. This repo has the following named/maintenance branches:
```
master
x.x
x.x.x
```
  2. These branches are protected on GitHub
  3. A personal access token was created for CircleCI.
  4. CircleCI was setup to run on this project and tag the releases
  5. Commit changes following the [Conventional commit guidelines](https://www.conventionalcommits.org/en/v1.0.0/)
  6. Push your change up and verify CircleCI passes and has run on your desired branch.

Troubleshooting
---------------

  1. Your branch must be a named stable release branch in order to get a tag.
  2. Prereleases are not supported with this package because they contain a dot.
