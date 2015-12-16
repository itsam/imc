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
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/imc.php';

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

	public function postComment($api = false, $userid = null)
	{
		$app = JFactory::getApplication();
		$params = $app->getParams('com_imc');
		$showComments = $params->get('enablecomments');
		$directpublishing = $params->get('directpublishingcomment');

		try {

			// Check for request forgeries.
			if (!$api && !JSession::checkToken('get')) {
				throw new Exception('Invalid session token');
			}

			if(!$showComments)
			{
				throw new Exception('Comments are not allowed');
			}

			$issueid = $app->input->getInt('issueid', null);
			if($api)
			{
				$issueid = $app->input->getInt('id', null);
			}

			//check if issue exists
			$issueModel = JModelLegacy::getInstance( 'Issue', 'ImcModel', array('ignore_request' => true) );
			$issue = $issueModel->getData($issueid);
			if(!is_object($issue))
			{
				throw new Exception(JText::_('COM_IMC_API_ISSUE_NOT_EXIST'));
			}

			if(is_null($userid))
			{
				$userid = $app->input->getInt('userid', null);
			}

			if(is_null($issueid) || is_null($userid))
			{
				throw new Exception('issueid or userid are missing');
			}

			if($userid == 0)
			{
				throw new Exception('guests cannot post comments');
			}

			$parentid = $app->input->getInt('parentid', 0);

			//check if parentid is valid
			$commentsModel = $this->getModel();
			$ids = $commentsModel->getIds($issueid);
			if(!in_array($parentid, $ids))
			{
				throw new Exception('parentid is invalid');
			}

			$description = $app->input->getString('description', '');

			//check is user is admin
			$created_by_admin = ImcHelper::getActions(JFactory::getUser($userid))->get('imc.manage.comments');

			//make comment
			$comment = new StdClass();
			$comment->state = 1;
			$comment->issueid = $issueid;
			if($parentid > 0){
				$comment->parentid = $parentid;
			}
			$comment->created = ImcFrontendHelper::convert2UTC(date('Y-m-d H:i:s'));
			$comment->updated = $comment->created;
			$comment->created_by = $userid;
			$comment->description = $description;
			$comment->fullname = JFactory::getUser($userid)->name;
			$comment->moderation = (!$directpublishing && !$created_by_admin) ? 1 : 0;
			$comment->language = "*";
			$comment->isAdmin = (int)$created_by_admin;

			//post comment to the model
			$insertedId = $commentsModel->add($comment);

			//fill missing fields to be aligned with jquery-comments and send back to the client
			$comment->id = $insertedId;
			$comment->profile_picture_url = JURI::base().'components/com_imc/assets/images/user-icon.png';
			$comment->created_by_admin = $created_by_admin;
			$comment->created_by_current_user = true;

			if($comment->moderation)
			{
				$comment->profile_picture_url = JURI::base().'components/com_imc/assets/images/user-icon-moderated.png';
			}

			if($created_by_admin)
			{
				$comment->profile_picture_url = JURI::base().'components/com_imc/assets/images/admin-user-icon.png';
			}

			if($api)
			{
				$result = ImcFrontendHelper::sanitizeComment($comment, $userid);
				//be consistent return as array (of size 1)
				$result = array($result);
				echo new JResponseJson($result, 'Comment post successfully');
			}
			else
			{
				echo new JResponseJson($comment);
			}
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
			$userid = $app->input->getInt('userid', 0);

			//$commentsModel = JModelLegacy::getInstance( 'Comments', 'ImcModel', array('ignore_request' => true) );
			$commentsModel = $this->getModel();
			$commentsModel->setState('imc.filter.issueid', $issueid);
			$commentsModel->setState('imc.filter.state', 1);
			$commentsModel->setState('filter.imcapi.userid', $userid);
			if($userid == 0)
			{
				$commentsModel->setState('filter.imcapi.guest', true);
			}

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
				if($item->moderation)
				{
					$comment->profile_picture_url = JURI::base().'components/com_imc/assets/images/user-icon-moderated.png';
				}

				if($item->isAdmin)
				{
					$comment->profile_picture_url = JURI::base().'components/com_imc/assets/images/admin-user-icon.png';
				}
				if($item->parentid > 0)
				{
					$comment->parentid = $item->parentid;
				}
				$comment->created_by_admin = (boolean) $item->isAdmin;
				$comment->created_by_current_user = $item->created_by == $userid ? true : false;

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

