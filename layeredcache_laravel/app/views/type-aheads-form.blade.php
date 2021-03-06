@extends('layout')

@section('content')
   
   
   <h4>Ajax direct and cached access to data using type aheads:</h4> 
   
	<input id="myTypeahead" class='ajax-typeahead' type="text" data-link="myUrl" data-provide="typeahead" />
	<!--script type="text/javascript">

	$('#myTypeahead').typeahead({
	    source: function (query, process) {
	        return $.ajax({
	            url: $('#myTypeahead').data('link'),
	            type: 'post',
	            data: { query: query },
	            dataType: 'json',
	            success: function (result) {

	                var resultList = result.map(function (item) {
	                    var aItem = { id: item.Id, name: item.Name };
	                    return JSON.stringify(aItem);
	                });

	                return process(resultList);

	            }
	        });
	    },

	 matcher: function (obj) {
	        var item = JSON.parse(obj);
	        return ~item.name.toLowerCase().indexOf(this.query.toLowerCase())
	    },

	    sorter: function (items) {          
	       var beginswith = [], caseSensitive = [], caseInsensitive = [], item;
	        while (aItem = items.shift()) {
	            var item = JSON.parse(aItem);
	            if (!item.name.toLowerCase().indexOf(this.query.toLowerCase())) beginswith.push(JSON.stringify(item));
	            else if (~item.name.indexOf(this.query)) caseSensitive.push(JSON.stringify(item));
	            else caseInsensitive.push(JSON.stringify(item));
	        }

	        return beginswith.concat(caseSensitive, caseInsensitive)

	    },

	    highlighter: function (obj) {
	        var item = JSON.parse(obj);
	        var query = this.query.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, '\\$&')
	        return item.name.replace(new RegExp('(' + query + ')', 'ig'), function ($1, match) {
	            return '<strong>' + match + '</strong>'
	        })
	    },

	    updater: function (obj) {
	        var item = JSON.parse(obj);
	        $('#IdControl').attr('value', item.id);
	        return item.name;
	    }
	});
	</script-->

@stop
