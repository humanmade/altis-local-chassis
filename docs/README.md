# Local Chassis

![](./assets/banner-local-server.png)

[Chassis](http://chassis.io/) is the bundled local development environment.


## Configuration

It is possible to extend the default configuration for your local environment through the `composer.json` file. The values you can set correspond to those found in the Chassis documentation. In most cases you won't need to change anything here.

The following example adds some custom hosts and an extension:

```json
{
	"extra": {
		"altis": {
			"modules": {
				"local-chassis": {
					"hosts": [
						"project.local",
						"subdomain.project.local",
						"alt-project.local"
					],
					"extensions": [
						"xdebug"
					]
				}
			}
		}
	}
}
```


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

* `composer chassis init` - Initializes your local Chassis setup and starts the virtual machine.
* `composer chassis start` - Starts the virtual machine.
* `composer chassis stop` - Stops the virtual machine.
* `composer chassis status` - Displays the status of the virtual machine.
* `composer chassis secure` - Installs the generated SSL certificate to your trusted certificate store.
* `composer chassis destroy` - Destroys the virtual machine.
* `composer chassis ssh|shell` - Logs in to the virtual machine.
* `composer chassis exec -- <command>` - Run a command on the virtual machine.
* `composer chassis restart|reload` - Restart the virtual machine.
* `composer chassis provision` - Updates the `config.local.yaml` file and re-provisions the machine.
* `composer chassis upgrade` - Upgrade Chassis and the extensions. _Note this will remove any extensions added manually._

Under the hood, the Local Chassis environment is powered by [Chassis](http://chassis.io/) and [Vagrant](https://www.vagrantup.com/).

You can use the low-level [Vagrant commands](https://www.vagrantup.com/docs/cli/) inside the `chassis` directory after you have run `composer chassis init` for the first time.


## Extensions

Chassis has a [number of extensions](https://beta.chassis.io/extensions/) available which can be used to add additional functionality to your development environment. By default, your Local Chassis install is set up to mirror the Altis infrastructure, but you may wish to enable other tools for local development.

We recommend the following common development tools:

* [SequelPro](https://github.com/Chassis/SequelPro) - Adds a `vagrant sequel` command to instantly connect to your development MySQL server in [Sequel Pro](https://www.sequelpro.com/)
* [XDebug](https://github.com/Chassis/Xdebug) - Installs XDebug for interactive debugging in your editor
* [phpdbg](https://github.com/Chassis/phpdbg) - Installs phpdbg for interactive command-line debugging
* [Mailhog](https://github.com/Chassis/MailHog) - Captures outbound email from Altis and provides a fake inbox

You can add extra extensions by adding them to the project's `composer.json` config as shown in the [configuration section of this page](#Configuration) and running `composer chassis provision`.

Consult the [Chassis documentation](http://docs.chassis.io/en/latest/extend/) for further information about installing additional extensions.


## Using HTTPS locally

Local Chassis will generate an HTTPS security certificate you can use to run your local environment over HTTPS. The file will be located in the `/chassis` directory, by default it will be called `altis.local.cert` but if you have customized the `hosts` in `config.local.yaml` it will use the first host name in that list for the file name.

Once your VM is running run the following command to install the certificate:

```
composer chassis secure
```

You should now be able to browse your local environment via HTTPS without certificate warnings.

**Note:** this command only supports OSX and Windows currently.

### Windows

On Windows systems note that the `composer chassis secure` command requires administrator privileges.

In order for it to work you'll need to start your command prompt application such as GitBash by right-clicking the icon and selecting "Run as Administrator" from the context menu.

You can run the entire `composer chassis init` command in the administrator context but you should be sure that you are comfortable with everything the command is doing beforehand.

### Firefox

Because the Firefox browser uses its own certificate store you will either need to install the generated certificate file manually or alternatively follow these steps:

1. Open Firefox
1. Browse to `about:config`
1. Set `security.enterprise_roots.enabled` to true

## Troubleshooting

Consult the [Chassis Troubleshooting guide](https://docs.chassis.io/en/latest/reference/#troubleshooting)
