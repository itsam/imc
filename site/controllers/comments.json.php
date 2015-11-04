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

	public function postComment()
	{
		$app = JFactory::getApplication();
		$params = $app->getParams('com_imc');
		$showComments = $params->get('enablecomments');
		$directpublishing = $params->get('directpublishingcomment');

		try {

			// Check for request forgeries.
			if (!JSession::checkToken('get')) {
				throw new Exception('Invalid session token');
			}

			if(!$showComments)
			{
				throw new Exception('Comments are not allowed');
			}

			$issueid = $app->input->getInt('issueid', null);
			$userid = $app->input->getInt('userid', null);
			$parentid = $app->input->getInt('parentid', 0);
			$description = $app->input->getString('description', '');

			if(is_null($issueid) || is_null($userid))
			{
				throw new Exception('issueid or userid are missing');
			}

			//post comment to the model
			//test with dummy
			$comment = new StdClass();
			$comment->id = 444;
			$comment->created = ImcFrontendHelper::convert2UTC(date('Y-m-d H:i:s'));
			$comment->fullname = JFactory::getUser($userid)->name;
			$comment->description = $description;
			$comment->profile_picture_url = JURI::base().'components/com_imc/assets/images/user-icon.png';
			//TODO: check for admin and own comment
			$comment->created_by_admin = false;
			$comment->created_by_current_user = false;
			$comment->under_moderation = $directpublishing ? true : false;
			//--------

			echo new JResponseJson($comment);
		}
		catch(Exception $e)	{
			header("HTTP/1.0 403 Accepted");
			echo new JResponseJson($e);
		}

	}

	public function comments()
	{
		try {
			// Check for request forgeries.
			if(!JSession::checkToken('get')){
				throw new Exception('Invalid session token');
			}
			$app = JFactory::getApplication();
			$issueid = $app->input->getInt('issueid', null);
			if(is_null($issueid))
			{
				throw new Exception('Invalid issueid');
			}

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
			header("HTTP/1.0 403 Accepted");
			echo new JResponseJson($e);
		}
	}	
}

