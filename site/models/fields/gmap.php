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
class JFormFieldGmap extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'Gmap';

	/**
	* @var string
	* relative to joomla root (e.g. images/imc)
	*/
	protected $imagedir;

	/**
	* @var string
	* instead of JRequest::getVar('id') use the userstate session (e.g. com_imc.edit.issue.id)
	* mainly used on front-end edit forms
	*/
	protected $userstate;

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
			case 'imagedir':
			case 'userstate':
			case 'side':
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
			case 'imagedir':
			case 'userstate':
			case 'side':
				$this->$name = (string) $value;
				break;
			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{
		JFactory::getDocument()->addStyleSheet(JURI::root(true).'/components/com_imc/models/fields/gmap/css/gmap.css');
		
		//$api_key = (isset($this->element['api_key']) ? $this->element['api_key'] : '');
		$params = JComponentHelper::getParams('com_imc');
		$api_key = $params->get('api_key');
		if($api_key == '')
			return '<strong>Google Maps API KEY missing</strong>';
		JFactory::getDocument()->addScript('https://maps.googleapis.com/maps/api/js?key='.$api_key);
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/gmap/js/gmap.js');

		//get google maps options
		$lat        = $params->get('latitude');
		$lng        = $params->get('longitude');
		$zoom 	    = $params->get('zoom');
		$language   = $params->get('maplanguage');
		$hiddenterm = $params->get('hiddenterm');

		//set js variables
		$script = array();
		$script[] = "var Lat=".$lat.";";
		$script[] = "var Lng=".$lng.";";
		$script[] = "var zoom=".$zoom.";";
		$script[] = "var language='".$language."';";
		$script[] = "var hiddenterm='".$hiddenterm."';";
		$script[] = "var info='".JText::_('COM_IMC_DRAG_MARKER')."';";
		$script[] = "var info_unlock='".JText::_('COM_IMC_UNLOCK_ADDRESS')."';";
		$script[] = "var notfound='".JText::_('COM_IMC_ADDRESS_NOT_FOUND')."';";
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));


		//initialize map
		$script = array();
		$script[] = "google.maps.event.addDomListener(window, 'load', initialize);";
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		//set html
		$html = array();
        $html[] = '<div id="map-canvas"></div>';
        $html[] = '<br />';
        $html[] = '<div class="row-fluid">';
        $html[] = '	<div class="span1" style="text-align: right;">';
		$html[] = '		<button id="locateposition" class="btn btn-mini" type="button"><i class="icon-home"></i></button><br /><br />';
		$html[] = '		<button id="searchaddress" class="btn btn-mini" type="button"><i class="icon-search icon-white"></i></button>';
		$html[] = '	</div>';
        $html[] = '	<div class="span10">';
		$html[] = '		<textarea style="width:100%;resize:none;" rows="3" cols="75" id="' . $this->id . '" name="' . $this->name . '">'.htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8').'</textarea>';
		$html[] = '	</div>';
        $html[] = '	<div class="span1" style="text-align: right;">';
		$html[] = '		<button id="lockaddress" class="btn btn-mini" type="button"><i class="icon-lock"></i></button><br /><br /><br />';
		$html[] = '	</div>';
		$html[] = '</div>';		

		//$html[] = '	<input type="text" name="' . $this->name . '" id="' . $this->id . '" value="'
		//	. htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '" readonly="readonly" />';


		return implode("\n", $html);
	}
}