<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Find a Pokeman!</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">

</head>
<body>

<div class="hero-logo">Pokemon Gallery</div><!--todo: this should be moved into views/headers -->
<div ng-app="pokemonApp" class="ml20">
    <div ui-view id="main_view"></div>
</div>
</body>

<!-- Application Dependencies -->
<script data-require="angular.js@*" data-semver="1.3.0-beta.5" src="lib/angular.min.js"></script>
<script data-require="ui-router@*" data-semver="0.2.10" src="lib/angular-ui-router.min.js"></script>
<script src="lib/satellizer.js"></script>
<script src="lib/api-check.js"></script>

<script src="lib/jquery.min.js"></script>
<script src="lib/bootstrap.min.js"></script>
<script src="lib/ui-bootstrap-tpls-0.11.0.min.js"></script>

<!-- Application Scripts -->
<script src="common/app.js"></script>
<script src="services/pokemon.js"></script>
<script src="services/simptypeahead.js"></script>
<script src="directives/compile.js"></script>
<script src="directives/text_select.js"></script>
<script src="controllers/pokemonListController.js"></script>

</html>