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
require_once JPATH_COMPONENT_SITE . '/helpers/MCrypt.php';
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

class ImcControllerApi extends ImcController
{
    private $mcrypt;
    private $keyModel;
    private $params;

    function __construct()
    {
        $this->params = JComponentHelper::getParams('com_imc');
    	$this->mcrypt = new MCrypt();
        JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models');
        $this->keyModel = JModelLegacy::getInstance( 'Key', 'ImcModel', array('ignore_request' => true) );
    	parent::__construct();

    }

    public function exception_error_handler($errno, $errstr, $errfile, $errline){
        $ee = new ErrorException($errstr, 0, $errno, $errfile, $errline);
        JFactory::getApplication()->enqueueMessage($ee, 'error');
        throw $ee;
    }

    private function validateRequest($isNew = false)
    {
        $app = JFactory::getApplication();
        $token = $app->input->getString('token');
        $m_id  = $app->input->getInt('m_id');
        $l     = $app->input->getString('l');

        //1. check necessary arguments are exist
        if(is_null($token) || is_null($m_id) || is_null($l) ){
            $app->enqueueMessage('Either token, m_id (modality), or l (language) are missing', 'error');
            throw new Exception('Request is invalid');
        }

        //set language
        ImcFrontendHelper::setLanguage($app->input->getString('l'), array('com_users', 'com_imc'));

        //check for nonce (existing token)
        if($this->params->get('advancedsecurity'))
        {
            if (ImcModelTokens::exists($token)) {
                throw new Exception('Token is already used');
            }
        }
        //2. get the appropriate key according to given modality
        $result = $this->keyModel->getItem($m_id);
        $key = $result->skey;
        if(strlen($key) < 16){
            $app->enqueueMessage('Secret key is not 16 characters', 'error');
            throw new Exception('Secret key is invalid. Contact administrator');
        }
        else {
            $this->mcrypt->setKey($key);
        }

        //3. decrypt and check token validity
        $decryptedToken = $this->mcrypt->decrypt($token);
	    $decryptedToken = base64_decode($decryptedToken);
        $objToken = json_decode($decryptedToken);

        if(!is_object($objToken)){
            throw new Exception('Token is invalid');
        }

        if(!isset($objToken->u) || !isset($objToken->p) || !isset($objToken->t) || !isset($objToken->r)) {
            throw new Exception('Token is not well formatted');
        }

        if($this->params->get('advancedsecurity')) {
            if ((time() - $objToken->t) > 3 * 60) {
                throw new Exception('Token has expired');
            }
        }

        //4. authenticate user
        $userid = JUserHelper::getUserId($objToken->u);
        $user = JFactory::getUser($userid);
		$userInfo = array();
		if ($isNew) {
			$userInfo['username'] =$objToken->u;
			$userInfo['password'] =$objToken->p;
		}
		else
		{
			if($objToken->u == 'imc-guest' && $objToken->p == 'imc-guest')
			{
				$userid = 0;
			}
			else
			{
		        $match = JUserHelper::verifyPassword($objToken->p, $user->password, $userid);
		        if(!$match){
		            $app->enqueueMessage(JText::_('COM_IMC_API_USERNAME_PASSWORD_NO_MATCH'), 'error');
		            throw new Exception('Token does not match');
		        }

		        if($user->block){
		            $app->enqueueMessage(JText::_('COM_IMC_API_USER_NOT_ACTIVATED'), 'error');
		            throw new Exception(JText::_('COM_IMC_API_USER_BLOCKED'));
		        }
			}
		}

        //5. populate token table
        if($this->params->get('advancedsecurity')) {
            $record = new stdClass();
            $record->key_id = $m_id;
            $record->user_id = $userid;
            //$record->json_size = $json_size;
            $record->method = $app->input->getMethod();
            $record->token = $token;
            $record->unixtime = $objToken->t;
            ImcModelTokens::insertToken($record); //this static method throws exception on error
        }

        return $isNew ? $userInfo : (int)$userid;
    }

    public function languages()
    {
		$result = null;
		$app = JFactory::getApplication();
		try {
		    self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch languages');
			}

            $availLanguages = JFactory::getLanguage()->getKnownLanguages();
            $languages = array();
            foreach ($availLanguages as $key => $value) {
                array_push($languages, $key);
            }

            $result = $languages;
            $app->enqueueMessage('size: '.sizeof($result), 'info');
			echo new JResponseJson($result, 'Languages fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
    }

	public function rawissues()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
		    self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch raw issues');
			}

			//get necessary arguments
			$minLat = $app->input->getString('minLat');
			$maxLat = $app->input->getString('maxLat');
			$minLng = $app->input->getString('minLng');
			$maxLng = $app->input->getString('maxLng');
			$ts = $app->input->getString('ts');
			$prior_to = $app->input->getString('prior_to');


			if(!is_null($ts))
			{
				if(!ImcFrontendHelper::isValidTimeStamp($ts))
				{
					throw new Exception('Invalid timestamp');
				}

				//get date from ts
				$ts = gmdate('Y-m-d H:i:s', $ts);
			}
			if(!is_null($prior_to))
			{
				if(!ImcFrontendHelper::isValidTimeStamp($prior_to))
				{
					throw new Exception('Invalid prior_to timestamp');
				}
				//get date from ts
				$prior_to = gmdate('Y-m-d H:i:s', $prior_to);
			}

