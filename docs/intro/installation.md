# Installation

Bonfire is an admin area at heart. It is designed so that you can download it and incorporate into your new or existing CodeIgniter 4 application, modifying everything as you would need to. It uses [Composer](https://getcomposer.org) to install and manage dependencies. These directions assume that you have Composer installed globally. If you do not, then you will need to adjust the commands according to your setup.

## Install CodeIgniter

You must have an existing CodeIgniter 4 project already setup. To install a new project, type the following at the command line:

    > composer create-project codeigniter4/appstarter my-app

You need to add the following setting to composer.json:

    "minimum-stability": "dev",
    "prefer-stable": true

This creates a new CodeIgniter 4 project in the `my-app` directory. Finish any required setup as per
the [CodeIgniter User Guide](https://codeigniter.com/user_guide/installation/installing_composer.html#installation-set-up).

## Install Bonfire

Next you need to install Bonfire as a dependency in your project. From your command line type the following:

    >  composer require lonnieezell/bonfire:dev-develop

This will download the latest version and all dependencies.

## Run The Install Script

To setup the initial environment, a CLI command is provided to do all of the setup you need to get up and running.
From the project root, type:

    > php spark bf:install

This will:

- Copy the `env` file to `.env` to save your site's customized setting.
- Set the environment to `development` so you can see errors and use the Debug Toolbar
- Prompt you for your site's base url and update the `.env` file (i.e. http://localhost:8080 or http://bonfire.test)
- Prompt you for your database credentials and database name, saving that to the `.env` file
- Copy all of the Bonfire module config files into your `app/Config` directory.
- Copy the theme directories to `ROOTPATH/themes`

Then it will present you with two recommendations:

```
If you need to create your database, you may run:
	php spark db:create <database name>

To migrate and create the initial user, please run:
	php spark bf:install --continue
```

Continuing the installation will then do the following:

- Run all migrations so the database is setup and ready to go
- Prompt you for your super admin name and credentials and create that user in the database.


## Apache Web Server
Apache must have the mod_rewrite extension enabled. If it does, you can use a .htaccess file with some simple rules. 
Here is an example of such a file, using the “negative” method in which everything is redirected except the specified items:

```
# Disable directory browsing
Options All -Indexes

# ----------------------------------------------------------------------
# Rewrite engine
# ----------------------------------------------------------------------
# Turning on the rewrite engine is necessary for the following rules and features.
# FollowSymLinks must be enabled for this to work.
<IfModule mod_rewrite.c>
	Options +FollowSymlinks
	RewriteEngine On
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>
```

## nginx
Under nginx, you can define a location block and use the try_files directive to get the same effect as we did with the above Apache configuration:

```
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
```

This will first look for a file or directory matching the URI (constructing the full path to each file from the settings of the root and alias directives), and then sends the request to the index.php file along with any arguments.

---
**NOTE**

You can see the [CodeIgniter User Guide](https://codeigniter.com/user_guide/general/urls.html#removing-the-index-php-file) on how to remove the index.php

---

## Enjoy

That's all that's needed to get started. You can now visit [http://localhost:8080/admin](http://localhost:8080/admin) and login with your new user.

In case the index.php part is not removed (see suggested configuration for Apache or ngnx), to reach Bonfire 2 (paths with /admin/) it may be necessary to add the complete URL of index.php (example /index. php/admin/ )
