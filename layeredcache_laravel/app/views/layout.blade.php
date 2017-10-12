
<html><!-- old layout using blade's direct (default) inclusion of content -->
    <head>
		<style>
		    /* style for our menu links */
			div.horzNavBar li{	
				font-size: medium;
				display: inline;
				padding: 10px;
			}

			div.horzNavBar li a{
				color:red;
				text-decoration:none;
			}
		</style>
	</head>
	<body>
	<h1>Fleetcutter Interview Question 4 -Layered Cache</h1>
	<div class="horzNavBar">
		 	  <ul>
				<li><a href="{{$baseUrl}}/accessTime/directly">Access Directly</a></li>
				<li><a href="{{$baseUrl}}/accessTime/cached">Access Cached</a></li>
				<li><a href="{{$baseUrl}}/accessTime/cleared">Clear Cache</a></li>
				<li><a href="{{$baseUrl}}/accessTime/main-page">Bootstrap Cached (Incomplete)</a></li>
				<li><a href="{{$baseUrl}}/accessTime/interview">All Questions</a></li>
				
				<li><a href="{{$baseUrl}}/accessTime/about">About</a></li>
			  </ul> 
     </div>
	 
	 
        	@yield('content')
    </body>
</html>