# Media AV Portal

[![Build Status](https://drone.fpfis.eu/api/badges/openeuropa/media_avportal/status.svg?branch=8.x-1.x)](https://drone.fpfis.eu/openeuropa/media_avportal)
[![Packagist](https://img.shields.io/packagist/v/openeuropa/media_avportal.svg)](https://packagist.org/packages/openeuropa/media_avportal)

Media AV Portal adds the [European Audiovisual Services](http://ec.europa.eu/avservices/) as a supported media provider.

# Supported media types

Only 3 media assets types from AV Portal are currently supported:

* PHOTO (resources like [https://audiovisual.ec.europa.eu/en/photo/P-038924](https://audiovisual.ec.europa.eu/en/photo/P-038924)).
* VIDEO (resources like [https://audiovisual.ec.europa.eu/en/video/I-183993](https://audiovisual.ec.europa.eu/en/video/I-183993)).
* REPORTAGE (resources like [https://audiovisual.ec.europa.eu/en/photo/P-038924~2F00-15](https://audiovisual.ec.europa.eu/en/photo/P-038924~2F00-15)).

# Mock service

The project also comes with a test module that provides a mock to the remote AV Portal service. Meaning that tests do not have to be run against the remote service but local resources are used.

## How to use the mock

Enable the `media_avportal_mock` inside a given test and all HTTP requests going to AV Portal API will be intercepted automatically. These, then, will return predefined responses that can be inspected in the `responses` folder of the mock module.

By default, there are 3 types of requests that are mocked:

* A default request with no options
* A request for a given resource (the options contain the `ref` key)
* A request that searches for a given term (the options contain the `kwgg` key)

Additionally, any request to a resource thumbnail will return a local thumbnail image.

## Extending the mock

If another module needs to test interactions that require more responses, these can be provided via an event subscriber (to the `AvPortalMockEvent` event).

In the subscriber, 3 types of responses (in JSON format) can be provided:

* Individual resources
* Search results for a given term
* A default response to replace the existing one

As an example, you can see the subscriber that provides the default resources, `AvPortalMockEventSubscriber`.

**Table of contents:**

- [Installation](#installation)
- [Development](#development)
  - [Project setup](#project-setup)
  - [Using Docker Compose](#using-docker-compose)
  - [Disable Drupal 8 caching](#disable-drupal-8-caching)
- [Contributing](#contributing)
- [Versioning](#versioning)

## Installation

The recommended way of installing the module is via [Composer][2].

```bash
composer require drupal/media_avportal
```

### Enable the module

In order to enable the module in your project run:

```bash
./vendor/bin/drush en media_avportal -y
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
composer install
```

This will build a fully functional Drupal test site in the `./build` directory that can be used to develop and showcase
the module's functionality.

Before setting up and installing the site make sure to customize default configuration values by copying [runner.yml.dist](runner.yml.dist)
to `./runner.yml` and overriding relevant properties.

This will also:

- Symlink the theme in  `./build/modules/custom/media_avportal` so that it's available for the test site
- Setup Drush and Drupal's settings using values from `./runner.yml.dist`. This includes adding parameters for EULogin
- Setup PHPUnit and Behat configuration files using values from `./runner.yml.dist`

**Please note:** project files and directories are symlinked within the test site by using the
[OpenEuropa Task Runner's Drupal project symlink](https://github.com/openeuropa/task-runner-drupal-project-symlink) command.

If you add a new file or directory in the root of the project, you need to re-run `drupal:site-setup` in order to make
sure they are be correctly symlinked.

If you don't want to re-run a full site setup for that, you can simply run:

```
$ ./vendor/bin/run drupal:symlink-project
```

After a successful setup install the site by running:

```bash
./vendor/bin/run drupal:site-install
```

This will:

- Install the test site
- Enable the Media AV Portal module

### Using Docker Compose

Alternatively, you can build a development site using [Docker](https://www.docker.com/get-docker) and
[Docker Compose](https://docs.docker.com/compose/) with the provided configuration.

Docker provides the necessary services and tools such as a web server and a database server to get the site running,
regardless of your local host configuration.

#### Requirements:

- [Docker](https://www.docker.com/get-docker)
- [Docker Compose](https://docs.docker.com/compose/)

#### Configuration

By default, Docker Compose reads two files, a `docker-compose.yml` and an optional `docker-compose.override.yml` file.
By convention, the `docker-compose.yml` contains your base configuration and it's provided by default.
The override file, as its name implies, can contain configuration overrides for existing services or entirely new
services.
If a service is defined in both files, Docker Compose merges the configurations.

Find more information on Docker Compose extension mechanism on [the official Docker Compose documentation](https://docs.docker.com/compose/extends/).

#### Usage

To start, run:

```bash
docker-compose up
```

It's advised to not daemonize `docker-compose` so you can turn it off (`CTRL+C`) quickly when you're done working.
However, if you'd like to daemonize it, you have to add the flag `-d`:

```bash
docker-compose up -d
```

Then:

```bash
docker-compose exec web composer install
docker-compose exec web ./vendor/bin/run drupal:site-install
```

Using default configuration, the development site files should be available in the `build` directory and the development site
should be available at: [http://127.0.0.1:8080/build](http://127.0.0.1:8080/build).

#### Running the tests

To run the grumphp checks:

```bash
docker-compose exec web ./vendor/bin/grumphp run
```

To run the phpunit tests:

```bash
docker-compose exec web ./vendor/bin/phpunit
```

#### Step debugging

To enable step debugging from the command line, pass the `XDEBUG_SESSION` environment variable with any value to
the container:

```bash
docker-compose exec -e XDEBUG_SESSION=1 web <your command>
```

Please note that, starting from XDebug 3, a connection error message will be outputted in the console if the variable is
set but your client is not listening for debugging connections. The error message will cause false negatives for PHPUnit
tests.

To initiate step debugging from the browser, set the correct cookie using a browser extension or a bookmarklet
like the ones generated at https://www.jetbrains.com/phpstorm/marklets/.

### Disable Drupal 8 caching

Manually disabling Drupal 8 caching is a laborious process that is well described [here][10].

Alternatively you can use the following Drupal Console commands to disable/enable Drupal 8 caching:

```bash
./vendor/bin/drupal site:mode dev  # Disable all caches.
./vendor/bin/drupal site:mode prod # Enable all caches.
```

Note: to fully disable Twig caching the following additional manual steps are required:

1. Open `./build/sites/default/services.yml`
2. Set `cache: false` in `twig.config:` property. E.g.:

```yaml
parameters:
 twig.config:
   cache: false
```

3. Rebuild Drupal cache: `./vendor/bin/drush cr`

This is due to the following [Drupal Console issue][11].

## Contributing

Please read [the full documentation](https://github.com/openeuropa/openeuropa) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the available versions, see the [tags on this repository](https://github.com/openeuropa/media_avportal/tags).

[2]: https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed
[3]: https://github.com/openeuropa/task-runner
[4]: https://docs.docker.com/compose
[7]: https://www.drupal.org/project/config_devel
[8]: https://www.docker.com/get-docker
[9]: https://docs.docker.com/compose
[10]: https://www.drupal.org/node/2598914
[11]: https://github.com/hechoendrupal/drupal-console/issues/3854
[12]: https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
[13]: https://www.drush.org/
