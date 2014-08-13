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

/**
 * Supports an HTML select list of categories
 */
class JFormFieldStep extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'Step';

	protected $latitudefield;
	protected $longitudefield;
	protected $width;
	protected $height;

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
			case 'latitudefield':
			case 'longitudefield':
			case 'width':
			case 'height':
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
			case 'latitudefield':
			case 'longitudefield':
			case 'width':
			case 'height':
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
	        $options[] = JHtml::_('select.option', $result->id, $result->value);
	    }
		return $options;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{
		if(!isset($this->element['descriptionfield']))
			return '<strong>Step field argument `descriptionfield` is not set</strong>';
		if(!isset($this->element['flagfield']))
			return '<strong>Step field argument `flagfield` is not set</strong>';

		$disabled = false;
		if(isset($this->element['disabled'])){
			$disabled = $this->element['disabled'];
		}
		

		$script = array();
		$script[] = "var descriptionfield='".$this->element['descriptionfield']."';";
		$script[] = "var flagfield='".$this->element['flagfield']."';";
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/step/js/step.js');		

	    $html = array();
	    $input_options = 'class="' . $this->getAttribute('class') . '" onchange="'.'stepChange('.$this->value.', this.value );'.'"';
		$html[] = JHtml::_('select.genericlist', $this->getOptions(), $this->name, $input_options, 'value', 'text', $this->value);

		$html[] = '<input id="jform_is_step_modified" type="hidden" value="false" name="jform[is_step_modified]">';

		$html[] = '<a id="step_reason_btn" href="#stepModal" role="button" class="btn btn-mini hide" data-toggle="modal">Reason</a>';

		$html[] = '<!-- Step Modal -->';
		$html[] = '<div id="stepModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="stepModalLabel" aria-hidden="true">';
		$html[] = '	<div class="modal-header">';
		$html[] = '		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>';
		$html[] = '		<h3 id="stepModalLabel">Step Modification: Reason Description</h3>';
		$html[] = '	</div>';
		$html[] = '	<div class="modal-body">';
		$html[] = '		<p id="stepBody">';
		$html[] = '		<textarea style="width:98%;resize:none;" rows="6" cols="75" id="jform_step_modified_description" name="jform[step_modified_description]"></textarea>';
		$html[] = '		</p>';
		$html[] = '		<p>(if set on options, notifications will be sent on save)</p>';
		$html[] = '	</div>';
		$html[] = '	<div class="modal-footer">';
		$html[] = '		<button id="save_step_reason" class="btn btn-primary" data-dismiss="modal" aria-hidden="true">OK</button>';
		$html[] = '	</div>';
		$html[] = '</div>';
		return implode("\n", $html);
	}
}