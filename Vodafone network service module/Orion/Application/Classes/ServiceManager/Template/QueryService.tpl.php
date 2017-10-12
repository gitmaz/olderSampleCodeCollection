<?php

//check if we are a post back
$isPostBack = false;
if (isset($_GET['descriptor'])) {
    $descriptor = $_GET['descriptor'];

    $isPostBack = true;
} else {
    $descriptor = "";
}

if (isset($_GET['tab'])) {
    $activeTabId = $_GET['tab'];
} else {
    $activeTabId = null;
}
$serviceTypeToStartWith = "DWDM";

//fill search drop down with service search parameters
/* @var $netDataProvider NetworkSetupFromInventory */
$netDataProvider = $this->getVariable("netDataProvider");
$strParameterListForJs = $netDataProvider->getAllServiceKeyParametersForJs();

?>

<!DOCTYPE html>
<html lang="en" ng-app="app">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Query Service</title>

    <link href="../Static/Css/Handler/ServiceManager/bootstrap.min.css" rel="stylesheet">
    <link href="../Static/Css/Handler/ServiceManager/jquery.dataTables.min.css" rel="stylesheet">
    <link href="../Static/Css/Handler/ServiceManager/jquery-ui.css" rel="stylesheet">
    <link href="../Static/Css/Handler/ServiceManager/styles.css" rel="stylesheet">

    <script src="../Static/Js/JQuery/jquery-1.12.0.min.js"></script>
    <script src="../Static/Js/JQuery/jquery.dataTables.min.js"></script>
    <script src="../Static/Js/JQuery/jquery-ui.js"></script>
    <script type="text/javascript">
        mxImageBasePath = '../../vendor/mxgraph/javascript/src/images';
    </script>
    <script src="../Static/Js/Angular/mxClient.js"></script>
    <!--script src="../Static/Js/Handler/ServiceManager/service-mxgraph.js"></script-->
    <script src="../Static/Js/Handler/ServiceManager/service-mxgraph-enhanced.js"></script>
    <script src="../Static/Js/Handler/ServiceManager/service-query-post.js"></script>

</head>
<body>
<?php
if ($isPostBack) {
    echo "<script>
                 $(function(){
                                setTimeout(function(){ applyQueryWithKnownQueryStr(\"($descriptor)\"); }, 10);
                            });
                </script>
               ";
}

if ($activeTabId != null) {
    echo "<script>
                     activeTab=$activeTabId;
                </script>
                   ";
} else {
    echo "<script>
                     activeTab=1;
                </script>
                   ";
}

echo "<script>
             serviceKeyParametersList=$strParameterListForJs;
           </script>
           ";


?>

<div class="container" ng-controller="QueryBuilderCtrl">
    <div class="query-services-title">Query Services</div>
    <div id="div_query_builder">
        <query-builder group="filter.group"></query-builder>
    </div>
    <div id="div_query_str" class="alert alert-info "><!--class="alert alert-info "-->
        <b style="color:#4A79A0;font-size: 12px">Query:</b> <span style="float:right;color:#4A79A0;font-size:12px"> example: (vlan_x_id = 112233 AND ...) </span><br>
        <span ng-bind-html="output" id="spn_query_str" style="color:#4A79A0;font-size: 12px"></span>
        <br>
        <br>
        <button id="btn_apply" style="margin-right: 5px" class="btn btn-sm btn-success" onclick="applyQuery();">Apply
        </button>

    </div>
</div>


<div id="div_network_graph" class="alert alert-success large-screen" role="alert">
    <h5> please select a criteria from above to sketch its network graph. </h5>
</div>

<script type="text/ng-template" id="/queryBuilderDirective.html">
    <div class="alert alert-warning alert-group" id="div_query_builder_inner">
        <div class="form-inline">
            <select ng-options="o.name as o.name for o in operators" ng-model="group.operator"
                    class="form-control input-sm"></select>
            <button id="btn_add_condition" style="margin-left: 5px"
                    ng-click="addCondition(<?= $serviceTypeToStartWith ?>)" class="btn btn-sm btn-success">
                <!--span class="glyphicon glyphicon-plus-sign"></span--> Add Condition
            </button>
            <button style="margin-left: 5px" ng-click="addGroup()" class="btn btn-sm btn-success">
                <!--span class="glyphicon glyphicon-plus-sign"></span--> Add Group
            </button>
            <button style="margin-left: 5px" ng-click="removeGroup()" class="btn btn-sm btn-danger">
                <!--span class="glyphicon glyphicon-minus-sign"></span--> Remove Group
            </button>
        </div>
        <div class="group-conditions">
            <div ng-repeat="rule in group.rules | orderBy:'index'" class="condition">
                <div ng-switch="rule.hasOwnProperty('group')">
                    <div ng-switch-when="true">
                        <query-builder group="rule.group"></query-builder>
                    </div>
                    <div ng-switch-default="ng-switch-default">
                        <div class="form-inline">
                            <select ng-options="t.name as t.name for t in fields" ng-model="rule.field"
                                    class="form-control input-sm"></select>
                            <select style="margin-left: 5px" ng-options="c.name as c.name for c in conditions"
                                    ng-model="rule.condition" class="form-control input-sm"></select>
                            <input style="margin-left: 5px" type="text" ng-model="rule.data"
                                   class="form-control input-sm"/>
                            <button style="margin-left: 5px" ng-click="removeCondition($index)"
                                    class="btn btn-sm btn-danger"><span class="glyphicon glyphicon-minus-sign"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script src="../Static/Js/Angular/angular.min.js"></script>
<script src="../Static/Js/Angular/angular-sanitize.min.js"></script>
<script src="../Static/Js/Handler/ServiceManager/service-query.js"></script>
<script src="../Static/Js/JQuery/bootstrap.js"></script>
</body>
</html>


