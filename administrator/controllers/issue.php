<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Issue controller class.
 */
class ImcControllerIssue extends JControllerForm
{

    function __construct() {
        $this->view_list = 'issues';
        parent::__construct();
    }
    
    //override postSaveHook to move any images
    public function postSaveHook($model, $validData)
    {
        //check if record is new
    	if($validData['id'] > 0)
    		return;

    	//check if any files uploaded
    	$obj = json_decode( $validData['photo'] );
		if(empty($obj->files))
			return;

        $srcDir = JPATH_ROOT . '/' . $obj->imagedir . '/' . $obj->id;
        $dstDir = JPATH_ROOT . '/' . $obj->imagedir . '/' . $model->getItem()->get('id');

		$success = rename ( $srcDir , $dstDir );

		if(!$success){
			JFactory::getApplication()->enqueueMessage('Cannot move '.$srcDir.' to '.$dstDir.'. Check folder rights', 'error');	
		}
		
    }
}