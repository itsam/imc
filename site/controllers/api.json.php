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

/**
 * Issues list controller class.
 */
class ImcControllerApi extends ImcController
{

	public function issues()
	{
		try {
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
			$app = JFactory::getApplication();
			$id = $app->input->getInt('id', null, 'int');
			if ($id == null){
				echo new JResponseJson(null, 'Id is not set', true);
			}
			else {
				$result = array('issueid'=>$id, 'details'=>'what a nice detail', 'method'=>$app->input->getMethod());
				echo new JResponseJson($result);
			}
		}
		catch(Exception $e)	{
			echo new JResponseJson($e);
		}
	}
}