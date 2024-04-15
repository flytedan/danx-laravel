# Danx Laravel Package

## Installation

```bash
composer require danx/laravel
```

## Setup

### Publish the configuration file

```bash
sail artisan vendor:publish --provider="Flytedan\DanxLaravel\DanxServiceProvider"
```

## Development

### Install Danx UI

[Setup Danx UI](https://github.com/flytedan/quasar-ui-danx)

#### Configure CORS
```
sail artisan config:publish cors
```

* Configure the `paths` so the desired routes are allowed
  * NOTE: By default it is open to all requests

## Publish package to composer

To publish packages, simply push a new tagged version to the repository.

```bash
make VERSION=1.0.0 publish
```
