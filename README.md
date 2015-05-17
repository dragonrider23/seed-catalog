## Seed Catalog

Seed Catalog is a simple database interface in PHP. It aims to make the most common interactions with the database easier. I've been using it for quite some time. I felt like it's about time I share it.

Seed Catalog is a fork of the [Base](https://github.com/erusev/base) project by erusev.

### Features

- Simple
- Intuitive
- Independent
- Secure
- Based on [PDO](http://php.net/manual/en/book.pdo.php)
- Tested in 5.3, 5.4, 5.5, 5.6 and [HHVM](http://hhvm.com/)

### Installation

Include `SC.php`, `Collection.php`, and 'SCException.php' or install [the composer package](https://packagist.org/packages/onesimus-systems/seed-catalog).

### Examples

Connect to a database:
```php
# initialize the connection
# connect($dbtype, $host, $database, $username, $password);
$SC = \SC\SC::connect('mysql', 'localhost', 'example', 'username', 'password');

# to use the connection somewhere else, just call connect() with no parameters
$SC = \SC\SC::connect();
# connect() will return false if you haven't initialized it yet.
```

Work with records:
```php
# read user 1
$SC->readItem('user', 1);
# update the username of user 1
$SC->updateItem('user', 1, ['username' => 'john.doe']);
# create a user
$SC->createItem('user', ['username' => 'jane.doe', 'email' => 'jane@example.com']);
# delete user 1
$SC->deleteItem('user', 1);
```

Work with collections:
```php
# read all users
$SC->find('user')->read();
# read the users that are marked as verified in a desc order
$SC->find('user')->whereEqual('is_verified', 1)->orderDesc('id')->read();
# read the user with the most reputation
$SC->find('user')->limit(1)->orderDesc('reputation')->readRecord();
# mark users 1 and 3 as verified
$SC->find('user')->whereIn('id', [1, 3])->update(['is_verified' => 1]);
# count the users that don't have a location
$SC->find('user')->whereNull('location')->count();
# plain sql conditions are also supported
$SC->find('user')->where('is_verified = ?', [1])->read();
```

Handle relationships:
```php
# read the users that have a featured post
$SC->find('user')->has('post')->whereEqual('post.is_featured', 1)->read();
# read the posts of user 1
$SC->find('post')->belongsTo('user')->whereEqual('user.id', 1)->read();
# read the posts that are tagged "php"
$SC->find('post')->hasAndBelongsTo('tag')->whereEqual('tag.name', 'php')->read();
# unconventional FK names are also supported
$SC->find('user')->has('post', 'author_id')->whereEqual('user.id', 1)->read();
```

Execute queries:
```php
# read all users
$SC->read('SELECT * FROM user');
# read user 1
$SC->readRecord('SELECT * FROM user WHERE id = ?', [1]);
# read the username of user 1
$SC->readField('SELECT username FROM user WHERE id = ?', [1]);
# read all usernames
$SC->readFields('SELECT username FROM user');
# update all users
$SC->update('UPDATE INTO user SET is_verified = ?', [1]);
```

### Notes

- Relationship methods require that table names are singular - ex: `user` instead of `users`.
- Only tested with MySQL. It may work with Postgres and SQLite, but I haven't tested it yet.

<!--
[![Build Status](http://img.shields.io/travis/erusev/base.svg?style=flat-square)](https://travis-ci.org/erusev/base)

[![Latest Stable Version](http://img.shields.io/packagist/v/erusev/base.svg?style=flat-square)](https://packagist.org/packages/erusev/base)
-->
