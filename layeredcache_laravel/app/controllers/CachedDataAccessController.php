<?php 
//use Maz\Models\SlowModel;
//use Maz\Models\FastModel;

class CachedDataAccessController extends BaseController {


	/*
	|--------------------------------------------------------------------------
	| Cached Access Controller
	|--------------------------------------------------------------------------
	|
	|This is where we manage faster access to some computationaly expensive data 
	|
	*/

	public function __construct()
	{
		
	}

//NON Ajax rendering of the pages

    /* be polite and tel you are awake
	   just a test to see we can get here
	*/
    public function getSalute(){
		return "Hi there!";
	}
	
	/* Show an itroduction page as well as navigation bar
	   so that we can getDirectly ,getCached and getCleared
	*/
    public function getIndex(){
		return View::make('intro')->with('baseUrl',Request::root() );
	}
	
	 public function getInterview(){
		return View::make('interview-answeres')->with('baseUrl',str_replace("public/index.php","app/storage/views",Request::root()) );
	}


	/* direct access to slow data 
	|  get data directly...so user needs to wait until data is ready
	|
	*/
	public function getDirectly()
	{
	   //ask slow model to give us its data
	   $startTime = microtime(true);
	    $data=SlowModel::getData();
	   $elapsed = round(microtime(true)-$startTime,2);
	   	
		return View::make('directly')->with('data', $data)
									 ->with('elapsed',$elapsed)
									 ->with('baseUrl',Request::root() );
	}
	
	/* cached access to slow data 
	|  get cached version of data if available ...so user waits less on second call and forwards
	|
	*/
	public function getCached()
	{  
	
	  /*Note: instead of dealing with data (business logic) in controller, we put this in models.
	   caching is no exception.We only pass configuration of caching to FastModel and it delegates
	   the configuration to a CacheStack singleton
	  */
	
	   //create an instance,instanciated with a cachestack of our own devised caching levels and parameters
	   //for our case we are configuring our cache stack to have  2 layers of memoy and file
	   
	   //A demonstration of Memory cache not working (YES because web is stateless,use memcached or sessions in Pip's classes to make it work!)	   
	   //$fastModel=new FastModel();
	   
	   //we need at least a kind of file to keep data! because of stateless above bug
	   $fastModel=new FastModel(array("Memory","File"));
		
	    //ask fast model to give us its data
	   $startTime = microtime(true);
	     $data=$fastModel->getData();
	   $elapsed = round(microtime(true)-$startTime,2);
	   
		return View::make('cached')->with('data', $data)
									   ->with('elapsed',$elapsed)
									   ->with('baseUrl',Request::root() );
	}
	/* clear the CACHE
	*/
	public function getCleared(){
		//we dont have any clear key mekanism yet in Pip's class ,lets imitate a cache clear if File Cache is used:
		$fastModel=new FastModel(array("Memory","File"));
		$fastModel->clearFileCacheIfAny();
		
		return View::make('cleared')->with('baseUrl',Request::root() );
	}

	public function getAbout(){
		return View::make('about')->with('baseUrl',Request::root() );
	}

//Ajax rendering of main page in a tabbed content style with each tab showing content for one of above pages

   /* we first need to render a page to trigger ajax renderings from
   
   */
   public function getMainPage(){
   	return View::make('main-page');
   }

   /* direct access to slow data 
	|  get data directly...so user needs to wait until data is ready
	|
	*/
	public function postDirectlyAjax()
	{
	   //ask slow model to give us its data
	  
	   $startTime = microtime(true);
	    $data=SlowModel::getData();
	   $elapsed = round(microtime(true)-$startTime,2);
	   	
	   $view =    View::make('directly')->with('data', $data)
									 ->with('elapsed',$elapsed);
	   
	   //we only need the content of this layout when called through ajax!
	   
	   return "A bug here! temp fix!<br />";//$view->renderSections()['content'];
	   //return Response::json($view->renderSections()['content']);
	
	}
	
