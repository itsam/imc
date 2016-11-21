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

jimport('joomla.application.component.controllerform');

/**
 * Issue controller class.
 */
class ImcControllerUsers extends JControllerLegacy
{

	public function members()
	{
        // Check for request forgeries.
		if(!JSession::checkToken('get')){
			echo new JResponseJson(null, 'Invalid Token', true);
			jexit();
		}

		$app = JFactory::getApplication();
		$jinput = $app->input;
		$groups = $jinput->get('groups', null);
		if($groups == null){
			echo new JResponseJson(null, 'No given group', true);
			JFactory::getApplication()->close();
		}

		try {
			//TODO: move this on ImcHelper so as to be used besides json
			$members = array();
			$groupIds = explode('-', $groups);
			foreach ($groupIds as $groupId) {
				$membersIds = JAccess::getUsersByGroup($groupId); //getUsersByGroup($groupId, true) recursively
				foreach ($membersIds as $userId) {
					$user = JFactory::getUser($userId);
					array_push($members, array('name'=>$user->name, 'email'=>$user->email));
				}
			}
			echo new JResponseJson($members);
		}
		catch(Exception $e)	{
			echo new JResponseJson($e);
		}
	}

	public function setProfile()
    {
        // Check for request forgeries.
        if(!JSession::checkToken('get'))
        {
            echo new JResponseJson(null, 'Invalid Token', true);
            jexit();
        }

        $app = JFactory::getApplication();
        $jinput = $app->input;
        $userid = $jinput->get('userid', null);
        $key = $jinput->get('key', null);
        $value = $jinput->get('value', null);
        if($userid == null || $key == null || $value == null){
            echo new JResponseJson(null, 'Arguments are missing', true);
            JFactory::getApplication()->close();
        }

        //TODO: Move the following to a model
        //check if exists
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $conditions = array(
            $db->quoteName('user_id') . ' = ' . $userid,
            $db->quoteName('profile_key') . ' = ' . $db->quote($key)
        );

        $query->select('COUNT(*) AS c');
        $query->from('#__user_profiles');
        $query->where($conditions);
        $db->setQuery($query);
        $c = $db->loadResult();
        if($c > 0)
        {

            $query = $db->getQuery(true);
            $fields = array(
                $db->quoteName('profile_value') . ' = ' . $db->quote($value)
            );
            $query->update($db->quoteName('#__user_profiles'))->set($fields)->where($conditions);
            $db->setQuery($query);
            $db->execute();
        }
        else
        {
            // Create and populate an object.
            $profile = new stdClass();
            $profile->user_id = $userid;
            $profile->profile_key = $key;
            $profile->profile_value = $value;
            $profile->ordering=1;
            JFactory::getDbo()->insertObject('#__user_profiles', $profile);
        }
    }

    public function getProfile()
    {
        // Check for request forgeries.
        if(!JSession::checkToken('get'))
        {
            echo new JResponseJson(null, 'Invalid Token', true);
            jexit();
        }

        $app = JFactory::getApplication();
        $jinput = $app->input;
        $userid = $jinput->get('userid', null);
        $key = $jinput->get('key', null);

        if($userid == null || $key == null){
            echo new JResponseJson(null, 'Arguments are missing', true);
            JFactory::getApplication()->close();
        }

        //TODO: Move the following to a model
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('profile_value');
        $query->from('#__user_profiles');
        $query->where('user_id = ' . $userid);
        $query->where('profile_key = ' . $db->quote($key));
        $db->setQuery($query);

        $results = $db->loadResult();
        echo new JResponseJson($results);

    }


}
