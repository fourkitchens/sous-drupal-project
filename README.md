# Sous Project

## Contributing

1) Change the `        "fourkitchens/sous": "dev-master",` line to match the name of the branch you wish to test or change into the `web/profiles/contrib/sous/` directory and checkout the branch you are looking for if you have already setup the project.
2) `composer install` # If you do this from outside of lando you won't have to setup your composer key to access the private sous repo every time you rebuild lando.
3) `lando start`
4) Open the url that lando prints
5) Follow the setup instructions
6) Database connection information can be found with `lando info` in the database section.
7) Finish setup and test as appropriate.
