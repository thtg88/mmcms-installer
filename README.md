# mmCMS Installer

## Table of Contents

* [Installation](#installation)
* [Usage](#usage)
* [License](#license)
* [Security Vulnerabilities](#security-vulnerabilities)

## Installation

```bash
composer global require thtg88/mmcms-installer
```

Make sure to place Composer's system-wide vendor bin directory in your $PATH so the `mmcms` executable can be located by your system.
This directory exists in different locations based on your operating system;
however, some common locations include:

- macOS: `$HOME/.composer/vendor/bin`
- Windows: `%USERPROFILE%\AppData\Roaming\Composer\vendor\bin`
- GNU / Linux Distributions: `$HOME/.config/composer/vendor/bin` or `$HOME/.composer/vendor/bin`

You could also find the composer's global installation path by running `composer global about` and looking up from the first line.

## Usage

Once installed, the `mmcms` new command will create a fresh mmCMS installation in the directory you specify.
For instance, `mmcms new blog` will create a directory named `blog` containing a fresh mmCMS installation with all of mmCMS's dependencies already installed:

```bash
mmcms new blog
```

## License

mmCMS Installer is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Security Vulnerabilities

If you discover a security vulnerability within mmCMS Installer, please send an e-mail to Marco Marassi at security@marco-marassi.com. All security vulnerabilities will be promptly addressed.
