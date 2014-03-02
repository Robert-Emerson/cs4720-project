<?php
require 'flight/Flight.php';

Flight::route('/@name', function($id) {
    echo "Hello $name";
});

Flight::route('/users/@id', function($id) {
  echo "You have logged in as $id";
});

Flight::route('/', function(){
    echo 'hello world!';
});

Flight::start();
?>
