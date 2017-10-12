<?php

/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 1/22/2016
 * Time: 6:20 AM
 *
 * Defines a sample connectivity between Nodes "A" to "E" and the length of each connection
 *
 */
class DistanceMatrix
{
    static $distances;

    static function getDistances()
    {
        /*   Sample distances between nodes
             A  B  C  D  E
           A 0  1  n  3  n
           B 1  0  4  5  n
           C n  4  0  6  n
           D 3  5  6  0  n
           E n  n  n  n  0     */
        
        DistanceMatrix::$distances = array(
            "A" => array("B" => 1, "D" => 3),
            "B" => array("A" => 1, "C" => 4, "D" => 5),
            "C" => array("B" => 4, "D" => 6),
            "D" => array("A" => 3, "B" => 5, "C" => 6,));
        
        return DistanceMatrix::$distances;
    }
}