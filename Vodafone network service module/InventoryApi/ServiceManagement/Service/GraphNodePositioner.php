<?php
namespace InventoryApi\ServiceManagement\Service;

/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 18/02/2016
 * Time: 8:28 AM
 *
 * This will position every graph nodes (either vertices or edges) so that they do not visually overlap.
 * It is usefull for putting any object in a two dimensional matrix.The context dependent display strategies
 *  then will display this in their own method having filled this with appropriate objects
 */
class GraphNodePositioner
{
    public $displayCells;//array with indices [y][x] ,it should be filled with blank in init by calling createBlankDisplayCells
    public $columnReservedFor;//array containing the name of the device the column is reserved for
    private $maxCellX_charOffset;//biggest str a column contains

    private $maxOccupiedCellY = 0;//downmoset cel Y
    private $numberOfCellsInRow = 0;
    private $nextAvailColIndex = 1;
    private $firstFreeYForDevice = [];

    /*
     *  inserts a row immediately after $insertY,ans sets its $insertX element to $value
     */
    function insertCellUnder($insertX, $insertY, $value)
    {
        $displayCellsTmp = $this->displayCells;//make a copy

        //grow one row or grow many rows and coloumns base on our position
        $numberOfRows = count($this->displayCells);
        if ($insertY + 1 <= $numberOfRows) { //grow one row algorithm (if we are in the middle of the crowd

            //check if this row occupied from a column not starting from left border
            if ($this->isRowOccupiedWithBindedRow($insertY + 1)) {
                $insertY++;
            }
            //insert new empty row at location $insertY+1
            $numberOfCellsInRow = count($this->displayCells[0]);
            $toBeInserted = array_fill(0, $numberOfCellsInRow, null);
            array_splice($this->displayCells, $insertY + 1, 0, [$toBeInserted]);

        }

        //and set element at new y,x
        $this->displayCells[$insertY + 1][$insertX] = $value;

        $this->maxOccupiedCellY++;

        if (isset($value->position)) {
            $value->position = ["x" => $insertX, "y" => $insertY + 1];
        }


    }

    function isRowOccupiedWithBindedRow($y)
    {
        if ($y == 0) {
            return false;
        }
        $firstX = $this->getFirstOccupiedCellX($y);
        if ($firstX == null) {
            return false;
        } else {
            $yAttachedCell = $this->displayCells[$y - 1][$firstX];
            if ($yAttachedCell == null) {
                return false;
            } else {
                if ($yAttachedCell->id == $this->displayCells[$y][$firstX]->id) {
                    return true;
                }
            }
        }
        return true;
    }

    function getFirstOccupiedCellX($y)
    {
        for ($x = 0; $x < $this->numberOfCellsInRow; $x++) {
            if ($this->displayCells[$y][$x] == null) {
                continue;
            } else {
                return $x;
            }
        }

        return null;
    }

    function resolveDeviceX($value, $x)
    {
        do {
            if (isset($this->columnReservedFor[$x])) {
                $deviceIdForThisColumn = $this->columnReservedFor[$x];
                if ($deviceIdForThisColumn == $value->id) {

                } else {
                    $x += 2; //put us under somewhere else
                }
            } else {
                $this->columnReservedFor[$x] = $value->id;
                break;
            }
        } while ($deviceIdForThisColumn != $value->id);

    }

    function reserveNextAvailColumnForDevice($node)
    {

        if (!isset($this->columnReservedFor[$node->id])) {//if not still dedicated
            $this->columnReservedFor[$node->id] = $this->nextAvailColIndex;
            $this->nextAvailColIndex += 2;
        }

    }

    function putCellIn($x, $y, $value, $cellType)
    {

        if ($cellType == "device") {//for devices,we dedicate a column for each device for graph to look nice
        }

        $this->displayCells[$y][$x] = $value;
        if ($y > $this->maxOccupiedCellY) $this->maxOccupiedCellY = $y;

        if (isset($value->position)) {
            $value->position = ["x" => $x, "y" => $y];
        }


    }

