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

    function __construct()
    {
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
        if(ImcModelTokens::exists($token)){
            throw new Exception('Token is already used');
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
        $objToken = json_decode($decryptedToken);

        if(!is_object($objToken)){
            throw new Exception('Token is invalid');
        }

        if(!isset($objToken->u) || !isset($objToken->p) || !isset($objToken->t) || !isset($objToken->r)) {
            throw new Exception('Token is not well formatted');
        }

        //TODO: Set timeout at options
        if((time() - $objToken->t) > 3 * 60){
            throw new Exception('Token has expired');
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
		            $app->enqueueMessage('Either username or password do not match', 'error');
		            throw new Exception('Token does not match');
		        }

		        if($user->block){
		            $app->enqueueMessage('User is found but probably is not yet activated', 'error');
		            throw new Exception('User is blocked');
		        }
			}
		}

        //5. populate token table
        $record = new stdClass();
        $record->key_id = $m_id;
        $record->user_id = $userid;
        //$record->json_size = $json_size;
        $record->method = $app->input->getMethod();
        $record->token = $token;
        $record->unixtime = $objToken->t;
        ImcModelTokens::insertToken($record); //this static method throws exception on error

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
				$issuesModel->setState('filter.imcapi.ts', $ts);
			}
			if(!is_null($prior_to))
			{
			    if(!ImcFrontendHelper::isValidTimeStamp($prior_to))
                {
                    throw new Exception('Invalid prior_to timestamp');
                }
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

                    //get issue model
                    $issueModel = JModelLegacy::getInstance( 'Issue', 'ImcModel', array('ignore_request' => true) );
                    $logsModel = JModelLegacy::getInstance( 'Logs', 'ImcModel', array('ignore_request' => true) );

                    //handle unexpected warnings from model
                    set_error_handler(array($this, 'exception_error_handler'));
                    $data = $issueModel->getData($id);
                    //merge logs as timeline
                    if(is_object($data))
                    {
                        $data->timeline = $logsModel->getItemsByIssue($id);
		                $votesModel = JModelLegacy::getInstance( 'Votes', 'ImcModel', array('ignore_request' => true) );
		                $data->hasVoted = $votesModel->hasVoted($data->id, $userid);
                    }

                    restore_error_handler();

                    if(!is_object($data)){
                        throw new Exception('Issue does not exist');
                    }

                    $result = ImcFrontendHelper::sanitizeIssue($data, $userid);

                    //check for any restrictions
                    if(!$result->myIssue && $result->moderation){
                        throw new Exception('Issue is under moderation');
                    }
                    if($result->state != 1){
                        throw new Exception('Issue is not published');
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
                        throw new Exception('Guests are now allowed to post new issues');
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
                        throw new Exception('Category does not exist or unpublished');
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
                            throw new Exception('Upload failed');
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
				$app->enqueueMessage('Username exists', 'info');
				$usernameExists = true;
			}

			if(ImcFrontendHelper::emailExists($args['email']))
			{
				$app->enqueueMessage('Email exists', 'info');
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
                    $result = array('userid' => $userid);

                    //be consistent return as array (of size 1)
                    $result = array($result);
                break;
                //create new user
                case 'POST':
                    $userInfo = self::validateRequest(true);

					if(JComponentHelper::getParams('com_users')->get('allowUserRegistration') == 0) {
						throw new Exception('Registration is not allowed');
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
                throw new Exception('Guests are now allowed to vote/unvote');
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
                    $voting = $votesModel->remove($id, $userid);

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

			if($app->input->getMethod() != 'GET')
			{
			    throw new Exception('You cannot use other method than GET to fetch timestamp');
			}

            $args = array (
                'ts' => $app->input->getString('ts'),
            );
            ImcFrontendHelper::checkNullArguments($args);

            if(!ImcFrontendHelper::isValidTimeStamp($args['ts']))
            {
                throw new Exception('Invalid timestamp');
            }

            //handle unexpected warnings
            set_error_handler(array($this, 'exception_error_handler'));
			$result = self::getModifications($args['ts'], $userid);
            restore_error_handler();

            //be consistent return as array (of size 1)
            $result = array($result);

			echo new JResponseJson($result, 'Modifications since timestamp fetched successfully');
		}
		catch(Exception $e)	{
			header("HTTP/1.0 202 Accepted");
			echo new JResponseJson($e);
		}
	}

	private function getModifications($ts, $userid)
	{
		$tsDate = date("Y-m-d H:i:s", $ts);
		$offsetDate = JDate::getInstance($tsDate, JFactory::getConfig()->get('offset') );

        //1. get issues
        $issuesModel = JModelLegacy::getInstance( 'Issues', 'ImcModel', array('ignore_request' => true) );
        $issuesModel->setState('filter.imcapi.ts', $ts);
        $issuesModel->setState('filter.imcapi.raw', true); //Do not unset anything in getItems()
		$data = $issuesModel->getItems();
		$issues = ImcFrontendHelper::sanitizeIssues($data, $userid, true);

        //2. get categories
        $categories = ImcFrontendHelper::getModifiedCategories($ts);
        $categories = ImcFrontendHelper::sanitizeCategories($categories);

        //3. get steps
        $stepsModel = JModelLegacy::getInstance( 'Steps', 'ImcModel', array('ignore_request' => true) );
        $stepsModel->setState('filter.imcapi.ts', $ts);
        $stepsModel->setState('filter.imcapi.raw', true);
        $data = $stepsModel->getItems();
        $steps = ImcFrontendHelper::sanitizeSteps($data, true);

        //4. get votes
        $votesModel = JModelLegacy::getInstance( 'Votes', 'ImcModel', array('ignore_request' => true) );
        $votesModel->setState('filter.imcapi.ts', $ts);
        $data = $votesModel->getItems();
        $votes = ImcFrontendHelper::sanitizeVotes($data);

        $info = array(
			'count_issues' => sizeof($issues),
			'count_categories' => sizeof($categories),
			'count_steps' => sizeof($steps),
			'count_votes' => sizeof($votes),
			'given_ts'   => $ts,
			'offset'     => $offsetDate,
        );

		$updated = array(
			'issues'     => $issues,
			'categories' => $categories,
			'steps'      => $steps,
			'votes'      => $votes
		);

		return array('info' => $info, 'updated' => $updated);
	}

}