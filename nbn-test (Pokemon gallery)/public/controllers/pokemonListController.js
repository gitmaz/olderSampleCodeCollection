(function () {
    'use strict';

    angular
        .module('pokemonApp')
        .controller('PokemonListController', PokemonListController)
        .filter('startFrom', function () {
            return function (input, start) {
                start = +start;
                return input.slice(start);
            }
        });

    function PokemonListController($scope, $http, $timeout, $state, $sce, pokemon, simptypeahead) {

        $scope.isLoadingData = true;

        $scope.searchPhrase = "";
        $scope.rawOptionsStr = "";
        $scope.optionsStr = "";
        $scope.optionsHtml = "";

        $scope.pokemonRows = [];
        $scope.pokemonRowsFiltered = [];

        //pagination
        $scope.currentPage = 0;
        $scope.pageSize = 5;
        $scope.data = [];
        $scope.nOfPages = 0;
        $scope.numberOfPages = function () {
            $scope.nOfPages = Math.ceil($scope.pokemonRowsFiltered.length / $scope.pageSize);
            return $scope.nOfPages;
        }


        $scope.anyResult = function () {
            return ($scope.nOfPages > 0 );
        }
        for (var i = 0; i < 45; i++) {
            $scope.data.push("Item " + i);
        }

        pokemon.loadFromServer($scope);
        $timeout(function () {
                $scope.fillOptions();
                $scope.isLoadingData = false;
            }
            , 5000);

        $scope.onSearchBoxTextChange = function () {

            pokemon.filterAndDecorate($scope, $sce, $timeout, simptypeahead);

        };

        $scope.onSearchFinalised = function (liIndex) {

            $scope.searchPhrase = $scope.pokemonRows[liIndex].name;
            $scope.isSearching = false;

        };

        $scope.fillOptions = function () {
            $scope.optionsStr = $scope.getOptionsStr($scope.pokemonRows);
            $scope.optionsHtml = $sce.trustAsHtml($scope.optionsStr);
            $scope.pokemonRowsFiltered = $scope.pokemonRows;
            $scope.currentPage = 0;
        };

        $scope.getOptionsStr = function (options) {
            var optionsStr = "";
            for (var i in options) {
                var imgFileName = options[i].url;
                optionsStr += ("<li class='block' ng-click=\"onSearchFinalised(" + imgFileName + ")\"><a><img src='./assets/imgs/" + imgFileName + ".png' alt='" + imgFileName + "'>" + options[i].name + "</a></li>");
            }
            return optionsStr;

        };

        $scope.getOptionsUlHtml = function (options) {
            var optionsStr = $scope.getOptionsStr(options);
            var optionsUl = "<ul>" +
                "<li ng-repeat='item in data | startFrom:currentPage*pageSize | limitTo:pageSize'>" +
                "{{item}}" +
                "</li>" +
                "</ul>";
            return optionsUlHtml;

        };

        $scope.tabSelect = function (viewType) {
            switch (viewType) {
                case 'tree_view':
                    // $('.nav-tabs a:first').tab('show');
                    $state.go('index.catsubcatstree');
                    break;
                case 'dropdown_view':
                    break;
            }
        };

    }

})();