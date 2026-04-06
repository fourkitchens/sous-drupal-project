# Local Setup

## Requirements

### Terminus

Pantheon's Terminus is an almost-essential command-line tool for managing sites. The CLI is required to run many of the scripts for building, updating, and deploying changes between the local development environment and the Pantheon platform.

1. [Install Terminus](https://pantheon.io/docs/terminus/install/)
2. [Setup a valid machine token](https://pantheon.io/docs/machine-tokens)
3. Log in to terminus using the machine token\
   `terminus auth:login --email=<email@example.com> --machine-token=<machine_token>`
4. [Review documentation](https://pantheon.io/docs/terminus/) to get started with the CLI

### Local Development Environment

This project supports development with **Ddev**. Choose your own adventure!

#### Ddev

[Download and install the latest release](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/)

Increase Docker resources: Locate the 'Resources' section in your Docker preferences. For most architectures, this project requires at least 3GB of memory and 3 CPUs. Additional CPUs and memory may be helpful but should stay under the halfway mark of your total available resources. Also disable the _'Start Docker when you log in'_ setting under the 'General' tab.

#### Fire

This project is using the FIRE tool, this will provide you with all the required commands to easily setup and work with this site, to see available commands just run: fire list of visit the [fire projects page](https://github.com/fourkitchens/fire)

### Additional tools

- [Fire launcher](https://github.com/fourkitchens/fire-launcher/releases/tag/v0.0.1)
- [Composer](https://getcomposer.org/download/): PHP package manager. Version 2.x.
- [NVM](https://github.com/nvm-sh/nvm#install--update-script): Recommended for installing and switching between Node versions.
- Node.js (>=20). We recommend installing via NVM.

## Project setup script

This project has a streamline setup script that builds a local instance of the site that is ready for active development. Below is the setup command that will only need to be ran once on your local machine.

```bash
# Executing the setup script will prepare the local development environment.
npm run setup
```

## Alternative manual setup instructions

A detailed explanation of the setup script appears below.

### Step 1: Clone this repository and enter the project directory

```bash
git clone git@github.com:fourkitchens/sous-project.git && cd sous-project
```

### Step 2: Start Ddev and build your dependencies

For ddev...

```bash
ddev start
ddev composer install
ddev npm install
ddev npm --prefix ./web/themes/custom/sous-project install
```

### Step 3: Import the remote database and sync config

For ddev...

```bash
terminus backup:get sous-project.live --element=db --to=reference/site-db.sql.gz
ddev import-db --file=reference/site-db.sql.gz
ddev drush cache-rebuild
ddev drush updatedb -y
ddev drush cache-rebuild
ddev drush config-import --source="../config/_splits" --partial -y
ddev drush cache-rebuild
ddev drush config-import -y
```

### Step 4: Log in as our sous_chef superuser

For ddev...

```bash
ddev drush user:login --name=sous_chef
```

Visit the local ddev site [https://sous-project.ddev.site](https://sous-project.ddev.site) or run `fire drush user:login --name=sous_chef` to obtain a login link.
