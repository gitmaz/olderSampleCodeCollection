<?php //namespace Maz\Models;
class SlowModel{
	
	public function _construct(){
		
		
	}
	
	public static function getData(){
		$data = null;
		
	    sleep(3);
		$data = date('Y/m/d H:i:s').' LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ';
		return $data;
	}
}



?>