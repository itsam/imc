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
require_once JPATH_COMPONENT_SITE . '/helpers/imc.php';
JPluginHelper::importPlugin('imc');
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

            $catTitle = ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid']);

            $data2['id'] = 0;
            $data2['state'] = 1;
            $data2['action'] = 'step'; //enum(step|category)
            $data2['issueid'] = $model->getItem()->get('id');
            $data2['stepid'] = $validData['stepid'];
            $data2['description'] = JText::_('COM_IMC_LOGS_ACTION_INITIAL_COMMIT') . ' ' . JText::_('COM_IMC_LOGS_AT_CATEGORY') . ' ' . $catTitle;
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


            try
            {
                $dispatcher = JEventDispatcher::getInstance();
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


        }
        else {

            //a. check for step modification
            if(isset($validData['is_step_modified']) && $validData['is_step_modified'] === 'true'){
                $user = JFactory::getUser();
                $log = JTable::getInstance('Log', 'ImcTable', array());

                $data2['id'] = 0;
                $data2['state'] = 1;
                $data2['action'] = 'step'; //enum(step|category)
                $data2['issueid'] = $validData['id'];
                $data2['stepid'] = $validData['stepid'];
                $data2['description'] = $validData['step_modified_description'];
                $data2['created'] = $validData['updated'];
                $data2['created_by'] = $user->id;
                $data2['updated'] = $validData['updated'];
                $data2['language'] = $validData['language'];
                $data2['rules'] = $validData['rules'];
                $data2['catid'] = $validData['catid'];

                if (!$log->bind($data2))
                {
                    JFactory::getApplication()->enqueueMessage('Cannot bind data to log table', 'error'); 
                }

                if (!$log->save($data2))
                {
                    JFactory::getApplication()->enqueueMessage('Cannot save data to log table', 'error'); 
                }
                
                $dispatcher = JEventDispatcher::getInstance();
                $dispatcher->trigger( 'onAfterStepModified', array( $model, $validData ) );
            }

            //b. check for category modification
            if(isset($validData['is_category_modified']) && $validData['is_category_modified'] === 'true'){
                $user = JFactory::getUser();
                $log = JTable::getInstance('Log', 'ImcTable', array());

                $data2['id'] = 0;
                $data2['state'] = 1;
                $data2['action'] = 'category'; //enum(step|category)
                $data2['issueid'] = $validData['id'];
                $data2['stepid'] = $validData['stepid'];
                $data2['description'] = $validData['category_modified_description'];
                $data2['created'] = $validData['updated'];
                $data2['created_by'] = $user->id;
                $data2['updated'] = $validData['updated'];
                $data2['language'] = $validData['language'];
                $data2['rules'] = $validData['rules'];
                $data2['catid'] = $validData['catid'];

                if (!$log->bind($data2))
                {
                    JFactory::getApplication()->enqueueMessage('Cannot bind data to log table', 'error'); 
                }

                if (!$log->save($data2))
                {
                    JFactory::getApplication()->enqueueMessage('Cannot save data to log table', 'error'); 
                }

                $dispatcher = JEventDispatcher::getInstance();
                $dispatcher->trigger( 'onAfterCategoryModified', array( $model, $validData ) );                
            }

	        //c. check for moderation modification
	        if(isset($validData['is_moderation_modified']) && $validData['is_moderation_modified'] === 'true'){
		        $dispatcher = JEventDispatcher::getInstance();
		        $dispatcher->trigger( 'onAfterModerationModified', array( $model, $validData ) );
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

            if($success){
                //update photo json isnew, id
                unset($obj->isnew);
                $obj->id = $model->getItem()->get('id');
                $photo = json_encode($obj);

                // Create an object for the record we are going to update.
                $object = new stdClass();
                $object->id = $model->getItem()->get('id');
                $object->photo = $photo;
                // Update photo
                $result = JFactory::getDbo()->updateObject('#__imc_issues', $object, 'id');

            }
            else {
                JFactory::getApplication()->enqueueMessage('Cannot move '.$srcDir.' to '.$dstDir.'. Check folder rights', 'error'); 
            }

        }
        
        
        
        

        // Calculate "step_days_diff" column value in logs table
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select(array('a.id AS issue_id', 'a.stepid AS issue_step_id', 'a.catid', 'a.title', 'b.id AS log_id', 'b.stepid AS log_step_id', 'b.description AS log_description', 'b.action', 'b.created AS log_created'));
        $query->from($db->quoteName('#__imc_issues', 'a'));
        $query->join('INNER', $db->quoteName('#__imc_log', 'b').' ON ('.$db->quoteName('a.id').' = '.$db->quoteName('b.issueid').')');
        $query->where($db->quoteName('a.id')." = ".$db->quote($validData['id']));
        $query->where($db->quoteName('a.state')." = ".$db->quote('1'));
        $query->where($db->quoteName('b.action')." = ".$db->quote('step'));
        $query->order($db->quoteName('b.id').' ASC');

        $db->setQuery($query);
        $results =  $db->loadAssocList();

        //echo($query->__toString());


        if(1 < count($results)) {
            $stepDaysDiff = 0;

            for($i=0; $i<count($results); $i++) {
                if($results[$i]['issue_step_id'] == $results[$i]['log_step_id']) {
                    $datePrev = new DateTime(date('Y-m-d H:i:s', strtotime($results[($i-1)]['log_created'])));
                    $dateNext = new DateTime(date('Y-m-d H:i:s', strtotime($results[$i]['log_created'])));

                    $stepDaysDiff =  $datePrev->diff($dateNext)->days;
                    echo $results[$i]['log_id'].' - '.$stepDaysDiff;
                    
                    $query = $db->getQuery(true);
                    
                    // Fields to update.
                    $fields = array(
                        $db->quoteName('step_days_diff') . ' = ' . $db->quote($stepDaysDiff),
                    );
                    
                    // Conditions for which records should be updated.
                    $conditions = array(
                        $db->quoteName('id') . ' = ' . $db->quote($results[$i]['log_id'])
                    );
                    
                    $query->update($db->quoteName('#__imc_log'))->set($fields)->where($conditions);
                    
                    $db->setQuery($query);
                    $result = $db->execute();

                }
            }
        }

        // echo '<br>';
        // echo '<pre>';
        // print_r($results);
        // echo '</pre>';
        // die;
        
    }

    /*
    public function printIssue($pk = null)
    {
        // Get the input
        $input = JFactory::getApplication()->input;
        $issueid = $input->get('id', 0);
        $model = $this->getModel();
        $model->setState('printid', $issueid);

        $v = $this->getView('issue', 'print');              //view.print.php
        $v->setModel($model, true);                         //load issue model
        $v->setModel($this->getModel('Logs', 'ImcModel'));  //load logs as well
        $v->display('print');                               //default_print   

        // Redirect to the list screen.
        //$this->setRedirect(JRoute::_('index.php?option=com_imc&view=issue&layout=edit&id='.$issueid, false));
    }
    */

    public function mail($pk = null)
    {
        // Get the input
        $input = JFactory::getApplication()->input;
        $issueid = $input->get('id', 0);
        $recipient = $input->get('recipient', '', 'raw');
        $content = $input->get('content', '', 'raw');
        $model = $this->getModel();


        $dispatcher = JEventDispatcher::getInstance();
        $dispatcher->trigger( 'onBeforeIssueMail', array( $model, $issueid, $recipient, $content) );
        // Redirect to the list screen.
        $this->setRedirect(JRoute::_('index.php?option=com_imc&view=issue&layout=edit&id='.$issueid, false));
    }

    /**
     * Method to run batch operations.
     *
     * @param   object  $model  The model.
     *
     * @return  boolean   True if successful, false otherwise and internal error is set.
     *
     * @since   1.6
     */
    public function batch($model = null)
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        // Set the model
        $model = $this->getModel('Issue', '', array());

        // Preset the redirect
        $this->setRedirect(JRoute::_('index.php?option=com_imc&view=issues' . $this->getRedirectToListAppend(), false));

        return parent::batch($model);
    }
}