<h1 align="center">Couch DB Check</h1>

<p align="center">
A Couch DB check to use in your workshops!
</p>

<p align="center">
<a href="https://phpschool-team.slack.com/messages">
    <img src="https://phpschool.herokuapp.com/badge.svg">
</a>
</p>
----

## Installation

In your workshop:

```sh
composer require php-school/couch-db-check
```

Register the check with the application:

```php
//app/bootstrap.php

use PhpSchool\CouchDb\CouchDbCheck;

...

$app = new Application('My Workshop', __DIR__ . '/config.php');
$app->addCheck(CouchDbCheck::class);
```

Register the check with the container:

```php
//app/config.php

use PhpSchool\CouchDb\CouchDbCheck;

return [

   ...
  
   CouchDbCheck::class => object(),
]
```


## Usage

Your exercise should implement the interface `PhpSchool\CouchDb\CouchDbExerciseCheck`

This introduces the methods:

```php
/**
 * @param CouchDBClient $couchDbClient
 * @return void
 */
public function seed(CouchDBClient $couchDbClient);

/**
 * @param CouchDBClient $couchDbClient
 * @return bool
 */
public function verify(CouchDBClient $couchDbClient);

```

The check will automatically create two databases before running/verifying, and remove them at the end of running/verifying. The databases `phpschool` 
and `phpschool-student` will be created. These arguments will be prepended to the exercise cli arguments. `phpschool` to your proposed solution and `phpschool-student` to the students submission.

In the `seed` method, you should configure the database with any data you might want in there for the exercise. You are passed an instance
of `CouchDBClient` which is connected to the students database. You can read about the methods available on it [here](https://github.com/doctrine/couchdb-client).
After seeding, the check will make sure all the data added, is synchronised with the solution database. This allows for the solution and the students submission to
act on the same data without interferring with each other.

The `verify` method is called after your solution and the students submission have been run, therefore you can perform analysis on the database (you will be passed a client connected to the students database)
to check the exercise was completed succesfully. For example, you can check documents were inserted, updated or removed.


