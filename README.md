# Danx Laravel Package

## Installation

```bash
composer require danx/laravel
```

## Setup

### Publish the configuration file

```bash
php artisan vendor:publish --provider="Danx\DanxServiceProvider" --tag="config"
```

## Development

### Install dependencies

```bash
composer install
```

### Publish package to composer

To publish packages, simply push a new tagged version to the repository.

```bash
git tag -a v0.1.0 -m "Initial release"
git push origin v0.1.0
```

