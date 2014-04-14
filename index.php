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
	'sort'		=> 'date-posted-desc',
	'per_page'	=> '500',
	'radius'	=> '1',
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
    if ($pictures['stat'] == 'fail' || $pictures['photos']['total'] < 10) {
        #We don't do anything here
    } else {
        foreach (range(0, 9) as $number) { //choosing 10 pictures now.
            $rand = rand(0, count($picture_list)-1);

	    # adds location data to each chosen image
            $picture_list[$rand]['location'] = get_location($picture_list[$rand]['id']);
	    # adds the randomly selected image to our array of chosen images
            $chosenPictures[] = $picture_list[$rand];
		}
    }

    return $chosenPictures;
}

function insert_game($pictures, $lat, $lon, $user) {


	$retval = array();

	$db_connection = new mysqli('stardock.cs.virginia.edu', 'cs4720roe2pj', 'spring2014', 'cs4720roe2pj');
	if (mysqli_connect_errno()) {
		echo "error connecting to database";
	}
	$stmt = $db_connection->stmt_init();
	if($stmt->prepare("select count(*) from games")) {
		$stmt->execute();
		$stmt->bind_result( $gameID);
		$stmt->fetch();
	}
	
	$gameID = $gameID + 1;
		
    foreach ($pictures as $key => $value) {
		$id = $value['id'];
		$pictureLat = $value['location']['lat'];
		$pictureLon = $value['location']['lon'];
		$url = "http://farm".$value['farm'].".staticflickr.com/".$value['server']."/".$id."_".$value['secret'].".jpg";
		
		$db_connection = new mysqli('stardock.cs.virginia.edu', 'cs4720roe2pj', 'spring2014', 'cs4720roe2pj');
		if (mysqli_connect_errno()) {
			echo "error connecting to database";
		}
		$stmt = $db_connection->stmt_init();
		if($stmt->prepare("insert into pictures (`gameID`, `photoID`, `lat`, `lon`, `url`) values (?, ?, ?, ?, ?)")) {
			$stmt->bind_param("isdds", $gameID, $id, $pictureLat, $pictureLon, $url);
			$stmt->execute();
		}
		$retval[] = array('url' => $url, 'lat' => $pictureLat, 'lon' => $pictureLon);
    }

	# once we insert the pictures, we insert a game into the games table
    # Date can probably be automagically added when it is inserted
	$db_connection = new mysqli('stardock.cs.virginia.edu', 'cs4720roe2pj', 'spring2014', 'cs4720roe2pj');
	if (mysqli_connect_errno()) {
		echo "error connecting to database";
	}
	$stmt = $db_connection->stmt_init();
	if (!is_null($user)) {

		if($stmt->prepare("insert into games (`gameID`, `lat`, `lon`) values (?, ?, ?)")) {
			$stmt->bind_param("idd", $gameID, $lat, $lon);
			$stmt->execute();
		}
		if($stmt->prepare("insert into usersInGame (`gameID`, `username`) values (?, ?)")) {
			$stmt->bind_param("is", $gameID, $user);
			$stmt->execute();
		}
	} else {
		if($stmt->prepare("insert into games (`gameID`, `lat`, `lon`) values (?, ?, ?)")) {
		$stmt->bind_param("idd", $gameID, $lat, $lon);
		$stmt->execute();
		}
	}
	return $retval;
}

function new_game($lat, $lon, $user) {

    # First get the pictures we're using for locations
    $pictures = get_pictures($lat, $lon);

    # now we've got our pictures, so let's put them in the database
    # this should probably return the data that we added, in some manner our client will use
    echo json_encode(insert_game($pictures, $lat, $lon, $user));
}

