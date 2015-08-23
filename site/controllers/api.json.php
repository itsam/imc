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

    //private $userModel;

    function __construct()
    {
    	$this->mcrypt = new MCrypt();

        JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models');
        $this->keyModel = JModelLegacy::getInstance( 'Key', 'ImcModel', array('ignore_request' => true) );

    	//JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models/');
        //$this->userModel = JModelLegacy::getInstance( 'User', 'UsersModel');

    	parent::__construct();
    }

    public function exception_error_handler($errno, $errstr, $errfile, $errline){
        $ee = new ErrorException($errstr, 0, $errno, $errfile, $errline);
        JFactory::getApplication()->enqueueMessage($ee, 'error');
        throw $ee;
    }

    private function validateRequest()
    {
        return 569; //TODO: REMOVE THIS LINE. ONLY FOR DEBUGGING PURPOSES
        $app = JFactory::getApplication();
        $token = $app->input->getString('token');
        $m_id  = $app->input->getInt('m_id');
        $l     = $app->input->getString('l');

        //1. check necessary arguments are exist
        if(is_null($token) || is_null($m_id) || is_null($l) ){
            $app->enqueueMessage('Either token, m_id (modality), or l (language) are missing', 'error');
            throw new Exception('Request is invalid');
        }

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

        //TODO: Set timeout at options (default is 1 minute)
        if((time() - $objToken->t) > 1 * 60){
            throw new Exception('Token has expired');
        }

        //4. authenticate user
        $userid = JUserHelper::getUserId($objToken->u);
        $user = JFactory::getUser($userid);

        $match = JUserHelper::verifyPassword($objToken->p, $user->password, $userid);
        if(!$match){
            $app->enqueueMessage('Either username or password do not match', 'error');
            throw new Exception('Token does not match');
        }

        if($user->block){
            $app->enqueueMessage('User is found but probably is not yet activated', 'error');
            throw new Exception('Token user is blocked');
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

        return $userid;
    }

	public function issues()
	{
		$result = null;
		$app = JFactory::getApplication();
		try {
		    $userid = self::validateRequest();
			//get necessary arguments
			$minLat = $app->input->getString('minLat');
			$maxLat = $app->input->getString('maxLat');
			$minLng = $app->input->getString('minLng');
			$maxLng = $app->input->getString('maxLng');
			$owned = $app->input->get('owned', false);
			$lim = $app->input->getInt('lim', 0);

            //get issues model
            $issuesModel = JModelLegacy::getInstance( 'Issues', 'ImcModel', array('ignore_request' => true) );
            //set states
            $issuesModel->setState('filter.owned', ($owned === 'true' ? 'yes' : 'no'));
            $issuesModel->setState('filter.imcapi.userid', $userid);
            //$issuesModel->setState('filter.imcapi.ordering', 'id');
            //$issuesModel->setState('filter.imcapi.direction', 'DESC');
            $issuesModel->setState('list.limit', $lim);

			if(!is_null($minLat) && !is_null($maxLat) && !is_null($minLng) && !is_null($maxLng))
			{
				$issuesModel->setState('filter.imcapi.minLat', $minLat);
				$issuesModel->setState('filter.imcapi.maxLat', $maxLat);
				$issuesModel->setState('filter.imcapi.minLng', $minLng);
				$issuesModel->setState('filter.imcapi.maxLng', $maxLng);
			}

            //handle unexpected warnings from model
            set_error_handler(array($this, 'exception_error_handler'));
			//get items and sanitize them
			$data = $issuesModel->getItems();
			restore_error_handler();
			$result = ImcFrontendHelper::sanitizeIssues($data, $userid);

			echo new JResponseJson($result, 'Issues fetched successfully');
		}
		catch(Exception $e)	{
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

                    //handle unexpected warnings from model
                    set_error_handler(array($this, 'exception_error_handler'));
                    $data = $issueModel->getData($id);
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

                    $args['userid'] = $userid;
                    $args['created_by'] = $userid;
                    $args['stepid'] = ImcFrontendHelper::getPrimaryStepId();
                    $args['id'] = 0;
                    $args['created'] = date('Y-m-d H:i:s');
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
                                    'upload_url' => JURI::root(true) . '/'.$imagedir . '/'.$tmpTime.'/',
                                    'param_name' => 'files',
                                    'imc_api' => true

                                );
                        $upload_handler = new UploadHandler($options);
                        if(isset($upload_handler->imc_api))
                        {
                            $files_json = json_decode($upload_handler->imc_api);
                            $args['photo'] = json_encode( array('isnew'=>1,'id'=>$tmpTime,'imagedir'=>$imagedir,'files'=>$files_json->files), JSON_UNESCAPED_SLASHES);
                            $app->enqueueMessage('File(s) uploaded successfully', 'info');
                        }
                        else
                        {
                            throw new Exception('Upload failed');
                        }
                    }
                    else
                    {
                        $args['photo'] = json_encode( array('isnew'=>1,'id'=>$tmpTime,'imagedir'=>$imagedir,'files'=>array()), JSON_UNESCAPED_SLASHES);
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

                    $result = 'Newly submitted issue ID is ' . $insertid;
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
			echo new JResponseJson($e);
		}
	}
}