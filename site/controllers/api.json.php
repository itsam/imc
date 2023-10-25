<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
/**
 * @version     3.0.1
 * @package     com_imc
 * @copyright   Copyright (C) 2019. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

// No direct access.
defined('_JEXEC') or die;

require_once JPATH_COMPONENT . '/controller.php';
require_once JPATH_COMPONENT_SITE . '/helpers/imc.php';
require_once JPATH_COMPONENT_SITE . '/helpers/MCrypt2.php';
require_once JPATH_COMPONENT_SITE . '/models/tokens.php';
require_once JPATH_COMPONENT_SITE . '/controllers/comments.json.php';

/**
 * IMC API controller class.
 * Make sure you have mcrypt module enabled
 * e.g. $ sudo php5enmod mcrypt
 *
 * Every request should contain token, m_id, l
 * where *token* is the m-crypted "json_encode(array)" of username, password, timestamp, randomString in the following form:
 * {'u':'username','p':'plain_password','t':'1439592509','r':'i452dgj522'}
 * all casted to strings including the UNIX timestamp time()
 * where *m_id* is the modality ID according to the REST/API key definition in the administrator side
 * where *l* is the 2-letter language code used for for the responses translation (en, el, de, es, etc)
 *
 * Every token is allowed to be used ^only once^ to avoid MITM attacks
 *
 * Check helpers/MCrypt.php for details on how to use Rijndael-128 AES encryption algorithm
 *
 * Please note that for better security it is highly recommended to protect your site with SSL (https)
 */

class ImcControllerApi2 extends ImcController
{
	private $mcrypt;
	private $keyModel;
	private $params;

	function __construct()
	{
		$this->params = JComponentHelper::getParams('com_imc');
		$this->mcrypt = new MCrypt();
		JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models');
		$this->keyModel = JModelLegacy::getInstance('Key', 'ImcModel', array('ignore_request' => true));
		parent::__construct();
	}

	public function exception_error_handler($errno, $errstr, $errfile, $errline)
	{
		$ee = new ErrorException($errstr, 0, $errno, $errfile, $errline);
		JFactory::getApplication()->enqueueMessage($ee, 'error');
		throw $ee;
	}

