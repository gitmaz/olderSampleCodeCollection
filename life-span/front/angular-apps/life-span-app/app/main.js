angular.module("lifeSpan", ["ngRoute","ui.bootstrap"])
    .config(function($routeProvider) {
        $routeProvider
            .when('/user', {
                templateUrl: 'controllers/user/user-frame.html',
                controller: 'userController'
            })
            .when('/birthday', {
                templateUrl: 'controllers/birthday/birthday-frame.html',
                controller: 'birthdayController'
            })
            .otherwise({redirectTo: '/user'});
    })
    .service('sharedKeyVals', function () {
        var keyVals = [];

        return {
            getVal: function (key) {
                return keyVals[key];
            },
            setVal: function(key,val) {
                keyVals[key] = val;
            }
        };
    })
    .directive('navigation', function($rootScope, $location) {
        return {
            template: '<li ng-repeat="option in options" ng-class="{active: isActive(option)}">' +
                      '    <a ng-href="{{option.href}}">{{option.label}}</a>' +
                      '</li>',
            link: function (scope, element, attr) {
                scope.options = [
                    {label: "user details", href: "#/user"},
                    {label: "user birthday", href: "#/birthday"},
                 ];

                scope.isActive = function(option) {
                    return option.href.indexOf(scope.location) === 1;
                };

                $rootScope.$on("$locationChangeSuccess", function(event, next, current) {
                    scope.location = $location.path();
                });
            }
        };
    });

