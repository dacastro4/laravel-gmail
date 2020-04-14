# Laravel Gmail

[![Build Status](https://scrutinizer-ci.com/g/dacastro4/laravel-gmail/badges/build.png?b=master)](https://scrutinizer-ci.com/g/dacastro4/laravel-gmail/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dacastro4/laravel-gmail/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/dacastro4/laravel-gmail/?branch=master)
[![GitHub issues](https://img.shields.io/github/issues/dacastro4/laravel-gmail.svg)](https://github.com/dacastro4/laravel-gmail/issues)
[![Total Downloads](https://poser.pugx.org/dacastro4/laravel-gmail/downloads)](https://packagist.org/packages/dacastro4/laravel-gmail)
[![Monthly Downloads](https://poser.pugx.org/dacastro4/laravel-gmail/d/monthly)](https://packagist.org/packages/dacastro4/laravel-gmail)
[![GitHub license](https://img.shields.io/github/license/dacastro4/laravel-gmail.svg)](https://github.com/dacastro4/laravel-gmail/blob/master/LICENSE)
[![Beerpay](https://beerpay.io/dacastro4/laravel-gmail/badge.svg)](https://beerpay.io/dacastro4/laravel-gmail)


# Gmail
Gmail API for Laravel 7

You need to create an application in the [Google Console](https://console.developers.google.com/apis/credentials?project=robotic-jet-193118&authuser=1). Guidance [here](https://developers.google.com/gmail/api/quickstart/php#step_1_turn_on_the_api_name).

if you need **Laravel 5** compatibility please use version `2.0.x`.
if you need **Laravel 6** compatibility please use version `3.0.x`.

# Requirements

* \>= PHP 7.2.5 <= PHP 7.3
* Laravel 7

# Installation

Add dacastro4/laravel-gmail to composer.json.

`"dacastro4/laravel-gmail": "^4.0"`

Run composer update to pull down the latest version.

Or run

`composer require dacastro4/laravel-gmail`
 
Now open up `config/app.php` and add the service provider to your providers array.

``` php
'providers' => [
    Dacastro4\LaravelGmail\LaravelGmailServiceProvider::class,
]
```

Now add the alias.

``` php
'aliases' => [
    'LaravelGmail' => Dacastro4\LaravelGmail\Facade\LaravelGmail::class,
]
```

For laravel >=5.5 that's all. This package supports Laravel new [Package Discovery](https://laravel.com/docs/5.5/packages#package-discovery).

# Migration from 3.0 to 4.0

Requires Laravel 7 and you have to change the dependency to `"laravel/laravel": "^7.0"`
Please, follow [Upgrading To 7.0 From 6.x Guide](https://laravel.com/docs/7.x/upgrade)

# Migration from 2.0 to 3.0

Requires Laravel 6 and you only have to change the dependency to `"laravel/laravel": "^6.0"`

# Migration from 1.0 to 2.0
The only changed made was the multi credentials feature.
- Change your composer.json from `"dacastro4/laravel-gmail": "^1.0"` to `"dacastro4/laravel-gmail": "^2.0"`

I had to change version because of a typo and that might break apps calling those attributes.

All variable with the word "threat" was change to "thread" (yeah, I know.. sorry)
Ex:
 
 Mail Class
    `$threatId` => `$threadId`
 
 Replyable Class
    `$mail->setReplyThreat()` => `$mail->setReplyThread()`    

and so on.

# Migration from 0.6 to 1.0
The only changed made was the multi credentials feature.
- Change your composer.json from `"dacastro4/laravel-gmail": "^0.6"` to `"dacastro4/laravel-gmail": "^1.0"`

If you don't want the multi user credentials, you don't have to do anything else, if you do, you're going to have to 
login again to create a new credentials file per user.


# Configuration

You only have to set the following variables on your `.env` file and you'll be on your way:

``` dotenv
GOOGLE_PROJECT_ID=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
GOOGLE_ALLOW_MULTIPLE_CREDENTIALS
GOOGLE_ALLOW_JSON_ENCRYPT
```

To modify the scopes and the credentials file name, just run:

Run `php artisan vendor:publish --provider="Dacastro4\LaravelGmail\LaravelGmailServiceProvider"` and modify the config file `config/gmail.php`.

### Allow multi user credentials
To allow multi user credentials change `allow_multiple_credentials` to `true` in your config file or set the .env variable 
`GOOGLE_ALLOW_MULTIPLE_CREDENTIALS` to true if you're not using the config file.
### Allow encryption for json files
To allow encryption for json files change `allow_json_encrypt` to `true` in your config file or set the .env variable 
`GOOGLE_ALLOW_JSON_ENCRYPT` to true if you're not using the config file.

### Available Scopes

* all *(this one doesn't exists on Gmail Scopes, I added it.)*
* compose         
* insert          
* labels          
* metadata        
* modify          
* readonly        
* send            
* settings_basic  
* settings_sharing

[More about Gmail API scopes](https://developers.google.com/gmail/api/auth/scopes)

Note: To change the scopes, users have to logout and login again. 

#### Additional Scopes
If for some reason you need to add additional scopes.

Add additional scopes in URL Style in config/gmail.php

```
 'additional_scopes' => [
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/documents',
            'https://www.googleapis.com/auth/spreadsheets'
    ],
```


# Example

## Welcome Blade:
```blade
<body>
    <h1>{{ LaravelGmail::user() }}</h1>
    @if(LaravelGmail::check())
        <a href="{{ url('oauth/gmail/logout') }}">logout</a>
    @else
        <a href="{{ url('oauth/gmail') }}">login</a>
    @endif
</body>
```

## Routes:
```php
Route::get('/oauth/gmail', function (){
    return LaravelGmail::redirect();
});

Route::get('/oauth/gmail/callback', function (){
    LaravelGmail::makeToken();
    return redirect()->to('/');
});

Route::get('/oauth/gmail/logout', function (){
    LaravelGmail::logout(); //It returns exception if fails
    return redirect()->to('/');
});
```

Then if in your controller or wherever you want to do your logic, you do something like:
```php
$messages = LaravelGmail::message()->subject('test')->unread()->preload()->all();
foreach ( $messages as $message ) {
    $body = $message->getHtmlBody();
    $subject = $message->getSubject();
}
```
Note that if you don't preload the messages you have to do something like:
` $body = $message->load()->getSubject();`
and after that you don't have to call it again.

# Documentation

## Basic

`LaravelGmail::getAuthUrl` Gets the URL to auth the user.

`LaravelGmail::redirect` You can use this as a direct method `<a href="{{ LaravelGmail::redirect() }}">Login</a>`

`LaravelGmail::makeToken()` Set and Save AccessToken in json file (useful in the callback)

`LaravelGmail::logout` Logs out the user

`LaravelGmail::check` Checks if the user is logged in


## Sending

```
use Dacastro4\LaravelGmail\Services\Message\Mail;

...

$mail = new Mail;
``` 
For `to`, `from`, `cc` and `bcc`, you can set an array of emails and name or a string of email and name.


`$mail->using( $token )` If you don't want to use the token file, you can use this function that sets the token to use in the request. It doesn't refresh

`$mail->to( $to, $name = null )` sets the recipient email and name as optional

`$mail->from( $from, $name = null )` sets sender's email

`$mail->cc( $cc, $name = null )` sets carbon copy

`$mail->bcc( $bcc, $name = null )` sets a blind carbon copy

`$mail->subject( $subject )` sets the subject of the email

`$mail->message( $message )` sets the body of the email

`$mail->view( 'view.name', $dataArray )` sets the body from a blade file

`$mail->attach( ...$path )` add file attachments to the email

`$mail->priority( $priority )` sets the priority of the email from 1 to 5

`$mail->reply()` replies to an existent email

`$mail->send()` sends a new email

`$mail->setHeader( $header, $value )` sets header to the email

## Mail

`$mail->getId` returns the email's ID

`$mail->getInternalDate` returns date in UNIX format

`$mail->getDate` returns a Carbon date from the header of the email

`$mail->getLabels` returns an array of all the labels of the email

`$mail->getHeaders` returns a collection of the header. Each header is an array with two rows key and value

`$mail->getSubject` returns an string of the subject

`$mail->getFrom` Returns an array with name and email of sender

`$mail->getFromName` Returns string of name

`$mail->getFromEmail` Returns string of email

`$mail->getTo` Returns an array with name and email of all recipients

`$mail->getDeliveredTo` Returns the email of the receiver

`$mail->getPlainTextBody` Returns the plain text version of the email

`$mail->getRawPlainTextBody` Returns the raw version of the body base64 encrypted

`$mail->hasAttachments` Returns a boolean if the email has attachments

`$mail->load` Load all the information of the email (labels, body, headers). You call this function on a single email. To load from the beginning see [preload()](#preload)

`$mail->getHeader( $headerName, $regex = null )` Returns the header by name. Optionally, you can execute a regex on the value


# Labels

`$mail->markAsRead` Removes the 'UNREAD' label from the email.

`$mail->markAsUnread` Adds the 'UNREAD' label to the email.

`$mail->markAsImportant` Adds the 'IMPORTANT' label to the email.

`$mail->markAsNotImportant` Removes the 'IMPORTANT' label from the email.

`$mail->addStar` Adds the 'STARRED' label to the email.

`$mail->removeStar` Removes the 'STARRED' label from the email.

`$mail->sendToTrash` Adds the 'TRASH' label to the email.

`$mail->removeFromTrash` Removes the 'TRASH' label from the email.

`$mail->addLabel($string|$array)` Add multiple or single label to the email

`$mail->removeLabel($string|$array)` Removes multiple or single label from the email

`$mail->getAttachments()` Get a collection of all the attachments on the email

`$mail->getAttachmentsWithData()` Get a collection of all the attachments on the email including the data

## Attachment

```
use Dacastro4\LaravelGmail\Services\Message\Attachment
...

$attachment = new Attachment;
``` 

`$attachment->getId` Returns the ID of the attachment

`$attachment->getFileName` Returns the file name of the attachment

`$attachment->getMimeType` Returns the mime type Ex: application/pdf

`$attachment->getSize` Returns the size of the attachment in bytes

`$attachment->getData` Get the all the information from the attachment. If you call `getAttachmentsWithData` you won't need this method. 

`$attachment->saveAttachmentTo($path = null, $filename = null, $disk = 'local')` Saves the attachment on the storage folder. You can pass the path, name and disk to use. 


## Messages

`LaravelGmail::message()->all( $pageToken = null )` Returns all the emails from the inbox

`LaravelGmail::message()->take(2)->all( $pageToken = null )` The `take` method limits the emails coming from the query by the number set

`LaravelGmail::message()->get( $id )` Returns a single email with all the information

### Modifiers

You can modify your query with these methods. For example:

To get all unread emails: `LaravelGmail::message()->unread()->all()`

`message()->unread()`

`message()->from( $email )`

`message()->in( $box = 'inbox' )`

`message()->hasAttachment()`

`message()->subject($subject)`

`->after($date)` and `->before($date)`

`message()->raw($query)` for customized queries

All the possible filters are in the [Filterable Trait](https://github.com/dacastro4/laravel-gmail/blob/master/src/Traits/Filterable.php)

Of course you can use as a fluent api.

``` php
    
    LaravelGmail::message()
                ->from('someone@gmail.com')
                ->unread()
                ->in('TRASH')
                ->hasAttachment()
                ->all()
```

### Preload

You can preload the body, header and the rest of every single email just by calling this method.

`LaravelGmail::preload()`

Example:

``` php
    
    LaravelGmail::message()
                ->from('someone@gmail.com')
                ->unread()
                ->in('TRASH')
                ->hasAttachment()
                ->preload()
                ->all()
```

### Frequent Issues

#### Login Required

If you're getting the `Login Required` error, try creating the `gmail-json.json` file under `/storage/app/gmail/tokens/`.


## Support on Beerpay
Hey dude! Help me out for a couple of :beers:!

[![Beerpay](https://beerpay.io/dacastro4/laravel-gmail/badge.svg?style=beer-square)](https://beerpay.io/dacastro4/laravel-gmail)  [![Beerpay](https://beerpay.io/dacastro4/laravel-gmail/make-wish.svg?style=flat-square)](https://beerpay.io/dacastro4/laravel-gmail?focus=wish)
