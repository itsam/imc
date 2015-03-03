<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');
require_once JPATH_COMPONENT_SITE . '/helpers/imc.php';

class plgImcmail_notifier extends JPlugin
{

	public function onAfterNewIssueAdded($model, $validData, $id = null)
	{
		$details = $this->getDetails($id, $model);

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
				JFactory::getApplication()->enqueueMessage('Admin notifications for this category are not set', 'Info');
			}
			else {
				$recipients = implode(',', $details->emails);
				if ($this->sendMail($subject, $body, $details->emails) ) {
					JFactory::getApplication()->enqueueMessage('Admin notification mail is sent to '.$recipients, 'Info');
				}
				else {
					JFactory::getApplication()->enqueueMessage('Admin notification mail to '.$recipients.' have failed', 'Error');
				}
			}
		}

		//Prepare email for user
		if ($this->params->get('mailnewissueuser')) {		
			JFactory::getApplication()->enqueueMessage('User notification mail is sent to '.$details->username.' at '.$details->usermail, 'Info');
		}
	}	

	public function onAfterStepModified($model, $validData, $id = null)
	{
		$details = $this->getDetails($id, $model);
		
		//Prepare email for admins
		//TODO: Do we really need to notify admins to every issue status modification? Set this on settings
		if(empty($details->emails) || $details->emails[0] == ''){
			JFactory::getApplication()->enqueueMessage('Admin notifications when issue status modified are not set', 'Info');
		}
		else {
			$recipients = implode(',', $details->emails);
			JFactory::getApplication()->enqueueMessage('Admin notification mail due to issue status modification is sent to '.$recipients, 'Info');
		}

		//Prepare email for user
		JFactory::getApplication()->enqueueMessage('User notification mail (because issue status has modified) sent to '.$details->username.' at '.$details->usermail, 'Info');
	}	

	public function onAfterCategoryModified($model, $validData, $id = null)
	{
		$details = $this->getDetails($id, $model);
		
		//Prepare email for admins
		if(empty($details->emails) || $details->emails[0] == ''){
			JFactory::getApplication()->enqueueMessage('Admin notifications when category has modified are not set', 'Info');
		}
		else {
			$recipients = implode(',', $details->emails);
			JFactory::getApplication()->enqueueMessage('Notification mail due to category modification is sent to '.$recipients, 'Info');
		}

		//Prepare email for user
		//TODO: Do we really need to notify user for categeory modification? Set this on settings
		JFactory::getApplication()->enqueueMessage('Notification mail (because category has modified) sent to '.$details->username.' at '.$details->usermail, 'Info');
	}	

	private function sendMail($subject, $body, $recipients) {
		$app = JFactory::getApplication();
		$mailfrom	= $app->getCfg('mailfrom');
		$fromname	= $app->getCfg('fromname');
		$sitename	= $app->getCfg('sitename');

		$mail = JFactory::getMailer();
		$mail->isHTML(true);
		$mail->Encoding = 'base64';
		foreach($recipients as $recipient)
			$mail->addRecipient($recipient);
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
		$details->emails = $emails;
		$details->userid = $userid;
		$details->username = $username;
		$details->usermail = $usermail;

		return $details;
	}
}
