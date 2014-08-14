jQuery(document).ready(function() {
	jQuery( "#save_step_reason" ).click(function() {
		if (!jQuery.trim(jQuery('#'+descriptionfield).val()))
			jQuery('#step_reason_btn').addClass('btn-danger');
		else
			jQuery('#step_reason_btn').removeClass('btn-danger');		
	});
});


function stepChange(prev, current){
	jQuery('#'+flagfield).val(prev!=current);

	if(prev!=current){
		//make field required
		jQuery('#'+descriptionfield).prop('required',true);

		//show btn
		jQuery('#step_reason_btn').removeClass('hide');
		if (!jQuery.trim(jQuery('#'+descriptionfield).val()))
			jQuery('#step_reason_btn').addClass('btn-danger');
		else
			jQuery('#step_reason_btn').removeClass('btn-danger');
	}
	else{
		//make field non required 
		jQuery('#'+descriptionfield).prop('required',false);

		//hide btn
		jQuery('#step_reason_btn').addClass('hide');
	}
}