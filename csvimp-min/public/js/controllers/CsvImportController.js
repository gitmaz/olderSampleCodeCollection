/**
 * This controller does the responsibility of importing csv from front end point of view
 *
 */
(function () {
    'use strict';

    angular
        .module('app')
        .controller('CsvImportController', function ($http, $scope, $rootScope, ImportDataService) {

            $scope.onEnterIntroduction = function () {
                $rootScope.table1Options = [];
                $rootScope.table2Options = [];
            };


            $scope.import = function () {
                alert("hi");
                ImportDataService.importCsv($scope.mappingArrayStr, $scope.onImportComplete);
            };

            $scope.onImportComplete = function (result) {

                alert(result);
            };


            $scope.getMappingNamesOptions = function (nameToSelelect) {

                $scope.nameToSelect = nameToSelelect;
                ImportDataService.getExistingMappingNames($scope.mappingNamesFetched);

            };

            $scope.mappingNamesFetched = function (mappingNames) {

                $scope.options = mappingNames;
                if ($scope.nameToSelect == null) {
                    $scope.selectedMapping = $scope.options[0];
                    $scope.selectedMappingInd = 0;
                } else {
                    for (var ind in $scope.options) {
                        if ($scope.options[ind].label == $scope.nameToSelect) {
                            $scope.selectedMapping = $scope.options[ind];
                            $scope.selectedMappingInd = ind;
                            break;
                        }
                    }
                }
                $scope.selectMapping($scope.selectedMapping.value);
            };

            $scope.selectMapping = function (selectedMappingId) {
                ImportDataService.getMappingArrayStr(selectedMappingId, $scope.afterMappingSelected)

            };

            $scope.afterMappingSelected = function (mapping) {
                $scope.mappingArrayStr = mapping.mappingStr;

                ImportDataService.recogniseModelNamesInMapping($scope.mappingArrayStr, $scope.afterRecogniseModelNamesInMapping);
            };

            $scope.afterRecogniseModelNamesInMapping = function (modelNames) {

                if (modelNames == "false") {
                    return;
                }
                if (modelNames.length > 0) {
                    var alreadyInOptions1 = false;
                    for (var ind in $rootScope.table1Options) {
                        if (modelNames[0].value == $rootScope.table1Options[ind].value) {
                            alreadyInOptions1 = true;
                            break;
                        }
                    }
                    if (!alreadyInOptions1) {
                        $rootScope.table1Options.push(modelNames[0]);
                        $rootScope.table2Options.push(modelNames[0]);
                        $scope.selectedTable1 = $rootScope.table1Options[$rootScope.table1Options.length - 1];
                    }
                }

                if (modelNames.length > 1) {
                    var alreadyInOptions2 = false;
                    for (var ind in $rootScope.table2Options) {
                        if (modelNames[1].value == $rootScope.table2Options[ind].value) {
                            alreadyInOptions2 = true;
                            break;
                        }
                    }
                    if (!alreadyInOptions2) {
                        $rootScope.table1Options.push(modelNames[1]);
                        $rootScope.table2Options.push(modelNames[1]);
                        $scope.selectedTable2 = $rootScope.table2Options[$rootScope.table2Options.length - 1];
                    }
                }
            };

            $scope.showMapping = function () {
                document.getElementById('aDefineMappings').click();
            };

            $scope.saveMapping = function () {

                $scope.shouldShowSaveMappingButton = false;
                var selectedMappingId = -1;
                var newMappingName = null;
                if (!$scope.isNewMapping) {
                    selectedMappingId = $scope.selectedMapping.value;
                } else {
                    newMappingName = $scope.newMappingName;
                }
                ImportDataService.saveMappingArrayStr(selectedMappingId, newMappingName, $scope.mappingArrayStr, $scope.afterMappingSaved)
            };

            $scope.afterMappingSaved = function (mapping) {
                $scope.getMappingNamesOptions($scope.newMappingName);
                alert("saved");
                $scope.isNewMapping = false;
                var selectedMappingId = mapping.id;
                $scope.selectMapping(selectedMappingId);

            };

            $scope.addNewMapping = function () {
                $scope.newMappingName = null;
                $scope.isNewMapping = true;
                $scope.mappingArrayStr = "";
            };

            $scope.duplicateMapping = function () {
                $scope.newMappingName = $scope.selectedMapping.label + " (copy)";
                $scope.isNewMapping = true;
            };

            $scope.deleteMapping = function () {
                ImportDataService.deleteMapping($scope.selectedMapping.value, $scope.afterMappingDeleted);
            };


            $scope.afterMappingDeleted = function () {
                $scope.getMappingNamesOptions(null);
                $scope.selectedMapping = $scope.options[$scope.options.length - 1];
                $scope.selectMapping($scope.selectedMapping.value);
            };

            $scope.onShowImportedVisible = function () {
                $scope.selectedTable1 = $rootScope.table1Options[0];
                $scope.selectedTable2 = $rootScope.table2Options[1];
                ImportDataService.fetchTableData("Country", "table1Html", $scope.constructTableHtml);
                ImportDataService.fetchTableData("City", "table2Html", $scope.constructTableHtml);
            };

            $scope.selectTable1 = function (selectedTable1Id) {
                ImportDataService.fetchTableData(selectedTable1Id, "table1Html", $scope.constructTableHtml);
            };

            $scope.selectTable2 = function (selectedTable2Id) {
                ImportDataService.fetchTableData(selectedTable2Id, "table2Html", $scope.constructTableHtml);
            };

            $scope.constructTableHtml = function (fetchedArray, destination) {

                $scope.tableData = fetchedArray;
                if ($scope.tableData.length > 0) {


                    var colNames = Object.keys(fetchedArray[0]);
                    console.log(colNames);
                    var optionsStr = "<thead><tr >";
                    for (var colId in colNames) {
                        optionsStr += "<td>" + colNames[colId] + "</td>";
                    }
                    optionsStr += "</tr></thead><tbody>";
                    for (var i in $scope.tableData) {

                        optionsStr += "<tr>";
                        for (var colId in colNames) {
                            var thisTd = "<td><b>" + $scope.tableData[i][colNames[colId]] + "</b></td>";

                            optionsStr += thisTd;
                        }
                        optionsStr += "</tr>";

                    }
                    $scope[destination] = optionsStr + "</tbody>";
                }
                else {
                    $scope[destination] = "<li >No records found on " + destination + ".</li>"
                }

            };


        });
})();