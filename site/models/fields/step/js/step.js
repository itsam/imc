jQuery(document).ready(function() {
	
	jQuery( "#save_step_reason" ).click(function() {
		if (!jQuery.trim(jQuery('#jform_step_modified_description').val()))
			jQuery('#step_reason_btn').addClass('btn-danger');
		else
			jQuery('#step_reason_btn').removeClass('btn-danger');		
	});
});


function stepChange(prev, current){
	jQuery('#jform_is_step_modified').val(prev!=current);

	if(prev!=current){
		//make field required
		jQuery('#jform_step_modified_description').prop('required',true);

		//show btn
		jQuery('#step_reason_btn').removeClass('hide');
		if (!jQuery.trim(jQuery('#jform_step_modified_description').val()))
			jQuery('#step_reason_btn').addClass('btn-danger');
		else
			jQuery('#step_reason_btn').removeClass('btn-danger');
	}
	else{
		//make field non required 
		jQuery('#jform_step_modified_description').prop('required',false);

		//hide btn
		jQuery('#step_reason_btn').addClass('hide');
	}
}