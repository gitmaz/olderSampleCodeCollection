<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 1/21/2016
 * Time: 5:05 PM
 *
 *  This is non object oriented version of path.php + distanceMatrix.php +main.php
 */

/*   Sample distances between nodes
      A  B  C  D  E
    A 0  1  n  3  n
    B 1  0  4  5  n
    C n  4  0  6  n
    D 3  5  6  0  n
    E n  n  n  n  0     */

$distances['A']['A'] = 0;
$distances['A']['B'] = $distances['B']['A'] = 1;
//$distances['A']['C']=$distances['C']['A']=2;
$distances['A']['D'] = $distances['D']['A'] = 3;

$distances['B']['B'] = 0;
$distances['B']['C'] = $distances['C']['B'] = 4;
$distances['B']['D'] = $distances['D']['B'] = 5;

$distances['C']['C'] = 0;
$distances['C']['D'] = $distances['D']['C'] = 5;

$distances['D']['D'] = 0;


$path = array("startMilestone" => null,
    "endMilestone" => null,
    "maxMiles" => null,
    "milestones" => array(),
    "black_list_milestones" => array(),
    "milesTraversed" => 0,
    "exact_match" => false,
    "accomplished" => false,
    "failed" => false,
    "should_back_off" => false,
);

function initializePath($startMilestone, $endMilestone, $maxMiles, &$path)
{
    $path["startMilestone"] = $startMilestone;
    $path["endMilestone"] = $endMilestone;
    $path["maxMiles"] = $maxMiles;
    array_push($path["milestones"], $startMilestone);

    return $path;
}

function traverse(&$path, $distances)
{

    do {
        $debugMilestone = getNextMilestoneInPath($path, $distances);
        if ($path["should_back_off"]) {
            //back off one level and try again (don't do it again for backed off node of course-by black listing it)
            backOffInPath($path, $distances);
            continue;
        }
    } while (!$path["accomplished"] && !$path["failed"]);


    if ($path["accomplished"]) {
        echo "SUCCESS\n";
        var_dump($path);
        return;
    }

    //if we get here it means all milestone black listed ,so there is no successfu path
    if ($path["failed"]) {
        echo "FAILURE\n";
        var_dump($path);
        return;
    }

}

function backOffInPath(&$path, $distances)
{
    $backedOffMilestone = array_pop($path["milestones"]);
    $immediateBeforeBackedOffMilestone = end($path["milestones"]);

    //black list backed off node to prevent future traverses
    array_push($path["black_list_milestones"], $backedOffMilestone);

    //compensate backing off on milesTraversed cost
    $backedOffMiles = $distances[$immediateBeforeBackedOffMilestone][$backedOffMilestone];
    $path["milesTraversed"] -= $backedOffMiles;

    $path["should_back_off"] = false;

    //if we have backed off too much,we hav failed
    if ($backedOffMilestone == $path["startMilestone"]) {
        $path["failed"] = true;
    }
}

function getNeighborMilestones($milestone, &$path, $distances)
{

    $candidateMilestones = array_keys($distances[$milestone]);

    //if candidate already black listed,take it off,as we now it will result in void
    $candidateMilestones = array_diff($candidateMilestones, $path["black_list_milestones"]);

    //candidate milestones are the ones not already traversed
    $candidateMilestones = array_diff($candidateMilestones, $path["milestones"]);

    return ($candidateMilestones);
}

function getNextMilestoneInPath(&$path, $distances)
{
    $milestone = end($path["milestones"]);
    $neighbors = getNeighborMilestones($milestone, $path, $distances);

    foreach ($neighbors as $neighbor) {

        $distanceToNeighbor = $distances[$milestone][$neighbor];
        $totMilesTraversed = $distanceToNeighbor + $path["milesTraversed"];
        if ($totMilesTraversed == $path["maxMiles"]) {

            if ($path["endMilestone"] == $neighbor) {
                //mission accomplished. just flag us as accomplished and return
                array_push($path["milestones"], $neighbor);
                $path["milesTraversed"] = $totMilesTraversed;
                $path["exact_match"] = true;
                $path["accomplished"] = true;
                return $neighbor;
            }

        } else if ($totMilesTraversed < $path["maxMiles"]) {
            //keep neighbor in mind we still have air to accomplish later from neighbor onwards
            array_push($path["milestones"], $neighbor);
            $path["milesTraversed"] = $totMilesTraversed;


            //if we have landed on endMilestone, mission is accomplished
            if ($path["endMilestone"] == $neighbor) {
                //mission accomplished. just flag us as accomplished and return
                $path["accomplished"] = true;
                return $neighbor;
            }
            return $neighbor;
        } else { // $totMilesTraversed>$path["maxMiles"]

            //we exceeded the limit,back off and try from a sibling
            $path["should_back_off"] = true;
            return null;
        }
    }

    //if no eligible neighbor found ,then we have failed!
    $path["failed"] = true;
    return null;
}


initializePath('A', 'D', 9, $path);

traverse($path, $distances);

