# OCP8-TodoList-Improved

## Table of Contents

1. [Description](#description)
2. [Requirements](#requirements)
3. [Libraries](#libraries)
4. [Installation](#installation)
5. [Authentication](#authentication)
6. [Configuration](#configuration)
    1. [Database Configuration](#database-configuration)
    2. [Testing Configuration](#testing-configuration)
        1. [Unit and functional tests](#unit-and-functional-tests)
        2. [Tests launch](#tests-launch)
        3. [Tests coverage](#tests-coverage)
7. [Contributing](#contributing)
8. [Licence](#licence)

## Description

Restoration of an outdated application for managing daily tasks. New features have been added, and several bugs have been fixed to enhance overall quality and user experience. Both unit and functional tests were conducted to ensure the application's correct functionality, along with performance testing to further optimize its efficiency. The project prioritizes code quality, performance improvements, and reducing technical debt.

## Requirements

- PHP ⩾ 8.3.6
- Composer
- Symfony ⩾ 7.1
- Symfony CLI
- MySQL ⩾ 8.0.28

## Libraries

- `cache`: Caching library to improve performance.
- `doctrine/orm`: Database ORM.
- `symfony/security-bundle`: Security and user management.
- `twig/twig`: Templating engine.
- `phpunit/phpunit`: Unit testing framework for PHP.
- `friendsofphp/php-cs-fixer`: A tool to automatically fix PHP coding standards issues.
- `phpstan/phpstan`: PHP Static Analysis Tool - discover bugs in your code without running it.
- `dama/doctrine-test-bundle`: Symfony bundle to isolate Doctrine ORM database tests.

## Installation

```bash
# Clone the repository
git clone https://github.com/KenKaneki-42/OCP8-TodoList-Improved.git

# Change directory to the cloned repository
cd OCP8-TodoList-Improved

# Install dependencies with Composer
composer install

# Create database
php bin/console doctrine:database:create

# Run database migrations
php bin/console doctrine:migrations:migrate

# Load fixtures into the database
php bin/console doctrine:fixtures:load

# Start the Symfony server
symfony server:start
```

## Authentication

In order to authenticate yourselves, you will need either add your own user in the datas fixtures or to use the Super Admin created for this purpose.
It will allow you to create or modify all the user you need directly in the application, or manage all the tasks by yourselves, all the permissions are given to this superadmin user and circumvents some restrictions that even an admin user has. To log in in superadmin view, you will have to go to the login page and use his identifiers : login = <super.admin@orange.fr> / password = password.

## Configuration

### Database Configuration

- **Copy `.env` to `.env.local`.**
  This ensures that your local settings do not interfere with the production settings.
- **Set your database URL in `.env.local` under `DATABASE_URL`.**
  Example for a MySQL database:

  ```env
  DATABASE_URL="mysql://username:password@localhost:3306/database_name"
  ```

### Testing Configuration

#### Unit and functional tests

Testing environnement
To set up your testing environnement, you will have follow the following steps :

First of all you will have to go to your .env.test file and change the database name. Usually you use the same database name as in your .env file adding "_test" at the end.
Lastly, you will have to change the APP_ENV value to 'test' in the .env file

!! Don't forget to clear the cache !!

```php
php bin/console cache:clear
```

DAMA DoctrineTestBundle is used in order to roll back the actions which impacts the dabatase, which means the tests will never change the datas in database and will remain clean, so it won't interfer with the following tests launch. The configuration is available in the file config\packages\test\dama_doctrine_test_bundle.yaml and is activated thanks to the line in phpunit.xml.dist :

```php
<extensions> <bootstrap class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/> </extensions>
```

Then you will have to use the following commands to set up your test dabatase :

```bash
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:schema:create
php bin/console --env=test doctrine:fixtures:load
```

#### Tests launch

In order to launch your tests, you will have to open a terminal and use the following command :

```bash
vendor/bin/phpunit.
```

If you want to perform a single test on a targeted method, you will have to use the option --filter as :

```bash
vendor/bin/phpunit --filter=testMethodTargeted.
```

#### Tests coverage

To get the global tests coverage for the application, you will just have to do the following command in your terminal :

```bash
vendor/bin/phpunit --coverage-html public/test-coverage
```

and open the index.html file in your browser, located at public\test-coverage\index.html.

## Contributing

If you want to contribute to BileMo, please follow these steps:

1. Fork the repository
2. Create a new branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add a new feature'`)
4. Push the branch (`git push origin feature/new-feature`)
5. Create a pull request

Some rules to help us to keep the project consistent:
[contribution-guide.md](OCP8-TodoList-Improved/contribution-guide.md)

## Licence

This project is licensed under the MIT license. Please see the `LICENSE` file for more information.
