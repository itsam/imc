<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */


defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

class JFormFieldStep extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'Step';

	protected $descriptionfield;
	protected $flagfield;


	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   3.2
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'descriptionfield':
			case 'flagfield':
				return $this->$name;
		}

		return parent::__get($name);
	}


/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param   string  $name   The property name for which to the the value.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'descriptionfield':
			case 'flagfield':
				$this->$name = (string) $value;
				break;
			default:
				parent::__set($name, $value);
		}
	}


	public function getOptions()
	{
        //Load all the field options
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('id, title AS value')
              ->from('#__imc_steps')
              ->order('ordering');
        $db->setQuery($query);
        $results = $db->loadObjectList();		
        $options = array();
	    foreach ($results as $result) {
	        $tmp = JHtml::_('select.option', $result->id, $result->value);
			//$tmp->class = 'myclass';
	        $options[] = $tmp;
	    }
		return $options;
	}

	protected function getItemById($id)
	{
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('title, stepcolor')
              ->from('#__imc_steps')
              ->where('id='.$id);
        $db->setQuery($query);
        $results = $db->loadRow();		
		return $results;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{

		$disabled = false;
		if(isset($this->element['disabled'])){
			$disabled = $this->element['disabled'];
		}
		$readonly = false;
		if(isset($this->element['readonly'])){
			$readonly = $this->element['readonly'];
		}		

	
		if($readonly){
			$s = $this->getItemById($this->value);
			$html = array();
			$html[] = '<div>';
			if(isset($s[1]))
				$html[] = '<span style="font-size: 20px; color:'.$s[1].'">&marker;</span>';
			
			$html[] = $s[0];
			$html[] = '</div>';
			$html[] = '<input type="hidden" name="'.$this->name.'" id="'.$this->id. '" value="'.$this->value.'" />';
			return implode("\n", $html);

		}else{
			if(!isset($this->element['descriptionfield']))
				return '<strong>Step field argument `descriptionfield` is not set</strong>';
			if(!isset($this->element['flagfield']))
				return '<strong>Step field argument `flagfield` is not set</strong>';
		}
		//JFactory::getDocument()->addStyleSheet(JURI::root(true).'/components/com_imc/models/fields/step/css/step.css');

		$script = array();
		$script[] = "jQuery(document).ready(function() {";
		$script[] = "	var descriptionfield='jform_".$this->element['descriptionfield']."';";
		$script[] = "	var flagfield='jform_".$this->element['flagfield']."';";
		$script[] = "	var instance_type='".$this->type."';";
		$script[] = "	jQuery( '#save_'+instance_type+'_reason' ).click(function() {";
		$script[] = "		if (!jQuery.trim(jQuery('#'+descriptionfield).val()))";
		$script[] = "			jQuery('#'+instance_type+'_reason_btn').addClass('btn-danger');";
		$script[] = "		else";
		$script[] = "			jQuery('#'+instance_type+'_reason_btn').removeClass('btn-danger');";
		$script[] = "	});";
		$script[] = "});";

		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/step/js/step.js');		

	    $html = array();
	    $input_options = 'class="' . $this->getAttribute('class') . '" onchange="stepChange('.$this->value.', this.value, \''.$this->type.'\',\'jform_'.$this->element['flagfield'].'\',\'jform_'.$this->element['descriptionfield'].'\');"';
		
		$html[] = JHtml::_('select.genericlist', $this->getOptions(), $this->name, $input_options, 'value', 'text', $this->value);

		$html[] = '<input id="jform_'.$this->element['flagfield'].'" type="hidden" value="false" name="jform['.$this->element['flagfield'].']">';
		$html[] = '<a id="'.$this->type.'_reason_btn" href="#'.$this->type.'Modal" role="button" class="btn btn-mini hide" data-toggle="modal">Reason</a>';

		$html[] = '<!-- Modal -->';
		$html[] = '<div id="'.$this->type.'Modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="'.$this->type.'ModalLabel" aria-hidden="true">';
		$html[] = '	<div class="modal-header">';
		$html[] = '		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>';
		$html[] = '		<h3 id="'.$this->type.'ModalLabel">'.$this->type.' Modification: Reason Description</h3>';
		$html[] = '	</div>';
		$html[] = '	<div class="modal-body">';
		$html[] = '		<p id="'.$this->type.'Body">';
		$html[] = '		<textarea style="width:98%;resize:none;" rows="6" cols="75" id="jform_'.$this->element['descriptionfield'].'" name="jform['.$this->element['descriptionfield'].']"></textarea>';
		$html[] = '		</p>';
		$html[] = '		<p>(if set on options, notifications will be sent on save)</p>';
		$html[] = '	</div>';
		$html[] = '	<div class="modal-footer">';
		$html[] = '		<button id="save_'.$this->type.'_reason" class="btn btn-primary" data-dismiss="modal" aria-hidden="true">OK</button>';
		$html[] = '	</div>';
		$html[] = '</div>';
		return implode("\n", $html);
	}
}