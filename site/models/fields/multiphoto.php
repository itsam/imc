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
class JFormFieldMultiphoto extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'Multiphoto';

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
		$isSite = false;
		$isSite = JFactory::getApplication()->isSite();
		$pdfIcon = JURI::root(true).'/components/com_imc/models/fields/multiphoto/img/pdf.png';

		$imagedir = (isset($this->element['imagedir']) ? $this->element['imagedir'] : 'images/imc');
		$itemId   = (isset($this->element['userstate']) ? JFactory::getApplication()->getUserState($this->element['userstate']) : JRequest::getVar('id', 0));
		$isNew    = 0;
		//if isnew set timestamp as id
		if($itemId == 0){
			$itemId = time();
			$isNew = 1;
		}

		JFactory::getDocument()->addStyleSheet(JURI::root(true).'/components/com_imc/models/fields/multiphoto/css/blueimp-gallery.min.css');
		JFactory::getDocument()->addStyleSheet(JURI::root(true).'/components/com_imc/models/fields/multiphoto/css/imc-style.css');
		JFactory::getDocument()->addStyleSheet(JURI::root(true).'/components/com_imc/models/fields/multiphoto/css/jquery.fileupload.css');
		JFactory::getDocument()->addStyleSheet(JURI::root(true).'/components/com_imc/models/fields/multiphoto/css/jquery.fileupload-ui.css');

		$script = array();
		$script[] = '<!-- The template to display files available for upload -->';
		$script[] = '<script id="template-upload" type="text/x-tmpl">';
		$script[] = '{% for (var i=0, file; file=o.files[i]; i++) { %}';
		$script[] = '    <div class="row template-upload align-items-center mb-1">';
		$script[] = '        <div class="col-sm-4 span4">';
		$script[] = '            <span class="preview"></span>';
		$script[] = '        </div>';
		$script[] = '        <div class="col-sm-4 span4">';
		$script[] = '            <p class="name">{%=file.name%}</p>';
		$script[] = '            <strong class="error text-danger"></strong>';
		$script[] = '        </div>';
		$script[] = '        <div class="col-sm-4 span4">';
		$script[] = '            <p class="size">Processing...</p>';
		$script[] = '            <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>';
		$script[] = '        </div>';
		$script[] = '        <div class="col-sm-4 span4">';
		$script[] = '            {% if (!i && !o.options.autoUpload) { %}';
		$script[] = '                <button class="btn btn-primary start" disabled>';
		$script[] = '                    <i class="icon-upload"></i>';
		$script[] = '                    <span>'.JText::_('COM_IMC_JFIELD_MULTIPHOTO_START').'</span>';
		$script[] = '                </button>';
		$script[] = '            {% } %}';
		$script[] = '            {% if (!i) { %}';
		$script[] = '                <button class="btn btn-warning btn-sm cancel">';
		$script[] = '                    <i class="icon-remove"></i>';
		$script[] = '                    <span>'.JText::_('JCANCEL').'</span>';
		$script[] = '                </button>';
		$script[] = '            {% } %}';
		$script[] = '        </div>';
		$script[] = '    </div>';
		$script[] = '{% } %}';
		$script[] = '</script>';

		$script2 = array();
		$script2[] = '<script id="template-download" type="text/x-tmpl">';
		$script2[] = '{% for (var i=0, file; file=o.files[i]; i++) { %}';
		$script2[] = '    <div class="row template-download align-items-center mb-1">';
		$script2[] = '        <div class="col-sm-4 span4">';
		$script2[] = '            <span class="preview">';
		$script2[] = '                {% if (file.thumbnailUrl) { %}';
		$script2[] = '                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>';
		$script2[] = '                {% } else { %}';
		$script2[] = '                	<img src="'.$pdfIcon.'" alt="" />';
		$script2[] = '                {% } %}';
		$script2[] = '            </span>';
		$script2[] = '        </div>';
		$script2[] = '        <div class="col-sm-4 span4">';
		$script2[] = '            <span class="name">';
		$script2[] = '                {% if (file.url) { %}';
		$script2[] = '                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?\'data-gallery\':\'\'%}>{%=file.name%}</a>';
		$script2[] = '            			(<span class="size">{%=o.formatFileSize(file.size)%}</span>)';
		$script2[] = '                {% } else { %}';
		$script2[] = '                    <span>{%=file.name%}</span>';
		$script2[] = '                {% } %}';
		$script2[] = '            </span>';
		$script2[] = '            {% if (file.error) { %}';
		$script2[] = '                <div><span class="label label-danger">Error</span> {%=file.error%}</div>';
		$script2[] = '            {% } %}';
		$script2[] = '        </div>';
		$script2[] = '        <div class="col-sm-4 span4">';
		$script2[] = '            {% if (file.deleteUrl) { %}';
		$script2[] = '                <button class="btn btn-danger btn-sm delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields=\'{"withCredentials":true}\'{% } %}>';
		$script2[] =                     JText::_('JACTION_DELETE');
		$script2[] = '                </button>';
		$script2[] = '            {% } else { %}';
		$script2[] = '                <button class="btn btn-warning btn-sm cancel">';
		$script2[] = 					  JText::_('JCANCEL');
		$script2[] = '                </button>';
		$script2[] = '            {% } %}';
		$script2[] = '        </div>';
		$script2[] = '    </div>';
		$script2[] = '{% } %}';
		$script2[] = '</script>';

		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/vendor/jquery.ui.widget.js');
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/tmpl.min.js');
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/load-image.min.js');
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/canvas-to-blob.min.js');
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/jquery.blueimp-gallery.min.js');
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/jquery.iframe-transport.js');
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/jquery.fileupload.js');
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/jquery.fileupload-process.js');
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/jquery.fileupload-image.js');
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/jquery.fileupload-validate.js');
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/jquery.fileupload-ui.js');
		JFactory::getDocument()->addScript(JURI::root(true).'/components/com_imc/models/fields/multiphoto/js/multiphoto.js');

		$url = JRoute::_(  JURI::root(true)."/administrator/index.php?option=com_imc&task=upload.handler&format=json&id=".$itemId."&imagedir=".$imagedir."&".JSession::getFormToken()."=1" );
		//change controller url if accessed from backend to work with secure JSession token
		if($this->element['side'] == 'frontend'){
			$url = JRoute::_(  JURI::root(true)."/index.php?option=com_imc&task=upload.handler&format=json&id=".$itemId."&imagedir=".$imagedir."&".JSession::getFormToken()."=1" );
		}


		//TODO:  get `com_imc` as field argument `component`
		$init = array();
		$init[] = "function init() {";
		$init[] = "	   var form_id = jQuery('#".$this->id."').closest('form').attr('id');";
		$init[] = "    'use strict';";
		$init[] = "    // Initialize the jQuery File Upload widget:";
		$init[] = "    jQuery('#'+form_id).fileupload({";
		$init[] = "        autoUpload: true,";
		$init[] = "        // Uncomment the following to send cross-domain cookies:";
		$init[] = "        xhrFields: {withCredentials: true},";
		$init[] = "        url: '".$url."' ";
		//$init[] = "    }).bind('fileuploaddone',    function(e,data){onDone(data.result.files,".$this->id." )}).";
		$init[] = "    }).bind('fileuploaddone',    function(e,data){onDone(data.result.files,jQuery('#".$this->id."').attr('id') )}).";
		$init[] = "       bind('fileuploaddestroy', function(e,data){if (!window.confirm('".JText::_('COM_IMC_JFIELD_MULTIPHOTO_CONFIRM_DELETE')."')) {return false;}onDestroy(data.url.substring(data.url.indexOf('file=') + 5),jQuery('#".$this->id."').attr('id')  )}).";
		$init[] = "       bind('fileuploadadd',     function(e,data){jQuery('input[name=\"task\"]').val('upload.handler');});";
		$init[] = "    // Enable iframe cross-domain access via redirect option:";
		$init[] = "    jQuery('#'+form_id).fileupload(";
		$init[] = "        'option',";
		$init[] = "        'redirect',";
		$init[] = "        window.location.href.replace(";
		$init[] = "            /\/[^\/]*$/,";
		$init[] = "            '/cors/result.html?%s'";
		$init[] = "        )";
		$init[] = "    );";
		$init[] = "    jQuery('#'+form_id).addClass('fileupload-processing');";
		$init[] = "    jQuery.ajax({";
		$init[] = "        // Uncomment the following to send cross-domain cookies:";
		$init[] = "        //xhrFields: {withCredentials: true},";
		$init[] = "        url: jQuery('#'+form_id).fileupload('option', 'url'),";
		$init[] = "        dataType: 'json',";
		$init[] = "        context: jQuery('#'+form_id)[0]";
		$init[] = "    }).always(function () {";
		$init[] = "        jQuery(this).removeClass('fileupload-processing');";
		$init[] = "    }).done(function (result) {";
		//$init[] = "        if(result) onInit(result.files,".$this->id.",".$itemId.",".$isNew.",'".$imagedir."');";
		$init[] = "        if(result) onInit(result.files,jQuery('#".$this->id."').attr('id'),".$itemId.",".$isNew.",'".$imagedir."');";
		$init[] = "        jQuery(this).fileupload('option', 'done')";
		$init[] = "            .call(this, jQuery.Event('done'), {result: result});";
		$init[] = "    });";
		$init[] = "};";

		$init[] = 'jQuery(document).ready(function() {';
		$init[] = '	init();';
		$init[] = '});';

		JFactory::getDocument()->addScriptDeclaration( implode("\n", $init));

		$html = array();
		$html[] = '<div class="row'.($isSite ? '' : '-fluid').' fileupload-buttonbar">';
		$html[] = '    <div class="col-7 span7">';
		$html[] = '        <!-- The fileinput-button span is used to style the file input field as button -->';
		$html[] = '        <span class="btn btn-success fileinput-button">';
		$html[] = '            <i class="icon-plus"></i>';
		$html[] = '            <span>'.JText::_('COM_IMC_JFIELD_MULTIPHOTO_ADD_FILES').'</span>';
		$html[] = '            <input type="file" name="files[]" multiple>';
		$html[] = '        </span>';
		$html[] = '    </div>';
		$html[] = '    <!-- The global progress state -->';
		$html[] = '    <div class="col-5 span5 fileupload-progress fade">';
		$html[] = '        <!-- The global progress bar -->';
		$html[] = '        <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">';
		$html[] = '            <div class="progress-bar progress-bar-success" style="width:0%;"></div>';
		$html[] = '        </div>';
		$html[] = '        <!-- The extended global progress state -->';
		$html[] = '        <div class="progress-extended">&nbsp;</div>';
		$html[] = '    </div>';
		$html[] = '</div>';
		$html[] = '<!-- Listing the files available for upload/download -->';
		$html[] = '<div class="row'.($isSite ? '' : '-fluid').'">';
		$html[] = '	<div class="col-sm-12 span12">';		
		$html[] = '		<div class="drop-photos"><span class="dptitle">'.JText::_('COM_IMC_JFIELD_MULTIPHOTO_DROP_FILES').'</span>';
		$html[] = '			<div class="files"></div>';
		$html[] = '		</div>';
		$html[] = '	</div>';
		$html[] = '</div>';

		$html[] = implode("\n", $script);
		$html[] = implode("\n", $script2);

		$html[] = '<!-- The blueimp Gallery widget -->';
		$html[] = '<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls" data-filter=":even">';
		$html[] = '    <div class="slides"></div>';
		$html[] = '    <h3 class="title"></h3>';
		$html[] = '    <a class="prev">‹</a>';
		$html[] = '    <a class="next">›</a>';
		$html[] = '    <a class="close">×</a>';
		$html[] = '    <a class="play-pause"></a>';
		$html[] = '    <ol class="indicator"></ol>';
		$html[] = '</div>';

		$attr = '';
		$html[] = '	<input type=hidden name="' . $this->name . '" id="' . $this->id . '" value="'. htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '" readonly="readonly"' . $attr . ' />';

		return implode("\n", $html);
	}
}