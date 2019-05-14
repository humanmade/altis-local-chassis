# Local Chassis

[Chassis](http://chassis.io/) is the bundled local development environment.


## Setup

Chassis requires [Vagrant](https://www.vagrantup.com/) and [VirtualBox](https://www.virtualbox.org/) to be installed on your system.

To set up Chassis for Altis, run the following inside your project's directory:

```
composer chassis init
```

This will add Chassis to your development dependencies and prepare it to be run.

You can then use the other `composer chassis` commands to manage your machine.


## Available Commands

A number of convenience commands are available:

* `composer chassis init` - Initialises your local Chassis setup and starts the virtual machine.
* `composer chassis start` - Starts the virtual machine.
* `composer chassis stop` - Stops the virtual machine.
* `composer chassis status` - Displays the status of the virtual machine.

Under the hood, the Local Chassis environment is powered by [Chassis](http://chassis.io/) and [Vagrant](https://www.vagrantup.com/).

You can use the low-level [Vagrant commands](https://www.vagrantup.com/docs/cli/) inside the `chassis` directory after you have run `composer chassis init` for the first time.


## Extensions

Chassis has a [number of extensions](https://beta.chassis.io/extensions/) available which can be used to add additional functionality to your development environment. By default, your Local Chassis install is set up to mirror the HM Cloud infrastructure, but you may wish to enable other tools for local development.

We recommend the following common development tools:

* [SequelPro](https://github.com/Chassis/SequelPro) - Adds a `vagrant sequel` command to instantly connect to your development MySQL server in [Sequel Pro](https://www.sequelpro.com/)
* [XDebug](https://github.com/Chassis/Xdebug) - Installs XDebug for interactive debugging in your editor
* [phpdbg](https://github.com/Chassis/phpdbg) - Installs phpdbg for interactive command-line debugging
* [Mailhog](https://github.com/Chassis/MailHog) - Captures outbound email from Altis and provides a fake inbox

Consult the [Chassis documentation](http://docs.chassis.io/en/latest/extend/) for information about installing additional extensions.