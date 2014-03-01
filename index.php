<?php
require 'flight/Flight.php';

Flight::route('/', function(){
    echo '<h1> CS4720 - Project page! </h1>';
    echo '<hr>Sarah Clifton, Robert Emerson, Chet Gray';
});

Flight::start();
?>
