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
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{
		JFactory::getDocument()->addStyleSheet('http://blueimp.github.io/Gallery/css/blueimp-gallery.min.css');
		JFactory::getDocument()->addStyleSheet(JURI::root(true).'/administrator/components/com_imc/models/fields/css/jquery.fileupload.css');
		JFactory::getDocument()->addStyleSheet(JURI::root(true).'/administrator/components/com_imc/models/fields/css/jquery.fileupload-ui.css');

		$script = array();
		$script[] = '<!-- The template to display files available for upload -->';
		$script[] = '<script id="template-upload" type="text/x-tmpl">';
		$script[] = '{% for (var i=0, file; file=o.files[i]; i++) { %}';
		$script[] = '    <tr class="template-upload fade">';
		$script[] = '        <td>';
		$script[] = '            <span class="preview"></span>';
		$script[] = '        </td>';
		$script[] = '        <td>';
		$script[] = '            <p class="name">{%=file.name%}</p>';
		$script[] = '            <strong class="error text-danger"></strong>';
		$script[] = '        </td>';
		$script[] = '        <td>';
		$script[] = '            <p class="size">Processing...</p>';
		$script[] = '            <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>';
		$script[] = '        </td>';
		$script[] = '        <td>';
		$script[] = '            {% if (!i && !o.options.autoUpload) { %}';
		$script[] = '                <button class="btn btn-primary start" disabled>';
		$script[] = '                    <i class="glyphicon glyphicon-upload"></i>';
		$script[] = '                    <span>Start</span>';
		$script[] = '                </button>';
		$script[] = '            {% } %}';
		$script[] = '            {% if (!i) { %}';
		$script[] = '                <button class="btn btn-warning cancel">';
		$script[] = '                    <i class="glyphicon glyphicon-ban-circle"></i>';
		$script[] = '                    <span>Cancel</span>';
		$script[] = '                </button>';
		$script[] = '            {% } %}';
		$script[] = '        </td>';
		$script[] = '    </tr>';
		$script[] = '{% } %}';
		$script[] = '</script>		';



		//JFactory::getDocument()->addScriptDeclaration(implode("\n", $script), 'text/x-tmpl');

		$script2 = array();
		$script2[] = '<script id="template-download" type="text/x-tmpl">';
		$script2[] = '{% for (var i=0, file; file=o.files[i]; i++) { %}';
		$script2[] = '    <tr class="template-download fade">';
		$script2[] = '        <td>';
		$script2[] = '            <span class="preview">';
		$script2[] = '                {% if (file.thumbnailUrl) { %}';
		$script2[] = '                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>';
		$script2[] = '                {% } %}';
		$script2[] = '            </span>';
		$script2[] = '        </td>';
		$script2[] = '        <td>';
		$script2[] = '            <p class="name">';
		$script2[] = '                {% if (file.url) { %}';
		$script2[] = '                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?\'data-gallery\':\'\'%}>{%=file.name%}</a>';
		$script2[] = '                {% } else { %}';
		$script2[] = '                    <span>{%=file.name%}</span>';
		$script2[] = '                {% } %}';
		$script2[] = '            </p>';
		$script2[] = '            {% if (file.error) { %}';
		$script2[] = '                <div><span class="label label-danger">Error</span> {%=file.error%}</div>';
		$script2[] = '            {% } %}';
		$script2[] = '        </td>';
		$script2[] = '        <td>';
		$script2[] = '            <span class="size">{%=o.formatFileSize(file.size)%}</span>';
		$script2[] = '        </td>';
		$script2[] = '        <td>';
		$script2[] = '            {% if (file.deleteUrl) { %}';
		$script2[] = '                <button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields=\'{"withCredentials":true}\'{% } %}>';
		$script2[] = '                    <i class="glyphicon glyphicon-trash"></i>';
		$script2[] = '                    <span>Delete</span>';
		$script2[] = '                </button>';
		$script2[] = '                <input type="checkbox" name="delete" value="1" class="toggle">';
		$script2[] = '            {% } else { %}';
		$script2[] = '                <button class="btn btn-warning cancel">';
		$script2[] = '                    <i class="glyphicon glyphicon-ban-circle"></i>';
		$script2[] = '                    <span>Cancel</span>';
		$script2[] = '                </button>';
		$script2[] = '            {% } %}';
		$script2[] = '        </td>';
		$script2[] = '    </tr>';
		$script2[] = '{% } %}';
		$script2[] = '</script>		';


		
		//JFactory::getDocument()->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js');		
		JFactory::getDocument()->addScript(JURI::root(true).'/administrator/components/com_imc/models/fields/js/vendor/jquery.ui.widget.js');		
		JFactory::getDocument()->addScript('http://blueimp.github.io/JavaScript-Templates/js/tmpl.min.js');		
		JFactory::getDocument()->addScript('http://blueimp.github.io/JavaScript-Load-Image/js/load-image.min.js');		
		JFactory::getDocument()->addScript('http://blueimp.github.io/JavaScript-Canvas-to-Blob/js/canvas-to-blob.min.js');		
		JFactory::getDocument()->addScript('http://blueimp.github.io/Gallery/js/jquery.blueimp-gallery.min.js');		
		JFactory::getDocument()->addScript(JURI::root(true).'/administrator/components/com_imc/models/fields/js/jquery.iframe-transport.js');		
		JFactory::getDocument()->addScript(JURI::root(true).'/administrator/components/com_imc/models/fields/js/jquery.fileupload.js');		
		JFactory::getDocument()->addScript(JURI::root(true).'/administrator/components/com_imc/models/fields/js/jquery.fileupload-process.js');		
		JFactory::getDocument()->addScript(JURI::root(true).'/administrator/components/com_imc/models/fields/js/jquery.fileupload-image.js');		
		JFactory::getDocument()->addScript(JURI::root(true).'/administrator/components/com_imc/models/fields/js/jquery.fileupload-validate.js');		
		JFactory::getDocument()->addScript(JURI::root(true).'/administrator/components/com_imc/models/fields/js/jquery.fileupload-ui.js');		
		JFactory::getDocument()->addScript(JURI::root(true).'/administrator/components/com_imc/models/fields/js/main.js');		

		/*$foo = array();
		$foo[] = 'if(window.attachEvent) {';
		$foo[] = '    window.attachEvent(\'onload\', init);';
		$foo[] = '} else {';
		$foo[] = '    if(window.onload) {';
		$foo[] = '        var curronload = window.onload;';
		$foo[] = '        var newonload = function() {';
		$foo[] = '            curronload();';
		$foo[] = '            init();';
		$foo[] = '        };';
		$foo[] = '        window.onload = newonload;';
		$foo[] = '    } else {';
		$foo[] = '        window.onload = init;';
		$foo[] = '    }';
		$foo[] = '}';*/

		$init = array();
		$init[] = 'jQuery(document).ready(function() {';
		$init[] = '	init();';
		$init[] = '});';


		JFactory::getDocument()->addScriptDeclaration( implode("\n", $init));

		$html = array();
        $html[] = '<div class="row fileupload-buttonbar">';
        $html[] = '    <div class="col-lg-7">';
        $html[] = '        <!-- The fileinput-button span is used to style the file input field as button -->';
        $html[] = '        <span class="btn btn-success fileinput-button">';
        $html[] = '            <i class="glyphicon glyphicon-plus"></i>';
        $html[] = '            <span>Add files...</span>';
        $html[] = '            <input type="file" name="files[]" multiple>';
        $html[] = '        </span>';
        $html[] = '        <button type="submit" class="btn btn-primary start">';
        $html[] = '            <i class="glyphicon glyphicon-upload"></i>';
        $html[] = '            <span>Start upload</span>';
        $html[] = '        </button>';
        $html[] = '        <button type="reset" class="btn btn-warning cancel">';
        $html[] = '            <i class="glyphicon glyphicon-ban-circle"></i>';
        $html[] = '            <span>Cancel upload</span>';
        $html[] = '        </button>';
        $html[] = '        <button type="button" class="btn btn-danger delete">';
        $html[] = '            <i class="glyphicon glyphicon-trash"></i>';
        $html[] = '            <span>Delete</span>';
        $html[] = '        </button>';
        $html[] = '        <input type="checkbox" class="toggle">';
        $html[] = '        <!-- The global file processing state -->';
        $html[] = '        <span class="fileupload-process"></span>';
        $html[] = '    </div>';
        $html[] = '    <!-- The global progress state -->';
        $html[] = '    <div class="col-lg-5 fileupload-progress fade">';
        $html[] = '        <!-- The global progress bar -->';
        $html[] = '        <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">';
        $html[] = '            <div class="progress-bar progress-bar-success" style="width:0%;"></div>';
        $html[] = '        </div>';
        $html[] = '        <!-- The extended global progress state -->';
        $html[] = '        <div class="progress-extended">&nbsp;</div>';
        $html[] = '    </div>';
        $html[] = '</div>';
        $html[] = '<!-- The table listing the files available for upload/download -->';
        $html[] = '<table role="presentation" class="table table-striped"><tbody class="files"></tbody></table>';

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


		JHtml::_('jquery.framework');
		JHtml::_('script', 'system/html5fallback.js', false, true);

		return implode("\n", $html);
	}
}