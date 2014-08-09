/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

function onInit(data, elem, id) {
	var obj = new Object();
	obj.id = id;
	obj.files = data;
	
	console.log(obj);
	jQuery('#'+String(elem.id)).val( JSON.stringify(obj) );
}

function onDone(data, elem) {
	var obj = JSON.parse( jQuery('#'+String(elem.id)).val() );

	if(obj){
		obj['files'].push(data[0]);
	}
	
	console.log(obj);
	jQuery('#'+String(elem.id)).val( JSON.stringify(obj) );
}

function onDestroy(data, elem) {
	var obj = JSON.parse( jQuery('#'+String(elem.id)).val() );
	var i = -1;
	for (index = 0; index < obj.files.length; ++index) {
	    console.log(obj['files'][index]['name']);
	    if(data == obj['files'][index]['name']){
	    	i = index;
	    	console.log('found at:'+i);
	    	break;
	    }
	}
	console.log('remove this:'+data);

	if(i > -1){
		obj['files'].splice(i, 1);
	}


	console.log(obj);



	jQuery('#'+String(elem.id)).val( JSON.stringify(obj) );
}