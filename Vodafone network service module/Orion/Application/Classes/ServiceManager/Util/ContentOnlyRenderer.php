<?php

/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 12/07/2016
 * Time: 9:23 AM
 *
 * This class is useful for integrating any html reply (it could be  ajax) reply in a simple div by decorating with correct js library
 */
class ContentOnlyRenderer
{

    private $contentHtml;

    function __construct($url, $postKeyValArray)
    {

        foreach ($postKeyValArray as $postKey => $postVal) {

        }
        $this->contentHtml = file_get_contents($url);
    }


    private function renderHeader()
    {

        echo "<!DOCTYPE html>
                <html lang=\"en\" ng-app=\"app\">
                <head>
                    <meta charset=\"utf-8\">
                    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
                    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
                    <title>Query Service</title>

                    <link href=\"../../../../Static/Css/Handler/ServiceManager/bootstrap.min.css\" rel=\"stylesheet\">
                    <link href=\"../../../../Static/Css/Handler/ServiceManager/jquery.dataTables.min.css\" rel=\"stylesheet\">
                    <link href=\"../../../../Static/Css/Handler/ServiceManager/jquery-ui.css\" rel=\"stylesheet\">
                    <link href=\"../../../../Static/Css/Handler/ServiceManager/styles.css\" rel=\"stylesheet\">


                    <script src=\"../../../../Static/Js/JQuery/jquery-1.12.0.min.js\"></script>
                    <script src=\"../../../../Static/Js/JQuery/jquery.dataTables.min.js\"></script>
                    <script src=\"../../../../Static/Js/JQuery/jquery-ui.js\"></script>

                    <script type=\"text/javascript\">
                         //mxImageBasePath = '../../../../Static/Images/MxGraph';
                         mxImageBasePath = '../../../../../vendor/mxgraph/javascript/src/images';
                    </script>
                    <script src=\"../../../../Static/Js/Angular/mxClient.js\"></script>
                    <script src=\"../../../../Static/Js/Handler/ServiceManager/service-mxgraph-enhanced.js\"></script>
                    <script src=\"../../../../Static/Js/Handler/ServiceManager/service-query-post.js\"></script>

                </head>
                <body>
                  <div>

             ";
    }

    private function renderFooter()
    {
        echo "
                 </div>
                 <script src=\"../../../../Static/Js/Angular/angular.min.js\"></script>
                 <script src=\"../../../../Static/Js/Angular/angular-sanitize.min.js\"></script>
                 <script src=\"../../../../Static/Js/Handler/ServiceManager/service-query.js\"></script>
                 <script src=\"../../../../Static/Js/JQuery/bootstrap.js\"></script>
                </body>
             </html>
             ";

    }

    public function render()
    {
        $this->renderHeader();

        echo $this->contentHtml;

        $this->renderFooter();
    }
}

//$contentOnlyRenderer=new ContentOnlyRenderer("http://localhost/Orion/Application/Classes/ServiceManager/Util/SampleContentForContentOnlyRenderer.php",[]);
$contentOnlyRenderer = new ContentOnlyRenderer("http://localhost/Orion/Application/launchd.php", []);
$contentOnlyRenderer->render();