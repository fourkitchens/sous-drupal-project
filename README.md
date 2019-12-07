# Sous Project

## Contributing

1. Change the `"fourkitchens/sous": "dev-master",` line to match the name of the branch you wish to test or change into the `web/profiles/contrib/sous/` directory and checkout the branch you are looking for if you have already setup the project.
2. `composer install` # If you do this from outside of lando you won't have to setup your composer key to access the private sous repo every time you rebuild lando.
3. `lando start`
4. Open the url that lando prints
5. Follow the setup instructions
6. Database connection information can be found with `lando info` in the database section.
7. Finish setup and test as appropriate.

## Helper scripts

To use the helper script provided you will need to have `yarn` or `npm` installed. Then just run `yarn <command>` or `npm run <command>`. For example: `yarn import-data`. These commands are bash scripts located in the `./scripts/sous` directory and defined in `package.json`.

### Configuration management scripts

**confex**

```
yarn confex
```

Export active configuration to the config directory.

**confim**

```
yarn confim
```

Import the configuration to the database.

**import-data**

```
yarn import-data
```

Import a copy of the canonical database backup into your local instance. This assumes the database backup is located in `./reference/db.sql.gz`.

**local-data-bak**

```
yarn local-data-back
```

Create a local database backup. Saves the backup to the `./reference` directory.

**confex**

```
yarn rebuild
```

Rebuild a fresh local instance of your site. Imports the canonical database backup and imports configuration into it.
