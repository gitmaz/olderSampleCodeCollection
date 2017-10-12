<?php

/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 5/04/2016
 * Time: 1:22 PM
 */

namespace Orion\ServiceManager\GraphPresentation;

class MultiViewDisplayJqHtmlTabsStrategy
{


    public $html;

    function display($page1Html, $page1Title, $page2Html, $page2Title, $page3Html, $page3Title, $page4Html, $page4Title, $activeTab, $shouldEcho = true)
    {
        $activeTabId = "query_result_tabs-" . $activeTab;

        $html = "
    <div id='query_result_tabs'>
      <ul>
        <li><a href='#query_result_tabs-1' id='a_query_result_tabs-1'>$page1Title</a></li>
        <li><a href='#query_result_tabs-2' id='a_query_result_tabs-2'>$page2Title</a></li>
        <li><a href='#query_result_tabs-3' id='a_query_result_tabs-3'>$page3Title</a></li>
        <li><a href='#query_result_tabs-4' id='a_query_result_tabs-4'>$page4Title</a></li>
      </ul>
      <div id='query_result_tabs-1' >
        $page1Html
      </div>
      <div id='query_result_tabs-2' >
       $page2Html
      </div>
      <div id='query_result_tabs-3' >
       $page3Html
      </div>
      <div id='query_result_tabs-4' >
       $page4Html
      </div>
    </div>
    <script>
      $(function() {
        $( '#query_result_tabs' ).tabs();//{active:'$activeTabId'});
        $( '#a_$activeTabId').click();

        /*var applyBtnExists=($(\"#btnApply\").length>0);

        if(!applyBtnExists){*/

         fillAndShowMxGraphDiv();
        //}
      });
    </script>



    ";

        $this->html = $html;

        if ($shouldEcho) {
            echo $html;
        } else {
            return $html;
        }
    }
}