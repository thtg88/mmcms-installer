# mmCMS Installer

## Table of Contents

* [Installation](#installation)
* [Usage](#usage)
* [License](#license)
* [Security Vulnerabilities](#security-vulnerabilities)

## Installation

``` bash
# clone the repo
$ git clone https://github.com/thtg88/mmcms.git mmcms

# create a new Laravel application
$ laravel new mmcms-api
```

Add mmcms as a dependency of your API project
``` json
{
    ...
    "repositories": [
        {
            "type": "path",
            "url": "../mmcms"
        }
    ],
    "require": {
        ...
        "thtg88/mmcms": "*"
    },
    ...
}
```

Next from your terminal
``` bash
# Run composer update to bring in mmCMS as dependancy
composer update

# And install mmCMS
php artisan mmcms:install

```

## Usage

**Coming soon!**

## License

mmCMS Installer is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Security Vulnerabilities

If you discover a security vulnerability within mmCMS Installer, please send an e-mail to Marco Marassi at security@marco-marassi.com. All security vulnerabilities will be promptly addressed.
