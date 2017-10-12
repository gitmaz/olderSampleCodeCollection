<?php

/**
 * Created by PhpStorm.
 * User: maziar navabi
 * Date: 12/02/2016
 * Time: 9:45 AM
 */
namespace Orion\ServiceManager\GraphPresentation;

class ServiceGraphDisplayPrintWithPositioningStrategy
{

    function __construct()
    {
    }

    /* prints out graph
     *
     * @var array $displayCells : array[y][x]
     */
    public function display($displayCells)
    {
        $viewForDebug = true;

        echo "\n\n\nDisplay method: GRAPH PRINTOUT \n\n";
        echo "Legend:\n";
        echo "X : device X\n";
        echo "--Y--  : connection Y\n";
        echo "~~Z~~ : service delegate Z\n";


        echo "end result:\n\n";
        $this->positionPrint($displayCells, true);
    }


    function positionPrint($displayCells, $visible)
    {
        if (!$visible) return;

        $maxCellIndent = [];//[$x]
        //when everything is ready on displayCells,now is time to draw them
        foreach ($displayCells as $y => $cellRow) {
            $cellIndent = 0;
            foreach ($cellRow as $x => $cellValue) {

                if ($cellValue != null) {
                    $nodeId = $cellValue->id;
                    $nodeCaption = $cellValue->tag;
                    if ($nodeCaption == "main") {
                        $nodeCaption = $nodeId;//if we are a connection,use our id as caption for now
                    }
                    //use caption as a positioning key as it is also unique
                    $nodeId = $nodeCaption;

                    $leftSibling = $cellValue->leftSiblingNode;
                    $cellIndent += strlen($nodeId);

                    if ($cellValue->type == "connection") {
                        if ($cellValue->tag == "main") {
                            $startPad = "--";
                            $endPad = "--";
                        } else { //if this is a subservice
                            $startPad = "~~";
                            $endPad = "~~";
                            $shouldWrapInAnchor = true;
                            $serviceName = $cellValue->tag2;
                            $serviceId = $cellValue->tag3;
                            $service_config_type = $cellValue->tag4;
                            $nodeId = $cellValue->id;//$serviceId;
                            $descriptor = trim($nodeId, "~");
                            $nodeIdparts = explode("=", $nodeId);
                            if (count($nodeIdparts) > 1) {
                                $nodeId = rtrim(strtolower($nodeIdparts[0]), "id") . $nodeIdparts[1];
                            }
                        }
                        $cellIndent += 4;

                    } else {
                        $startPad = "";
                        $endPad = "";
                    }


                    $nodeId = $startPad . trim($nodeId) . $endPad;

                } else {
                    $nodeId = "";

                };

                $padCount = 15;

                $paddedId = str_pad($nodeId, $padCount, " ");

                echo " $paddedId";
            }
            echo "\n";
        }
        $m = 0;
    }

}