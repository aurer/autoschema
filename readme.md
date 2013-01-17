# AutoSchema

## A database management library for the Laravel Frameork

Laravel is a clean and classy framework for PHP web development. Freeing you
from spaghetti code, Laravel helps you create wonderful applications using
simple, expressive syntax. Development should be a creative experience that you
enjoy, not something that is painful. Enjoy the fresh air.

## Feature Overview

- Define Schema just using the standard larval syntax.
- Add form helpers and validation rules to a schema.
- Get an overview of your tables in the web interface.
- Quickly Create, Drop and Update tables using a web interface.

## A Few Examples

### Define a users table:

```php 
AutoSchema::define('users', function($table)
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
AutoSchema::define('users', function($table)
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

### Default Routes

```php 

// Show all tables and status reports
Route::get('/autoschema', function()
{
    AutoSchema::load_definitions();
    $data['tables'] = AutoSchema::check_tables();
    return View::make('autoschema.index')->with($data);
});

// Create a table using the definition
Route::get('autoschema/create/(:any)', function($table)
{
    $result = AutoSchema::create($table);
    return Redirect::back();
});

// Drop a table from the database
Route::get('autoschema/drop/(:any)', function($table)
{
    $result = AutoSchema::drop($table);
    return Redirect::back();
});

// Update a table based on a definition
Route::get('autoschema/update/(:any)', function($table)
{
    $result = AutoSchema::update_table($table);
    return Redirect::back();
});
 ```

## Core functions

### Loading definitions
Load in and cache any definitions in your **config/autoschema.php** file.
```php AutoSchema::load_definitions(); ```

### Creating tables
Create a table as defined in your **config/autoschema.php** file
```php AutoSchema::create($table_name); ```

### Droping tables
Drop a table from the database.
```php AutoSchema::drop($table_name); ```

### Updating tables
Update a table in the database using the definition in your **config/autoschema.php** file
```php AutoSchema::create($table_name); ```


## Helper functions

### Retrieving definitions to build a form
```php AutoSchema::get_for_form($table, $showall=false); ```
This function will return an array of field definitions to be used with laravels form methods.

#### Example
```php
$fields = AutoSchema::get_for_form('users');
$html = '';
foreach($fields as $field){
    $html .= '&lt;p class="field"&gt;';
    $html .= Form::label($field['label']);
    $html .= Form::input($field['type'], $field['name']);
    $html .= '&lt;/p&gt;';
}
echo $html;
```