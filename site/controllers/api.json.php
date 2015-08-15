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
require_once JPATH_COMPONENT_SITE . '/helpers/MCrypt.php';

/**
 * IMC API controller class.
 * Make sure you have mcrypt module enabled
 * e.g. $ sudo php5enmod mcrypt
 *
 * Every request should contain token, m_id, l
 * where *token* is the m-crypted "json_encode(array)" of username, password, timestamp in the following form:
 * {'u':'username','p':'plain_password','t':'1439592509'}
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

    private function validateRequest()
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

        if(!isset($objToken->u) || !isset($objToken->p) || !isset($objToken->t)) {
            throw new Exception('Token is not well formatted');
        }

        if((time() - $objToken->t) > 100 * 60){
            throw new Exception('Token has expired');
        }

        //4. authenticate user
        $userid = JUserHelper::getUserId($objToken->u);
        $user = JFactory::getUser($userid);
        //print_r($user);
        $match = JUserHelper::verifyPassword($objToken->p, $user->password, $userid);
        if(!$match){
            $app->enqueueMessage('Either username or password do not match', 'error');
            throw new Exception('Token does not match');
        }

        if($user->block){
            $app->enqueueMessage('User is found but probably is not yet activated', 'error');
            throw new Exception('Token user is blocked');
        }


        return true;
    }


	public function issues()
	{
		try {
		    self::validateRequest();
			$result = array('foo'=>'bar', 'moo'=>'koo', 'bar'=>231);
			echo new JResponseJson($result, 'Issues fetched successfully');
		}
		catch(Exception $e)	{
			echo new JResponseJson($e);
		}
	}	

	public function issue()
	{
		try {
		    self::validateRequest();
			$app = JFactory::getApplication();
			$id = $app->input->getInt('id', null);
			if ($id == null){
				echo new JResponseJson(null, 'Id is not set', true);
			}
			else {
				$result = array(
				    'issueid'=>$id,
				    'details'=>'what a nice detail',
				    'method'=>$app->input->getMethod()
				    //'imctoken'=>$_SERVER['HTTP_IMCTOKEN']
				);

				echo new JResponseJson($result);
			}
		}
		catch(Exception $e)	{
			echo new JResponseJson($e);
		}
	}
}