(function () {

    'use strict';

    angular
        .module('pokemonApp', ['ui.router', 'satellizer'])
        .config(function ($stateProvider, $httpProvider) {

            $stateProvider
                .state('pokemonlist', {
                    url: '/pokemonlist',
                    templateUrl: './views/domain/pokemon/pokemon-list-view.html',
                    controller: 'PokemonListController as ctrl',
                })
                .state('index.pokemonlist', { //todo: make this working to implement layouts
                    url: '/',
                    views: {
                        'left@index': {
                            templateUrl: './views/domain/pokemon/sides/list.html',
                            controller: 'DummyCtrl'
                        },
                        'main@index': {
                            templateUrl: './views/domain/pokemon/pokemon-list-view.html',
                            controller: 'PokemonListController as ctrl'
                        },
                    },
                })
        })
        .controller('DummyCtrl', function () {
        })
        .controller('DetailCtrl', function ($scope, $stateParams) {
            $scope.id = $stateParams.id;
        })
        .run(function ($rootScope, $state) {

        });
})();