	private function valid_email($email)
	{
		return !!filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	private function validateRequest($isNew = false)
	{
		$app = JFactory::getApplication();
		$token = $app->input->getString('token');
		$m_id  = $app->input->getInt('m_id');
		$l     = $app->input->getString('l');

		//1. check necessary arguments are exist
		if (is_null($token) || is_null($m_id) || is_null($l)) {
			$app->enqueueMessage('Either token, m_id (modality), or l (language) are missing', 'error');
			throw new Exception('Request is invalid');
		}

		//set language
		ImcFrontendHelper::setLanguage($app->input->getString('l'), array('com_users', 'com_imc'));

		//check for nonce (existing token)
		if ($this->params->get('advancedsecurity')) {
			if (ImcModelTokens::exists($token)) {
				throw new Exception('Token is already used');
			}
		}
		//2. get the appropriate key according to given modality
		$result = $this->keyModel->getItem($m_id);
		$key = $result->skey;
		if (strlen($key) < 16) {
			$app->enqueueMessage('Secret key is not 16 characters', 'error');
			throw new Exception('Secret key is invalid. Contact administrator');
		} else {
			$this->mcrypt->setKey($key);
		}

		//3. decrypt and check token validity
		$decryptedToken = $this->mcrypt->decrypt($token);
		$decryptedToken = base64_decode($decryptedToken);
		$objToken = json_decode($decryptedToken);

		if (!is_object($objToken)) {
			throw new Exception('Token is invalid');
		}

		if (!isset($objToken->u) || !isset($objToken->p) || !isset($objToken->t) || !isset($objToken->r)) {
			throw new Exception('Token is not well formatted');
		}

		if ($this->params->get('advancedsecurity')) {
			if ((time() - $objToken->t) > 3 * 60) {
				throw new Exception('Token has expired');
			}
		}

		//4. authenticate user
		$userid = 0;
		if (self::valid_email($objToken->u)) {
			//b. get userid given email
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('id');
			$query->from('#__users');
			$query->where('UPPER(email) = UPPER(' . $db->Quote($objToken->u) . ')');
			$db->setQuery($query);
			$result = $db->loadObject();
			$userid = $result->id;
		} else {
			//a. get userid given username
			$userid = JUserHelper::getUserId($objToken->u);
		}

		$user = JFactory::getUser($userid);
		$userInfo = array();
		if ($isNew) {
			$userInfo['username'] = $objToken->u;
			$userInfo['password'] = $objToken->p;
		} else {
			if ($objToken->u == 'imc-guest' && $objToken->p == 'imc-guest') {
				$userid = 0;
			} else {
				$match = JUserHelper::verifyPassword($objToken->p, $user->password, $userid);
				if (!$match) {
					$app->enqueueMessage(JText::_('COM_IMC_API_USERNAME_PASSWORD_NO_MATCH'), 'error');
					throw new Exception('Token does not match');
				}
				if ($user->block) {
					$app->enqueueMessage(JText::_('COM_IMC_API_USER_NOT_ACTIVATED'), 'error');
					throw new Exception(JText::_('COM_IMC_API_USER_BLOCKED'));
				}
			}
		}

		//5. populate token table
		if ($this->params->get('advancedsecurity')) {
			$record = new stdClass();
			$record->key_id = $m_id;
			$record->user_id = $userid;
			//$record->json_size = $json_size;
			$record->method = $app->input->getMethod();
			$record->token = $token;
			$record->unixtime = $objToken->t;
			ImcModelTokens::insertToken($record); //this static method throws exception on error
		}

		return $isNew ? $userInfo : (int) $userid;
	}

	public function languages()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			self::validateRequest();

			if ($app->input->getMethod() != 'GET') {
				throw new Exception('You cannot use other method than GET to fetch languages');
			}

			$availLanguages = JFactory::getLanguage()->getKnownLanguages();
			$languages = array();
			foreach ($availLanguages as $key => $value) {
				array_push($languages, $key);
			}

			$result = $languages;
			$app->enqueueMessage('size: ' . sizeof($result), 'info');
			header('Content-type: application/json');
			echo new JResponseJson($result, 'Languages fetched successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function rawissues()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			self::validateRequest();

			if ($app->input->getMethod() != 'GET') {
				throw new Exception('You cannot use other method than GET to fetch raw issues');
			}

			//get necessary arguments
			$minLat = $app->input->getString('minLat');
			$maxLat = $app->input->getString('maxLat');
			$minLng = $app->input->getString('minLng');
			$maxLng = $app->input->getString('maxLng');
			$ts = $app->input->getString('ts');
			$prior_to = $app->input->getString('prior_to');


			if (!is_null($ts)) {
				if (!ImcFrontendHelper::isValidTimeStamp($ts)) {
					throw new Exception('Invalid timestamp');
				}

				//get date from ts
				$ts = gmdate('Y-m-d H:i:s', $ts);
			}
			if (!is_null($prior_to)) {
				if (!ImcFrontendHelper::isValidTimeStamp($prior_to)) {
					throw new Exception('Invalid prior_to timestamp');
				}
				//get date from ts
				$prior_to = gmdate('Y-m-d H:i:s', $prior_to);
			}

			$data = ImcFrontendHelper::getRawIssues($ts, $prior_to, $minLat, $maxLat, $minLng, $maxLng);
			$app->enqueueMessage('size: ' . sizeof($data), 'info');
			restore_error_handler();
			header('Content-type: application/json');
			echo new JResponseJson($data, 'Issues fetched successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function issues()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			//$userid = self::validateRequest();

			if ($app->input->getMethod() != 'GET') {
				throw new Exception('You cannot use other method than GET to fetch issues');
			}

			$userid = self::validateRequest();

			//get necessary arguments
			$minLat = $app->input->getString('minLat');
			$maxLat = $app->input->getString('maxLat');
			$minLng = $app->input->getString('minLng');
			$maxLng = $app->input->getString('maxLng');
			$owned = $app->input->get('owned', false);
			$lim = $app->input->getInt('lim', 0);
			$offset = $app->input->getInt('offset', 0);
			$ts = $app->input->getString('ts');
			$prior_to = $app->input->getString('prior_to');
			$filterSteps = $app->input->getString('filterSteps');
			$filterCategories = $app->input->getString('filterCategories');
			$filterPersonal = $app->input->getString('filterPersonal');
			
			//get issues model
			$issuesModel = JModelLegacy::getInstance('Issues', 'ImcModel', array('ignore_request' => true));

			//set states
			if (!is_null($filterSteps)) {
				$issuesModel->setState('filter.steps', $filterSteps);
			}

			if (!is_null($filterCategories)) {
				$issuesModel->setState('filter.category', $filterCategories);
			}

			if ($filterPersonal == 'mine') {
				$owned = 'true';
			} else {
				$owned = 'false';
			}
			if ($filterPersonal == 'moderated') {
				$issuesModel->setState('filter.moderated', 'yes');
				$owned = 'true';
			}
			if ($filterPersonal == 'starred') {


				$votesModel = JModelLegacy::getInstance('Votes', 'ImcModel', array('ignore_request' => true));
				$votesModel->setState('filter.imcapi.userid', $userid);
				$votesModel->setState('filter.state', 1);
				//handle unexpected warnings from model
				set_error_handler(array($this, 'exception_error_handler'));
				//get items and sanitize them
				$data = $votesModel->getItems();
				//simplify votedIssues
				$voted = array();
				foreach ($data as $votedIssue) {
					$voted[] = $votedIssue->issueid;
				}

				restore_error_handler();

				$owned = 'false';
				$issuesModel->setState('filter.starred', $voted);
			}

			$issuesModel->setState('filter.owned', ($owned === 'true' ? 'yes' : 'no'));
			$issuesModel->setState('filter.imcapi.userid', $userid);
			if ($userid == 0) {
				$issuesModel->setState('filter.imcapi.guest', true);
			}
			//$issuesModel->setState('filter.imcapi.ordering', 'id');
			//$issuesModel->setState('filter.imcapi.direction', 'DESC');

			//$issuesModel->setState('list.limit', $lim);
			$issuesModel->setState('filter.imcapi.limit', $lim);
			$issuesModel->setState('filter.imcapi.offset', $offset);


			if (!is_null($minLat) && !is_null($maxLat) && !is_null($minLng) && !is_null($maxLng)) {
				$issuesModel->setState('filter.imcapi.minLat', $minLat);
				$issuesModel->setState('filter.imcapi.maxLat', $maxLat);
				$issuesModel->setState('filter.imcapi.minLng', $minLng);
				$issuesModel->setState('filter.imcapi.maxLng', $maxLng);
			}

			if (!is_null($ts)) {
				if (!ImcFrontendHelper::isValidTimeStamp($ts)) {
					throw new Exception('Invalid timestamp');
				}

				//get date from ts
				$ts = gmdate('Y-m-d H:i:s', $ts);
				$issuesModel->setState('filter.imcapi.ts', $ts);
			}
			if (!is_null($prior_to)) {
				if (!ImcFrontendHelper::isValidTimeStamp($prior_to)) {
					throw new Exception('Invalid prior_to timestamp');
				}
				//get date from ts
				$prior_to = gmdate('Y-m-d H:i:s', $prior_to);
				$issuesModel->setState('filter.imcapi.priorto', $prior_to);
			}

			$issuesModel->setState('filter.state', 1);
			
			//handle unexpected warnings from model
			set_error_handler(array($this, 'exception_error_handler'));
			//get items and sanitize them
			$data = $issuesModel->getItems();
			$total = $issuesModel->getTotal();
			
			$result = ImcFrontendHelper::sanitizeIssues($data, $userid);


			//API2 sanitize further and include timeline
			$logsModel = JModelLegacy::getInstance('Logs', 'ImcModel', array('ignore_request' => true));
			foreach ($result as &$issue) {
				unset($issue->created_by);
				unset($issue->hits);
				unset($issue->regnum);
				unset($issue->regdate);
				unset($issue->responsible);
				unset($issue->extra);
				unset($issue->subgroup);
				unset($issue->alias);
				unset($issue->created_by_name);
				unset($issue->attachments);
				unset($issue->created_TZ);
				unset($issue->updated_TZ);
				unset($issue->regdate_TZ);
				unset($issue->created_ts);
				unset($issue->updated_ts);
				unset($issue->coords);

				$logs = $logsModel->getItemsByIssue($issue->id);
				$timeline = ImcFrontendHelper::sanitizeLogs($logs);
				$issue->timeline = $timeline;
			}

			//$app->enqueueMessage('size: ' . sizeof($result), 'info');
			restore_error_handler();
			header('Content-type: application/json; charset=utf-8');
			//echo new JResponseJson($result, 'Issues fetched successfully');
			$res = new JResponseJson($result, 'Issues fetched successfully');
		    $resObj = json_decode($res);
			//echo $res;
	        $resObj->total = $total;
			
			echo json_encode($resObj, JSON_UNESCAPED_UNICODE);
//			echo json_encode($resObj);

			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}


	public function foo2() 
	{
		//$result = $_REQUEST;
		$app = JFactory::getApplication();
		header('Content-type: application/json');
		$result = $_FILES;
		echo new JResponseJson($result, 'Issue action completed successfully');
	}

	public function foo()
	{
		$result = null;
		$app = JFactory::getApplication();
		try{
			$userid = self::validateRequest();
			//$result = $_REQUEST;
			$app = JFactory::getApplication();
			header('Content-type: application/json');
			$result = $_FILES;
			echo new JResponseJson($result, 'Issue action completed successfully');
		} catch (Exception $e) {

			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();			
		}
	}




public function issue2()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();
			//get necessary arguments
			$id = $app->input->getInt('id', null);

			switch ($app->input->getMethod()) {

				case 'POST':
					// header('Content-type: application/json');
					// $result = $_FILES;
					// echo new JResponseJson($result, 'Issue action completed successfully');
					if ($id != null) {
						throw new Exception('You cannot use POST to fetch issue. Use GET instead');
					}

					//guests are not allowed to post issues
					//TODO: get this from settings
					if ($userid == 0) {
						throw new Exception(JText::_('COM_IMC_API_NO_GUESTS_NO_POST'));
					}

					//get necessary arguments
					$args = array(
						'catid' => $app->input->getInt('catid'),
						'title' => $app->input->getString('title'),
						'description' => $app->input->getString('description'),
						'address' => $app->input->getString('address'),
						'latitude' => $app->input->getString('lat'),
						'longitude' => $app->input->getString('lng'),
						'district' => $app->input->getInt('district')
					);
					ImcFrontendHelper::checkNullArguments($args);

					//check if category exists
					if (is_null(ImcFrontendHelper::getCategoryNameByCategoryId($args['catid'], true))) {
						throw new Exception(JText::_('COM_IMC_API_CATEGORY_NOT_EXIST'));
					}

					$args['userid'] = $userid;
					$args['created_by'] = $userid;
					$args['stepid'] = ImcFrontendHelper::getPrimaryStepId();
					$args['id'] = 0;
					$args['created'] = ImcFrontendHelper::convert2UTC(date('Y-m-d H:i:s'));
					$args['updated'] = $args['created'];
					$args['note'] = 'modality=' . $app->input->getInt('m_id');
					$args['language'] = '*';
					$args['subgroup'] = 0;
					$m_id  = $app->input->getInt('m_id', 0);
					$args['modality'] = $m_id;

					$tmpTime = time(); //used for temporary id
					$imagedir = 'images/imc';

					//check if post contains files
					$file = $app->input->files->get('files');
					if (!empty($file)) {
						require_once JPATH_ROOT . '/components/com_imc/models/fields/multiphoto/server/UploadHandler.php';
						$options = array(
							'script_url' => JRoute::_(JURI::root(true) . '/administrator/index.php?option=com_imc&task=upload.handler&format=json&id=' . $tmpTime . '&imagedir=' . $imagedir . '&' . JSession::getFormToken() . '=1'),
							'upload_dir' => JPATH_ROOT . '/' . $imagedir . '/' . $tmpTime . '/',
							'upload_url' => $imagedir . '/' . $tmpTime . '/',
							'param_name' => 'files',
							'imc_api' => true

						);
						$upload_handler = new UploadHandler($options);
						if (isset($upload_handler->imc_api)) {
							$files_json = json_decode($upload_handler->imc_api);
							$args['photo'] = json_encode(array('isnew' => 1, 'id' => $tmpTime, 'imagedir' => $imagedir, 'files' => $files_json->files));
							$app->enqueueMessage('File(s) uploaded successfully', 'info');
						} else {
							throw new Exception(JText::_('COM_IMC_API_UPLOAD_FAILED'));
						}
					} else {
						$args['photo'] = json_encode(array('isnew' => 1, 'id' => $tmpTime, 'imagedir' => $imagedir, 'files' => array()));
					}

					//get issueForm model and save
					$issueFormModel = JModelLegacy::getInstance('IssueForm', 'ImcModel', array('ignore_request' => true));

					//handle unexpected warnings from model
					set_error_handler(array($this, 'exception_error_handler'));
					$issueFormModel->save($args);
					$insertid = JFactory::getApplication()->getUserState('com_imc.edit.issue.insertid');

					//call post save hook
					require_once JPATH_COMPONENT . '/controllers/issueform.php';
					$issueFormController = new ImcControllerIssueForm();
					$issueFormController->postSaveHook($issueFormModel, $args);
					restore_error_handler();

					$result = array('issueid' => $insertid);

					//be consistent return as array (of size 1)
					$result = array($result);

					break;
				default:
					throw new Exception('HTTP method is not supported');
			}
			header('Content-type: application/json');
			echo new JResponseJson($result, 'Issue action completed successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}






	public function issue()
	{

		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();
			//get necessary arguments
			$id = $app->input->getInt('id', null);

			switch ($app->input->getMethod()) {
					//fetch existing issue
				case 'GET':
					if ($id == null) {
						throw new Exception('Id is not set');
					}

					//get models
					$issueModel = JModelLegacy::getInstance('Issue', 'ImcModel', array('ignore_request' => true));
					$logsModel = JModelLegacy::getInstance('Logs', 'ImcModel', array('ignore_request' => true));
					$votesModel = JModelLegacy::getInstance('Votes', 'ImcModel', array('ignore_request' => true));
					$commentsModel = JModelLegacy::getInstance('Comments', 'ImcModel', array('ignore_request' => true));

					//handle unexpected warnings from model
					set_error_handler(array($this, 'exception_error_handler'));
					$data = $issueModel->getData($id);
					if (is_object($data)) {
						unset($data->coords);
						//merge logs as timeline
						$data->timeline = $logsModel->getItemsByIssue($id);
						//merge hasVoted
						$data->hasVoted = $votesModel->hasVoted($data->id, $userid);
						//merge comments count if enabled
						require_once JPATH_COMPONENT_SITE . '/models/comments.php';
						$data->comments = $commentsModel->count($id, $userid);
					} else {
						throw new Exception(JText::_('COM_IMC_API_ISSUE_NOT_EXIST'));
					}

					restore_error_handler();

					$result = ImcFrontendHelper::sanitizeIssue($data, $userid);

					//check for any restrictions
					if (!$result->myIssue && $result->moderation) {
						throw new Exception(JText::_('COM_IMC_API_ISSUE_UNDER_MODERATION'));
					}
					if ($result->state != 1) {
						throw new Exception(JText::_('COM_IMC_API_ISSUE_NOT_PUBLISHED'));
					}

					//be consistent return as array (of size 1)
					$result = array($result);

					break;
					//create new issue
				case 'POST':
//header("Access-Control-Allow-Origin: *");
//header("Access-Control-Allow-Headers: *");
					if ($id != null) {
						throw new Exception('You cannot use POST to fetch issue. Use GET instead');
					}

					//guests are not allowed to post issues
					//TODO: get this from settings
					if ($userid == 0) {
						throw new Exception("Guests are not allowed to post:".JText::_('COM_IMC_API_NO_GUESTS_NO_POST'));
					}
					//get necessary arguments
					$args = array(
						'catid' => $app->input->getInt('catid'),
						'title' => $app->input->getString('title'),
						'description' => $app->input->getString('description'),
						'address' => $app->input->getString('address'),
						'latitude' => $app->input->getString('lat'),
						'longitude' => $app->input->getString('lng')
					);
					ImcFrontendHelper::checkNullArguments($args);

					//check if category exists
					if (is_null(ImcFrontendHelper::getCategoryNameByCategoryId($args['catid'], true))) {
						throw new Exception(JText::_('COM_IMC_API_CATEGORY_NOT_EXIST'));
					}

					$args['userid'] = $userid;
					$args['created_by'] = $userid;
					$args['stepid'] = ImcFrontendHelper::getPrimaryStepId();
					$args['id'] = 0;
					$args['created'] = ImcFrontendHelper::convert2UTC(date('Y-m-d H:i:s'));
					$args['updated'] = $args['created'];
					$args['note'] = 'modality=' . $app->input->getInt('m_id');
					$args['language'] = '*';
					$args['subgroup'] = 0;
					$m_id  = $app->input->getInt('m_id', 0);
					$args['modality'] = $m_id;

					$tmpTime = time(); //used for temporary id
					$imagedir = 'images/imc';

					//check if post contains files
					$file = $app->input->files->get('files');
					if (!empty($file)) {
						$app->enqueueMessage('Files found in body', 'info');
						require_once JPATH_ROOT . '/components/com_imc/models/fields/multiphoto/server/UploadHandler.php';
						$options = array(
							'script_url' => JRoute::_(JURI::root(true) . '/administrator/index.php?option=com_imc&task=upload.handler&format=json&id=' . $tmpTime . '&imagedir=' . $imagedir . '&' . JSession::getFormToken() . '=1'),
							'upload_dir' => JPATH_ROOT . '/' . $imagedir . '/' . $tmpTime . '/',
							'upload_url' => $imagedir . '/' . $tmpTime . '/',
							'param_name' => 'files',
							'imc_api' => true

						);
						$upload_handler = new UploadHandler($options);
						if (isset($upload_handler->imc_api)) {
							$files_json = json_decode($upload_handler->imc_api);
							$args['photo'] = json_encode(array('isnew' => 1, 'id' => $tmpTime, 'imagedir' => $imagedir, 'files' => $files_json->files));
							$app->enqueueMessage('File(s) uploaded successfully', 'info');
						} else {
							throw new Exception(JText::_('COM_IMC_API_UPLOAD_FAILED'));
						}
					} else {
						$app->enqueueMessage('No files found in body', 'info');
						$args['photo'] = json_encode(array('isnew' => 1, 'id' => $tmpTime, 'imagedir' => $imagedir, 'files' => array()));
					}

					//get issueForm model and save
					$issueFormModel = JModelLegacy::getInstance('IssueForm', 'ImcModel', array('ignore_request' => true));

					//handle unexpected warnings from model
					set_error_handler(array($this, 'exception_error_handler'));
					$issueFormModel->save($args);
					$insertid = JFactory::getApplication()->getUserState('com_imc.edit.issue.insertid');

					//call post save hook
					require_once JPATH_COMPONENT . '/controllers/issueform.php';
					$issueFormController = new ImcControllerIssueForm();
					$issueFormController->postSaveHook($issueFormModel, $args);
					restore_error_handler();

					$result = array('issueid' => $insertid);

					//be consistent return as array (of size 1)
					$result = array($result);
					break;
					//update existing issue
				case 'PUT':
				case 'PATCH':
					if ($id == null) {
						throw new Exception('Id is not set');
					}
					break;
				default:
					throw new Exception('HTTP method is not supported');
			}
			header('Content-type: application/json');
			echo new JResponseJson($result, 'Issue action completed successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 500 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function steps()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			self::validateRequest();

			if ($app->input->getMethod() != 'GET') {
				throw new Exception('You cannot use other method than GET to fetch steps');
			}

			//get necessary arguments
			$ts = $app->input->getString('ts');

			//get steps model
			$stepsModel = JModelLegacy::getInstance('Steps', 'ImcModel', array('ignore_request' => true));
			//set states
			$stepsModel->setState('filter.state', 1);
			//$stepsModel->setState('filter.imcapi.ordering', 'ordering');
			//$stepsModel->setState('filter.imcapi.direction', 'ASC');

			if (!is_null($ts)) {
				//get date from ts
				$ts = gmdate('Y-m-d H:i:s', $ts);
				$stepsModel->setState('filter.imcapi.ts', $ts);
			}

			//handle unexpected warnings from model
			set_error_handler(array($this, 'exception_error_handler'));
			//get items and sanitize them
			$data = $stepsModel->getItems();
			restore_error_handler();
			$result = ImcFrontendHelper::sanitizeSteps($data);

			$app->enqueueMessage('size: ' . sizeof($result), 'info');
			header('Content-type: application/json');
			echo new JResponseJson($result, 'Steps fetched successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function categories()
	{
		$result = null;
		$categories = null;
		$app = JFactory::getApplication();
		try {
			self::validateRequest();

			if ($app->input->getMethod() != 'GET') {
				throw new Exception('You cannot use other method than GET to fetch categories');
			}

			$ts = $app->input->getString('ts');
			$toFlat = $app->input->getString('toFlat');

			//handle unexpected warnings from JCategories
			set_error_handler(array($this, 'exception_error_handler'));
			$categories = ImcFrontendHelper::getCategories(false);

			if (!is_null($ts)) {
				if (!ImcFrontendHelper::isValidTimeStamp($ts)) {
					throw new Exception('Invalid timestamp');
				}
				foreach ($result as $cat) {
					//TODO: unset categories prior to ts (how to handle children?)
				}
			}


			$result = $categories;

			if (!is_null($toFlat)) {
				if ($toFlat) {
					$categoriesArray = json_decode(json_encode($categories), true);
					$result = ImcFrontendHelper::searchByKey($categoriesArray, 'title');

					if (!empty($result)) {
						for ($i = 0; $i < count($result); $i++) {
							unset($result[$i]['children']);
						}
					}
				}
			}


			restore_error_handler();
			$app->enqueueMessage('size: ' . sizeof($result), 'info');
			header('Content-type: application/json');
			echo new JResponseJson($result, 'Categories fetched successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function userexists()
	{
		$result = null;
		$usernameExists = false;
		$emailExists = false;

		$app = JFactory::getApplication();
		try {
			self::validateRequest();

			if ($app->input->getMethod() != 'GET') {
				throw new Exception('You cannot use other method than GET to check userexists');
			}

			//get necessary arguments
			$args = array(
				'username' => $app->input->getString('username'),
				'email' => $app->input->getString('email')
			);
			ImcFrontendHelper::checkNullArguments($args);
			$userid = JUserHelper::getUserId($args['username']);
			if ($userid > 0) {
				$app->enqueueMessage(JText::_('COM_IMC_API_USERNAME_EXISTS'), 'info');
				$usernameExists = true;
			}

			if (ImcFrontendHelper::emailExists($args['email'])) {
				$app->enqueueMessage(JText::_('COM_IMC_API_EMAIL_EXISTS'), 'info');
				$emailExists = true;
			}

			$result = array($usernameExists || $emailExists);

			header('Content-type: application/json');
			echo new JResponseJson($result, 'Check user action completed successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function user()
	{
		$result = null;
		$app = JFactory::getApplication();

		try {
			switch ($app->input->getMethod()) {
				case 'GET':
					$userid = self::validateRequest();
					$app->enqueueMessage('User is valid', 'info');

					//return also user votes to prevent internet loss conflicts
					$votesModel = JModelLegacy::getInstance('Votes', 'ImcModel', array('ignore_request' => true));
					$votesModel->setState('filter.imcapi.userid', $userid);
					$votesModel->setState('filter.state', 1);
					//handle unexpected warnings from model
					set_error_handler(array($this, 'exception_error_handler'));
					//get items and sanitize them
					$data = $votesModel->getItems();
					$votedIssues = ImcFrontendHelper::sanitizeVotes($data);
					restore_error_handler();

					//simplify votedIssues
					// $voted = array();
					// foreach ($votedIssues as $votedIssue) {
					// 	$voted[] = $votedIssue->issueid;
					// }

					$fullname = JFactory::getUser($userid)->name;

					//check is user is admin
					//TODO: Check ACL according to user's department, etc.
					$isAdmin = ImcHelper::getActions(JFactory::getUser($userid))->get('core.admin');
					if (is_null($isAdmin)) {
						$isAdmin = false;
					}

					//count submitted issues 
					$db = JFactory::getDbo();
					$query = $db->getQuery(true);
					$query->select('COUNT(id) as c');
					$query->from('`#__imc_issues` AS a');
					$query->where("a.created_by = '" . $userid . "'");
					$query->where("a.state >= 0");
					$db->setQuery($query);
					$result = $db->loadObject();
					$countIssues = $result->c;

					$result = array(
						'userid' => $userid,
						'fullname' => $fullname,
						'isAdmin' => $isAdmin,
						//'votedIssues' => $voted,
						'countVotes' => count($votedIssues),
						'countIssues' => $countIssues
					);

					//be consistent return as array (of size 1)
					$result = array($result);

					break;
					//create new user
				case 'POST':
					$userInfo = self::validateRequest(true);

					if (JComponentHelper::getParams('com_users')->get('allowUserRegistration') == 0) {
						throw new Exception(JText::_('COM_IMC_API_REGISTRATION_NOT_ALLOWED'));
					}

					//get necessary arguments
					$args = array(
						'name' => $app->input->getString('name'),
						'email' => $app->input->getString('email')
					);
					ImcFrontendHelper::checkNullArguments($args);

					//populate other data
					$args['username'] = $userInfo['username'];
					$args['password1'] = $userInfo['password'];
					$args['email1'] = $args['email'];
					$args['phone'] = $app->input->getString('phone', null);
					$args['address'] = $app->input->getString('address', null);

					//handle unexpected warnings from model
					set_error_handler(array($this, 'exception_error_handler'));

					JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_users/models/');
					$userModel = JModelLegacy::getInstance('Registration', 'UsersModel');
					$result = $userModel->register($args);
					if (!$result) {
						throw new Exception($userModel->getError());
					}
					restore_error_handler();

					if ($result === 'adminactivate') {
						$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_COMPLETE_VERIFY'), 'info');
					} elseif ($result === 'useractivate') {
						$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_COMPLETE_ACTIVATE'), 'info');
					} else {
						$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_SAVE_SUCCESS'), 'info');
					}

					//populate user profile
					if (!is_null($args['phone'])) {
						$userid = JUserHelper::getUserId($args['email']);
						$db = JFactory::getDbo();
						$tuples = array();
						$order = 1;
						$data = [
							'phone' => $args['phone'],
						];
						foreach ($data as $k => $v) {
							$tuples[] = '(' . $userid . ', ' . $db->quote('profile.' . $k) . ', ' . $db->quote(json_encode($v)) . ', ' . $order++ . ')';
						}
						$db->setQuery('INSERT INTO ' . $db->quoteName('#__user_profiles') . ' VALUES ' . implode(', ', $tuples));
						$db->execute();
					}

					//be consistent return as array (of size 1)
					$result = array($result);
					break;
					//update existing issue
				case 'PUT':
				case 'PATCH':
					$id = $app->input->getInt('id', null);
					if ($id == null) {
						throw new Exception('Id is not set');
					}
					break;
				default:
					throw new Exception('HTTP method is not supported');
			}

			header('Content-type: application/json');
			echo new JResponseJson($result, $msg = 'User action completed successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function vote()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();

			//get necessary arguments
			$id = $app->input->getInt('id', null);
			$modality = $app->input->getInt('m_id', null);

			//guests are not allowed to post/delete votes
			//TODO: get this from settings
			if ($userid == 0) {
				throw new Exception(JText::_('COM_IMC_API_GUESTS_NO_VOTE'));
			}

			//get votes model
			$votesModel = JModelLegacy::getInstance('Votes', 'ImcModel', array('ignore_request' => true));

			switch ($app->input->getMethod()) {
					//create vote
				case 'POST':
					//handle unexpected warnings from model
					set_error_handler(array($this, 'exception_error_handler'));
					$voting = $votesModel->add($id, $userid, $modality);
					if ($voting['code'] != 1) {
						throw new Exception($voting['msg']);
					}
					restore_error_handler();

					$app->enqueueMessage($voting['msg'], 'info');
					$result = array('votes' => (int) $voting['votes']);
					break;
					//delete vote
				case 'GET':
				case 'DELETE':
					//handle unexpected warnings from model
					set_error_handler(array($this, 'exception_error_handler'));
					$voting = $votesModel->remove($id, $userid, $modality);

					if ($voting['code'] != 1) {
						throw new Exception($voting['msg']);
					}
					restore_error_handler();

					$app->enqueueMessage($voting['msg'], 'info');
					$result = array('votes' => (int) $voting['votes']);
					break;
				default:
					throw new Exception('HTTP method is not supported');
			}

			//be consistent return as array (of size 1)
			$result = array($result);
			header('Content-type: application/json');
			echo new JResponseJson($result, 'Vote action completed successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function votes()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();

			if ($app->input->getMethod() != 'GET') {
				throw new Exception('You cannot use other method than GET to fetch votes');
			}

			//get necessary arguments
			$id = $app->input->getInt('id', null);
			$ts = $app->input->getString('ts', null);

			//get votes model
			$votesModel = JModelLegacy::getInstance('Votes', 'ImcModel', array('ignore_request' => true));
			if (!is_null($ts)) {
				//get date from ts
				$ts = gmdate('Y-m-d H:i:s', $ts);
				$votesModel->setState('filter.imcapi.ts', $ts);
			}
			if (is_null($id)) {
				$votesModel->setState('filter.imcapi.userid', $userid);
			} else {
				$votesModel->setState('filter.issueid', $id);
			}

			$votesModel->setState('filter.state', 1);
			//handle unexpected warnings from model
			set_error_handler(array($this, 'exception_error_handler'));
			//get items and sanitize them
			$data = $votesModel->getItems();
			$result = ImcFrontendHelper::sanitizeVotes($data);
			restore_error_handler();

			$app->enqueueMessage('size: ' . sizeof($result), 'info');
			header('Content-type: application/json');
			echo new JResponseJson($result, 'Votes fetched successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function voters()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();

			if ($app->input->getMethod() != 'GET') {
				throw new Exception('You cannot use other method than GET to fetch votes');
			}

			//get necessary arguments
			$ts = $app->input->getString('ts', null);

			//get votes model
			$votesModel = JModelLegacy::getInstance('Votes', 'ImcModel', array('ignore_request' => true));
			if (!is_null($ts)) {
				//get date from ts
				$ts = gmdate('Y-m-d H:i:s', $ts);
				$votesModel->setState('filter.imcapi.ts', $ts);
			}

			//handle unexpected warnings from model
			set_error_handler(array($this, 'exception_error_handler'));
			//get items and sanitize them
			$data = $votesModel->getItems();
			$result = ImcFrontendHelper::sanitizeVotes($data);
			restore_error_handler();

			$app->enqueueMessage('size: ' . sizeof($result), 'info');
			header('Content-type: application/json');
			echo new JResponseJson($result, 'Voters fetched successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function timeline()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();
			//get necessary arguments
			$id = $app->input->getInt('id', null);

			switch ($app->input->getMethod()) {
					//fetch issue's timeline
				case 'GET':
					if ($id == null) {
						throw new Exception('Id is not set');
					}

					//get logs model
					$issueModel = JModelLegacy::getInstance('Issue', 'ImcModel', array('ignore_request' => true));
					$logsModel = JModelLegacy::getInstance('Logs', 'ImcModel', array('ignore_request' => true));

					//handle unexpected warnings from model
					set_error_handler(array($this, 'exception_error_handler'));
					$data = $issueModel->getData($id);

					if (!is_object($data)) {
						throw new Exception('Issue does not exist');
					}

					$result = ImcFrontendHelper::sanitizeIssue($data, $userid);
					if ($result->state != 1) {
						throw new Exception('Issue is not published');
					}
					if (!$result->myIssue && $result->moderation) {
						$app->enqueueMessage('Issue is under moderation', 'info');
					}

					$data = $logsModel->getItemsByIssue($id);
					$result = ImcFrontendHelper::sanitizeLogs($data);
					restore_error_handler();
					break;
				case 'POST':
					if ($id != null) {
						throw new Exception('You cannot use POST to fetch issue. Use GET instead');
					}
					//TODO: Future implementation
					break;
					//update existing issue
				case 'PUT':
				case 'PATCH':
					if ($id == null) {
						throw new Exception('Id is not set');
					}
					break;
				default:
					throw new Exception('HTTP method is not supported');
			}

			header('Content-type: application/json');
			echo new JResponseJson($result, 'Timeline action completed successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}


	public function step()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();

			//get necessary arguments
			$id = $app->input->getInt('id', null); //issue id
			$stepid = $app->input->getInt('stepId', null); //step id
			$descr = $app->input->getString('description', null); //reason to change step

			switch ($app->input->getMethod()) {

				case 'PUT':
				case 'PATCH':
					if ($id == null) {
						throw new Exception('Id is not set');
					}
					if ($stepid == null) {
						throw new Exception('Stepid is not set');
					}
					if ($descr == null) {
						throw new Exception('Description is not set');
					}

					//guests are not allowed to post issues
					if ($userid == 0) {
						throw new Exception(JText::_('COM_IMC_API_NO_GUESTS_NO_POST'));
					}

					//get logs model
					$issueModel = JModelLegacy::getInstance('Issue', 'ImcModel', array('ignore_request' => true));
					$logModel = JModelLegacy::getInstance('Log', 'ImcModel', array('ignore_request' => true));

					$stepsModel = JModelLegacy::getInstance('Steps', 'ImcModel', array('ignore_request' => true));
					$stepsModel->setState('filter.state', 1);

					//handle unexpected warnings from model
					set_error_handler(array($this, 'exception_error_handler'));
					$data = $issueModel->getData($id);

					if (!is_object($data)) {
						throw new Exception('Issue does not exist');
					}

					$steps = $stepsModel->getItems();
					$found = false;
					foreach ($steps as $step) {
						if ($step->id == $stepid) {
							$found = true;
							break;
						}
					}
					if (!$found) {
						throw new Exception('Requested stepId does not exist');
					}

					$issue = ImcFrontendHelper::sanitizeIssue($data, $userid);
					if ($issue->state != 1) {
						throw new Exception('Issue is not published');
					}
					if (!$issue->myIssue && $issue->moderation) {
						$app->enqueueMessage('Issue is under moderation', 'info');
					}


					//1. update stepid of the issue
					$args['updated_by'] = $userid;
					$args['stepid'] = $stepid;
					$args['id'] = $id;
					//$issueFormModel = JModelLegacy::getInstance( 'IssueForm', 'ImcModel', array('ignore_request' => true) );
					$issueModel->getTable()->save($args);

					//2. create log
					$log['id'] = null;
					$log['issueid'] = $id;
					$log['stepid'] = $stepid;
					$log['description'] = $descr;
					$log['action'] = 'step';
					$log['state'] = 1;
					$log['created_by'] = $userid;
					$log['created'] = ImcFrontendHelper::convert2UTC(date('Y-m-d H:i:s'));
					$log['updated'] = $log['created'];
					$log['language'] = '*';

					$logModel->getTable()->save($log);

					$logsModel = JModelLegacy::getInstance('Logs', 'ImcModel', array('ignore_request' => true));
					$logs = $logsModel->getItemsByIssue($id);
					$result = array('issueid' => $id, 'stepid' => $stepid, 'logs' => $logs);

					restore_error_handler();
					break;
				default:
					throw new Exception('HTTP method is not supported');
			}

			header('Content-type: application/json');
			echo new JResponseJson($result, 'Updating step action is completed successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}


	public function modifications()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();

			if ($app->input->getMethod() != 'GET' && $app->input->getMethod() != 'HEAD') {
				throw new Exception('You cannot use other method than GET or HEAD to fetch modifications');
			}

			$args = array(
				'ts' => $app->input->getString('ts'),
			);
			ImcFrontendHelper::checkNullArguments($args);

			if (!ImcFrontendHelper::isValidTimeStamp($args['ts'])) {
				throw new Exception('Invalid timestamp');
			}

			switch ($app->input->getMethod()) {
				case 'GET':
					//handle unexpected warnings
					set_error_handler(array($this, 'exception_error_handler'));
					$result = self::getModifications($args['ts'], $userid);
					restore_error_handler();

					//be consistent return as array (of size 1)
					$result = array($result);

					$response = new JResponseJson($result, 'Modifications since timestamp fetched successfully');
					$length = mb_strlen($response, 'UTF-8');
					header('Content-Length: ' . $length);
					header('Content-type: application/json');
					echo $response;
					exit();

					break;
				case 'HEAD':
					//handle unexpected warnings
					set_error_handler(array($this, 'exception_error_handler'));
					//$result = self::getModifications($args['ts'], $userid, false);
					$count = ImcFrontendHelper::countModifiedIssues($args['ts'], 1000);
					$result = ($count * 700) + 3000;
					restore_error_handler();
					header('Content-type: application/json');
					header('Content-Length: ' . $result);
					break;
				default:
					throw new Exception('HTTP method is not supported');
			}
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	private function getModifications($ts, $userid, $sanitize = true)
	{
		$offsetDate = JDate::getInstance(date("Y-m-d H:i:s", $ts), JFactory::getConfig()->get('offset'));
		$offset = $offsetDate->format('Y-m-d H:i:s');

		//1. get issues
		$issuesModel = JModelLegacy::getInstance('Issues', 'ImcModel', array('ignore_request' => true));
		$issuesModel->setState('filter.imcapi.ts', $offset);
		$issuesModel->setState('filter.imcapi.limit', 1000);
		$issuesModel->setState('filter.imcapi.userid', $userid);
		$issuesModel->setState('filter.imcapi.raw', true); //Do not unset anything in getItems()
		$data = $issuesModel->getItems();
		$issues = $data;
		if ($sanitize) {
			$issues = ImcFrontendHelper::sanitizeIssues($data, $userid, true);
		}

		//2. get categories
		$categories = ImcFrontendHelper::getModifiedCategories($offset);
		if ($sanitize) {
			$categories = ImcFrontendHelper::sanitizeCategories($categories);
		}

		//3. get steps
		$stepsModel = JModelLegacy::getInstance('Steps', 'ImcModel', array('ignore_request' => true));
		$stepsModel->setState('filter.imcapi.ts', $offset);
		$stepsModel->setState('filter.imcapi.raw', true);
		$data = $stepsModel->getItems();
		$steps = $data;
		if ($sanitize) {
			$steps = ImcFrontendHelper::sanitizeSteps($data, true);
		}

		//4. get votes
		$data = ImcFrontendHelper::getModifiedVotes($offset);
		$votes = $data;
		if ($sanitize) {
			$votes = ImcFrontendHelper::sanitizeModifiedVotes($data);
		}

		//5. full categories structure if modified categories are found
		$allCategories = array();
		if (!empty($categories)) {
			$allCategories = ImcFrontendHelper::getCategories(false);
		}

		$info = array(
			'count_issues' => sizeof($issues),
			'count_categories' => sizeof($categories),
			'count_steps' => sizeof($steps),
			'count_votes' => sizeof($votes),
			'count_allcategories' => sizeof($allCategories),
			'given_ts' => $ts,
			'given_date' => gmdate('Y-m-d H:i:s', $ts),
			'offset' => $offsetDate
		);

		$updated = array(
			'issues'     => $issues,
			'categories' => $categories,
			'steps'      => $steps,
			'votes'      => $votes,
			'allcategories' => $allCategories
		);

		return array('info' => $info, 'updated' => $updated);
	}

	public function slogin()
	{
		$result = null;
		$app = JFactory::getApplication();

		try {
			switch ($app->input->getMethod()) {
				case 'POST':
					$userInfo = self::validateRequest(true);

					//get necessary arguments
					$provider = $app->input->getString('provider', 'whatever');
					$fullname = $app->input->getString('fullname', null);
					$phone = $app->input->getString('phone', null);
					$address = $app->input->getString('address', null);
					$secret = JComponentHelper::getParams('com_slogin')->get('secret');

					if (!JPluginHelper::isEnabled('slogin_auth', $provider)) {
						throw new Exception('sLogin is not available or given provider is not supported');
					}

					$slogin_id = $userInfo['username']; //'111309200021517229400';
					$username = str_replace(" ", "-", $fullname) . '-' . $provider; //'Ioannis-Tsampoulatidis-google';
					$email = $userInfo['password'];
					$password = md5($slogin_id . $provider . $secret);

					//handle unexpected warnings
					set_error_handler(array($this, 'exception_error_handler'));

					//check if user exists on social table
					$sUser = ImcFrontendHelper::getSocialUser($slogin_id);
					if (is_null($sUser)) {
						//register new user by temporary deactivating user activation, etc
						$params = JComponentHelper::getParams('com_users');
						$plugin = JPluginHelper::getPlugin('user', 'joomla');
						$plg_params = new JRegistry($plugin->params);

						$allowUserRegistration = $params->get('allowUserRegistration');
						$useractivation = $params->get('useractivation');
						$sendpassword = $params->get('sendpassword');
						$notificationMail = $plg_params->get('mail_to_user');

						$params->set('allowUserRegistration', 1);
						$params->set('useractivation', 0);
						$params->set('sendpassword', 0);
						$plg_params->set('mail_to_user', 0);
						$username = ImcFrontendHelper::checkUniqueName($username);
						$args = array(
							'name' => $fullname,
							'username' => $username,
							'password1' => $password,
							'email1' => ImcFrontendHelper::getFreeMail($email),
							'phone' => $phone,
							'address' => $address
						);

						JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_users/models/');
						$userModel = JModelLegacy::getInstance('Registration', 'UsersModel');
						$userid = (int) $userModel->register($args);

						//restore params
						$params->set('useractivation', $useractivation);
						$params->set('allowUserRegistration', $allowUserRegistration);
						$params->set('sendpassword', $sendpassword);
						$plg_params->set('mail_to_user', $notificationMail);

						if ($userid == 0) {
							$userid = ImcFrontendHelper::getUserId($args['username'], $args['email1']);
						}

						//create new social user
						ImcFrontendHelper::createSloginUser($userid, $slogin_id, $provider);

						//create new social profile
						ImcFrontendHelper::createSocialProfile($userid, $slogin_id, $provider, $fullname, '', $email, $phone);
					} else {
						//user exists
						$userid = $sUser['user_id'];

						$user = JUser::getInstance($userid);
						$hashed_password = $user->password;

						if ($user->username != $username) {
							//update joomla user username
							$username = ImcFrontendHelper::checkUniqueName($username);
							ImcFrontendHelper::updateUserUsername($userid, $username);
							$newName = $fullname;
							ImcFrontendHelper::updateUserName($userid, $newName);
						}

						if ($user->email != $email) {
							//update joomla user email
							$newEmail = ImcFrontendHelper::getFreeMail($email);
							ImcFrontendHelper::updateUserEmail($userid, $newEmail);
						}

						//update social profile
						ImcFrontendHelper::updateSocialProfile($userid, $slogin_id, $fullname, '', $email, $phone);

						//TODO: update imc profile if plugin is enabled
						//ImcFrontendHelper::checkImcProfile($userid, $phone, $address);

						//match password
						$match = JUserHelper::verifyPassword($password, $hashed_password, $userid);
						if (!$match) {
							throw new Exception('Unexpected error: User cannot authenticate');
						}
					}

					//all set, return same as GET/user
					$votesModel = JModelLegacy::getInstance('Votes', 'ImcModel', array('ignore_request' => true));
					$votesModel->setState('filter.imcapi.userid', $userid);
					$votesModel->setState('filter.state', 1);
					//get items and sanitize them
					$data = $votesModel->getItems();
					$votedIssues = ImcFrontendHelper::sanitizeVotes($data);
					restore_error_handler();

					//..and also the user's real credentials encrypted
					$credentials = array('u' => $username, 'p' => $password);
					$encryptedCredentials = $this->mcrypt->encrypt(base64_encode(json_encode($credentials)));
					$result = array(
						'userid' => $userid,
						'u' => $username,  //temporary for debug
						'p' => $password,  //temporary for debug
						'credentials' => $encryptedCredentials,
						'fullname' => $fullname,
						'votedIssues' => $votedIssues
					);

					//be consistent return as array (of size 1)
					$result = array($result);
					break;

				default:
					throw new Exception('HTTP method is not supported');
			}

			header('Content-type: application/json');
			echo new JResponseJson($result, $msg = 'User action completed successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function comments()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();

			if ($app->input->getMethod() != 'GET') {
				throw new Exception('You cannot use other method than GET to fetch comments');
			}

			//get necessary arguments
			$issueid = $app->input->getInt('id', null);
			if ($issueid == null) {
				throw new Exception('issueId is not set');
			}
			$parentid = $app->input->getInt('parentid', null);

			//check if issue exists
			$issueModel = JModelLegacy::getInstance('Issue', 'ImcModel', array('ignore_request' => true));
			$issue = $issueModel->getData($issueid);
			if (!is_object($issue)) {
				throw new Exception(JText::_('COM_IMC_API_ISSUE_NOT_EXIST'));
			}

			//get comments model
			$commentsModel = JModelLegacy::getInstance('Comments', 'ImcModel', array('ignore_request' => true));
			//set states
			$commentsModel->setState('filter.imcapi.userid', $userid);

			if (!is_null($parentid)) {
				$commentsModel->setState('imc.filter.parentid', $parentid);
			}

			if ($userid == 0) {
				$commentsModel->setState('filter.imcapi.guest', true);
			}
			$commentsModel->setState('imc.filter.issueid', $issueid);

			//handle unexpected warnings from model
			set_error_handler(array($this, 'exception_error_handler'));
			//get items and sanitize them
			$data = $commentsModel->getItems();
			$results = ImcFrontendHelper::sanitizeComments($data, $userid);

			$params = $app->getParams('com_imc');
			$commentsdisplayname = $params->get('commentsdisplayname');

			foreach ($results as &$item) {
				$created_by_admin = (bool) $item->isAdmin;
				if ($commentsdisplayname == 'dpt' && $created_by_admin) {
					//show department name
					$dpts = array();
					//get user groups higher than 9
					$usergroups = JAccess::getGroupsByUser($item->created_by, false);
					for ($i = 0; $i < count($usergroups); $i++) {
						if ($usergroups[$i] > 9) {
							$dpts[] = ImcFrontendHelper::getGroupNameById($usergroups[$i]);
						}
					}

					$item->fullname = implode(', ', $dpts);
				}
			}

			$app->enqueueMessage('size: ' . sizeof($results), 'info');
			restore_error_handler();

			header('Content-type: application/json');
			echo new JResponseJson($results, 'Comments fetched successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function comment()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();

			if ($app->input->getMethod() != 'POST') {
				throw new Exception('You cannot use other method than POST to create comment');
			}

			$commentCtrl = new ImcControllerComments();
			$commentCtrl->postComment(true, $userid);
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}

	public function issuesbycategory()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			self::validateRequest();

			if ($app->input->getMethod() != 'GET') {
				throw new Exception('You cannot use other method than GET to fetch steps');
			}

			//get necessary arguments
			$ts = null;
			$catid = null;
			$ts = $app->input->getString('ts');
			$catid = $app->input->getString('catid');

			if (!is_null($ts)) {
				if (!ImcFrontendHelper::isValidTimeStamp($ts)) {
					throw new Exception('Invalid timestamp');
				}
				//get date from ts
				$ts = gmdate('Y-m-d H:i:s', $ts);
			}

			//handle unexpected warnings from model
			set_error_handler(array($this, 'exception_error_handler'));
			//get items and sanitize them
			$result = ImcFrontendHelper::issuesByCategory($ts, $catid);
			restore_error_handler();

			$app->enqueueMessage('size: ' . sizeof($result), 'info');
			header('Content-type: application/json');
			echo new JResponseJson($result, 'Issues fetched successfully');
			exit();
		} catch (Exception $e) {
			header("HTTP/1.0 202 Accepted");
			header('Content-type: application/json');
			echo new JResponseJson($e);
			exit();
		}
	}


	private function raw_json_encode($input, $flags = 0)
	{
		$fails = implode('|', array_filter(array(
			'\\\\',
			$flags & JSON_HEX_TAG ? 'u003[CE]' : '',
			$flags & JSON_HEX_AMP ? 'u0026' : '',
			$flags & JSON_HEX_APOS ? 'u0027' : '',
			$flags & JSON_HEX_QUOT ? 'u0022' : '',
		)));
		$pattern = "/\\\\(?:(?:$fails)(*SKIP)(*FAIL)|u([0-9a-fA-F]{4}))/";
		$callback = function ($m) {
			return html_entity_decode("&#x$m[1];", ENT_QUOTES, 'UTF-8');
		};
		return preg_replace_callback($pattern, $callback, json_encode($input, $flags));
	}
}
