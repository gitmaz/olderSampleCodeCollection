<?php

/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 1/21/2016
 * Time: 8:14 PM
 *
 * todo: add inpu output meta datas for functions
 * todo :for decoupling of code and data,and simplicity,design a factory that reads simple text ("AB"=>2 ,"AC=>3 ,...,"BC"=>5,...) file and converts it to below assoc array
 */

require_once("path.php");
require_once("distanceMatrix.php");

class PathTest extends PHPUnit_Framework_TestCase
{
    private $distances;

    public function setup()
    {

        /*   Sample distances between nodes
           A  B  C  D  E
         A 0  1  n  3  n
         B 1  0  4  5  n
         C n  4  0  6  n
         D 3  5  6  0  n
         E n  n  n  n  0     */
        $this->distances = DistanceMatrix::getDistances();

    }

    public function testIfConnectedNodesWithProperDistanceSucceeds()
    {
        $path = new Path("A", "C", 20, $this->distances);
        $expected = array("accomplished" => true, "milestones" => array("A", "B", "C"));
        $actual = array("accomplished" => $path->accomplished, "milestones" => $path->milestones);
        $this->assertEquals($expected, $actual);
    }

    public function testIfConnectedNodesWithTooShortDistanceFails()
    {
        $path = new Path("A", "C", 3, $this->distances);
        $this->assertFalse($path->accomplished);
    }

    public function testIfNonConnectedNodesEvenWithLargeDistanceFails()
    {
        $path = new Path("A", "E", 20, $this->distances);
        $this->assertFalse($path->accomplished);

    }

    public function testIfNonConnectedNodesWithTooLittleDistanceFails()
    {
        $path = new Path("A", "E", 5, $this->distances);
        $this->assertFalse($path->accomplished);

    }
}