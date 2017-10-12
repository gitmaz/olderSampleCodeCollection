<html><!-- new layout using ajax for inclusion of compound content from different urls -->
<head>
		<!--  Include the jQuery libraries -->
	   <script src="//code.jquery.com/jquery-1.7.2.min.js"></script>
	   
	   <!--  Incorporate the Bootstrap JavaScript plugins -->
	   <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
	
	   <!-- for jQuery tabs -->
	   <link rel="stylesheet" href="http://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css">
	   <script  type="text/javascript" src="//code.jquery.com/ui/1.9.2/jquery-ui.min.js"></script>
		<script  type="text/javascript">
			$(function() {
				//create a tabbed kind of menu
				$( "#tabs" ).tabs();
				
			   
				fillFirstTabWithContent();
				
			});
			
			
			function fillFirstTabWithContent(){
				
				
				// couldnt get laravel to work with posts! it does not return anything!
				var url="/index.php/accesstime/directly-ajax";
				//var url="/index.php/accessTimeAjaxDirectly";
				$.post(url,function(data){
					$("#tabs-1").html(data);
				});
				
				/*
				var url="/index.php/cached-ajax";
				$.get(url,function(replyHtml){
					$("#tabs-1").html(replyHtml);
				});*/
			}
			
			function fillSecondTabWithContent(){
				
			}
			
			function fillThirdTabWithContent(){
				
			}
		</script>
	    <style>
			#tabs div{
				color:white;
			}
		</style>
	</head>
	
    <body>
        <h1>Layered Caching Tests</h1>

        <div id="tabs">
			<ul>
			<li><a href="#tabs-1" >Access Directly (simple)</a></li>
			<li><a href="#tabs-2">Access Cached (simple)</a></li>
			<li><a href="#tabs-3">Access Directly & Cached (ajax)</a></li>
			</ul>
			<div id="tabs-1" style='background-color:black'>
			</div>
			<div id="tabs-2" style='background-color:black'>
			</div>
			<div id="tabs-3" style='background-color:black'>
			</div>
		</div>
		
    </body>
</html>