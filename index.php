<?php
require 'flight/Flight.php';

function get_location($id) {
    # From http://www.flickr.com/services/api/response.php.html
    $params = array(
        'api_key'       => 'cb103a2b4f19e0feddc77c2f6295885c',
        'method'        => 'flickr.photos.geo.getLocation',
        'photo_id'	=> $id,
        'format'        => 'json',
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

    $flickr = file_get_contents($url);
    # flickr is dumb and doesn't return "proper" JSON so we have to replace \' with '
    $flickrProper = str_replace("\\'", "'", $flickr);
    $picture = json_decode($flickrProper, true);

    $retval = array();
    
    $retval['lat'] = $picture['photo']['location']['latitude'];
    $retval['lon'] = $picture['photo']['location']['longitude'];

    return $retval;    
}

function get_pictures($lat, $lon) {
    # From http://www.flickr.com/services/api/response.php.html
    $params = array(
        'api_key'       => 'cb103a2b4f19e0feddc77c2f6295885c',
        'method'        => 'flickr.photos.search',
        'lat'           => $lat,
        'lon'           => $lon,
        'format'        => 'json',
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

    $flickr = file_get_contents($url);
    # flickr is dumb and doesn't return "proper" JSON so we have to replace \' with '
    $flickrProper = str_replace("\\'", "'", $flickr);
    # Then we have to decode the JSON into a PHP array
    $pictures = json_decode($flickrProper, true);

    $picture_list = $pictures['photos']['photo']; //flickr adds a bit extra so we need to trim the returned result

    $chosenPictures = array(); //Blank array that holds the pictures we're using for locations

    # Choose 10 locations from everything flickr returned
    # Fails if flickr call didn't work
    if ($pictures['stat'] == 'fail') {
        echo 'well, that failed';
    } else {
        foreach (range(0, 10) as $number) { //choosing 10 pictures now.
            $rand = rand(0, count($picture_list)-1);

	    # adds location data to each chosen image
            $picture_list[$rand]['location'] = get_location($picture_list[$rand]['id']);
	    # adds the randomly selected image to our array of chosen images
            $chosenPictures[] = $picture_list[$rand];
	}
    }

    return $chosenPictures;
}

function insert_game($pictures, $lat, $lon) {
    $gameNum = 0; # Will actually get this value from querying count (SELECT * FROM games) + 1, so we get an id for the next game
    foreach ($pictures as $key => $value) {
	$id = $value['id'];
	$pictureLat = $value['location']['lat'];
	$picutreLon = $value['location']['lon'];
	$url = "http://farm".$value['farm'].".staticflickr.com/".$value['server']."/".$id."_".$value['secret'].".jpg";

	# insert all this stuff into the picture table (id, lat, lon, url, gameNum)
    }

    # once we insert the pictures, we insert a game into the games table
    # INSERT INTO games (game_id, lat, lon, winner) VALUES (gameNum, lat, lon, NULL)
    # Date can probably be automagically added when it is inserted
}

function new_game($lat, $lon) {

    # First get the pictures we're using for locations
    $pictures = get_pictures($lat, $lon);

    # now we've got our pictures, so let's put them in the database
    # this should probably return the data that we added, in some manner our client will use
    insert_game($pictures, $lat, $lon);
}

Flight::route('/new_game/@lat/@lon', function($lat, $lon) {

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
