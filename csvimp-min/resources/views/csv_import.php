<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Angular Csv Import by Maziar Navabi</title>

    <link rel="stylesheet" href="../../css/bootstrap.css">
    <link rel="stylesheet" href="../../css/style.css">


</head>

<body>
<html ng-app="app">
<body>
<div class="container">
    <h4>CSV Import <small>by Maziar Navabi</small></h4>
    <!--input type="hidden" name="_token" value="{{ csrf_token() }}"-->

    <?php
    //echo "<br>".csrf_token()."<br>";
    ?>

    <ul class="nav nav-tabs" ng-init="selectedTab='introduction'">
        <li ng-class="{active:(selectedTab=='introduction') }"><a href="" ng-click="selectedTab='introduction'"><b>Introduction</b></a>
        </li>
        <li ng-class="{active:(selectedTab=='uploadCsv')}"><a href="" ng-click="selectedTab='uploadCsv'"><b>Upload
                    csv</b></a></li>
        <li ng-class="{active:(selectedTab=='viewCsv')}"><a href="" id='aViewCsv' ng-click="selectedTab='viewCsv'"><b>View
                    csv</b></a></li>
        <li ng-class="{active:(selectedTab=='defineMappings')}"><a id='aDefineMappings' href=""
                                                                   ng-click="selectedTab='defineMappings'"><b>Define
                    Column Mappings & import</b></a></li>
        <li ng-class="{active:(selectedTab=='showImported')}"><a id='aShowImported' href=""
                                                                 ng-click="selectedTab='showImported'"><b>View Imported
                    Result</b></a></li>

    </ul>
    <div ng-show="selectedTab=='introduction'" class="row">
        <div class="col-md-12" ng-controller="CsvImportController" ng-init="onEnterIntroduction()">
            <br>
            <p>This is demo usage for CsvImporter class, it is a dynamically configurable csv to data table importer. </p>
            <p>You can configure which column from csv should be transferred to which columns of a set of configurable
                Eloquent models </p>
            <p>It can also map a master detail information flattened in a csv file to two master detail data tables. </p>
            <br>
            <p>
                Here is <a href="./../../../upload/samples/countriesAndCities.txt" download="countriesAndCities.txt" ><b>sample</b></a> csv file you can upload and import to default mapping (using orange import button).
            </p>


            <p>
                You can <a href="./../../../READ-ME-FIRST.txt" ><b>review READ-ME-FIRST.txt </b></a> file for more details on howto install and use this web app.
            </p>

            <button type="button" class="btn btn-primary pull-left" ng-click="selectedTab='uploadCsv'">Next</button>
        </div>
    </div>
    <div ng-show="selectedTab=='uploadCsv'" ng-controller="FileUploadController">
        <!-- based on: http://www.matlus.com/html5-file-upload-with-progress/ -->
        <div class="row">
            <div class="col-md-12">
                <br>
                <label for="fileToUpload">Select csv File to Upload or drag and drop it below.</label><br/>
                <input type="file" ng-model-instant id="fileToUpload" multiple
                       onchange="angular.element(this).scope().setFiles(this)"/>
                <br>
            </div>
        </div>
        <div id="dropbox" class="dropbox" ng-class="dropClass"><span>{{dropText}}</span></div>
        <div ng-show="files.length">
            <div ng-repeat="file in files.slice(0)">
                <span>{{file.webkitRelativePath || file.name}}</span>
                (<span ng-switch="file.size > 1024*1024">
                        <span ng-switch-when="true">{{file.size / 1024 / 1024 | number:2}} MB</span>
                        <span ng-switch-default>{{file.size / 1024 | number:2}} kB</span>
                    </span>)
            </div>
            <input type="button" ng-click="uploadFile()" value="Upload"/>
            <div ng-show="progressVisible">
                <div class="percent">{{progress}}%</div>
                <div class="progress-bar">
                    <div class="uploaded" ng-style="{'width': progress+'%'}"></div>
                </div>
            </div>
        </div>
    </div>
    <div ng-show="(selectedTab=='viewCsv')">
        <br>
        <span>uploaded csv file content:</span>
        <br>
        <textarea rows="12" cols="100" ng-model="uploadedfileContent">
        </textarea>
        <br>
        <button type="button" class="btn btn-primary pull-left" ng-click="selectedTab='defineMappings'">Next</button>
    </div>
    <div ng-show="(selectedTab=='defineMappings')">
        <div ng-controller="CsvImportController" ng-init="getMappingNamesOptions(null)">
            <div class="row">
                <br>
                <label class="pull-left">Selected mapping</label>
                <div class="col-md-10">
                    <select style="width:80%" ng-model="selectedMapping"
                            ng-options="opt as opt.label for opt in options"
                            ng-change="selectMapping(selectedMapping.value)" ng-hide="isNewMapping">
                    </select>
                    <input style="width:80%" type="text" placeholder="enter new mapping name here" value=""
                           ng-model="newMappingName" ng-show="isNewMapping" ng-keyup="shouldShowSaveMappingButton=true">
                </div>
                <br>
            </div>
            <div class="row">
                <span class="pull-left">Mapping array:</span>
                <br>

                <div class="col-md-10">
            <textarea rows="14" cols="100" ng-model="mappingArrayStr" ng-keyup="shouldShowSaveMappingButton=true">
            </textarea>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary pull-right" ng-click="addNewMapping()"
                            style="margin-right:10px" ng-hide="isNewMapping">Add New Mapping
                    </button>
                    <br>
                    <br>
                    <button type="button" class="btn btn-primary pull-right" ng-click="duplicateMapping()"
                            style="margin-right:10px" ng-hide="isNewMapping">Duplicate Mapping
                    </button>
                    <br>
                    <br>
                    <button type="button" class="btn btn-primary pull-right" ng-click="deleteMapping()"
                            style="margin-right:10px" ng-hide="isNewMapping">Delete Mapping
                    </button>

                    <br>
                    <br>
                    <div id="btnImport" ng-click="import();" style="display:none">ctrl</div>
                    <button type="button" class="btn btn-primary pull-left" ng-show="shouldShowSaveMappingButton"
                            ng-click="saveMapping()" style="margin-right:10px">Save
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-warning pull-left"
                    onclick="document.getElementById('btnImport').click();" ng-click="selectedTab='showImported'">Import
            </button>
        </div>
    </div>
    <div ng-show="selectedTab=='showImported'" ng-if="selectedTab=='showImported'">
        <div ng-controller="CsvImportController" ng-init="onShowImportedVisible()">
            <div class="row">

                <h4>Table 1 contents</h4>
                <div class="col-md-4">
                    <select style="width:80%" class="pull-right" ng-model="selectedTable1"
                            ng-options="opt as opt.label for opt in table1Options"
                            ng-change="selectTable1(selectedTable1.value)">
                    </select>
                </div>
            </div>
            <div class="row">
                <br>
                <div>
                    <table class="fit ml20 nav nav-list nav-pills" compile="table1Html">
                        {{x}}
                    </table>

                </div>

            </div>
            <div class="row">

                <h4>Table 2 contents</h4>
                <div class="col-md-4">
                    <select style="width:80%" class="pull-right" ng-model="selectedTable2"
                            ng-options="opt as opt.label for opt in table2Options"
                            ng-change="selectTable2(selectedTable2.value)">
                    </select>
                </div>
            </div>
            <div class="row">
                <br>
                <div>
                    <table class="fit ml20 nav nav-list nav-pills" compile="table2Html">
                        {{x}}
                    </table>

                </div>

            </div>
        </div>
    </div>
</div>
</body>
</html>
<script src='../../js/angular-1.2.5.js'></script>

<script src="../../js/jquery.min.js"></script>
<script src="../../js/bootstrap.min.js"></script>
<script src="../../js/controllers/FileUploadController.js"></script>
<script src="../../js/controllers/CsvImportController.js"></script>
<script src="../../js/services/ImportDataService.js"></script>
<script src="../../js/directives/compile.js"></script>

</body>
</html>
