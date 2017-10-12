angular.module("lifeSpan").controller("birthdayController", function ($scope, $http, $location, sharedKeyVals) {

    $scope.today = function () {
        $scope.dt = new Date();
    };
    $scope.today();

    $scope.options = {
        customClass: getDayClass,
        showWeeks: false
    };

    $scope.setDate = function (year, month, day) {
        $scope.dt = new Date(year, month, day);
    };

    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    var afterTomorrow = new Date(tomorrow);
    afterTomorrow.setDate(tomorrow.getDate() + 1);
    $scope.events = [
        {
            date: tomorrow,
            status: 'full'
        },
        {
            date: afterTomorrow,
            status: 'full'
        }
    ];

    $scope.user = {
        name: "X"
    };

    $scope.user.name = sharedKeyVals.getVal("user.name");

    $scope.result = {
        ready: false,
        value: 'not set'
    };

    $scope.birth_day = "";
    $scope.error_message = "";
    $scope.has_error = false;


    $scope.saveAsJson = function () {

        diff = getElapsedTillNowInYearsDaysAndHours($scope.dt);
        $scope.result.value = diff.years + ' Years , ' + diff.days + ' Days and ' + diff.hours + ' Hours';

        record = {
            user_name: $scope.user.name,
            birth_day: getDateShortStr($scope.dt),
            elapsed: $scope.result.value
        };

        $http({
            method: "POST",
            url: "../../../ajaxHandler.php",
            data: {action: 'save_as_json', birth_record: record}
        }).then(function mySucces(response) {

            responseData = response.data;

            if (responseData.status == "success") {
                $scope.result.ready = true;

            }
            else {
                alert(responseData.message);
            }
        }, function myError(response) {
            $scope.has_error = true;
            $scope.error_message = response.message;
        });


    };

    $scope.goBack = function () {
        $scope.result.ready = false;
        $scope.result.value = "";
        $location.path("/user");
    };

    function getDayClass(data) {
        var date = data.date,
            mode = data.mode;
        if (mode === 'day') {
            var dayToCheck = new Date(date).setHours(0, 0, 0, 0);
            for (var i = 0; i < $scope.events.length; i++) {
                var currentDay = new Date($scope.events[i].date).setHours(0, 0, 0, 0);

                if (dayToCheck === currentDay) {
                    return $scope.events[i].status;
                }
            }
        }

        return '';
    }

    function getElapsedTillNowInYearsDaysAndHours(originDate) {
        var date1 = originDate;
        var nowInMilliseconds = Date.now();
        var timeDiff = Math.abs(date1.getTime() - nowInMilliseconds);
        var diffHoursFloat = timeDiff / (1000 * 3600);
        var diffHours = Math.floor(diffHoursFloat);
        var diffDaysFloat = diffHours / 24;
        var diffDays = Math.floor(diffDaysFloat);
        diffHours = diffHours - diffDays * 24;
        var diffYearsFloat = diffDays / 365;
        var diffYears = Math.floor(diffYearsFloat);
        diffDays = diffDays - diffYears * 365;


        var DiffDates = {years: diffYears, days: diffDays, hours: diffHours}
        return DiffDates;
    }

    function getDateShortStr(date) {
        var day = date.getDate();
        var monthIndex = date.getMonth() + 1;
        var year = date.getFullYear();

        return day + " / " + monthIndex + " / " + year;
    }

});


