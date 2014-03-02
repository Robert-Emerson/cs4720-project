<?php
require 'flight/Flight.php';

function new_game($lat, $lon) {

    # From http://www.flickr.com/services/api/response.php.html
    $params = array(
	'api_key'	=> 'cb103a2b4f19e0feddc77c2f6295885c',
	'method'	=> 'flickr.photos.search',
	'lat'		=> $lat,
	'lon'		=> $lon,
	'format'	=> 'json',
	'nojsoncallback'=> '1'
    );
    $encoded_params = array();

    foreach ($params as $k => $v){

	$encoded_params[] = urlencode($k).'='.urlencode($v);
    }   


    #
    # call the API and decode the response
    #

    $url = "https://api.flickr.com/services/rest/?".implode('&', $encoded_params);

    echo "<a href=\"$url\"> $url </a> <br><br>";
    $flickr = file_get_contents($url);
    # flickr is dumb and doesn't return "proper" JSON so we have to replace \' with '
    $flickrProper = str_replace("\\'", "'", $flickr); 
    $pictures = json_decode($flickrProper, true);

    $picture_list = $pictures['photos']['photo']; //flickr adds a bit extra so we need to trim the returned result

    $chosenPictures = array(); //Blank array that holds the pictures we're using for locations

    # Choose 10 locations from everything flickr returned
    # Fails if flickr call didn't work
    if ($pictures['stat'] == 'fail') {
	echo 'well, that failed';
    } else {
    	foreach (range(0, 10) as $number) { //choosing 10 pictures now.
	    $rand = rand(0, count($picture_list)); 
	    echo "$rand: ";
	    $chosenPictures[] = $picture_list[$rand]; 
	}
    }
}

Flight::route('/new_game/@lat/@lon', function($lat, $lon) {
    echo "lat: $lat <br> lon: $lon <br>";
    new_game($lat, $lon);
});


Flight::route('/users/@id', function($id) {
  echo "You have logged in as $id";
});

Flight::route('/', function(){
    echo 'hello world!';
});

Flight::start();
?>
