# Media AV Portal

[![Build Status](https://drone.fpfis.eu/api/badges/openeuropa/media_avportal/status.svg?branch=8.x-1.x)](https://drone.fpfis.eu/openeuropa/media_avportal)
[![Packagist](https://img.shields.io/packagist/v/openeuropa/media_avportal.svg)](https://packagist.org/packages/openeuropa/media_avportal)

Media AV Portal adds the [European Audiovisual Services](http://ec.europa.eu/avservices/) as a supported media provider.

**Table of contents:**

- [Installation](#installation)
- [Development](#development)
  - [Project setup](#project-setup)
  - [Using Docker Compose](#using-docker-compose)
  - [Disable Drupal 8 caching](#disable-drupal-8-caching)
- [Demo module](#demo-module)

## Installation

The recommended way of installing the module is via [Composer][2].

```bash
$ composer require drupal/media_avportal
```

### Enable the module

In order to enable the module in your project run:

```bash
$ ./vendor/bin/drush en media_avportal -y
```

## Development

The project contains all the necessary code and tools for an effective development process,
such as:

- All PHP development dependencies (Drupal core included) are required by [composer.json](composer.json)
- Project setup and installation can be easily handled thanks to the integration with the [Task Runner][3] project.
- All system requirements are containerized using [Docker Composer][4]

### Project setup

Download all required PHP code by running:

```bash
$ composer install
```

This will build a fully functional Drupal test site in the `./build` directory that can be used to develop and showcase
the module's functionality.

Before setting up and installing the site make sure to customize default configuration values by copying [runner.yml.dist](runner.yml.dist)
to `./runner.yml` and overriding relevant properties.

To set up the project run:

```bash
$ ./vendor/bin/run drupal:site-setup
```

This will:

- Symlink the theme in  `./build/modules/custom/media_avportal` so that it's available for the test site
- Setup Drush and Drupal's settings using values from `./runner.yml.dist`. This includes adding parameters for EULogin
- Setup PHPUnit and Behat configuration files using values from `./runner.yml.dist`

After a successful setup install the site by running:

```bash
$ ./vendor/bin/run drupal:site-install
```

This will:

- Install the test site
- Enable the Media AV Portal module

### Using Docker Compose

The setup procedure described above can be unified and sensitively simplified by using Docker Compose.

Requirements:

- [Docker][8]
- [Docker-compose][9]

Copy `docker-compose.yml` into `docker-compose.override.yml`.

You can make any alterations you need for your local Docker setup.

If you're on the European Commission network, you'll need to add some configuration on each docker instance

```yml
    dns:
      - 10.57.33.13
      - 127.0.0.11
    dns_search: net1.cec.eu.int
```

If you are behind a proxy, you might add

```yml
    env_file:
      - .env
```

and edit the file `.env` then add your proxy configuration in it:

```
ftp_proxy=http://user:password@host:port
http_proxy=http://user:password@host:port
https_proxy=http://user:password@host:port
FTP_PROXY=http://user:password@host:port
HTTP_PROXY=http://user:password@host:port
HTTPS_PROXY=http://user:password@host:port
no_proxy=authentication,web,ecas.ec.europa.eu,mysql,selenium,node
NO_PROXY=authentication,web,ecas.ec.europa.eu,mysql,selenium,node
```

By doing this, those environment variables will be passed into each docker instances.

To start, run:

```bash
$ docker-compose up
```

It's advised to not daemonize `docker-compose` so you can turn it off (CTRL+C) quickly when you're done working.
However, if you'd like to daemonize it, you can use the `-d` flag:

```bash
$ docker-compose up -d
```

Then:

```bash
$ docker-compose exec web composer install
$ docker-compose exec web ./vendor/bin/run drupal:site-install
```

Your test site will be available at [http://localhost:8080/build](http://localhost:8080/build).

Run tests as follows:

```bash
$ docker-compose exec -u www-data web ./vendor/bin/phpunit
```

### Disable Drupal 8 caching

Manually disabling Drupal 8 caching is a laborious process that is well described [here][10].

Alternatively you can use the following Drupal Console commands to disable/enable Drupal 8 caching:

```bash
$ ./vendor/bin/drupal site:mode dev  # Disable all caches.
$ ./vendor/bin/drupal site:mode prod # Enable all caches.
```

Note: to fully disable Twig caching the following additional manual steps are required:

1. Open `./build/sites/default/services.yml`
2. Set `cache: false` in `twig.config:` property. E.g.:

```yml
parameters:
 twig.config:
   cache: false
```

3. Rebuild Drupal cache: `./vendor/bin/drush cr`

This is due to the following [Drupal Console issue][11].

[1]: https://github.com/openeuropa/oe_theme
[2]: https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed
[3]: https://github.com/openeuropa/task-runner
[4]: https://docs.docker.com/compose
[5]: https://github.com/openeuropa/oe_theme#project-setup
[6]: https://nodejs.org/en
[7]: https://www.drupal.org/project/config_devel
[8]: https://www.docker.com/get-docker
[9]: https://docs.docker.com/compose
[10]: https://www.drupal.org/node/2598914
[11]: https://github.com/hechoendrupal/drupal-console/issues/3854
[12]: https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
[13]: https://www.drush.org/
