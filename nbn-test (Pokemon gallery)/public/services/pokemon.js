(function () {

    'use strict';

    angular
        .module('pokemonApp')
        .factory('pokemon', pokemon);

    function pokemon($http) {

        function filterByKeywords(pokemons, keywords) {

            var filtered = [];

            for (var i in pokemons) {
                if (pokemons[i].name.includes(keywords)) {
                    filtered.push(pokemons[i]);
                }
            }

            return filtered;
        }

        function loadFromServer(scope) {
            $http({
                method: "GET",
                url: "http://pokeapi.co/api/v2/pokemon/?limit=151"
            }).then(function mySucces(response) {

                var responseData = response.data;
                for (var i in responseData.results) {
                    responseData.results[i].url = parseInt(i) + 1;
                }

                scope.pokemonRows = responseData.results;

            }, function myError(response) {
                scope.has_error = true;
                scope.error_message = response.message;
                return null;
            });
        }

        function filterAndDecorate(scope, sce, timeout, simptypeahead) {
            if (scope.searchPhrase == "") {
                scope.fillOptions();
            }
            else {

                scope.searchKeywords = scope.searchPhrase.split(",");
                scope.pokemonRowsFiltered = filterByKeywords(scope.pokemonRows, scope.searchKeywords);
                scope.optionsStr = simptypeahead.getHighlightedOptionsStr(scope.pokemonRowsFiltered, scope.searchKeywords);
                scope.optionsHtml = sce.trustAsHtml(scope.optionsStr);

            }
        }

        return {
            filterAndDecorate: filterAndDecorate,
            loadFromServer: loadFromServer


        }
    }

})();