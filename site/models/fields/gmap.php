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
	* the field that holds the latitude
	*/
	protected $latitudefield;

	/**
	* @var string
	* the field that holds the longitude
	*/
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
		JFactory::getDocument()->addStyleSheet(JURI::root(true).'/components/com_imc/models/fields/gmap/css/gmap.css');
		
		//(isset($this->element['api_key']) ? $this->element['api_key'] : '');
		if(!isset($this->element['latitudefield']))
			return '<strong>GMap field argument `latitudefield` is not set</strong>';
		if(!isset($this->element['longitudefield']))
			return '<strong>GMap field argument `longitudefield` is not set</strong>';

		$params = JComponentHelper::getParams('com_imc');
		$api_key = $params->get('api_key');
		if($api_key == '')
			return '<strong>Google Maps API KEY missing</strong>';
		JFactory::getDocument()->addScript('https://maps.googleapis.com/maps/api/js?key='.$api_key);
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/gmap/js/gmap.js');

		//get google maps default options if no value is set (e.g. new record)
		$lat        = $params->get('latitude');
		$lng        = $params->get('longitude');

		$zoom 	    = $params->get('zoom');
		$language   = $params->get('maplanguage');
		$hiddenterm = $params->get('hiddenterm');

		//set js variables
		$script = array();
		$script[] = "var disabled=".$disabled.";";
		$script[] = "var Lat=".$lat.";";
		$script[] = "var Lng=".$lng.";";
		$script[] = "var latfield='jform_".$this->element['latitudefield']."';";
		$script[] = "var lngfield='jform_".$this->element['longitudefield']."';";
		$script[] = "var addrfield='".$this->id."';";
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

		//style
		$style = array();
		$style[] = (isset($this->element['width']) ? 'width:'.$this->element['width'].';' : '');
		$style[] = (isset($this->element['height']) ? 'height:'.$this->element['height'].';' : '');



		//set html
		$html = array();
        $html[] = '<div style="'.implode("", $style).'">';
        $html[] = '	<div id="imc-map-canvas"></div>';
        $html[] = '	<br />';
        $html[] = '	<div class="row-fluid">';
        if(!$disabled) {
        $html[] = '		<div class="span1">';
		$html[] = '			<button id="locateposition" class="btn btn-mini" type="button"><i class="icon-home"></i></button><br /><br />';
		$html[] = '			<button id="searchaddress" class="btn btn-mini" type="button"><i class="icon-search icon-white"></i></button>';
		$html[] = '		</div>';
		}
        $html[] = '		<div class="'. ($disabled ? "span12" : "span10").'">';
		$html[] = '			<textarea '. ($disabled ? "disabled=\"\"" : "").' class="imc-gmap-textarea" rows="3" cols="75" id="' . $this->id . '" name="' . $this->name . '">'.htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8').'</textarea>';
		$html[] = '		</div>';
		if(!$disabled) {
        $html[] = '		<div class="span1 imc-text-right">';
		$html[] = '			<button id="lockaddress" class="btn btn-mini" type="button"><i class="icon-lock"></i></button><br /><br /><br />';
		$html[] = '		</div>';
		}
		$html[] = '	</div>';		
		$html[] = '</div>';		
	 
		$html[] = '<!-- Modal -->';
		$html[] = '<div id="searchModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="searchModalLabel" aria-hidden="true">';
		$html[] = '	<div class="modal-header">';
		$html[] = '		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>';
		$html[] = '		<h3 id="searchModalLabel">Search Results</h3>';
		$html[] = '	</div>';
		$html[] = '	<div class="modal-body">';
		$html[] = '		<p id="searchBody">One fine body…</p>';
		$html[] = '	</div>';
		$html[] = '	<div class="modal-footer">';
		$html[] = '		<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>';
		$html[] = '	</div>';
		$html[] = '</div>';

		return implode("\n", $html);
	}
}