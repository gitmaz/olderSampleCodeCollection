angular.module("lifeSpan").controller("userController", function ($scope, $http, $location, sharedKeyVals) {

    $scope.error_message = "";
    $scope.has_error = false;
    $scope.ready = false;
    $scope.birth_records = [];

    $http({
        method: "POST",
        url: "../../../ajaxHandler.php",
        data: {action: 'get_users_birth_records'}
    }).then(function mySucces(response) {

        responseData = response.data;

        if (responseData.status == "success") {
            $scope.ready = true;
            $scope.birth_records = responseData.birth_records;
            $scope.show_birth_records = (responseData.birth_records.length > 0);
        }
        else {
            alert(responseData.message);
        }
    }, function myError(response) {
        $scope.has_error = true;
        $scope.error_message = response.message;
    });

    $scope.goToBirthday = function () {
        sharedKeyVals.setVal("user.name", $scope.user.name);
        $location.path("/birthday");
    }


});


