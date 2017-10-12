<?php

/**
 * Created by PhpStorm.
 * User: maziar navabi
 * Date: 12/02/2016
 * Time: 9:45 AM
 */

namespace Orion\ServiceManager\GraphPresentation;

class ServiceGraphDisplayHtmlWithPositioningStrategy
{
    private $html;
    private $maxDisplayHeight;

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
    public function display($displayCells, $maxDisplayHeight, $shouldEcho = true)
    {
        $viewForDebug = true;

        $this->maxDisplayHeight = $maxDisplayHeight;


        $html = "<div id='div_textual_graph' >\n";//"<br><br>   GRAPH PRINTOUT <br><br>";
        $html .= "Legend:<br>";
        $html .= "X : device X<br>";
        $html .= "--Y--  : connection Y<br>";
        $html .= "~~Z~~ : subservice Z<br>";

        $html .= "<br><br>";
        $html .= "<b>Discovered topology: </b><br>";
        $this->html = $html;
        $this->positionPrint($displayCells, true);
        $this->html .= "</div>";
        if ($shouldEcho) {
            echo $this->html;
        } else {
            return $this->html;
        }
    }

    function rowNotEmpty(&$displayCells, $y)
    {
        $rowNotEmpty = false;
        $row = $displayCells[$y];
        foreach ($row as $cel) {
            if ($cel != null) {
                $rowNotEmpty = true;
                break;
            }
        }

        return $rowNotEmpty;
    }

    function positionPrint($displayCells, $visible)
    {
        if (!$visible) return;
        $startPad = "";
        $endPad = "";

        $maxCellIndent = [];//[$x]

        $html = $this->html . "<br>\n";
        //when everything is ready on displayCells,now is time to draw them
        foreach ($displayCells as $y => $cellRow) {
            $cellIndent = 0;
            $nonNullHit = false;
            if ($this->rowNotEmpty($displayCells, $y)) {
                foreach ($cellRow as $x => $cellValue) {

                    if ($x == 0) {
                        continue;//first column is always kept empty- no need to show it
                    }

                    if ($cellValue != null) {
                        $nodeId = $cellValue->id;
                        $nodeCaption = $cellValue->tag;
                        if ($nodeCaption == "main") {
                            $nodeCaption = $nodeId;//if we are a connection,use our id as caption for now
                        }


                        if ($nodeCaption != null) {
                            //use caption as a positioning key as it is also unique
                            $nodeId = $nodeCaption;
                        }

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

                                $descriptor = trim($nodeId, "~");
                                $nodeIdparts = explode("=", $nodeId);
                                if (isset($nodeIdparts[1])) {
                                    $nodeId = rtrim(strtolower($nodeIdparts[0]), "id") . $nodeIdparts[1];
                                } else {
                                    $nodeId = rtrim(strtolower($nodeIdparts[0]), "id");
                                }
                            }

                            $nodeId = $startPad . $nodeId . $endPad;

                        } else {
                            $startPad = "";
                            $endPad = "";
                            $intf1FullName = $cellValue->tag2;
                            if ($intf1FullName == null) {
                                $intf1FullName = $cellValue->tag3;
                            }
                            $tooltip = $intf1FullName . "\n" . $cellValue->site;
                        }

                        $nodeId = trim($nodeId);

                        if ($shouldWrapInAnchor) {//for connection type nodes of type subservice,create a link to expand to detailed nodes
                            $nodeId = "<a href='../../Orion/Application/launch.php?h=DashboardNew&descriptor=$descriptor&tab=2' target='_blank'>$nodeId</a>";
                        }

                        $nodeIdDecorated = "<span data-toggle=\"tooltip\" title='$tooltip'>$nodeId</span>";

                        $nonNullHit = true;
                    } else {
                        if ($nonNullHit) {
                            continue;//we don't need to show spans after showing a segment per row
                        }
                        $nodeId = "&nbsp;";
                        $nodeIdDecorated = $nodeId;
                    };

                    $paddedId = "<span style='float: left;min-width:130px;'>$nodeIdDecorated</span>";// str_pad($nodeId, 75, "&nbsp");

                    $html .= $paddedId;
                }
            }
            if ($y > $this->maxDisplayHeight) {
                break;
            }
            $html .= "<br>\n";
        }
        $this->html = "<div style='font-size: small'>$html</div>";

    }

}