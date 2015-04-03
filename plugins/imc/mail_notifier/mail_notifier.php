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
				//$validData['description'],
				//$issueLink
				//$issueAdminLink 
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
		if ($this->params->get('mailnewissueuser')) {		
			
			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_NEW_ISSUE_SUBJECT'), 
				$validData['title']
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_NEW_ISSUE_BODY'),
				ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid'])
				//$validData['title'],
				//$validData['address']
				//$validData['description'],
				//$issueLink
				//$issueAdminLink 
			);

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

		//Prepare email for admins
		if ($this->params->get('mailcategorychangeadmins')){
			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_CATEGORY_MODIFIED_SUBJECT'), 
				''
				//$details->username, 
				//$details->usermail
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_CATEGORY_MODIFIED_BODY'),
				''
				//ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid']),
				//$validData['title'],
				//$validData['address']
				//$validData['description'],
				//$issueLink
				//$issueAdminLink 
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
				''
				//$validData['title']
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_CATEGORY_MODIFIED_BODY'),
				''
				//ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid'])
				//$validData['title'],
				//$validData['address']
				//$validData['description'],
				//$issueLink
				//$issueAdminLink 
			);

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

		//Prepare email for admins
		if ($this->params->get('mailstatuschangeadmins')){
			$subject = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_STEP_MODIFIED_SUBJECT'), 
				''
				//$details->username, 
				//$details->usermail
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_ADMINS_STEP_MODIFIED_BODY'),
				''
				//ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid']),
				//$validData['title'],
				//$validData['address']
				//$validData['description'],
				//$issueLink
				//$issueAdminLink 
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
				''
				//$validData['title']
			);

			$body = sprintf(
				JText::_('PLG_IMC_MAIL_NOTIFIER_USER_STEP_MODIFIED_BODY'),
				''
				//ImcFrontendHelper::getCategoryNameByCategoryId($validData['catid'])
				//$validData['title'],
				//$validData['address']
				//$validData['description'],
				//$issueLink
				//$issueAdminLink 
			);

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


	private function sendMail($subject, $body, $recipients) {
		$app = JFactory::getApplication();
		$mailfrom	= $app->getCfg('mailfrom');
		$fromname	= $app->getCfg('fromname');
		$sitename	= $app->getCfg('sitename');

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

	private function getDetails($id, $model) {
		//check if issue added from frontend
		if($id == null){
			$issueid = $model->getItem()->get('id');
		} 
		else {
			$issueid = $id;
		}

		//$emails = $model->getItem($issueid)->get('notification_emails');
		JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models');
		$issueModel = JModelLegacy::getInstance( 'Issue', 'ImcModel' );
		$emails = $issueModel->getItem($issueid)->get('notification_emails');

		$userid = $issueModel->getItem($issueid)->get('created_by');
		$username = JFactory::getUser($userid)->name;
		$usermail = JFactory::getUser($userid)->email;

		$details = new stdClass();
		$details->issueid = $issueid;
		$details->emails = $emails;
		$details->userid = $userid;
		$details->username = $username;
		$details->usermail = $usermail;

		return $details;
	}
}
