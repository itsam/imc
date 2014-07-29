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
//JLoader::register('UploadHandler', JPATH_COMPONENT_ADMINISTRATOR . '/models/fields/server/php/UploadHandler.php');

jimport('joomla.application.component.controllerform');

/**
 * Issue controller class.
 */
class ImcControllerIssue extends JControllerLegacy /*ImcController*/
{
	
	
	public function handler()
	{

	    //JFactory::getDocument()->setMimeEncoding( 'application/json' );
	    //JResponse::setHeader('Content-Disposition','attachment;filename="test.json"');


	    require(JPATH_COMPONENT_ADMINISTRATOR . '/models/fields/server/php/UploadHandler.php');
		//should send somehow the upload_dir and upload_url... ()
		$options = array(
		            'upload_dir' => JPATH_COMPONENT_ADMINISTRATOR . '/models/fields/server/php/files3/',
		            'upload_url' => 'http://localhost/joomla3b/administrator/components/com_imc/models/fields/server/php/files3/'
		        );
		$upload_handler = new UploadHandler($options);
//print_r($_REQUEST);

/*	    $data = array(
	        'foo' => JPATH_COMPONENT_ADMINISTRATOR . '/models/fields/server/php/UploadHandler.php'
	    );
	    echo json_encode( $data );
*/	    
		//JFactory::getApplication()->close(); // or jexit();
	}

}
