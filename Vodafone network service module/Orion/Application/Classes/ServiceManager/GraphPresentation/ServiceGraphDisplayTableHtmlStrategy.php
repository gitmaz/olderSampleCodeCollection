<?php

/**
 * Created by PhpStorm.
 * User: maziar navabi
 * Date: 12/02/2016
 * Time: 9:45 AM
 */
namespace Orion\ServiceManager\GraphPresentation;

class ServiceGraphDisplayTableHtmlStrategy
{

    private $route;

    function __construct()
    {

    }

    /* prints out $route as an array of subsequent routeNodes in a route
     *  @VAR array $serviceSegments example: [["SITE1"=>"mosman","SITE2"=>"mosman","EQUIPMENT_NAME1"=>"B","EQUIPMENT_NAME2"=>"C","EQUIPMENT_ID1"=>"B","EQUIPMENT_ID2"=>"C","CONNECTION_ID"=>"con2","INTERFACE_OBJECT_ID1"=>"b2","INTERFACE_OBJECT_ID2"=>"c1"],...]
     */
    public function display($serviceSegments, $shouldEcho = true)
    {

        $html = "";//"<div><b>SEGMENTS TABULAR PRINTOUT </b></div><br>\n";

        $html .= "<div style='width:680px'>
               <table id='tabular_segments' class='display dataTable thick-border-table'>";

        // <tr class='highlighted_row'><th>SERVICE</th><th>SITE1</th><!--th>CONF_ID1</th--><th>EQUIPMENT_NAME1</th><!--th>EQUIPMENT_ID1</th><th>CARD_ID1</th><th>INTERFACE_OBJECT_ID1</th><th>CONNECTION_ID</th><th>INTERFACE_OBJECT_ID2</th><th>CARD_ID2</th><th>CARD_ID2</th!--><th>EQUIPMENT_NAME2</th><th>SITE2</th></tr>

        $html .= "   <thead ><tr class='highlighted_row'><th>SERVICE<span>+</span><br></th><th>SITE1<span>+</span><br></th><th>EQUIPMENT_NAME1<span>+</span><br></th><th>EQUIPMENT_NAME2<span>+</span><br></th><th>SITE_NAME2<span>+</span><br></th></tr></thead>\n";
        $html .= "  <tfoot>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>

                    </tr>
                 </tfoot>
                 ";

        $nodeCount = 0;

        $this->html = "";
        foreach ($serviceSegments as $serviceSegment) {
            $serviceSegmentStr = "<tr><td>{$serviceSegment["SERVICE_NAME"]}</td>
                                 <td>{$serviceSegment["SITE1"]}</td>
                                 <!--td>{$serviceSegment["LOGI_CONF1_ID"]}</td-->
                                 <td>{$serviceSegment["EQUIPMENT_NAME1"]}</td>
                                 <!--td>{$serviceSegment["EQUIPMENT_ID1"]}</td>
                                 <td>{$serviceSegment["CARD_ID1"]}</td>
                                 <td>{$serviceSegment["INTERFACE_OBJECT_ID1"]}</td>
                                 <td>{$serviceSegment["CONNECTION_ID"]}</td>
                                 <td>{$serviceSegment["INTERFACE_OBJECT_ID2"]}</td
                                 <td>{$serviceSegment["CARD_ID2"]}</td>
                                 <td>{$serviceSegment["LOGI_CONF2_ID"]}</td>
                                 <td>{$serviceSegment["EQUIPMENT_ID2"]}</td-->
                                 <td>{$serviceSegment["EQUIPMENT_NAME2"]}</td>
                                 <td>{$serviceSegment["SITE2"]}</td></tr>";
            $html .= $serviceSegmentStr;
        }

        $html .= "</table><br></div>\n";

        //.header.empty()
        $html .= " <script>
                   table=$('#tabular_segments').DataTable( {
                                            initComplete: function () {
                                                this.api().columns().every( function () {
                                                    var column = this;
                                                    var select = $('<select style=\"color:black\"><option value=\"\"></option></select>')
                                                        .appendTo( $(column.header()) )
                                                        .on( 'change', function () {
                                                            var val = $.fn.dataTable.util.escapeRegex(
                                                                $(this).val()
                                                            );

                                                            column
                                                                .search( val ? '^'+val+'$' : '', true, false )
                                                                .draw();
                                                        } );

                                                    column.data().unique().sort().each( function ( d, j ) {
                                                        select.append( '<option value=\"'+d+'\">'+d+'</option>' )
                                                    } );
                                                } );
                                            }
                                          } );


                     $('#tabular_segments tfoot th').each( function () {
                            var title = $(this).text();
                            $(this).html('<span>-</span><input type=\"text\" placeholder=\" \" />' );
                        } );

                   // Apply the search
                    table.columns().every( function () {
                        var that = this;

                        $( 'input', this.footer() ).on( 'keyup change', function () {
                            if ( that.search() !== this.value ) {
                                that
                                    .search( this.value )
                                    .draw();
                            }
                        } );
                    } );

                    $('#tabular_segments thead th select').toggle();
                    $('#tabular_segments tfoot th input').toggle();
                    $('#tabular_segments thead th span').click(function(){
                          $('#tabular_segments thead th select').toggle();
                    });
                     $('#tabular_segments tfoot th span').click(function(){
                          $('#tabular_segments tfoot th input').toggle();
                          var prevVal= $('#tabular_segments tfoot th span').html();
                          if(prevVal=='+'){
                           $('#tabular_segments tfoot th span').html('-');
                          }
                          else{
                            $('#tabular_segments tfoot th span').html('+');
                          }
                    });

                </script>
                ";


        $this->html = $html;
        if ($shouldEcho) {
            echo $this->html;
        } else {
            return $this->html;
        }
    }
}