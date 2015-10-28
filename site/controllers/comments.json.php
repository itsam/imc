<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

// No direct access.
defined('_JEXEC') or die;

require_once JPATH_COMPONENT.'/controller.php';
require_once JPATH_COMPONENT_SITE . '/helpers/imc.php';

/**
 * Issues list controller class.
 */
class ImcControllerComments extends ImcController
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function &getModel($name = 'Comments', $prefix = 'ImcModel')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}

	public function comments()
	{
        // Check for request forgeries.
		if(!JSession::checkToken('get')){
			echo new JResponseJson(null, 'Invalid Token', true);
			jexit();
		}
		$app = JFactory::getApplication();
		$issueid = $app->input->getInt('issueid', null);
		if(is_null($issueid))
		{
			echo new JResponseJson(null, 'Invalid issueid');
			jexit();
		}

		try {
			//$commentsModel = JModelLegacy::getInstance( 'Comments', 'ImcModel', array('ignore_request' => true) );
			$commentsModel = $this->getModel();
			$commentsModel->setState('imc.filter.issueid', $issueid);
			$commentsModel->setState('imc.filter.state', 1);
			$items = $commentsModel->getItems();
			//$items contains too much overhead, set only necessary data
			$comments = array();
			foreach ($items as $item) {
				$comment = new StdClass();
				$comment->id = $item->id;
				$comment->created = ImcFrontendHelper::convertFromUTC($item->created);
				$comment->fullname = $item->fullname;
				$comment->description = $item->description;
				$comment->profile_picture_url = JURI::base().'components/com_imc/assets/images/user-icon.png';
				if($item->parentid > 0)
				{
					$comment->parentid = $item->parentid;
				}
				//TODO: check for admin and own comment
				$comment->created_by_admin = false;
				$comment->created_by_current_user = false;

				$comments[] = $comment;
			}
			echo new JResponseJson($comments);
		}
		catch(Exception $e)	{
			echo new JResponseJson($e);
		}
	}	
}

