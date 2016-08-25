# dataworkstr/Revisionable

Nice and easy way to handle revisions of your MongoDB.


*Lumen5 + MongoDB revisions 
*Support Document Model


## Requirements

* This package requires PHP 5.4+
* MongoDB 3.0+
* Currently it works out of the box with Laravel5 + generic Illuminate Guard, tymon/jwt-auth OR cartalyst/sentry 2/sentinel 2



## Usage (Laravel5 basic example - see Customization below as well)

### 1. Download the package or require in your `composer.json`:

```
    "require": {
        
        // for Lumen5+ use:
        "dataworkstr/revisionable": "~1.0",
        ...
    },

```

### 2. Add the service provider to your `app/config/app.php`:

```php
    'providers' => array(

        ...

        'Sofa\Revisionable\Laravel\ServiceProvider',
    ),
```

### 3. Publish the package config file:

```
~$ php artisan vendor:publish [--provider="Sofa\Revisionable\Laravel\ServiceProvider"]
```

this will create `config/sofa_revisionable.php` file, where you can adjust a few settings:

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User provider (auth) implementation.
    |--------------------------------------------------------------------------
    |
    | By default Laravel generic Illuminate\Auth\Guard.
    |
    | Supported options:
    |  - illuminate
    |  - sentry
    |  - sentinel
    |  - jwt-auth
    */
    'userprovider' => 'illuminate',


    /*
    |--------------------------------------------------------------------------
    | User field to be saved as the author of tracked action.
    |--------------------------------------------------------------------------
    |
    | By default:
    |
    |  - id for illuminate
    |  - login field (email) for sentry/sentinel
    |  - id or ANY field in User model for tymon/jwt-auth
    */
    'userfield'    => null,


    /*
    |--------------------------------------------------------------------------
    | Table used for the revisions.
    |--------------------------------------------------------------------------
    */
    'table'        => 'revisions',
    
         /*
     |--------------------------------------------------------------------------
     | Table max revision count / default=10
     |--------------------------------------------------------------------------
     */
      'options'        => [
          'max_revision'=>10
      ],   

];
```


### 4. Run the migration in order to create the revisions table:

```
~$ php artisan migrate [--database=custom_connection]
```

You can provide additional `--database` param if you want the migration to be run using non-default db connection.


### 5. Add revisionable trait and contract to the models you wish to keep track of:

```php
<?php namespace App;

use Sofa\Revisionable\Laravel\RevisionableTrait; // trait
use Sofa\Revisionable\Revisionable; // interface

class User extends \Eloquent implements Revisionable {

    use RevisionableTrait;

    /*
     * Set revisionable whitelist - only changes to any
     * of these fields will be tracked during updates.
     */
    protected $revisionable = [
        'email',
        'name',
        'phonenumber.main_phone.0'
    ];
```

