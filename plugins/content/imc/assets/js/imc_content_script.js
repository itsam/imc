//TODO: Get as arguments the containers (textarea and aref)
function extractMailFromGroup(token){
    var groups = [];
	jQuery('#jform_params_imc_category_usergroup_chzn li a').each(function(index, value){
        index = jQuery(this).attr('data-option-array-index');
        id = jQuery('#jform_params_imc_category_usergroup option').eq(index).val();
        groups.push(id);
	});
	var g = groups.join('-');
    
    jQuery.ajax({
        'async': true,
        'global': false,
        'url': "index.php?option=com_imc&task=users.members&format=json&groups=" + g + "&" + token + "=1",
        'dataType': "json",
        'success': function (data) {
        	var json = data;
            var textarea = jQuery("textarea#jform_params_imc_category_emails");
        	var existing = textarea.val().split("\n");
        	var names = '';
         	jQuery(json.data).each(function(index, element) {
				if (jQuery.inArray(element.email+ ':' + element.name, existing) == -1){
					textarea.val( textarea.val() + "\n" + element.email + ':' + element.name);
					names += '<li>' + element.name + '</li>';
				}
         	});
         	if(names != ''){
         		jQuery('#jform_params_imc_category_emails-lbl').append('<div class="alert alert-warning"> Added:<ul>'+names+'</ul><strong>Apply changes by clicking the "save" button</strong></div>');
         	}
         },
         'error': function (error) {
            alert('Email extraction failed - See console for more information');
            console.log (error);
         }
    });

}