    /*
     *  used for final display to indent nicely
     */
    function updateMaxCellX_StrLen($value)
    {
        //todo: make this independent of $value structure

        $x = $value->position["x"];
        if (!isset($this->maxCellX_charOffset[$x])) {
            $this->maxCellX_charOffset[$x] = -1;
        }
        $this->maxCellX_charOffset[$x] = max($this->maxCellX_charOffset[$x], strlen($value->id));

        $leftSibling = $value->leftSiblingNode;
        $offsetFromLeftSibbling = 0;
        if ($leftSibling == null) {
            $offsetFromLeftSibbling = 0;
        } else {
            $offsetFromLeftSibbling = $leftSibling->nextCellIndent;
        }

        if (isset($this->maxCellX_charOffset[$x])) {

            $offset = $this->maxCellX_charOffset[$x] + $offsetFromLeftSibbling;

        } else {
            $offset = 0;
        }
        $this->maxCellX_charOffset[$x + 1] = $offset;

        $value->nextCellIndent = $offset;

    }

    function positionAsStartNode($node)
    {
        $similarNodeCellPosition = $this->getCellPositionHaving("id", $node->id);

        if ($similarNodeCellPosition != null) {
            //$this->putCellIn($parentPosition["x"] + 1, $parentPosition["y"], $serviceNodeToAdd);
            $this->insertCellUnder($similarNodeCellPosition["x"], $similarNodeCellPosition["y"], $node);
            $y = $similarNodeCellPosition["y"];
            $y += 1;
        } else {//first time encountering this id

            $y = $this->getFirstFreeY();
            $this->putCellIn(1, $y, $node, "device");
            $y = $y + 1;
        }

        return $y;
        // $this->updateMaxCellX_StrLen($node);
    }

    function suggestY($node)
    {
        $similarNodeCellPosition = $this->getCellPositionHaving("id", $node->id);

        if ($similarNodeCellPosition != null) {
            $y = $similarNodeCellPosition["y"];

        } else {//first time encountering this id

            $y = 0;//$this->getFirstFreeYForDevice();

        }

        return $y;
    }

    function positionNodeOnItsDedicatedColumn($node)
    {

        $deviceId = $node->id;
        $deviceX = $this->columnReservedFor[$deviceId];
        $deviceY = $this->suggestY($node);
        $this->putCellIn($deviceX, $deviceY, $node, "device");

        return $deviceY;
    }

    function positionAsConnectionNodeBetweenDedicatedColumns($node)
    {
        $leftSiblingDevice = $node->leftSiblingNode;
        $rightSiblingDevice = $node->rightSiblingNode;

        $leftDeviceX = $this->columnReservedFor[$leftSiblingDevice->id];
        $rightDeviceX = $this->columnReservedFor[$rightSiblingDevice->id];

        if ($rightDeviceX < $leftDeviceX) {
            //swap sibling as connection needs to revert direction
            $tmp = $rightSiblingDevice;
            $rightSiblingDevice = $leftSiblingDevice;
            $leftSiblingDevice = $tmp;

            //repeat end points in tabular view to mimic fan out
            $this->positionNodeOnItsDedicatedColumn($leftSiblingDevice);
            $this->positionNodeOnItsDedicatedColumn($leftSiblingDevice);

            $leftSiblingDevicePosition = $this->getCellPositionHaving("id", $leftSiblingDevice->id);
            $leftSiblingDeviceX = $leftSiblingDevicePosition['x'];
            $leftSiblingDeviceY = $leftSiblingDevicePosition['y'];

            $this->putCellIn($leftSiblingDeviceX + 1, $leftSiblingDeviceY + 1, $node, "connection");
        } else {


            $leftSiblingDevicePosition = $this->getCellPositionHaving("id", $leftSiblingDevice->id);
            $leftSiblingDeviceX = $leftSiblingDevicePosition['x'];
            $leftSiblingDeviceY = $leftSiblingDevicePosition['y'];

            $this->putCellIn($leftSiblingDeviceX + 1, $leftSiblingDeviceY, $node, "connection");
        }
    }

