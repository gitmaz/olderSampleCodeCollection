<?php

/**
 * Created by PhpStorm.
 * User: maziar navabi
 * Date: 12/02/2016
 * Time: 9:45 AM
 */

namespace Orion\ServiceManager\GraphPresentation;

class ServiceGraphDisplayMXGraphStrategy
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
    public function display($displayCells, $shouldEcho = true)
    {
        $viewForDebug = true;
        $colorCodingHtml = $this->getColorLegend();
        $this->html .= $colorCodingHtml;
        $this->prepareJsonForMxGraph($displayCells, true);
        $this->prepareJsonForMxGraphKeepingXY_InfoIntact($displayCells, true);
        if ($shouldEcho) {
            echo $this->html;
        } else {
            return $this->html;
        }
    }

    function prepareJsonForMxGraph($displayCells, $visible)
    {
        $displayCells_ary = $displayCells;
        if (count($displayCells_ary > 0)) {
            $conn_array = array();
            $cells_array = array();
            $k = 0;
            $deviconnection_ary = array_values(array_filter($displayCells_ary));
            foreach ($deviconnection_ary as $displayCells_conn) {
                $disp_con = array_filter($displayCells_conn);
                if (!empty ($disp_con)) {
                    foreach (array_values($disp_con) as $key1 => $displayCells) {
                        if (!empty($displayCells)) {
                            if ($displayCells->type == 'connection' && ($displayCells->tag == 'main' || $displayCells->tag == 'subservice')) {
                                $cells_array[$k][$key1] = $displayCells->id;
                            } else {
                                $tag = explode("/", $displayCells->tag);

                                if (isset($tag[1])) {
                                    $equipment = $tag[0] . '/' . $tag[1];
                                } else {
                                    $equipment = $tag[0];
                                }

                                if (isset($tag[2])) {
                                    if (isset($tag[3])) {
                                        $card = $tag[2] . '/' . $tag[3];
                                    } else {
                                        $card = $tag[2];
                                    }
                                } else {
                                    $card = 'null';
                                }

                                if (!empty($displayCells->tag2) && $displayCells->tag2 != '') {
                                    $interface = str_replace($displayCells->tag . '/', '', $displayCells->tag2);
                                } else {
                                    $interface = 'null';
                                }

                                $cells_array[$k][$key1] = $equipment . '=||=' . $card . '=||=' . $interface;
                            }
                        }
                    }
                    $k++;
                }
                //$conn_array[$key] = implode('||=||',$cells_array);
            }
        }

        $eachary_count = [];
        if (count($cells_array) > 0) {
            $i = 0;
            $tmp_prev = [];
            $tmp_last = '';
            $tmp_last_ary = '';
            foreach ($cells_array as $each) {
                if ($tmp_last == '') {
                    $tmp_last_ary = explode('=||=', $each[count($each) - 1]);
                    $tmp_last = $tmp_last_ary[0];
                    $tmp_prev[$i] = $each;
                } else if ($tmp_last != '') {
                    $a = explode('=||=', $each[0]);
                    if ($tmp_last == $a[0]) {
                        array_pop($tmp_prev[$i]);
                        $tmp_prev[$i] = array_merge($tmp_prev[$i], $each);
                        $tmp_last_ary = explode('=||=', $each[count($each) - 1]);
                        $tmp_last = $tmp_last_ary[0];
                    } else {
                        $i++;
                        $tmp_prev[$i] = $each;
                        $tmp_last_ary = explode('=||=', $each[count($each) - 1]);
                        $tmp_last = $tmp_last_ary[0];
                    }
                }
            }


            foreach ($tmp_prev as $key => $final_ary) {
                $eachary_count[$key] = count($final_ary);
                $conn_array[$key] = implode('||=||', $final_ary);
            }
        }

        if (count($eachary_count) > 0) {
            $mxGraphDataJson = max($eachary_count) . '+=+';
            $mxGraphDataJson .= json_encode(array_unique(array_filter($conn_array)));
            //$mxGraphDataJson = '5+=+["2265_ALCP2E\/01=||=S1\/MotherCard=||=IF-2||=||157843||=||2265_AS15SB2-H\/02=||=S1\/AS15SB2-H=||=null||=||mpls1||=||1023_MLTN6\/04=||=S9\/NPU3=||=null","4762_SFP-O\/00=||=S1\/GESFP-O=||=SFP||=||157767||=||4762_ATN950B\/01=||=S1\/EM8F=||=FE\/GE-5||=||157779||=||4762_SFP-O\/00=||=S1\/GESFP-O=||=SFP"]';

            $html = "<script type='application/json' id='script_mxgraph_data'>
                  $mxGraphDataJson
              </script>
              <div id='div_mxgraph'></div>
               ";
        } else {
            $html = "<div id='div_mxgraph'>No Result</div>";
        }
        $this->html .= $html;
    }

    function getColorLegend()
    {
        $html = "<div style='position: relative;width:1400px;height:30px'>
               <div id='divColorLegend' >
				<table class=\"DummyEqTblSec\" style=\"width:100%;border-collapse: collapse\">
				 <tbody>
				  <tr>
				   <th style=\"height:5px\">Colors</th>
				   <td style=\"background: lightgrey; white-space: nowrap;\">Cable As Is</td>
				   <td style=\"background: darkgrey; white-space: nowrap;\">Service As Is</td>
				   <td style=\"background: lightgreen\">Propose</td>
				   <td style=\"background: yellow\">Decommission</td>
				   <td style=\"background: lightblue\">Modification</td>
				   <td style=\"background: orange\">Suggestion</td>
				   <td style=\"background: #FFFFFF; white-space: nowrap;\">Existing Unknown</td>
				  </tr>
				 </tbody>
				</table>
			 </div>
			</div>";
        return $html;
    }

    function prepareJsonForMxGraphKeepingXY_InfoIntact($displayCells, $visible)
    {

        $anyValue = false;
        foreach ($displayCells as $rowInd => $row) {
            foreach ($row as $cellInd => $cell) {
                if ($cell != null) {
                    $anyValue = true;
                    //avoid recursion in Json
                    $cell->leftSiblingNode = null;
                    $cell->rightSiblingNode = null;

                    $displayCells[$rowInd][$cellInd] = $cell;
                }
            }
        }
        if ($anyValue) {

            $mxGraphDataJson = json_encode($displayCells);
            $html = "<script type='application/json' id='script_mxgraph_data_xy_intact'>
                  $mxGraphDataJson
              </script>";
        } else {
            $html = "<script type='application/json' id='script_mxgraph_data_xy_intact'>
                  no result
                  </script>";
        }
        $this->html .= $html;

    }
}