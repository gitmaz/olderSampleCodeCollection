(function () {

    'use strict';

    angular
        .module('pokemonApp')
        .factory('simptypeahead', simptypeahead);

    function simptypeahead($http) {

        function getHighlightedOptionsStr(options, searchKeywordsToHighlight) {
            var highlightedOptions = highlightKeywordsInArray(options, searchKeywordsToHighlight);
            var optionsStr = "";
            for (var i in options) {
                var imgFileName = highlightedOptions[i].url;
                optionsStr += ("<li  class='block'  ng-click=\"onSearchFinalised(" + highlightedOptions[i].url + ")\"><a><img src='./assets/imgs/" + imgFileName + ".png' alt='" + imgFileName + "'>" + highlightedOptions[i].name + "</a></li>");
            }
            return optionsStr;
        }

        function highlightKeywordsInArray(rows, wordsToHighlight) {

            var highlightedArray = [];
            for (var i in rows) {
                var row = rows[i];
                var haystack = row.name;
                var highlightedValue = wrapInTag({
                    haystack: haystack,
                    needles: wordsToHighlight,
                    tag: 'b'
                });

                highlightedArray.push({
                    url: row.url,
                    name: highlightedValue

                });
            }

            return highlightedArray;
        }

        //util
        function wrapInTag(opts) {

            var tag = opts.tag || 'strong'
                , needles = opts.needles || []
                , regex = RegExp(needles.join('|'), 'gi') // case insensitive
                , replacement = '<' + tag + '>$&</' + tag + '>'
                , haystack = opts.haystack || '';

            return haystack.replace(regex, replacement);

        }

        function testWrapInTag() {
            var result = wrapInTag({
                haystack: "world is full of red apples",
                needles: ['world', 'red'],
                tag: 'b',
            });
            console.log(result);
        }


        function getHighlightedHtml(htmlStr, wordsToHighlight) {
            var highlightedHtml = wrapInTag({
                haystack: htmlStr,
                needles: wordsToHighlight,
                tag: 'b'
            });

            return highlightedHtml;
        }

        //index
        return {
            getHighlightedOptionsStr: getHighlightedOptionsStr
        }
    }

})();