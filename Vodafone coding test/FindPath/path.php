<?php

/**
 * Created by PhpStorm.
 * User: Maziar navabi
 * Date: 1/21/2016
 * Time: 6:03 PM
 *
 *  Searches and suggests a path in an array of interconnected nodes with total distance <= specified value
 *  the connectivity of nodes are defined in DistanceMatrix.php
 *
 */

class Path
{
    private $startMilestone, $endMilestone, $maxMiles, $milesTraversed = 0;

    //flags
    public $exact_match = false, $accomplished = false, $failed = false;
    private $should_back_off = false;

    //internal arrays
    public $milestones = array();
    private $black_list_milestones = array(), $distances = array();
    
    /**
     * 
     *  given a matrix of distances between nodes(as a two dimensional array with keys as name of nodes),
     *  returns an object with $milestones array filled with nodes of a suggested path between
     *  $startMilestone and $endMilestone where total length of this path is at most $maxMiles
     *  if successful on finding a path, $accomplished will be set to true
     * 
     * Path constructor.
     * @param $startMilestone
     * @param $endMilestone
     * @param $maxMiles
     * @param $distances
     */
    function __construct($startMilestone, $endMilestone, $maxMiles, $distances)
    {

        $this->startMilestone = $startMilestone;
        $this->endMilestone = $endMilestone;
        $this->maxMiles = $maxMiles;
        $this->distances = $distances;
        array_push($this->milestones, $startMilestone);

        $this->traverse();
    }

    /**
     *  traverses the nodes to find a path
     */
    private function traverse()
    {
        $should_back_off = $this->should_back_off;
        $accomplished = $this->accomplished;
        $failed = $this->failed;
        $milestones = $this->milestones;

        do {
            $debugMilestone = $this->getNextMilestone();
            if ($this->should_back_off) {//if we failed on a path
                //back off one level and try again with backed off node sibling (also don't do it again for backed off node of course-by black listing it)
                $this->backOff();
                continue;
            }
        } while (!$this->accomplished && !$this->failed);

    }

    /**
     * backs off an unsuccessful path to a previous milestone
     */
    private function backOff()
    {

        $startMilestone = $this->startMilestone;

        $backedOffMilestone = array_pop($this->milestones);
        $immediateBeforeBackedOffMilestone = end($this->milestones);

        //black list backed off node to prevent future traverses
        array_push($this->black_list_milestones, $backedOffMilestone);

        //compensate backing off on milesTraversed cost
        $backedOffMiles = $this->distances[$immediateBeforeBackedOffMilestone][$backedOffMilestone];
        $this->milesTraversed -= $backedOffMiles;

        $this->should_back_off = false;

        //if we have backed off too much,we hav failed
        if ($backedOffMilestone == $startMilestone) {
            $this->failed = true;
        }
    }

    /**
     *  returns next candidate milestones with $milestone as the center   
     * 
     * @param $milestone
     * @return array
     */
    private function getNeighborMilestones($milestone)
    {

        $candidateMilestones = array_keys($this->distances[$milestone]);

        //if candidate already black listed,take it off,as we now it will result in void
        $candidateMilestones = array_diff($candidateMilestones, $this->black_list_milestones);

        //candidate milestones are the ones not already traversed
        $candidateMilestones = array_diff($candidateMilestones, $this->milestones);

        return ($candidateMilestones);
    }

    /**
     * gets next milestone in the path
     * 
     * @return mixed|null
     */
    private function getNextMilestone()
    {
        $milestone = end($this->milestones);
        $neighbors = $this->getNeighborMilestones($milestone);

        foreach ($neighbors as $neighbor) {

            $distanceToNeighbor = $this->distances[$milestone][$neighbor];
            $totMilesTraversed = $distanceToNeighbor + $this->milesTraversed;
            if ($totMilesTraversed == $this->maxMiles) {

                if ($this->endMilestone == $neighbor) {
                    //mission accomplished. just flag us as accomplished and return
                    array_push($this->milestones, $neighbor);
                    $this->milesTraversed = $totMilesTraversed;
                    $this->exact_match = true;
                    $this->accomplished = true;
                    return $neighbor;
                }

            } else if ($totMilesTraversed < $this->maxMiles) {
                //keep neighbor in mind we still have air to accomplish later from neighbor onwards
                array_push($this->milestones, $neighbor);
                $this->milesTraversed = $totMilesTraversed;

                //if we have landed on endMilestone, mission is accomplished
                if ($this->endMilestone == $neighbor) {
                    //mission accomplished. just flag us as accomplished and return
                    $this->accomplished = true;
                    return $neighbor;
                }
                return $neighbor;
            } else { // $totMilesTraversed>$this->maxMiles

                //we exceeded the limit,back off and try from a sibling
                $this->should_back_off = true;
                return null;
            }
        }

        //if no eligible neighbor found ,then we have failed!
        $this->failed = true;
        return null;
    }
}