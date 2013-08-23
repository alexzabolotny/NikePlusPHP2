# Nike+ PHP 2 #

Disclaimer: This library is not affiliated with Nike, Inc.

## Purpose ##

Using Nike+ gadgets and apps generates all sorts of data, like running stats and GPS tracks. Nike+ PHP 2 provides
PHP API to access your data on Nike servers, retrieve and filter it.

## Usage ##
### Composer installation ###

Add composer dependency:

    $ composer require "alexzabolotny/nike-plus-php2=0.1.*"

In code:

    use NikePlusPHP2\Api;

    $nike = new Api(NIKE_USERNAME, NIKE_PASSWORD);
    $data = $nike->generalData();

Read `NikePlusPHP\Api` class annotations to get idea of available methods. Also refer to documentation at
[Nike Dev site](https://developer.nike.com/) for additional information.
