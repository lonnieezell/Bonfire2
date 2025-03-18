# Installation

Bonfire is a set of modules and libraries for CodeIgniter 4. It is designed to be installed via [Composer](https://getcomposer.org) and is not intended to be installed manually. These directions assume that you have Composer [installed globally](https://getcomposer.org/doc/00-intro.md#globally). If you do not, then you will need to adjust the commands according to your setup.

## Install CodeIgniter App Starter

You must have an existing CodeIgniter 4 project already setup. If you do not already, you can install a new project as detailed in [CodeIgniter User Guide](https://codeigniter.com/user_guide/installation/installing_composer.html#installation-set-up) by typing the following at the command line:

```bash
composer create-project codeigniter4/appstarter my-app
cd my-app
```

You need to allow installation of development stability level packages by executing the following commands:

```bash
composer config minimum-stability dev
composer config prefer-stable true
```

## Install Bonfire

Next you need to install Bonfire as a dependency in your project. From your command line type the following (omit `:dev-develop` if you want the current stable version):

```bash
composer require lonnieezell/bonfire:dev-develop
```

This will download the latest version and all dependencies.

## Run The Install Script

To setup the initial environment, a CLI command is provided to do all of the setup you need to get up and running.
From the project root, type:

```bash
php spark bf:install
```

This will:

- Copy the `env` file to `.env` to save your site's customized setting.
- Set the environment to `development` so you can see errors and use the Debug Toolbar
- Prompt you for your site's base url and update the `.env` file (i.e. http://localhost:8080 or http://bonfire.test)
- Remove the `index.php` from your urls
- Prompt you for your database credentials and database name, saving that to the `.env` file
- Copy all of the Bonfire module config files into your `app/Config` directory.
- Copy the theme directories to `ROOTPATH/themes`

Then it will present you with a recommendation on creating the database via command

```bash
php spark db:create <database name>
```

and to create the initial user via command

```bash
php spark bf:install --continue
```

Continuing the installation will then do the following:

- Run all migrations so the database is setup and ready to go
- Prompt you for your super admin name and credentials and create that user in the database.

!!! info Note

    For getting a feel of Bonfire or even light use websites you can use SQLite3 database engine, which keeps the database in a file and does not require setup.

## Run the Site

You can now run the site using CodeIgniter's built-in [development server](https://codeigniter.com/user_guide/installation/running.html#local-development-server):

```bash
php spark serve
```

## Enjoy!

That's all that's needed to get started. You can now visit [http://localhost:8080/admin](http://localhost:8080/admin) and login with your new user.

If you'd rather use a different server, like Apache or Nginx, you can follow the [CodeIgniter 4 User Guide](https://codeigniter.com/user_guide/installation/running.html) suggestions for a number of different server setups.

## Generate some data for testing

You can insert some records of users into your database by running this migration (each run will add 100 users randomly distributed through Users, Developers and Beta groups, randomly activated):

```bash
php spark db:seed Bonfire\\Users\\Database\\Seeds\\Seed100Users
```
