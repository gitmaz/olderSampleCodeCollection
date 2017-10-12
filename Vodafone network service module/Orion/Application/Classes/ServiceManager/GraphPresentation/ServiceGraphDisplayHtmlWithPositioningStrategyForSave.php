<?php

/**
 * Created by PhpStorm.
 * User: maziar navabi
 * Date: 12/02/2016
 * Time: 9:45 AM
 */
namespace Orion\ServiceManager\GraphPresentation;

class ServiceGraphDisplayHtmlWithPositioningStrategyForSave
{
    private $html;

    function __construct()
    {
    }

    function getHtml()
    {
        return $this->html;
    }

    /* prints out graph
     *
     * @var array $displayCells : array[y][x]
     */
    public function display($displayCells)
    {
        $viewForDebug = true;

        $html = "<br><br>
             GRAPH PRINTOUT <br><br>";
        $html .= "Legend:<br>";
        $html .= "X : device X<br>";
        $html .= "--Y--  : connection Y<br>";
        $html .= "~~Z~~ : subservice Z<br>";

        $html .= "<br><br>";
        $html .= "<b>Discovered topology: </b><br>";
        $this->html = $html;
        $this->positionPrint($displayCells, true);
        echo $this->html;
    }


    function positionPrint($displayCells, $visible)
    {
        if (!$visible) return;
        $startPad = "";
        $endPad = "";

        $maxCellIndent = [];//[$x]

        $html = $this->html;
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
                    $shouldWrapInAnchor = false;

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
                        }
                        $cellIndent += 4;

                    } else {
                        $startPad = "";
                        $endPad = "";
                        $intf1FullName = $cellValue->tag2;
                        if ($intf1FullName == null) {
                            $intf1FullName = $cellValue->tag3;
                        }

                    }

                    $nodeId = $startPad . trim($nodeId) . $endPad;
                    if ($shouldWrapInAnchor) {//for connection type nodes of type subservice,create a link to expand to detailed nodes
                        //todo: form better name display stategy for subservice connections
                        //temp nice looking subservice  segment names for demo only
                        $descriptor = trim($nodeId, "~");

                        //depreciated in favor of hash autogeneration
                        //$nodeIdparts=explode("=",$nodeId);
                        //$nodeId=rtrim(strtolower($nodeIdparts[0]),"id").$nodeIdparts[1];
                        $nodeId = "~~$descriptor~~";
                        $nodeId = "<a href='./discover.php?descriptor=$descriptor&tab=2' target='_blank'>$nodeId</a>";
                        //$nodeId = "<a href='http://10.162.74.76/Orion/sandbox/network_services/UI/query/discover.php?descriptor=$descriptor&tab=2' target='_blank'>$nodeId</a>";
                        //$nodeId="<a href='http://localhost/Orion/sandbox/network_services/UI/query/discover.php?descriptor=$descriptor' target='_blank'>$nodeId</a>";
                    }

                    if (isset($maxCellIndent[$x + 1])) {
                        $maxCellIndent[$x + 1] = max($cellIndent, $maxCellIndent[$x + 1]);
                    } else {
                        $maxCellIndent[$x + 1] = $cellIndent;
                    }


                    //$nodeId="<p class='w3-tooltip'>$nodeId<span class='w3-text'>hi</span></p>";
                    $nodeId = "<span data-toggle=\"tooltip\" title='$intf1FullName'>$nodeId</span>";

                    if ($leftSibling == null) {

                        if (isset($maxCellIndent[$x])) {
                            $padCount = $maxCellIndent[$x];
                        } else {
                            $padCount = 0;
                        }
                        $paddedId = str_pad("", $padCount * 10, "&nbsp");
                        $paddedId = $paddedId . $nodeId;

                    } else {
                        $paddedId = $nodeId;
                    }
                } else {
                    $nodeId = "";
                    $cellIndent = 0;
                    $paddedId = $nodeId;
                };

                $html .= " $paddedId";
            }
            $html .= "<br>";
        }
        $this->html = $html;

    }

}