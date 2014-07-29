<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
error_reporting(E_ALL | E_STRICT);
// No direct access
defined('_JEXEC') or die;
JLoader::register('UploadHandler', JPATH_COMPONENT_ADMINISTRATOR . '/models/fields/server/php/UploadHandler.php');

jimport('joomla.application.component.controllerform');

/**
 * Issue controller class.
 */
class ImcControllerUpload extends JControllerForm /*ImcController*/
{
	
	
	public function handler()
	{
    	//JFactory::getDocument()->setMimeEncoding( 'application/json' );
	    //JResponse::setHeader('Content-Disposition','attachment;filename="test.json"');

	    //require(JPATH_COMPONENT_ADMINISTRATOR . '/models/fields/server/php/UploadHandler.php');
		//should send somehow the upload_dir and upload_url... ()
		$options = array(
		            'script_url' => JURI::root(true).'/administrator/index.php?option=com_imc&task=upload.handler&format=json',
		            'upload_dir' => JPATH_COMPONENT_ADMINISTRATOR . '/models/fields/server/php/files4/',
		            'upload_url' => 'http://localhost/joomla3/administrator/components/com_imc/models/fields/server/php/files4/'
		        );
		$upload_handler = new UploadHandler($options);
		//JFactory::getApplication()->close(); // or jexit();
	}

}