	public function getDirectlyAjax()
	{
	   //ask slow model to give us its data
	  
	   $startTime = microtime(true);
	    $data=SlowModel::getData();
	   $elapsed = round(microtime(true)-$startTime,2);
	   	
	   $view =    View::make('directly')->with('data', $data)
									 ->with('elapsed',$elapsed);
	   
	   //we only need the content of this layout when called through ajax!
	   
	   return "A bug here! temp fix!<br />";//$view->renderSections()['content'];
	   //return Response::json($view->renderSections()['content']);
	
	}
	
	
	
	
	/* cached access to slow data 
	|  get cached version of data if available ...so user waits less on second call and forwards
	|
	*/
	public function postCachedAjax()
	{  
	
	  /*Note: instead of dealing with data (business logic) in controller, we put this in models.
	   caching is no exception.We only pass configuration of caching to FastModel and it delegates
	   the configuration to a CacheStack singleton
	  */
	
	   //create an instance,instanciated with a cachestack of our own devised caching levels and parameters
	   //for our case we are configuring our cache stack to have  2 layers of memoy and file
	   $fastModel=new FastModel(array("Memory","File"));
		
	    //ask fast model to give us its data	
	   $startTime = microtime(true);
	     $data=$fastModel->getData();
	   $elapsed = round(microtime(true)-$startTime,2);
	   
	   $view =    View::make('cached')->with('data', $data)
									 ->with('elapsed',$elapsed);
	   
	   //we only need the content of this layout when called through ajax!
	   return "A bug here! temp fix!<br />";//$view->renderSections()['content'];
	}
	
	public function getCachedAjax()
	{  
	
	    //for our case we are configuring our cache stack to have  2 layers of memoy and file
	   $fastModel=new FastModel(array("Memory","File"));
		
	    //ask fast model to give us its data
	   $startTime = microtime(true);
	     $data=$fastModel->getData();
	   $elapsed = round(microtime(true)-$startTime,2);
	   
	   $view =    View::make('cached')->with('data', $data)
									 ->with('elapsed',$elapsed);
	   
	   //we only need the content of this layout when called through ajax!
	   return "A bug here! temp fix!<br />";//$view->renderSections()['content'];
	}

   
	
//BOOTSTRAPPED !
    
	/*show us a form with two typeahead elements from which we can ajax fetch some data 
	  and compare server responsiveness for direct and cached access
	*/
	public function getTypeaheadsform(){
		
		return View::make('type-aheads-form');
	}

	/* direct access to slow data (ajax posted from a bootstrap typeahead element)
	|  get data directly...so user needs to wait until data is ready
	|
	*/
	public function postDirectlyTypedaheads()
	{
	   //ask slow model to give us its data
	   $startTime = microtime(true);
	    $data=SlowModel::getData();
	   $elapsed = round(microtime(true)-$startTime,2);
	   	
		return View::make('directly')->with('data', $data)
									 ->with('elapsed',$elapsed);
	}
	
	/* cached access to slow data  (ajax posted from a bootstrap typeahead element)
	|  get cached version of data if available ...so user waits less on second call and forwards
	|
	*/
	public function postCachedTypedaheads()
	{  
	
	  /*Note: instead of dealing with data (business logic) in controller, we put this in models.
	   caching is no exception.We only pass configuration of caching to FastModel and it delegates
	   the configuration to a CacheStack singleton
	  */
	
	   //create an instance,instanciated with a cachestack of our own devised caching levels and parameters
	   //for our case we are configuring our cache stack to have  2 layers of memoy and file
	   $fastModel=new FastModel(array("Memory","File"));
		
	    //ask fast model to give us its data
	   $startTime = microtime(true);
	     $data=$fastModel->getData();
	   $elapsed = round(microtime(true)-$startTime,2);
	   
		return View::make('cached')->with('data', $data)
									   ->with('elapsed',$elapsed);
	}
	
	
	

}

?>