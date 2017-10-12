<?php

class HomeController extends BaseController {

	

    /*
	 give a brief wellcome message and links to variety of site functions
	*/
	public function showWelcome()
	{
		return View::make('hello');
	}
	
	public function getInterviewAnsweres()
	{
		return View::make('interview-answeres')->with('baseUrl',str_replace("public/index.php","app/storage/views",Request::root()) );// URL::to('/')
	}
	
	public function getIndex()
	{
		return View::make('hello');
	}
	
	public function getBye()
	{
		return View::make('bye');
	}

}
