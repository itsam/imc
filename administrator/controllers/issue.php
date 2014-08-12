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
    
    //override postSaveHook
    protected function postSaveHook(JModelLegacy $model, $validData = array())
    {

        //A: inform log table about the new issue
        if($validData['id'] == 0){
        
            $log = JTable::getInstance('Log', 'ImcTable', array());

            $data2['state'] = 1;
            $data2['issueid'] = $model->getItem()->get('id');
            $data2['stepid'] = $validData['stepid'];
            $data2['description'] = 'Issue created';
            $data2['created'] = $validData['created'];
            $data2['created_by'] = $validData['created_by'];
            $data2['updated'] = $validData['created'];
            $data2['language'] = $validData['language'];
            $data2['rules'] = $validData['rules'];

            if (!$log->bind($data2))
            {
                JFactory::getApplication()->enqueueMessage('Cannot bind data to log table', 'error'); 
            }

            if (!$log->save($data2))
            {
                JFactory::getApplication()->enqueueMessage('Cannot save data to log table', 'error'); 
            }


        }

        //B: move any images only if record is new
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