function join_game($gameID, $userID) {

	$retval = array();

	
	$db_connection = new mysqli('stardock.cs.virginia.edu', 'cs4720roe2pj', 'spring2014', 'cs4720roe2pj');
	if (mysqli_connect_errno()) {
		echo "error connecting to database";
	}
	$stmt = $db_connection->stmt_init();
	if($stmt->prepare("INSERT INTO usersInGame (`username`, `gameID`) VALUES (?,?)")) {
		$stmt->bind_param('ss', $userID, $gameID);
		$stmt->execute();
	}
	
	$stmt = $db_connection->stmt_init();
	if($stmt->prepare("SELECT url, lat, lon FROM pictures WHERE gameID = ?")) {
		$stmt->bind_param('s', $gameID);
		$stmt->execute();
		$stmt->bind_result( $url, $lat, $lon);
		while($stmt->fetch()) {
		    $retval[] = array('url' => $url, 'lat' => $lat, 'lon' => $lon);
		}
	}
	echo json_encode($retval);
}

function get_scores($gameID) {
	$retval = array();

	$db_connection = new mysqli('stardock.cs.virginia.edu', 'cs4720roe2pj', 'spring2014', 'cs4720roe2pj');
	if (mysqli_connect_errno()) {
		echo "error connecting to database";
	}
	$stmt = $db_connection->stmt_init();
	if($stmt->prepare("SELECT `username`,`score` FROM `usersInGame` WHERE gameID = ?")) {
		$stmt->bind_param('i', $gameID);
		$stmt->execute();
		$stmt->bind_result( $username, $score);

		while($stmt->fetch()) {
		    $retval[] = array('username' => $username, 'score' => $score);
		}
	}

	echo json_encode($retval);

}

function someone_won($gameID, $user) {
	$db_connection = new mysqli('stardock.cs.virginia.edu', 'cs4720roe2pj', 'spring2014', 'cs4720roe2pj');
	if (mysqli_connect_errno()) {
		echo "error connecting to database";
	}
	$stmt = $db_connection->stmt_init();
	if($stmt->prepare("UPDATE `games` SET `winner`= ? WHERE `gameID` = ?")) {
		$stmt->bind_param('si', $user, $gameID);
		$stmt->execute();
		
	}
}

function update_score($gameID, $user, $score) {
	$db_connection = new mysqli('stardock.cs.virginia.edu', 'cs4720roe2pj', 'spring2014', 'cs4720roe2pj');
	if (mysqli_connect_errno()) {
		echo "error connecting to database";
	}
	$stmt = $db_connection->stmt_init();
	if($stmt->prepare("UPDATE `usersInGame` SET `score`= ? WHERE `username` = ? AND `gameID` = ?")) {
		$stmt->bind_param('isi', $score, $user, $gameID);
		$stmt->execute();
		if ($stmt->errno) {
			echo '{"status":"'.$stmt->error.'"}';
		} else {
			echo '{"status":"OK"}';
		}
	}

}

function get_recent_game($user) {
	
	$db_connection = new mysqli('stardock.cs.virginia.edu', 'cs4720roe2pj', 'spring2014', 'cs4720roe2pj');
	if (mysqli_connect_errno()) {
		echo "error connecting to database";
	}
	$stmt = $db_connection->stmt_init();
	if($stmt->prepare("SELECT gameID from usersInGame NATURAL JOIN games WHERE username = ? ORDER BY gameID DESC LIMIT 1")) {
		$stmt->bind_param('s', $user);
		$stmt->execute();
		$stmt->bind_result( $gameID);

		while($stmt->fetch()) {
		    echo '{"gameID":'.$gameID.'}';
		}

	}
}

Flight::route('/new_game/@lat/@lon(/@user)', function($lat, $lon, $user) {
    new_game($lat, $lon, $user);
});

Flight::route('/join_game/@id/@user', function($id, $user) {
    join_game($id, $user);
});

Flight::route('/get_scores/@gameID', function($gameID) {
	get_scores($gameID);
});

Flight::route('/winning/@gameID/@user', function($gameID, $user) {
	someone_won($gameID, $user);
	
});

Flight::route('/update_score/@gameID/@user/@score', function($gameID, $user, $score) {
	update_score($gameID, $user, $score);
});

Flight::route('/get_recent_game/@user', function ($user) {
	get_recent_game($user);
});

Flight::route('/', function() {
	echo "photo-enteering";
});

Flight::start();
