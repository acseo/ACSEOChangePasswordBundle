#User Password History Management

## Purpose

This Bundle allows to manage user password history. It has been developped and tested to work with the famous FOSUserBundle Bundle.

What this bundle does :
- Store the User's password whenever this password is changed in the table _password_history_.
- Redirect the User to the route __fos_user_change_password__ eveytime the User's password is older than 30 days.
- Optionaly, provide a constraints that forbids the User to set a password if this password has already been used.

## Installation

1) Add the bundle to you composer.json file :
```
composer require 'acseo/change-password-bundle:dev-master'
```

2) Enable the Bundle
```php
// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
        //...
        new ACSEO\ChangePasswordBundle\ACSEOChangePasswordBundle(),
        //...
```

3) Map your User Class
The bundle use an Entity, *PasswordHistory*, which store previous hashed passwords used by an user. In order to be generic, this entity has a ManyToOne relation with a User entity. This user Entity _must_ extends the *FOS\UserBundle\Model\User* abstract class.

Edit your config file :
```
# app/config/config.yml
doctrine:
    orm:
        resolve_target_entities:
            "FOS\UserBundle\Model\User": "YourBundle\Entity\YourUser"
```

4) Update your database to create the new *password_history* table
```
$ app/console doctrine:schema:update --dump-sql
$ app/console doctrine:schema:update --force
```

*From now Password History is set up. The table password_history will store the changed user password whenever this password is changed*

5) Enable Password history constraint
````
# src/YourBundle/Resources/config/validation.yml
YourBundle\Entity\YourUser:
    properties:
        # ...
        plainPassword:
            - ACSEO\ChangePasswordBundle\Validator\Constraints\NotInPreviousPasswords: ~

*And that's it !*

## About
Feel free to comment or improve this bundle by creating issues or submitting pull requests
