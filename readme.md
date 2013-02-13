# AutoSchema

## A database management library for the Laravel Framework

"Laravel is a clean and classy framework for PHP web development. Freeing you
from spaghetti code, Laravel helps you create wonderful applications using
simple, expressive syntax. Development should be a creative experience that you
enjoy, not something that is painful. Enjoy the fresh air."

## Feature Overview

- Define Schema just using the standard laravel syntax.
- Add form helpers and validation rules to a schema.
- Get an overview of your tables in the web interface.
- Quickly Create, Drop and Update tables using a web interface.

## A Few Examples

### Define a users table:

```php 
AutoSchema::table('users', function($table)
{
    $table->increments('id');
    $table->string('forename', 255);
    $table->string('surname', 255);
    $table->string('username', 255);
    $table->string('email', 255);
    $table->string('password', 255);
    $table->boolean('active');
    $table->timestamps();
});
 ```

### Add additional information:

```php 
AutoSchema::table('users', function($table)
{
    $table->increments('id');
    $table->string('forename', 255)->label('Forename')->rules('required');
    $table->string('surname', 255)->labe('Surname')->rules('required');
    $table->string('username', 255)->label('Username')->rules('required|unique');
    $table->string('email', 255)->label('Email address')->rules('required|unique|email');
    $table->string('password', 255)->label('Password');
    $table->boolean('active');
    $table->timestamps();
});
 ```

## Core functions

### Loading definitions
Load in and cache any definitions in your **config/autoschema.php** file.
```php AutoSchema::load_definitions(); ```

### Creating tables
Create a table as defined in your **config/autoschema.php** file
```php AutoSchema::create_table($table_name); ```

### Droping tables
Drop a table from the database.
```php AutoSchema::drop_table($table_name); ```

### Updating tables
Update a table in the database using the definition in your **config/autoschema.php** file
```php AutoSchema::update_table($table_name); ```

more to come...
