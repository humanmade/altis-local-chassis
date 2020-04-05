<h1 align="center"><img src="https://make.hmn.md/altis/Altis-logo.svg" width="89" alt="Altis" /> Local Chassis</h1>

<p align="center">Local <a href="https://github.com/Chassis/Chassis">Chassis</a> development server for <strong><a href="https://altis-dxp.com/">Altis</a></strong>.</p>

<p align="center"><a href="https://packagist.org/packages/altis/local-chassis"><img alt="Packagist Version" src="https://img.shields.io/packagist/v/altis/local-chassis.svg"></a></p>

## Local Chassis

A local development environment for Altis projects, built on [Chassis](https://github.com/Chassis/Chassis) and [Vagrant](https://www.vagrantup.com/).

## Dependencies

* [Composer](https://getcomposer.org/download/)
* [Vagrant](https://www.vagrantup.com/)
* A supported hypervisor - we recommend [Virtualbox](https://www.virtualbox.org/wiki/Downloads)

## Installation with Altis

Altis Local Chassis is included by default in an Altis project, so you don't need to install anything else.

## Installation without Altis

Altis Local Chassis can be installed as a dependency within a Composer-based WordPress project:

`composer require --dev altis/local-chassis`

## Getting Started

To get started once you have set up your Altis project you can run the following commands:

```
# Initialize the virtual machine 
composer chassis init

# Start the VM
composer chassis start

# Stop the VM
composer chassis stop
```

[Click here for full documentation](./docs).
