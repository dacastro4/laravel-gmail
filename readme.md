# Laravel Gmail

[![Build Status](https://scrutinizer-ci.com/g/dacastro4/laravel-gmail/badges/build.png?b=master)](https://scrutinizer-ci.com/g/dacastro4/laravel-gmail/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dacastro4/laravel-gmail/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/dacastro4/laravel-gmail/?branch=master)
[![GitHub issues](https://img.shields.io/github/issues/dacastro4/laravel-gmail.svg)](https://github.com/dacastro4/laravel-gmail/issues)
[![GitHub license](https://img.shields.io/github/license/dacastro4/laravel-gmail.svg)](https://github.com/dacastro4/laravel-gmail/blob/master/LICENSE)

# Gmail
Gmail API for Laravel 5

You need to create an application in the [Google Console](https://console.developers.google.com/apis/credentials?project=robotic-jet-193118&authuser=1). Guidance [here](https://developers.google.com/gmail/api/quickstart/php#step_1_turn_on_the_api_name).

# Requirements

* PHP 5.6 or higher
* Laravel 5

# Installation

Add dacastro4/laravel-gmail to composer.json.

`"dacastro4/laravel-gmail": "^0.1"`

Run composer update to pull down the latest version.

Or run

`composer require dacastro4/laravel-gmail`
 
Now open up `config/app.php` and add the service provider to your providers array.

```
'providers' => [
	Dacastro4\LaravelGmail\LaravelGmailServiceProvider::class,
]
```

Now add the alias.

```
'aliases' => [
	'Twitter' => Dacastro4\LaravelGmail\Facades\LaravelGmail::class,
]
```

# Configuration

You only have to set the following variables on your `.env` file and you'll be on your way:

```
GOOGLE_PROJECT_ID=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
```

To modify the scopes and the credentials file name, just run:

Run `php artisan vendor:publish --provider="Dacastro4\LaravelGmail\LaravelGmailServiceProvider"` and modify the config file `config/gmail.php`.

# Examples

# Documentation

## Messages
