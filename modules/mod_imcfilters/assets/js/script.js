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