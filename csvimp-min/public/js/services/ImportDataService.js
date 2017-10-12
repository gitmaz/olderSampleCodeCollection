(function(){
    'use strict';

    angular
        .module('app')
        .factory('ImportDataService', ImportDataService);

    function ImportDataService($http) {

        function importCsv(mappingArrayStr, onComplete){
              ajaxPost("../../index.php/imports/csv/ajax_import_csv", {mappingArrayStr:mappingArrayStr}, onComplete);

        }

        function recogniseModelNamesInMapping(mappingArrayStr, onComplete){
           ajaxPost("../../index.php/imports/csv/ajax_recognise_model_names_in_mapping", {mappingArrayStr:mappingArrayStr}, onComplete);
        }

        function fetchTableData(modelName, destination, onComplete){
            ajaxPost("../../index.php/imports/csv/ajax_fetch_table_data", {modelName:modelName}, onComplete, destination);
        }

        function deleteMapping(mappingId, onComplete){
            ajaxPost("../../index.php/imports/csv/ajax_delete_mapping", {mappingId:mappingId}, onComplete);
        }

        function saveMappingArrayStr(mappingId, newMappingName, mappingArrayStr, onComplete){
            ajaxPost("../../index.php/imports/csv/ajax_save_mapping_array_str", {mappingId:mappingId,newMappingName:newMappingName, mappingArrayStr:mappingArrayStr}, onComplete);
         }

        function getMappingArrayStr(mappingId, onComplete){
           ajaxPost("../../index.php/imports/csv/ajax_get_mapping_array_str_by_id", {mappingId:mappingId}, onComplete);
        }

        function getExistingMappingNames(onComplete){
           ajaxPost("../../index.php/imports/csv/ajax_get_existing_mapping_names", {}, onComplete);
        }

        function ajaxPost(url, data, onComplete, destination){
            $http({
                method: "POST",
                url: url,
                data: data
            }).then(function mySucces(response) {

                var responseData = response.data;

                if(responseData.system_error){
                    alert("a system error has occured while recalling group data!");
                    return;
                }

                if(destination == null){
                  onComplete(responseData);
                } else {
                   onComplete(responseData, destination);
                }

            }, function myError(response) {

                alert(response.data.message);
                $rootScope.has_error = true;
                $rootScope.error_message = response.message;
                return null;
            });
        }



        return {
            importCsv:importCsv,
            recogniseModelNamesInMapping: recogniseModelNamesInMapping,
            fetchTableData:fetchTableData,
            deleteMapping: deleteMapping,
            saveMappingArrayStr: saveMappingArrayStr,
            getMappingArrayStr: getMappingArrayStr,
            getExistingMappingNames: getExistingMappingNames
        }
    }

})();
