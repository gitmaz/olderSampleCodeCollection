<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 1/22/2016
 * Time: 7:10 AM
 *
 *  Sample usage of Path class (a class which finds a path with specified length-or shorter- amongst a collection of nodes)
 */

require_once("path.php");
require_once("distanceMatrix.php");

$distances = DistanceMatrix::getDistances();

$path = new Path("A", "E", 8, $distances);

if ($path->accomplished) {
    echo "SUCCESS\n";
    var_dump(array("accomplished" => $path->accomplished, "milestones" => $path->milestones));

} else if ($path->failed) {
    echo "FAILURE\n";
    var_dump(array("accomplished" => $path->accomplished, "milestones" => $path->milestones));
}