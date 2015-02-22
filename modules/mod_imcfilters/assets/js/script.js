/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

//show markers according to filtering
function show(category) {
	// == check the checkbox ==
	document.getElementById('cat-'+category).checked = true;
}			

function hide(category) {
	// == clear the checkbox ==
	document.getElementById('cat-'+category).checked = false;
}

//--- non recursive since IE cannot handle it (doh!!)
function imc_filterbox_click(box, category) {
	if (box.checked) {
		show(category);
	} else {
		hide(category);	
	}
	var com = box.getAttribute('path');
	var arr = new Array();
	arr = document.getElementsByName('cat[]');
	for(var i = 0; i < arr.length; i++)
	{
		var obj = document.getElementsByName('cat[]').item(i);
		var c = obj.id.substr(4, obj.id.length);

		var path = obj.getAttribute('path');
		if(com == path.substring(0,com.length)){
			if (box.checked) {
				obj.checked = true;
				show(c);
			} else {
				obj.checked = false;
				hide(c);
			}
		}
	}
	return false;
}

function vote(issue_id, user_id, token){
	jQuery.ajax({ 
	    'async': true, 
	    'global': false, 
	    'url': "index.php?option=com_imc&task=votes.add&format=json&issue_id=" + issue_id + "&user_id=" + user_id + "&" + token + "=1", 
	    'dataType': "json", 
	    'success': function (data) {
	    	var json = data;
	        if(json.data.code == 0 || json.data.code == -1){
	        	alert(json.data.msg);
	        }
	        else {
	        	jQuery('#votes-counter').html(json.data.votes);
	        }
	     },
	     'error': function (error) {
	        alert('Voting failure - See console for more information');
	        console.log (error);
	     }             
	});
}