    function popSegmentHavingY($y, &$leftDeviceNode, &$connectionNode, &$rightDeviceNode)
    {
        //get first filled cell in the row

        $leftDeviceNode = null;
        for ($X = 0; $X < 10; $X++) {
            if ($this->displayCells[$y][$X] != null) {
                $leftDeviceNode = $this->displayCells[$y][$X];
                $this->displayCells[$y][$X] = null;;
                break;
            };
        }
        $connectionNode = $this->displayCells[$y][$X + 1];
        $this->displayCells[$y][$X + 1] = null;
        $rightDeviceNode = $this->displayCells[$y][$X + 2];
        $this->displayCells[$y][$X + 2] = null;
    }

    function positionAsEndNode($node)
    {

        $leftSibbling = $node->leftSiblingNode;
        $leftSiblingNodeCellPosition = $leftSibbling->position;

        if ($leftSiblingNodeCellPosition != null) {

            $this->putCellIn($leftSiblingNodeCellPosition["x"] + 1, $leftSiblingNodeCellPosition["y"], $node, "device");
        }
        // $this->updateMaxCellX_StrLen($node);
    }

    function positionAsConnectionNode($node)
    {

        $leftSibbling = $node->leftSiblingNode;
        $leftSiblingNodeCellPosition = $leftSibbling->position;

        if ($leftSiblingNodeCellPosition != null) {

            $this->putCellIn($leftSiblingNodeCellPosition["x"] + 1, $leftSiblingNodeCellPosition["y"], $node, "connection");
        }

        //$this->updateMaxCellX_StrLen($node);
    }

    /*
     * gets the cell positioning after display updates due to insert grows
     */
    function getCellPosition($needleValue)
    {
        $matchFound = false;
        //search all the cells to find this value:
        foreach ($this->displayCells as $y => $cellRow) {
            foreach ($cellRow as $x => $cellValue) {
                if ($cellValue == null) continue;
                if ($cellValue->id == $needleValue->id) {
                    return ["x" => $x, "y" => $y];
                }
            }
        }

        return null;

    }

    function checkNodeWithSameIdExistInMiddle($cellValue)
    {
        $pos = $this->getCellPosition($cellValue);
        $isGoodMatch = false;
        if ($pos != null) {
            if ($pos["x"] >= 1) {
                $isGoodMatch = true;
            } else {
                $isGoodMatch = false;
            }
        }
        return $isGoodMatch;
    }

    /*
     *  searches all cells and finds first object on them having its "attribute" of $attribute being set to $value
     */
    function getCellHaving($attribute, $value)
    {
        //search all the cells to find this value:
        foreach ($this->displayCells as $y => $cellRow) {
            foreach ($cellRow as $x => $cellValue) {
                if (isset($cellValue[$attribute])) {
                    if ($cellValue[$attribute] == $value) {
                        return $cellValue;
                    }
                }
            }
        }
    }

    function getCellAt($x, $y)
    {
        if (!isset($this->displayCells[$y][$x])) return null;
        return $this->displayCells[$y][$x];
    }

    /*
   *  searches all cells and finds first object on them having its "attribute" of $attribute being set to $value
   *   then it returns the object position
   */
    function getCellPositionHaving($attribute, $value)
    {
        //search all the cells to find this value:
        foreach ($this->displayCells as $y => $cellRow) {
            foreach ($cellRow as $x => $cellValue) {
                if (isset($cellValue->$attribute)) {
                    if ($cellValue->$attribute == $value) {
                        return ["x" => $x, "y" => $y];
                    }
                }
            }
        }
    }

    /*
     *  first we need a canvas with predicted size to draw cells on
     *  $maxX,$maxY are the max values x & y can get
     */
    function createBlankDisplayCells($maxX, $maxY)
    {
        $this->numberOfCellsInRow = $maxX;
        $toBeInserted = array_fill(0, $this->numberOfCellsInRow, null);
        for ($addedRowIndex = 0; $addedRowIndex <= $maxY; $addedRowIndex++) {
            $this->displayCells[] = $toBeInserted;
        }

    }

    function getFirstFreeY()
    {
        return $this->maxOccupiedCellY + 1;
    }

    function getFirstFreeYForDevice($deviceName)
    {
        if (isset($this->firstFreeYForDevice[$deviceName])) {
            $this->firstFreeYForDevice[$deviceName] += 1;
            return $this->firstFreeYForDevice[$deviceName];
        } else {
            $this->firstFreeYForDevice[$deviceName] = 0;
            return 0;
        }

    }

}
