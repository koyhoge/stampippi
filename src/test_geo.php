<?php

require __DIR__ . '/../vendor/autoload.php';

use Location\Coordinate;
use Location\Distance\Vincenty;
use Location\Distance\Haversine;


$coordinate1 = new Coordinate(35.706183773092164, 139.68633360267796);
$coordinate2 = new Coordinate(35.70599793998768, 139.68638304503946);

// $calculator = new Vincenty();
$calculator = new Haversine();

echo $calculator->getDistance($coordinate1, $coordinate2);