			$data = ImcFrontendHelper::getRawIssues($ts, $prior_to, $minLat, $maxLat, $minLng, $maxLng);
			$app->enqueueMessage('size: '.sizeof($data), 'info');
			restore_error_handler();

			echo new JResponseJson($data, 'Issues fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}

	public function issues()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
		    $userid = self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch issues');
			}

			//get necessary arguments
			$minLat = $app->input->getString('minLat');
			$maxLat = $app->input->getString('maxLat');
			$minLng = $app->input->getString('minLng');
			$maxLng = $app->input->getString('maxLng');
			$owned = $app->input->get('owned', false);
			$lim = $app->input->getInt('lim', 0);
			$ts = $app->input->getString('ts');
			$prior_to = $app->input->getString('prior_to');

            //get issues model
            $issuesModel = JModelLegacy::getInstance( 'Issues', 'ImcModel', array('ignore_request' => true) );
            //set states
            $issuesModel->setState('filter.owned', ($owned === 'true' ? 'yes' : 'no'));
            $issuesModel->setState('filter.imcapi.userid', $userid);
            if($userid == 0)
            {
                $issuesModel->setState('filter.imcapi.guest', true);
            }
            //$issuesModel->setState('filter.imcapi.ordering', 'id');
            //$issuesModel->setState('filter.imcapi.direction', 'DESC');

            //$issuesModel->setState('list.limit', $lim);
            $issuesModel->setState('filter.imcapi.limit', $lim);


			if(!is_null($minLat) && !is_null($maxLat) && !is_null($minLng) && !is_null($maxLng))
			{
				$issuesModel->setState('filter.imcapi.minLat', $minLat);
				$issuesModel->setState('filter.imcapi.maxLat', $maxLat);
				$issuesModel->setState('filter.imcapi.minLng', $minLng);
				$issuesModel->setState('filter.imcapi.maxLng', $maxLng);
			}

			if(!is_null($ts))
			{
			    if(!ImcFrontendHelper::isValidTimeStamp($ts))
                {
                    throw new Exception('Invalid timestamp');
                }

				//get date from ts
	            $ts = gmdate('Y-m-d H:i:s', $ts);
				$issuesModel->setState('filter.imcapi.ts', $ts);
			}
			if(!is_null($prior_to))
			{
			    if(!ImcFrontendHelper::isValidTimeStamp($prior_to))
                {
                    throw new Exception('Invalid prior_to timestamp');
                }
				//get date from ts
	            $prior_to = gmdate('Y-m-d H:i:s', $prior_to);
				$issuesModel->setState('filter.imcapi.priorto', $prior_to);
			}

            //handle unexpected warnings from model
            set_error_handler(array($this, 'exception_error_handler'));
			//get items and sanitize them
			$data = $issuesModel->getItems();
			$result = ImcFrontendHelper::sanitizeIssues($data, $userid);
			$app->enqueueMessage('size: '.sizeof($result), 'info');
			restore_error_handler();

