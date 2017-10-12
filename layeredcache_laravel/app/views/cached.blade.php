@extends('layout')

@section('content')
   <h4>Cached access to data:</h4> 
   <b>data:</b>&nbsp;{{$data}}
   <br /><br />
   <b>data access time:</b>&nbsp;{{$elapsed}}
@stop