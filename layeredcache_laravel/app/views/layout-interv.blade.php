<html><!-- old layout using blade's direct (default) inclusion of content -->
	<HEAD>
		<!--  Include the jQuery libraries -->
	   <script src="//code.jquery.com/jquery-1.7.2.min.js"></script>
	</HEAD>
	<body>
	 <h1>Fleetcutter Interview Questions</h1>
        	@yield('content')
    </body>
</html>