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
            $data2['action'] = JText::_('COM_IMC_LOGS_ACTION_INITIAL_COMMIT');
            $data2['issueid'] = $model->getItem()->get('id');
            $data2['stepid'] = $validData['stepid'];
            $data2['description'] = JText::_('COM_IMC_LOGS_ACTION_INITIAL_COMMIT');
            $data2['created'] = $validData['created'];
            $data2['created_by'] = $validData['created_by'];
            $data2['updated'] = $validData['created'];
            $data2['language'] = $validData['language'];
            $data2['rules'] = $validData['rules'];
            
            // Get the event dispatcher.
            $dispatcher = JEventDispatcher::getInstance();

            // Load the finder plugin group.
            JPluginHelper::importPlugin('imc');
            try
            {
                // Trigger the event.
                $results = $dispatcher->trigger( 'onAfterNewIssueAdded', array( $model, $validData ) );

                // Check the returned results. This is for plugins that don't throw
                // exceptions when they encounter serious errors.
                if (in_array(false, $results))
                {
                    throw new Exception($dispatcher->getError(), 500);
                }
            }
            catch (Exception $e)
            {
                // Handle a caught exception.
                throw $e;
            }



            if (!$log->bind($data2))
            {
                JFactory::getApplication()->enqueueMessage('Cannot bind data to log table', 'error'); 
            }

            if (!$log->save($data2))
            {
                JFactory::getApplication()->enqueueMessage('Cannot save data to log table', 'error'); 
            }

        }
        else {

            //a. check for step modification
            if($validData['is_step_modified'] === 'true'){
                $user = JFactory::getUser();
                $log = JTable::getInstance('Log', 'ImcTable', array());

                $data2['state'] = 1;
                $data2['action'] = JText::_('COM_IMC_LOGS_ACTION_STEP_MODIFIED');
                $data2['issueid'] = $validData['id'];
                $data2['stepid'] = $validData['stepid'];
                $data2['description'] = $validData['step_modified_description'];
                $data2['created'] = $validData['updated'];
                $data2['created_by'] = $user->id;
                $data2['updated'] = $validData['updated'];
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

            //b. check for category modification
            if($validData['is_category_modified'] === 'true'){
                $user = JFactory::getUser();
                $log = JTable::getInstance('Log', 'ImcTable', array());

                $data2['state'] = 1;
                $data2['action'] = JText::_('COM_IMC_LOGS_ACTION_CATEGORY_MODIFIED');
                $data2['issueid'] = $validData['id'];
                $data2['stepid'] = $validData['stepid'];
                $data2['description'] = $validData['category_modified_description'];
                $data2['created'] = $validData['updated'];
                $data2['created_by'] = $user->id;
                $data2['updated'] = $validData['updated'];
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

        }   

        //B: move any images only if record is new
    	if($validData['id'] == 0) {
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
}