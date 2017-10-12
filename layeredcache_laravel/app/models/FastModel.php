<?php //namespace Maz\Models;
define('CACHE_KEY1' , 'key1');

//include caching library autoload way 
use Maz\MazClass;
use Pip\SP_Cache_Layer_Stack;
use Pip\SP_Cache_Layer_MemoryCache;
use Pip\SP_Cache_Layer_FileCache;

//actual data comes from here
//use SlowModel;

class FastModel{
	
	private $arrayCacheLevelNames;
	
	public function __construct($arrayCacheLevelNames=array("Memory")){
		
		$this->arrayCacheLevelNames=$arrayCacheLevelNames;
		
		
	}
	/* configuring our cache stack to have arbirary levels designated by their names as an array ($arrayCacheLevelNames)
	*/
	private function configureCache(&$cache){
		
		
		$arrayCacheLevelNames=$this->arrayCacheLevelNames;
		
		//instanciate arbitrary Cache Levels from their supplied name in $arrayCacheLevelNames
		foreach($arrayCacheLevelNames as $cacheLevelName){
		
		    $layerClassName = "Pip\SP_Cache_Layer_{$cacheLevelName}Cache";
			$clInstance = new $layerClassName;
			
			//todo: add other levels default config here in a switch case 
			if($cacheLevelName=="File")
			 $clInstance->configure('CLIENT_TAG', 'DEPLOYMENT');
			
			$res = $cache->registerCacheLayer($clInstance);
			if(!$res->bResult){
				throw(new Exception("failed registering $cacheLevelName Cache!"));
			}
		}
		
		
	}
	
	public function getData(){
		
		
		//a test of namespace inclusion (use clause) + laravel vendor autoload 
		//$maz=new MazClass();
		//$maz->func1();
		
	
		$data = null;
		$cache = SP_Cache_Layer_Stack::singleton();
		
		$this->configureCache($cache);
		
		
		$key = __CLASS__.CACHE_KEY1;


		// Step 1, check the cache for prefab data
		$res = $cache->read($key);
		if($res->bResult){
			// extract it
			$data = $res->mData;
		}
		else {
			// Step 2, make the data 
		
			$data=SlowModel::getData();
			
			// Step 3, cache the data
			$res = $cache->write($key, $data);
			
			// whatever error handling strategy you choose here:
			if(!$res->bResult){
				trigger_error($res->sMessage);
			}
		 }
		 
		 return $data;
		
	}
	
	public function clearFileCacheIfAny(){
		
		
		$key = __CLASS__.CACHE_KEY1;
        
		$arrayCacheLevelNames=$this->arrayCacheLevelNames;
		
		//instanciate arbitrary Cache Levels from their supplied name in $arrayCacheLevelNames
		foreach($arrayCacheLevelNames as $cacheLevelName){
		  if($cacheLevelName=="File"){
		  	
		    $layerClassName = "Pip\SP_Cache_Layer_{$cacheLevelName}Cache";
			$clInstance = new $layerClassName;
			
			//todo: add other levels default config here in a switch case 
			
			 $clInstance->configure('CLIENT_TAG', 'DEPLOYMENT');
	   		 $clInstance->clear($key);
			break;
		  }
		}
	}
	
}
?>