			echo new JResponseJson($result, 'Issues fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
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

            switch($app->input->getMethod())
            {
                //fetch existing issue
                case 'GET':
                    if ($id == null){
                        throw new Exception('Id is not set');
                    }

                    //get models
                    $issueModel = JModelLegacy::getInstance( 'Issue', 'ImcModel', array('ignore_request' => true) );
                    $logsModel = JModelLegacy::getInstance( 'Logs', 'ImcModel', array('ignore_request' => true) );
					$votesModel = JModelLegacy::getInstance( 'Votes', 'ImcModel', array('ignore_request' => true) );
					$commentsModel = JModelLegacy::getInstance( 'Comments', 'ImcModel', array('ignore_request' => true) );

					//handle unexpected warnings from model
					set_error_handler(array($this, 'exception_error_handler'));
					$data = $issueModel->getData($id);
					if(is_object($data))
					{
						//merge logs as timeline
						$data->timeline = $logsModel->getItemsByIssue($id);
						//merge hasVoted
						$data->hasVoted = $votesModel->hasVoted($data->id, $userid);
						//merge comments count if enabled
						require_once JPATH_COMPONENT_SITE . '/models/comments.php';
						$data->comments = $commentsModel->count($id, $userid);
                    }
					else
					{
						throw new Exception(JText::_('COM_IMC_API_ISSUE_NOT_EXIST'));
					}

					restore_error_handler();

                    $result = ImcFrontendHelper::sanitizeIssue($data, $userid);

                    //check for any restrictions
                    if(!$result->myIssue && $result->moderation){
                        throw new Exception(JText::_('COM_IMC_API_ISSUE_UNDER_MODERATION') );
                    }
                    if($result->state != 1){
                        throw new Exception(JText::_('COM_IMC_API_ISSUE_NOT_PUBLISHED'));
                    }

                    //be consistent return as array (of size 1)
                    $result = array($result);

                break;
                //create new issue
                case 'POST':
                    if ($id != null){
                        throw new Exception('You cannot use POST to fetch issue. Use GET instead');
                    }

                    //guests are not allowed to post issues
                    //TODO: get this from settings
                    if($userid == 0)
                    {
                        throw new Exception(JText::_('COM_IMC_API_NO_GUESTS_NO_POST'));
                    }

                    //get necessary arguments
                    $args = array (
                        'catid' => $app->input->getInt('catid'),
                        'title' => $app->input->getString('title'),
                        'description' => $app->input->getString('description'),
                        'address' => $app->input->getString('address'),
                        'latitude' => $app->input->getString('lat'),
                        'longitude' => $app->input->getString('lng')
                    );
                    ImcFrontendHelper::checkNullArguments($args);

                    //check if category exists
                    if( is_null(ImcFrontendHelper::getCategoryNameByCategoryId($args['catid'], true)) )
                    {
                        throw new Exception(JText::_('COM_IMC_API_CATEGORY_NOT_EXIST') );
                    }

                    $args['userid'] = $userid;
                    $args['created_by'] = $userid;
                    $args['stepid'] = ImcFrontendHelper::getPrimaryStepId();
                    $args['id'] = 0;
                    $args['created'] = ImcFrontendHelper::convert2UTC(date('Y-m-d H:i:s'));
                    $args['updated'] = $args['created'];
                    $args['note'] = 'modality='.$app->input->getInt('m_id');
                    $args['language'] = '*';
                    $args['subgroup'] = 0;
                    $m_id  = $app->input->getInt('m_id', 0);
                    $args['modality'] = $m_id;

                    $tmpTime = time(); //used for temporary id
                    $imagedir = 'images/imc';

                    //check if post contains files
                    $file = $app->input->files->get('files');
                    if(!empty($file))
                    {
                        require_once JPATH_ROOT . '/components/com_imc/models/fields/multiphoto/server/UploadHandler.php';
                        $options = array(
                                    'script_url' => JRoute::_( JURI::root(true).'/administrator/index.php?option=com_imc&task=upload.handler&format=json&id='.$tmpTime.'&imagedir='.$imagedir.'&'.JSession::getFormToken() .'=1' ),
                                    'upload_dir' => JPATH_ROOT . '/'.$imagedir . '/' . $tmpTime.'/',
                                    'upload_url' => $imagedir . '/'.$tmpTime.'/',
                                    'param_name' => 'files',
                                    'imc_api' => true

                                );
                        $upload_handler = new UploadHandler($options);
                        if(isset($upload_handler->imc_api))
                        {
                            $files_json = json_decode($upload_handler->imc_api);
                            $args['photo'] = json_encode( array('isnew'=>1,'id'=>$tmpTime,'imagedir'=>$imagedir,'files'=>$files_json->files) );
                            $app->enqueueMessage('File(s) uploaded successfully', 'info');
                        }
                        else
                        {
                            throw new Exception(JText::_('COM_IMC_API_UPLOAD_FAILED'));
                        }
                    }
                    else
                    {
                        $args['photo'] = json_encode( array('isnew'=>1,'id'=>$tmpTime,'imagedir'=>$imagedir,'files'=>array()) );
                    }

                    //get issueForm model and save
                    $issueFormModel = JModelLegacy::getInstance( 'IssueForm', 'ImcModel', array('ignore_request' => true) );

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
                    if ($id == null){
                        throw new Exception('Id is not set');
                    }
                break;
                default:
                    throw new Exception('HTTP method is not supported');
            }

            echo new JResponseJson($result, 'Issue action completed successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}

	public function steps()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
		    self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch steps');
			}

			//get necessary arguments
			$ts = $app->input->getString('ts');

            //get steps model
            $stepsModel = JModelLegacy::getInstance( 'Steps', 'ImcModel', array('ignore_request' => true) );
            //set states
            $stepsModel->setState('filter.state', 1);
            //$stepsModel->setState('filter.imcapi.ordering', 'ordering');
            //$stepsModel->setState('filter.imcapi.direction', 'ASC');

			if(!is_null($ts))
			{
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

    	    $app->enqueueMessage('size: '.sizeof($result), 'info');
			echo new JResponseJson($result, 'Steps fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}

	public function categories()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
		    self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch categories');
			}

            $ts = $app->input->getString('ts');

            //handle unexpected warnings from JCategories
            set_error_handler(array($this, 'exception_error_handler'));
            $result = ImcFrontendHelper::getCategories(false);
            if(!is_null($ts))
            {
                if(!ImcFrontendHelper::isValidTimeStamp($ts))
                {
                    throw new Exception('Invalid timestamp');
                }
                foreach ($result as $cat) {
                    //TODO: unset categories prior to ts (how to handle children?)
                }
            }
			restore_error_handler();
            $app->enqueueMessage('size: '.sizeof($result), 'info');
			echo new JResponseJson($result, 'Categories fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
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

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to check userexists');
			}

            //get necessary arguments
            $args = array (
                'username' => $app->input->getString('username'),
                'email' => $app->input->getString('email')
            );
            ImcFrontendHelper::checkNullArguments($args);
			$userid = JUserHelper::getUserId($args['username']);
			if($userid > 0)
			{
				$app->enqueueMessage(JText::_('COM_IMC_API_USERNAME_EXISTS'), 'info');
				$usernameExists = true;
			}

			if(ImcFrontendHelper::emailExists($args['email']))
			{
				$app->enqueueMessage(JText::_('COM_IMC_API_EMAIL_EXISTS'), 'info');
				$emailExists = true;
			}

			$result = array($usernameExists || $emailExists);

            echo new JResponseJson($result, 'Check user action completed successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}

	}

	public function user()
	{
		$result = null;
		$app = JFactory::getApplication();

		try {
            switch($app->input->getMethod())
            {
                case 'GET':
					$userid = self::validateRequest();
                    $app->enqueueMessage('User is valid', 'info');

                    //return also user votes to prevent internet loss conflicts
                    $votesModel = JModelLegacy::getInstance( 'Votes', 'ImcModel', array('ignore_request' => true) );
                    $votesModel->setState('filter.imcapi.userid', $userid);
		            $votesModel->setState('filter.state', 1);
		            //handle unexpected warnings from model
		            set_error_handler(array($this, 'exception_error_handler'));
					//get items and sanitize them
					$data = $votesModel->getItems();
					$votedIssues = ImcFrontendHelper::sanitizeVotes($data);
					restore_error_handler();
					$fullname= JFactory::getUser($userid)->name;

        			//check is user is admin
        			//TODO: Check ACL according to user's department, etc.
		        	$isAdmin = ImcHelper::getActions(JFactory::getUser($userid))->get('core.admin');
		        	if(is_null($isAdmin))
		        	{
		        	    $isAdmin = false;
		        	}

					$result = array('userid' => $userid, 'fullname' => $fullname, 'isAdmin' => $isAdmin, 'votedIssues' => $votedIssues);

					//be consistent return as array (of size 1)
                    $result = array($result);

                break;
                //create new user
                case 'POST':
                    $userInfo = self::validateRequest(true);

					if(JComponentHelper::getParams('com_users')->get('allowUserRegistration') == 0) {
						throw new Exception(JText::_('COM_IMC_API_REGISTRATION_NOT_ALLOWED'));
					}

                    //get necessary arguments
                    $args = array (
                        'name' => $app->input->getString('name'),
                        'email' => $app->input->getString('email')
                    );
                    ImcFrontendHelper::checkNullArguments($args);

					//populate other data
                    $args['username'] = $userInfo['username'];
                    $args['password1'] = $userInfo['password'];
                    $args['email1'] = $args['email'];
                    $args['phone'] = $app->input->getString('phone', '');
                    $args['address'] = $app->input->getString('address', '');

                    //handle unexpected warnings from model
                    set_error_handler(array($this, 'exception_error_handler'));

					JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_users/models/');
					$userModel = JModelLegacy::getInstance( 'Registration', 'UsersModel');
					$result = $userModel->register($args);
					if (!$result)
					{
						throw new Exception($userModel->getError());
					}
                    restore_error_handler();

					if ($result === 'adminactivate')
					{
						$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_COMPLETE_VERIFY'), 'info');
					}
					elseif ($result === 'useractivate')
					{
						$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_COMPLETE_ACTIVATE'), 'info');
					}
					else
					{
						$app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_SAVE_SUCCESS'), 'info');
					}

					//be consistent return as array (of size 1)
                    $result = array($result);
                break;
                //update existing issue
                case 'PUT':
                case 'PATCH':
                    $id = $app->input->getInt('id', null);
                    if ($id == null){
                        throw new Exception('Id is not set');
                    }
                break;
                default:
                    throw new Exception('HTTP method is not supported');
            }

            echo new JResponseJson($result, $msg = 'User action completed successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
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
            if($userid == 0)
            {
                throw new Exception(JText::_('COM_IMC_API_GUESTS_NO_VOTE'));
            }

            //get votes model
            $votesModel = JModelLegacy::getInstance( 'Votes', 'ImcModel', array('ignore_request' => true) );

            switch($app->input->getMethod())
            {
                //create vote
                case 'POST':
                    //handle unexpected warnings from model
                    set_error_handler(array($this, 'exception_error_handler'));
                    $voting = $votesModel->add($id, $userid, $modality);
                    if($voting['code'] != 1)
                    {
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

                    if($voting['code'] != 1)
                    {
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

            echo new JResponseJson($result, 'Vote action completed successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}

	public function votes()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
		    $userid = self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch votes');
			}

            //get necessary arguments
            $id = $app->input->getInt('id', null);
            $ts = $app->input->getString('ts', null);

            //get votes model
            $votesModel = JModelLegacy::getInstance( 'Votes', 'ImcModel', array('ignore_request' => true) );
			if(!is_null($ts))
			{
				//get date from ts
	            $ts = gmdate('Y-m-d H:i:s', $ts);
				$votesModel->setState('filter.imcapi.ts', $ts);
			}
			if(is_null($id))
			{
				$votesModel->setState('filter.imcapi.userid', $userid);
			}
			else
			{
			    $votesModel->setState('filter.issueid', $id);
			}

            $votesModel->setState('filter.state', 1);
            //handle unexpected warnings from model
            set_error_handler(array($this, 'exception_error_handler'));
			//get items and sanitize them
			$data = $votesModel->getItems();
			$result = ImcFrontendHelper::sanitizeVotes($data);
			restore_error_handler();

    	    $app->enqueueMessage('size: '.sizeof($result), 'info');
			echo new JResponseJson($result, 'Votes fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}

	public function voters()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
		    $userid = self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch votes');
			}

            //get necessary arguments
            $ts = $app->input->getString('ts', null);

            //get votes model
            $votesModel = JModelLegacy::getInstance( 'Votes', 'ImcModel', array('ignore_request' => true) );
			if(!is_null($ts))
			{
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

    	    $app->enqueueMessage('size: '.sizeof($result), 'info');
			echo new JResponseJson($result, 'Voters fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
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

            switch($app->input->getMethod())
            {
                //fetch issue's timeline
                case 'GET':
                    if ($id == null){
                        throw new Exception('Id is not set');
                    }

                    //get logs model
                    $issueModel = JModelLegacy::getInstance( 'Issue', 'ImcModel', array('ignore_request' => true) );
                    $logsModel = JModelLegacy::getInstance( 'Logs', 'ImcModel', array('ignore_request' => true) );

                    //handle unexpected warnings from model
                    set_error_handler(array($this, 'exception_error_handler'));
                    $data = $issueModel->getData($id);

                    if(!is_object($data)){
                        throw new Exception('Issue does not exist');
                    }

                    $result = ImcFrontendHelper::sanitizeIssue($data, $userid);
                    if($result->state != 1){
                        throw new Exception('Issue is not published');
                    }
                    if(!$result->myIssue && $result->moderation){
                        $app->enqueueMessage('Issue is under moderation', 'info');
                    }

                    $data = $logsModel->getItemsByIssue($id);
                    $result = ImcFrontendHelper::sanitizeLogs($data);
                    restore_error_handler();
                break;
                case 'POST':
                    if ($id != null){
                        throw new Exception('You cannot use POST to fetch issue. Use GET instead');
                    }
                    //TODO: Future implementation
                break;
                //update existing issue
                case 'PUT':
                case 'PATCH':
                    if ($id == null){
                        throw new Exception('Id is not set');
                    }
                break;
                default:
                    throw new Exception('HTTP method is not supported');
            }

            echo new JResponseJson($result, 'Timeline action completed successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}


	public function modifications()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
		    $userid = self::validateRequest();

			if($app->input->getMethod() != 'GET' && $app->input->getMethod() != 'HEAD')
			{
				throw new Exception('You cannot use other method than GET or HEAD to fetch modifications');
			}

			$args = array(
				'ts' => $app->input->getString('ts'),
			);
			ImcFrontendHelper::checkNullArguments($args);

			if (!ImcFrontendHelper::isValidTimeStamp($args['ts']))
			{
				throw new Exception('Invalid timestamp');
			}

			switch($app->input->getMethod())
			{
				case 'GET':
					//handle unexpected warnings
					set_error_handler(array($this, 'exception_error_handler'));
					$result = self::getModifications($args['ts'], $userid);
					restore_error_handler();

					//be consistent return as array (of size 1)
					$result = array($result);

					$response = new JResponseJson($result, 'Modifications since timestamp fetched successfully');
					$length = mb_strlen($response, 'UTF-8');
					header('Content-Length: '.$length);
					echo $response;

				break;
				case 'HEAD':
					//handle unexpected warnings
					set_error_handler(array($this, 'exception_error_handler'));
					//$result = self::getModifications($args['ts'], $userid, false);
					$count = ImcFrontendHelper::countModifiedIssues($args['ts'], 1000);
					$result = ($count * 700) + 3000;
					restore_error_handler();
					header('Content-Length: '.$result);
				break;
				default:
					throw new Exception('HTTP method is not supported');
			}
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}

	private function getModifications($ts, $userid, $sanitize = true)
	{
		$offsetDate = JDate::getInstance(date("Y-m-d H:i:s", $ts), JFactory::getConfig()->get('offset') );
        $offset = $offsetDate->format('Y-m-d H:i:s');

        //1. get issues
        $issuesModel = JModelLegacy::getInstance( 'Issues', 'ImcModel', array('ignore_request' => true) );
        $issuesModel->setState('filter.imcapi.ts', $offset);
        $issuesModel->setState('filter.imcapi.limit', 1000);
        $issuesModel->setState('filter.imcapi.raw', true); //Do not unset anything in getItems()
		$data = $issuesModel->getItems();
		$issues = $data;
		if($sanitize)
		{
			$issues = ImcFrontendHelper::sanitizeIssues($data, $userid, true);
		}

		//2. get categories
        $categories = ImcFrontendHelper::getModifiedCategories($offset);
		if($sanitize)
		{
			$categories = ImcFrontendHelper::sanitizeCategories($categories);
		}

        //3. get steps
        $stepsModel = JModelLegacy::getInstance( 'Steps', 'ImcModel', array('ignore_request' => true) );
        $stepsModel->setState('filter.imcapi.ts', $offset);
        $stepsModel->setState('filter.imcapi.raw', true);
        $data = $stepsModel->getItems();
		$steps = $data;
		if($sanitize)
		{
			$steps = ImcFrontendHelper::sanitizeSteps($data, true);
		}

        //4. get votes
        $data = ImcFrontendHelper::getModifiedVotes($offset);
		$votes = $data;
		if($sanitize)
		{
			$votes = ImcFrontendHelper::sanitizeModifiedVotes($data);
		}

		//5. full categories structure if modified categories are found
		$allCategories = array();
		if(!empty($categories))
		{
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

    public function topusers()
    {
        self::getTop('users');
    }

    public function topcategories()
    {
        self::getTop('categories');
    }

    public function topsteps()
    {
        self::getTop('steps');
    }

    public function topvoters()
    {
        self::getTop('voters');
    }

    public function topcommenters()
    {
        self::getTop('commenters');
    }

    private function getTop($type)
    {
		$result = null;
		$app = JFactory::getApplication();
		try {
		    self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch top users');
			}

            //get necessary arguments
            $ts = $app->input->getString('ts', null);
            $prior_to = $app->input->getString('prior_to', null);
            $lim = $app->input->getInt('lim', null);
            $ids = $app->input->getString('ids', null);

		    if(!is_null($ts) && !ImcFrontendHelper::isValidTimeStamp($ts))
            {
                throw new Exception('Invalid timestamp ts');
            }
		    if(!is_null($prior_to) && !ImcFrontendHelper::isValidTimeStamp($prior_to))
            {
                throw new Exception('Invalid timestamp prior_to');
            }

			//get date from ts
            if(!is_null($ts))
            {
                $ts = gmdate('Y-m-d H:i:s', $ts);
            }
			if(!is_null($prior_to))
            {
	            $prior_to = gmdate('Y-m-d H:i:s', $prior_to);
            }

            //handle unexpected warnings from model
            set_error_handler(array($this, 'exception_error_handler'));
            switch($type)
            {
                case 'users':
                    $result = ImcFrontendHelper::getTopUsers($lim, $ts, $prior_to, $ids);
                break;
                case 'categories':
                    $result = ImcFrontendHelper::getTopCategories($lim, $ts, $prior_to, $ids);
                break;
                case 'steps':
                    $result = ImcFrontendHelper::getTopSteps($lim, $ts, $prior_to, $ids);
                break;
                case 'voters':
                    $result = ImcFrontendHelper::getTopVoters($lim, $ts, $prior_to, $ids);
                break;
                case 'commenters':
                    $result = ImcFrontendHelper::getTopCommenters($lim, $ts, $prior_to, $ids);
                break;
            }
			restore_error_handler();

    	    $app->enqueueMessage('size: '.sizeof($result), 'info');
			echo new JResponseJson($result, 'Top fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
    }

    public function boundaries()
    {
		$result = array();
		$app = JFactory::getApplication();
		try {
		    self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch boundaries');
			}

			$params = JComponentHelper::getParams('com_imc');
			$boundaries = $params->get('boundaries', null);

/*			if(!is_null($boundaries))
			{
				$boundaries = str_replace("\r", "", $boundaries);
				$bounds = array();
				$arBoundaries = explode("\n", $boundaries);
				foreach ($arBoundaries as $bnd)
				{
					$latLng = explode(',', $bnd);
					array_push($bounds, array('lng'=>(double)$latLng[0], 'lat'=>(double)$latLng[1]));
				}
				if(!empty($bounds))
				{
					$result = $bounds;
				}
			}*/

            $borders = array();
            if(!is_null($boundaries))
            {
                $arPolygons = explode(";", $boundaries);

                foreach ($arPolygons as $poly) {
                    $polygon = str_replace("\r", "", $poly);

                    $bounds = array();
                    $arBoundaries = explode("\n", $polygon);
                    foreach ($arBoundaries as $bnd)
                    {
                        if(strlen($bnd) > 1) {
                            $latLng = explode(',', $bnd);
                            array_push($bounds, array('lng' => (double)$latLng[0], 'lat' => (double)$latLng[1]));
                        }
                    }

                    array_push($borders, $bounds);
                }
                $result = $borders;
            }

            $app->enqueueMessage('size: '.sizeof($result), 'info');
			echo new JResponseJson($result, 'Boundaries fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
    }

	public function totals()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
		    self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch totals');
			}

            //handle unexpected warnings from model
            set_error_handler(array($this, 'exception_error_handler'));
			$result = ImcFrontendHelper::getTotals();

			// Calculate the difference between start and end date (in years, months & days).
			$diff = date_diff(date_create($result[0]['newest_issue_date']), date_create($result[0]['oldest_issue_date']));
            $result[0]['years'] = $diff->y;
            $result[0]['months'] = $diff->m;
            $result[0]['days'] = $diff->d;

			restore_error_handler();

    	    $app->enqueueMessage('size: '.sizeof($result), 'info');
			echo new JResponseJson($result, 'Totals fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}

    public function issuesbykeywords()
    {
		$result = null;
		$app = JFactory::getApplication();
		try {
		    $userid = self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch top users');
			}

            //get necessary arguments
            $keywords = $app->input->getString('keywords', null);
            $ts = $app->input->getString('ts', null);
            $prior_to = $app->input->getString('prior_to', null);
            $lim = $app->input->getInt('lim', null);

		    if(is_null($keywords))
		    {
		        throw new Exception('Invalid keywords');
		    }
		    if(!is_null($ts) && !ImcFrontendHelper::isValidTimeStamp($ts))
            {
                throw new Exception('Invalid timestamp ts');
            }
		    if(!is_null($prior_to) && !ImcFrontendHelper::isValidTimeStamp($prior_to))
            {
                throw new Exception('Invalid timestamp prior_to');
            }

			//get date from ts
            if(!is_null($ts))
            {
                $ts = gmdate('Y-m-d H:i:s', $ts);
            }
			if(!is_null($prior_to))
            {
	            $prior_to = gmdate('Y-m-d H:i:s', $prior_to);
            }

            //handle unexpected warnings
            set_error_handler(array($this, 'exception_error_handler'));
			$arComments = ImcFrontendHelper::searchIssuesByComments($keywords, $lim, $ts, $prior_to);
			$arDescription = ImcFrontendHelper::searchIssues($keywords, 'description', $lim, $ts, $prior_to);
			$arTitle = ImcFrontendHelper::searchIssues($keywords, 'title', $lim, $ts, $prior_to);
			$arAddress = ImcFrontendHelper::searchIssues($keywords, 'address', $lim, $ts, $prior_to);
			restore_error_handler();

	        $info = array(
				'count_in_comments' => sizeof($arComments),
				'count_in_description' => sizeof($arDescription),
				'count_in_title' => sizeof($arTitle),
				'count_in_address' => sizeof($arAddress),
				'ids_in_comments' => ImcFrontendHelper::getIds($arComments),
				'ids_in_description' => ImcFrontendHelper::getIds($arDescription),
				'ids_in_title' => ImcFrontendHelper::getIds($arTitle),
				'ids_in_address' => ImcFrontendHelper::getIds($arAddress)

	        );

			$found = array(
				'in_comments' => ImcFrontendHelper::sanitizeIssues(ImcFrontendHelper::array2obj($arComments), $userid),
				'in_description' => ImcFrontendHelper::sanitizeIssues(ImcFrontendHelper::array2obj($arDescription), $userid),
				'in_title' => ImcFrontendHelper::sanitizeIssues(ImcFrontendHelper::array2obj($arTitle), $userid),
				'in_address' => ImcFrontendHelper::sanitizeIssues(ImcFrontendHelper::array2obj($arAddress), $userid)
			);

			$result = array('info' => $info, 'found' => $found);
			echo new JResponseJson($result, 'Issues by keywords fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
    }

    public function calendar()
    {
		$result = null;
		$app = JFactory::getApplication();
		try {
		    self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch calendar issues');
			}

            //get necessary arguments
            $field = $app->input->getString('field', null);
            $allowedFields = array('stepid', 'catid');
            if(!in_array($field, $allowedFields))
            {
                $field = null;
            }

            //handle unexpected warnings
            set_error_handler(array($this, 'exception_error_handler'));
			$calendar = ImcFrontendHelper::calendar($field);
			$result = ImcFrontendHelper::sanitizeCalendar($calendar);
			restore_error_handler();

			echo new JResponseJson($result, 'Calendar Issues fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
    }

    public function dailyCalendar()
    {
		$result = null;
		$app = JFactory::getApplication();
		try {
		    self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch calendar issues');
			}

            //get necessary arguments
            $field = $app->input->getString('field', null);
            $allowedFields = array('stepid', 'catid');
            if(!in_array($field, $allowedFields))
            {
                $field = null;
            }

            $year = $app->input->getInt('year', null);
            $month = $app->input->getInt('month', null);
            if(is_null($year))
            {
                throw new Exception('Year is mandatory');
            }
			if(is_null($month))
            {
	            throw new Exception('Month (1-12) is mandatory');
            }

            //handle unexpected warnings
            set_error_handler(array($this, 'exception_error_handler'));
			$calendar = ImcFrontendHelper::dailyCalendar($year, $month, $field);
			$result = ImcFrontendHelper::sanitizeDailyCalendar($calendar, $year, $month);
			restore_error_handler();

			echo new JResponseJson($result, 'Daily Calendar Issues fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
    }

    public function intervals()
    {
		$result = null;
		$app = JFactory::getApplication();
		try {
		    self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch intervals');
			}

            //get necessary arguments
            $by_step = $app->input->getString('by_step', null);
            $by_category = $app->input->getString('by_category', null);
            $by_step = ($by_step === 'true');
            $by_category = ($by_category === 'true');
            $ts = $app->input->getString('ts', null);
            $prior_to = $app->input->getString('prior_to', null);

		    if(!is_null($ts) && !ImcFrontendHelper::isValidTimeStamp($ts))
            {
                throw new Exception('Invalid timestamp ts');
            }
		    if(!is_null($prior_to) && !ImcFrontendHelper::isValidTimeStamp($prior_to))
            {
                throw new Exception('Invalid timestamp prior_to');
            }

			//get date from ts
            if(!is_null($ts))
            {
                $ts = gmdate('Y-m-d H:i:s', $ts);
            }
			if(!is_null($prior_to))
            {
	            $prior_to = gmdate('Y-m-d H:i:s', $prior_to);
            }

            //handle unexpected warnings
            set_error_handler(array($this, 'exception_error_handler'));
			$result = ImcFrontendHelper::intervals($by_step, $by_category, $ts, $prior_to);
			restore_error_handler();

			echo new JResponseJson($result, 'Intervals fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
    }

	public function slogin()
	{
		$result = null;
		$app = JFactory::getApplication();

		try {
			switch($app->input->getMethod())
			{
				case 'POST':
					$userInfo = self::validateRequest(true);

					//get necessary arguments
					$provider = $app->input->getString('provider', 'whatever');
					$fullname = $app->input->getString('fullname', null);
					$phone = $app->input->getString('phone', null);
					$address = $app->input->getString('address', null);
					$secret = JComponentHelper::getParams('com_slogin')->get('secret');

					if(!JPluginHelper::isEnabled('slogin_auth', $provider))
					{
						throw new Exception('sLogin is not available or given provider is not supported');
					}

					$slogin_id = $userInfo['username']; //'111309200021517229400';
					$username = str_replace(" ","-",$fullname).'-'.$provider; //'Ioannis-Tsampoulatidis-google';
					$email = $userInfo['password'];
					$password = md5($slogin_id.$provider.$secret);

					//handle unexpected warnings
					set_error_handler(array($this, 'exception_error_handler'));

					//check if user exists on social table
					$sUser = ImcFrontendHelper::getSocialUser($slogin_id);
					if(is_null($sUser))
					{
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
						$args = array (
							'name' => $fullname,
							'username' => $username,
							'password1' => $password,
							'email1' => ImcFrontendHelper::getFreeMail($email),
                            'phone' => $phone,
                            'address' => $address
						);

						JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_users/models/');
						$userModel = JModelLegacy::getInstance( 'Registration', 'UsersModel');
						$userid = (int)$userModel->register($args);

						//restore params
						$params->set('useractivation', $useractivation);
						$params->set('allowUserRegistration', $allowUserRegistration);
						$params->set('sendpassword', $sendpassword);
						$plg_params->set('mail_to_user', $notificationMail);

						if ($userid == 0)
						{
							$userid = ImcFrontendHelper::getUserId($args['username'], $args['email1']);
						}

						//create new social user
						ImcFrontendHelper::createSloginUser($userid, $slogin_id, $provider);

						//create new social profile
						ImcFrontendHelper::createSocialProfile($userid, $slogin_id, $provider, $fullname, '', $email, $phone);

					}
					else
					{
						//user exists
						$userid = $sUser['user_id'];

						$user = JUser::getInstance($userid);
						$hashed_password = $user->password;

						if($user->username != $username)
						{
							//update joomla user username
							$username = ImcFrontendHelper::checkUniqueName($username);
							ImcFrontendHelper::updateUserUsername($userid, $username);
							$newName = $fullname;
							ImcFrontendHelper::updateUserName($userid, $newName);
						}

						if($user->email != $email)
						{
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
						if(!$match)
						{
							throw new Exception('Unexpected error: User cannot authenticate');
						}

					}

					//all set, return same as GET/user
					$votesModel = JModelLegacy::getInstance( 'Votes', 'ImcModel', array('ignore_request' => true) );
					$votesModel->setState('filter.imcapi.userid', $userid);
					$votesModel->setState('filter.state', 1);
					//get items and sanitize them
					$data = $votesModel->getItems();
					$votedIssues = ImcFrontendHelper::sanitizeVotes($data);
					restore_error_handler();

					//..and also the user's real credentials encrypted
					$credentials = array('u'=>$username, 'p'=>$password);
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

			echo new JResponseJson($result, $msg = 'User action completed successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}

	public function comments()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();

			if($app->input->getMethod() != 'GET')
			{
				throw new Exception('You cannot use other method than GET to fetch comments');
			}

			//get necessary arguments
			$issueid = $app->input->getInt('id', null);
			if ($issueid == null){
				throw new Exception('issueId is not set');
			}
			$parentid = $app->input->getInt('parentid', null);

			//check if issue exists
			$issueModel = JModelLegacy::getInstance( 'Issue', 'ImcModel', array('ignore_request' => true) );
			$issue = $issueModel->getData($issueid);
			if(!is_object($issue))
			{
				throw new Exception(JText::_('COM_IMC_API_ISSUE_NOT_EXIST'));
			}

			//get comments model
			$commentsModel = JModelLegacy::getInstance( 'Comments', 'ImcModel', array('ignore_request' => true) );
			//set states
			$commentsModel->setState('filter.imcapi.userid', $userid);

			if(!is_null($parentid))
			{
				$commentsModel->setState('imc.filter.parentid', $parentid);
			}

			if($userid == 0)
			{
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

            foreach ($results as &$item)
            {
                $created_by_admin = (boolean) $item->isAdmin;
                if ($commentsdisplayname == 'dpt' && $created_by_admin)
                {
                    //show department name
                    $dpts = array();
                    //get user groups higher than 9
                    $usergroups = JAccess::getGroupsByUser($item->created_by, false);
                    for ($i=0; $i < count($usergroups); $i++) {
                        if($usergroups[$i] > 9){
                            $dpts[] = ImcFrontendHelper::getGroupNameById($usergroups[$i]);
                        }
                    }

                    $item->fullname = implode(', ', $dpts);
                }
            }

			$app->enqueueMessage('size: '.sizeof($results), 'info');
			restore_error_handler();

			echo new JResponseJson($results, 'Comments fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}

	public function comment()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
			$userid = self::validateRequest();

			if($app->input->getMethod() != 'POST')
			{
				throw new Exception('You cannot use other method than POST to create comment');
			}

			$commentCtrl = new ImcControllerComments();
			$commentCtrl->postComment(true, $userid);
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}

    public function issuesbycategory()
    {
        $result = null;
        $app = JFactory::getApplication();
        try {
            self::validateRequest();

            if($app->input->getMethod() != 'GET')
            {
                throw new Exception('You cannot use other method than GET to fetch steps');
            }

            //get necessary arguments
            $ts = null;
            $catid = null;
            $ts = $app->input->getString('ts');
            $catid = $app->input->getString('catid');

            if(!is_null($ts))
            {
                if(!ImcFrontendHelper::isValidTimeStamp($ts))
                {
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

            $app->enqueueMessage('size: '.sizeof($result), 'info');
            echo new JResponseJson($result, 'Issues fetched successfully');
        }
        catch(Exception $e)	{
            header("HTTP/1.0 202 Accepted");
            echo new JResponseJson($e);
        }
    }


}