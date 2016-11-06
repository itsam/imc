<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');
require_once JPATH_COMPONENT_SITE . '/helpers/imc.php';

class plgImcmail_notifier extends JPlugin
{
	public function onAfterNewIssueAdded($model, $validData, $id = null)
	{
		$details = $this->getDetails($id, $model);
		$app = JFactory::getApplication();

		$showMsgsFrontend = ($this->params->get('messagesfrontend') && !$app->isAdmin());
		$showMsgsBackend  = ($this->params->get('messagesbackend') && $app->isAdmin());

		$DOMAIN = $this->params->get('domain');
		if($DOMAIN == '')
		{
			$DOMAIN = $_SERVER['HTTP_HOST'];
		}
		$MENUALIAS = $this->params->get('menualias');
		$appSite = JApplicationCms::getInstance('site');
		$router = $appSite->getRouter();
		$uri = $router->build('index.php?option=com_imc&view=issue&id='.(int) ($id == null ? $validData['id'] : $id));
		$parsed_url = $uri->toString();
		$parsed_url = str_replace('administrator/', '', $parsed_url);
		$parsed_url = str_replace('component/imc', $MENUALIAS, $parsed_url);
		$issueLink = $DOMAIN . $parsed_url;

		//Prepare email for admins
		if ($this->params->get('mailnewissueadmins')){
			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_NEW_ISSUE_SUBJECT'), 
				$details->username, 
				$details->usermail
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_NEW_ISSUE_BODY'),
				ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid']),
				$validData['title'],
				$validData['address']
			);
			$body .= '<a href="'.$issueLink.'">'.$issueLink.'</a>';
		
			if(empty($details->emails) || $details->emails[0] == ''){
				if($showMsgsBackend)
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MAIL_NOT_SET').ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid']), 'warning');
			}
			else {
				$recipients = implode(',', $details->emails);
				if ($this->sendMail($subject, $body, $details->emails) ) {
					if($showMsgsBackend)
						$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MAIL_CONFIRM').$recipients);
				}
				else {
					if($showMsgsBackend)
						$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_FAILED').$recipients, 'error');
				}
			}
		}

		//Prepare email for user
		if ($this->params->get('mailnewissueuser')) {		
			
			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_NEW_ISSUE_SUBJECT'), 
				$validData['title']
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_NEW_ISSUE_BODY'),
				ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid'])
			);
			$body .= '<a href="'.$issueLink.'">'.$issueLink.'</a>';

			if ($this->sendMail($subject, $body, $details->usermail) ) {
				if($showMsgsBackend){
					//do we really want to sent confirmation mail if issue is submitted from backend?
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_NEW_ISSUE_CONFIRM').$details->usermail . ' (' . $details->username . ')');
				}
				if($showMsgsFrontend){
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_NEW_ISSUE_CONFIRM').$details->usermail . ' (' . $details->username . ')');
				}				
			}
			else {
				$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_FAILED').$recipients, 'error');
			}

		}
	}	

	public function onAfterCategoryModified($model, $validData, $id = null)
	{
		$details = $this->getDetails($id, $model);
		$app = JFactory::getApplication();

		$showMsgsFrontend = ($this->params->get('messagesfrontend') && !$app->isAdmin());
		$showMsgsBackend  = ($this->params->get('messagesbackend') && $app->isAdmin());

		$DOMAIN = $this->params->get('domain');
		if($DOMAIN == '')
		{
			$DOMAIN = $_SERVER['HTTP_HOST'];
		}
		$MENUALIAS = $this->params->get('menualias');
		$appSite = JApplicationCms::getInstance('site');
		$router = $appSite->getRouter();
		$uri = $router->build('index.php?option=com_imc&view=issue&id='.(int) ($id == null ? $validData['id'] : $id));
		$parsed_url = $uri->toString();
		$parsed_url = str_replace('administrator/', '', $parsed_url);
		$parsed_url = str_replace('component/imc', $MENUALIAS, $parsed_url);
		$issueLink = $DOMAIN . $parsed_url;

		//Prepare email for admins
		if ($this->params->get('mailcategorychangeadmins')){
			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_CATEGORY_MODIFIED_SUBJECT'), 
				($id == null ? $validData['id'] : $id)
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_CATEGORY_MODIFIED_BODY'),
				$validData['title'],
				ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid']),
				JFactory::getUser()->name
			);
		
			if(empty($details->emails) || $details->emails[0] == ''){
				if($showMsgsBackend)
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MAIL_NOT_SET').ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid']), 'warning');
			}
			else {
				$recipients = implode(',', $details->emails);
				if ($this->sendMail($subject, $body, $details->emails) ) {
					if($showMsgsBackend)
						$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MAIL_CONFIRM').$recipients);
				}
				else {
					if($showMsgsBackend)
						$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_FAILED').$recipients, 'error');
				}
			}
		}

		//Prepare email for user
		if ($this->params->get('mailcategorychangeuser')) {		
			
			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_CATEGORY_MODIFIED_SUBJECT'), 
				($id == null ? $validData['id'] : $id)
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_CATEGORY_MODIFIED_BODY'),
				$validData['title'],
				ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid'])
			);
			
			$body .= '<a href="'.$issueLink.'">'.$issueLink.'</a>';

			if ($this->sendMail($subject, $body, $details->usermail) ) {
				if($showMsgsBackend){
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_CATEGORY_MODIFIED_CONFIRM').$details->usermail . ' (' . $details->username . ')');
				}
				if($showMsgsFrontend){
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_CATEGORY_MODIFIED_CONFIRM').$details->usermail . ' (' . $details->username . ')');
				}				
			}
			else {
				$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_FAILED').$recipients, 'error');
			}

		}
	}	

	public function onAfterStepModified($model, $validData, $id = null)
	{
		$details = $this->getDetails($id, $model);
		$app = JFactory::getApplication();

		$showMsgsFrontend = ($this->params->get('messagesfrontend') && !$app->isAdmin());
		$showMsgsBackend  = ($this->params->get('messagesbackend') && $app->isAdmin());

		$step = ImcFrontendHelper::getStepByStepId($validData['stepid']);

		$DOMAIN = $this->params->get('domain');
		if($DOMAIN == '')
		{
			$DOMAIN = $_SERVER['HTTP_HOST'];
		}
		$MENUALIAS = $this->params->get('menualias');
		$appSite = JApplicationCms::getInstance('site');
		$router = $appSite->getRouter();
		$uri = $router->build('index.php?option=com_imc&view=issue&id='.(int) ($id == null ? $validData['id'] : $id));
		$parsed_url = $uri->toString();
		$parsed_url = str_replace('administrator/', '', $parsed_url);
		$parsed_url = str_replace('component/imc', $MENUALIAS, $parsed_url);
		$issueLink = $DOMAIN . $parsed_url;

		//Prepare email for admins
		if ($this->params->get('mailstatuschangeadmins')){
			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_STEP_MODIFIED_SUBJECT'), 
				($id == null ? $validData['id'] : $id)
			);


			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_STEP_MODIFIED_BODY'),
				$validData['title'],
				$step['stepid_title'],
				JFactory::getUser()->name
			);
		
			if(empty($details->emails) || $details->emails[0] == ''){
				if($showMsgsBackend)
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MAIL_NOT_SET').ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid']), 'warning');
			}
			else {
				$recipients = implode(',', $details->emails);
				if ($this->sendMail($subject, $body, $details->emails) ) {
					if($showMsgsBackend)
						$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MAIL_CONFIRM').$recipients);
				}
				else {
					if($showMsgsBackend)
						$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_FAILED').$recipients, 'error');
				}
			}
		}

		//Prepare email for user
		if ($this->params->get('mailstatuschangeuser')) {		
			
			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_STEP_MODIFIED_SUBJECT'), 
				($id == null ? $validData['id'] : $id)
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_STEP_MODIFIED_BODY'),
				$validData['title'],
				$step['stepid_title'],
				$issueLink
			);

			$body .= '<a href="'.$issueLink.'">'.$issueLink.'</a>';

			if ($this->sendMail($subject, $body, $details->usermail) ) {
				if($showMsgsBackend){
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_STEP_MODIFIED_CONFIRM').$details->usermail . ' (' . $details->username . ')');
				}
				if($showMsgsFrontend){
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_STEP_MODIFIED_CONFIRM').$details->usermail . ' (' . $details->username . ')');
				}				
			}
			else {
				$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_FAILED').$recipients, 'error');
			}

		}
	}

	public function onAfterNewCommentAdded($model, $validData, $id = null)
	{
		$details = $this->getDetails($id, $model);
		$app = JFactory::getApplication();

		$showMsgsFrontend = ($this->params->get('messagesfrontend') && !$app->isAdmin());
		$showMsgsBackend  = ($this->params->get('messagesbackend') && $app->isAdmin());

		$DOMAIN = $this->params->get('domain');
		if($DOMAIN == '')
		{
			$DOMAIN = $_SERVER['HTTP_HOST'];
		}
		$MENUALIAS = $this->params->get('menualias');
		$appSite = JApplicationCms::getInstance('site');
		$router = $appSite->getRouter();
		$uri = $router->build('index.php?option=com_imc&view=issue&id='.(int) ($id == null ? $validData['id'] : $id));
		$parsed_url = $uri->toString();
		$parsed_url = str_replace('administrator/', '', $parsed_url);
		$parsed_url = str_replace('component/imc', $MENUALIAS, $parsed_url);
		$issueLink = $DOMAIN . $parsed_url;

		//Prepare email for admins
		if ($this->params->get('mailnewcommentadmins')){

			//check if comment posted by admin
			$created_by_admin = ImcHelper::getActions(JFactory::getUser($details->userid))->get('imc.manage.comments');
			if($created_by_admin)
			{
				$subject = sprintf(
					JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_NEW_COMMENT_SUBJECT_BY_ADMIN')
				);
			}

			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_NEW_COMMENT_SUBJECT'),
				$details->username,
				$details->usermail
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_NEW_COMMENT_BODY'),
				$validData['title']
			);
			$body .= '<br /><a href="'.$issueLink.'">'.$issueLink.'</a>';

			if(empty($details->emails) || $details->emails[0] == ''){
				if($showMsgsBackend)
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MAIL_NOT_SET').ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid']), 'warning');
			}
			else {
				$recipients = implode(',', $details->emails);
				if ($this->sendMail($subject, $body, $details->emails) ) {
					if($showMsgsBackend)
						$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MAIL_CONFIRM').$recipients);
				}
				else {
					if($showMsgsBackend)
						$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_FAILED').$recipients, 'error');
				}
			}
		}

		//Prepare email for user
		if ($this->params->get('mailnewcommentuser')) {

			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_NEW_COMMENT_SUBJECT')
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_NEW_COMMENT_BODY'),
				$validData['title']
			);
			$body .= '<br /><a href="'.$issueLink.'">'.$issueLink.'</a>';

			if ($this->sendMail($subject, $body, $details->usermail) ) {
				if($showMsgsBackend){
					//do we really want to sent confirmation mail if issue is submitted from backend?
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_NEW_COMMENT_CONFIRM').$details->usermail . ' (' . $details->username . ')');
				}
				if($showMsgsFrontend){
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_NEW_COMMENT_CONFIRM').$details->usermail . ' (' . $details->username . ')');
				}
			}
			else {
				$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_FAILED').$recipients, 'error');
			}

		}
	}

	public function onBeforeIssueMail($model, $id, $recipient, $content = '')
	{
		$app = JFactory::getApplication();

		//$issueModel = new ImcModelIssue();
        $item = $model->getItem($id);
		$stepid = $item->get('stepid');

		$step = ImcFrontendHelper::getStepByStepId($stepid);

		$DOMAIN = $this->params->get('domain');
		if($DOMAIN == '')
		{
			$DOMAIN = $_SERVER['HTTP_HOST'];
		}
		$MENUALIAS = $this->params->get('menualias');
		$appSite = JApplicationCms::getInstance('site');
		$router = $appSite->getRouter();
		$uri = $router->build('index.php?option=com_imc&view=issue&id='.(int)$id );
		$parsed_url = $uri->toString();
		$parsed_url = str_replace('administrator/', '', $parsed_url);
		$parsed_url = str_replace('component/imc', $MENUALIAS, $parsed_url);
		$issueLink = $DOMAIN . $parsed_url;

		//Prepare email for user

		$subject = sprintf(
			JText::_('PLG_IMC_MAIL_ISSUE_SUBJECT'),
			$id
		);

		$body = sprintf(
			JText::_('PLG_IMC_MAIL_ISSUE_BODY'),
			$model->getItem($id)->get('title'),
			$step['stepid_title']
		);

		$body .= '<a href="'.$issueLink.'">'.$issueLink.'</a>';
        $body .= '<p>' . JText::_('COM_IMC_SETTINGS_COMMENTS_LABEL') . ':<br />';
        $body .= $content . '</p>';
        $body .= '<hr />';

        //include images
        $body .=  JText::_('COM_IMC_FORM_LBL_ISSUE_CREATED_BY') . '<br />';
        foreach ($item->creatorDetails as $details) {
            $body .= $details . ' / ';
        }
        $body .= '<strong>' . ImcFrontendHelper::convertFromUTC($item->created). '</strong>';
        $body .= '<p><strong>'. JText::_('COM_IMC_FORM_LBL_ISSUE_ID') . '</strong>:';
        $body .= $item->id. '</p>';

        $body .= '<p><strong>'. JText::_('COM_IMC_FORM_LBL_ISSUE_STEPID') . '</strong>:';
        $st = ImcFrontendHelper::getStepByStepId($item->stepid);
        $body .= $st['stepid_title'] . '</p>';

        $body .= '<p><strong>'. JText::_('COM_IMC_FORM_LBL_ISSUE_CATID') . '</strong>:';
        $body .= ImcFrontendHelper::getCategoryNameByCategoryId($item->catid) . '</p>';

        $body .= '<p><strong>'. JText::_('COM_IMC_FORM_LBL_ISSUE_ADDRESS') . '</strong>:';
        $body .= $item->address . '</p>';

        $body .= '<p><strong>'. JText::_('COM_IMC_FORM_LBL_ISSUE_DESCRIPTION') . '</strong>:';
        $body .= $item->description . '</p>';

        $body .= '<p><strong>'. JText::_('COM_IMC_FORM_LBL_ISSUE_EXTRA') . '</strong>:';
        $body .= $item->extra . '</p>';

        $body .= '<p><strong>'. JText::_('COM_IMC_FORM_LBL_ISSUE_PHOTOS') . '</strong>:<br />';

        $photos = json_decode($item->photo);
        $i=0;
        foreach ($photos->files as $photo) {
            if(!isset($photo->thumbnailUrl))
                unset($photos->files[$i]);
            $i++;
        }
        $attachments = json_decode($item->photo);
        $i=0;
        foreach ($attachments->files as $attachment) {
            if(isset($attachment->thumbnailUrl))
                unset($attachments->files[$i]);
            $i++;
        }

        if(!empty($attachments->files))
        {
            $body .= JText::_('COM_IMC_ISSUE_ATTACHMENTS');
            $body .= '<ul>';
            foreach ($attachments->files as $attachment)
            {
                $body .= '<li><a href="'. $attachment->url.'">'. $attachment->name.'</a></li>';
            }
            $body .= '</ul>';
        }

        if(!empty($photos->files) && file_exists(JPATH_ROOT . '/' . $photos->imagedir .'/'. $photos->id . '/thumbnail/' . (@$photos->files[0]->name)))
        {

            foreach ($photos->files as $photo)
            {
                $src = JURI::root() . '/'. $photos->imagedir .'/'. $photos->id . '/medium/' . ($photo->name);
                $body .= '<img src="'. $src .'" alt="" style="max-width: 350px;" /><br />';
            }
        }


        $body .= '<p><strong>'. JText::_('COM_IMC_SETTINGS_GOOGLE_MAP_LABEL') .'</strong>:<br />';

        $api_key = JComponentHelper::getParams('com_imc')->get('api_key');
        $center = $item->latitude . ',' . $item->longitude;

        $map_src  = 'https://maps.googleapis.com/maps/api/staticmap';
        $map_src .=	'?center=' . $center;
        $map_src .=	'&markers=color:red%7Clabel:C%7C'. $center;
        $map_src .=	'&zoom=17&size=400x400';
        if($api_key != '')
        {
            $map_src .=	'&key='.$api_key;
        }

        $body .= '<img src="'. $map_src.'"/></p>';

		if ($this->sendMail($subject, $body, $recipient) ) {
			$app->enqueueMessage('Mail sent to '. $recipient);
		}
		else
		{
			$app->enqueueMessage('Mail to '.$recipient.' failed', 'error');
		}
	}

	public function onAfterModerationModified($model, $validData, $id = null)
	{
		$details = $this->getDetails($id, $model);
		$app = JFactory::getApplication();

		$showMsgsFrontend = ($this->params->get('messagesfrontend') && !$app->isAdmin());
		$showMsgsBackend  = ($this->params->get('messagesbackend') && $app->isAdmin());

		$moderation = ($validData['moderation'] ? JText::_('PLG_IMC_MAIL_MODERATION_ON') : JText::_('PLG_IMC_MAIL_MODERATION_OFF'));

		$DOMAIN = $this->params->get('domain');
		if($DOMAIN == '')
		{
			$DOMAIN = $_SERVER['HTTP_HOST'];
		}
		$MENUALIAS = $this->params->get('menualias');
		$appSite = JApplicationCms::getInstance('site');
		$router = $appSite->getRouter();
		$uri = $router->build('index.php?option=com_imc&view=issue&id='.(int) ($id == null ? $validData['id'] : $id));
		$parsed_url = $uri->toString();
		$parsed_url = str_replace('administrator/', '', $parsed_url);
		$parsed_url = str_replace('component/imc', $MENUALIAS, $parsed_url);
		$issueLink = $DOMAIN . $parsed_url;

		//Prepare email for admins
		if ($this->params->get('mailmoderationchangeadmins')){
			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MODERATION_MODIFIED_SUBJECT'),
				($id == null ? $validData['id'] : $id)
			);


			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MODERATION_MODIFIED_BODY'),
				$validData['title'],
				$moderation,
				JFactory::getUser()->name
			);

			if(empty($details->emails) || $details->emails[0] == ''){
				if($showMsgsBackend)
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MAIL_NOT_SET').ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid']), 'warning');
			}
			else {
				$recipients = implode(',', $details->emails);
				if ($this->sendMail($subject, $body, $details->emails) ) {
					if($showMsgsBackend)
						$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_MAIL_CONFIRM').$recipients);
				}
				else {
					if($showMsgsBackend)
						$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_FAILED').$recipients, 'error');
				}
			}
		}

		//Prepare email for user
		if ($this->params->get('mailmoderationchangeuser')) {

			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_MODERATION_MODIFIED_SUBJECT'),
				($id == null ? $validData['id'] : $id)
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_MODERATION_MODIFIED_BODY'),
				$validData['title'],
				$moderation,
				$issueLink
			);

			$body .= '<a href="'.$issueLink.'">'.$issueLink.'</a>';

			if ($this->sendMail($subject, $body, $details->usermail) ) {
				if($showMsgsBackend){
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_MODERATION_MODIFIED_CONFIRM').$details->usermail . ' (' . $details->username . ')');
				}
				if($showMsgsFrontend){
					$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_MODERATION_MODIFIED_CONFIRM').$details->usermail . ' (' . $details->username . ')');
				}
			}
			else {
				$app->enqueueMessage(JText::_('PLG_IMC_MAIL_NOTIFIER_MAIL_FAILED').$recipients, 'error');
			}

		}
	}

	private function sendMail($subject, $body, $recipients) 
	{
		$app = JFactory::getApplication();
		$mailfrom	= $app->get('mailfrom');
		$fromname	= $app->get('fromname');
		$sitename	= $app->get('sitename');

		$mail = JFactory::getMailer();
		$mail->isHTML(true);
		$mail->Encoding = 'base64';
		if(is_array($recipients)){
			foreach($recipients as $recipient){
				if ($mail->ValidateAddress($recipient)){
					$mail->addRecipient($recipient);
				}
			}
		}
		else {
			$mail->addRecipient($recipients);
		}
		$mail->setSender(array($mailfrom, $fromname));
		$mail->setSubject($sitename.': '.$subject);
		$mail->setBody($body);
		if ($mail->Send()) {
		  return true;
		} else {
		  return false;
		}			
	}

	private function getDetails($id, $model) 
	{
		$issueid = $id;
		//check if issue added from frontend
		if($id == null){
			$issueid = $model->getItem()->get('id');
		} 

		require_once JPATH_COMPONENT_ADMINISTRATOR . '/models/issue.php';
		//JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models');
		$issueModel = JModelLegacy::getInstance( 'Issue', 'ImcModel' );

		$item = $issueModel->getItem($issueid);

		$members = array();

        if($this->params->get('enableusergroup')) {
            $groupIds = $item->get('imc_category_usergroup');
            foreach ($groupIds as $groupId) {
                $membersIds = JAccess::getUsersByGroup($groupId); //getUsersByGroup($groupId, true) recursively
                foreach ($membersIds as $userId) {
                    $user = JFactory::getUser($userId);
                    array_push($members, $user->email);
                }
            }
        }
		$extra_emails = $item->get('notification_emails');

		$emails = array_merge($members, $extra_emails);
		$emails = array_unique($emails);

		$userid = $item->get('created_by');
		$username = JFactory::getUser($userid)->name;
		$usermail = JFactory::getUser($userid)->email;

		//check if social plugin is enabled and user is on social table
		if(JPluginHelper::isEnabled('slogin_integration', 'profile'))
		{
			$socialEmail = ImcFrontendHelper::getSocialEmail($userid);

			if(JFactory::getMailer()->ValidateAddress($socialEmail))
			{
				$usermail = $socialEmail;
			}
		}

		$details = new stdClass();
		$details->issueid = $issueid;
		$details->emails = $emails;
		$details->userid = $userid;
		$details->username = $username;
		$details->usermail = $usermail;

		return $details;
	}
}
