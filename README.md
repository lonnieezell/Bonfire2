# Bonfire 2

[![Build Status](https://github.com/lonnieezell/Bonfire2/workflows/PHPUnit/badge.svg)](https://github.com/lonnieezell/Bonfire2/actions?query=workflow%3A%22PHPUnit%22)
[![Coverage Status](https://coveralls.io/repos/github/lonnieezell/Bonfire2/badge.svg?branch=develop)](https://coveralls.io/github/lonnieezell/Bonfire2?branch=develop)
[![GitHub license](https://img.shields.io/github/license/lonnieezell/Bonfire2)](https://github.com/lonnieezell/Bonfire2/blob/develop/LICENSE)
[![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/lonnieezell/Bonfire2/pulls)

NOT QUITE READY FOR PRIME TIME. More details at [Patreon](https://www.patreon.com/lonnieezell)

NOTE: If you need the previous, standalone version, it is available under the `kickstart` branch.

This repo holds the primary code of [Bonfire](https://github.com/lonnieezell/Bonfire2). It was ripped from the previous
standalone installation and converted into a standalone package. In short order this will be installable through Composer
to work as an admin panel for your new or existing CodeIgniter 4 projects.

This allows the Bonfire core to be easily upgraded, without touching any parts that
may have been customized in the final application itself.

It is still undecided if there will be ready-to-download app kickstarter like it was originally written or if it
will only be available as a drop-in admin panel.

## What is Bonfire?

Bonfire will be a robust application skeleton for CodeIgniter 4-based applications. It provides a number of helpful
libraries to assist you in making better software for your clients, faster, while allowing you to focus on the
new parts that matter to each specific application.

Currently, it includes the following features:
- Theme/template system, that ships with a flexible Auth and Admin theme.
- View Components to reduce the complexity of your UI by allowing you to create reusable HTML snippets, that can be optionally controlled via code.
- A Settings library that allows you to save config file values to the database and access them whether they're in the db or just in the files.
- Resource Filter system to make filtering lists of User, Post, etc, simple to implement and with a comfortable, consistent UI.
- A powerful, very customizable, user authentication/authorization system, [Shield](https://github.com/lonnieezell/codigniter-shield).
- Global search feature that modules can easily integrate into
- A Recycle Bin to handle restoring/purghing soft deleted models that modules can easily integrate into
- A simple documentation system that allows you to provide guides for your clients, or your development teams.
- and more to come...


## Server Requirements

This currently has the same requirements as CodeIgniter 4.

## Installation

Installation is a simple 2-step process: (Needs updating)

```php
> composer create-project lonnieezell/bonfire app --stability dev
> cd app
> php spark bf:install
```

Find more details in the [docs folder](docs).

## Third Party Software Used

- [Bootstrap 5](https://getbootstrap.com/) for the CSS foundation
- [FontAwesome 5](https://fontawesome.com/) icons used in the admin
- [Alpine.js](https://alpinejs.dev/) handles interactivity within the page for the admin area.
- [htmx](https://htmx.org/) provides AJAX form handling, and more.
- [Tatter/Alerts](https://github.com/tattersoftware/codeigniter4-alerts) CodeIgniter library for simple user alerts.
