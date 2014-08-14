function stepChange(prev, current, instance, flagfld, descriptionfld) {
	jQuery('#'+flagfld).val(prev!=current);

	if(prev!=current){
		//make field required
		jQuery('#'+descriptionfld).prop('required',true);

		//show btn
		jQuery('#'+instance+'_reason_btn').removeClass('hide');
		if (!jQuery.trim(jQuery('#'+descriptionfld).val()))
			jQuery('#'+instance+'_reason_btn').addClass('btn-danger');
		else
			jQuery('#'+instance+'_reason_btn').removeClass('btn-danger');
	}
	else{
		//make field non required 
		jQuery('#'+descriptionfld).prop('required',false);

		//hide btn
		jQuery('#'+instance+'_reason_btn').addClass('hide');
	}
}