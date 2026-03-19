# MyAdmin Xen VPS Plugin

[![Tests](https://github.com/detain/myadmin-xen-vps/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-xen-vps/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-xen-vps/version)](https://packagist.org/packages/detain/myadmin-xen-vps)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-xen-vps/downloads)](https://packagist.org/packages/detain/myadmin-xen-vps)
[![License](https://poser.pugx.org/detain/myadmin-xen-vps/license)](https://packagist.org/packages/detain/myadmin-xen-vps)

Event-driven MyAdmin plugin for provisioning, managing, and deactivating Xen hypervisor-based virtual private servers. Integrates with the Symfony EventDispatcher to handle VPS lifecycle events including activation, deactivation, settings configuration, and queue-based template rendering for server operations.

## Features

- Xen Linux and Xen Windows VPS provisioning support
- Event-driven architecture using Symfony EventDispatcher
- Template-based queue processing for server operations (start, stop, restart, backup, restore, etc.)
- Configurable slice-based pricing and server assignment
- Out-of-stock toggle for sales management

## Requirements

- PHP 8.2 or higher
- ext-soap
- Symfony EventDispatcher 5.x, 6.x, or 7.x

## Installation

```sh
composer require detain/myadmin-xen-vps
```

## Usage

The plugin registers event hooks for the `vps` module automatically:

| Hook              | Handler         | Description                              |
|-------------------|-----------------|------------------------------------------|
| `vps.settings`    | `getSettings`   | Registers Xen VPS configuration options  |
| `vps.deactivate`  | `getDeactivate` | Handles VPS deactivation and cleanup     |
| `vps.queue`       | `getQueue`      | Processes queued server operations        |

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

Licensed under the LGPL-2.1. See [LICENSE](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.en.html) for details.
