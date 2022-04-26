# Icinga PHP Library - IPL

This project bundles all Icinga PHP libraries into one piece and can be integrated as library into Icinga Web 2.

## Requirements

* [Icinga Web 2](https://github.com/Icinga/icingaweb2) (>= 2.9)
* PHP (>= 7.0)

## Bundled Parts

* [ipl-html](https://github.com/Icinga/ipl-html)
* [ipl-i18n](https://github.com/Icinga/ipl-i18n)
* [ipl-orm](https://github.com/Icinga/ipl-orm)
* [ipl-sql](https://github.com/Icinga/ipl-sql)
* [ipl-stdlib](https://github.com/Icinga/ipl-stdlib)
* [ipl-validator](https://github.com/Icinga/ipl-validator)
* [ipl-web](https://github.com/Icinga/ipl-web)

## Installation

Please download the latest release and install it in one of your configured library paths. The default library
path for Icinga Web 2 installations is: `/usr/share/icinga-php`

Download or clone this repository there (e.g. `/usr/share/icinga-php/ipl`) and you're done.

> **Note**: Do NOT install the GIT master, it will not work! Checking out a
> branch like `stable/0.8.1` or a tag like `v0.8.1` is fine.

### Examples

**Sample Tarball installation**

```sh
INSTALL_PATH="/usr/share/icinga-php/ipl"
INSTALL_VERSION="v0.8.1"
mkdir "$INSTALL_PATH" \
&& wget -q "https://github.com/Icinga/icinga-php-library/archive/$INSTALL_VERSION.tar.gz" -O - \
   | tar xfz - -C "$INSTALL_PATH" --strip-components 1
```

**Sample GIT installation**

```
INSTALL_PATH="/usr/share/icinga-php/ipl"
INSTALL_VERSION="stable/0.8.1"
git clone https://github.com/Icinga/icinga-php-library.git "$INSTALL_PATH" --branch "$INSTALL_VERSION"
```
