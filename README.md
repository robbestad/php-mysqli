# Lightweight MySQL/MariaDB module for PHP

###More info

http://blog.robbestad.com

### How to use

In your composer.json, add the following line

    "svenanders/sarmysql": "dev-master"


In your code, include the class:

    use SarMysql/SarMysql;

(or use composer's autoloader)

and then in your functions, use it like this:

    $dbConn = new SarMysql/SarMysql("environment", "database", "collection");
    $cursor = $dbConn->find(array("key" => "value"));

Supports:

    ->find
    ->insert
    ->update
    ->delete

Tests: 

execute **phpunit vendor/svenanders/sardatabases/tests/** from the root of your project to run the tests

#####License:

Sven Anders Robbestad (C) 2014

<img src="http://i.creativecommons.org/l/by/3.0/88x31.png" alt="CC BY">

