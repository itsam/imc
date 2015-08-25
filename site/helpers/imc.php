<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
defined('_JEXEC') or die;

class ImcFrontendHelper
{

	private static $_items = array(); //used by getCategories
	private static $_parent; //used by getCategories

    public static function convert2UTC($date)
    {
        //get timezone from settings
        $offset = JFactory::getConfig()->get('offset');

        $utc = new DateTime($date, new DateTimeZone($offset));
        $utc->setTimezone(new DateTimeZone('UTC'));
        return $utc->format('Y-m-d H:i:s');
    }

	public static function checkNullArguments($args)
	{
		$nullArguments = array();

		if(!is_array($args))
		{
			throw new Exception('Checking arguments bad input');
		}

		foreach($args as $name => $value)
		{
			if (is_null($value))
			{
				array_push($nullArguments, $name);
			}
		}

		if(!empty($nullArguments))
		{
			$errMsg = 'The following arguments are missing or bad input: ' . implode(', ',$nullArguments);
			throw new Exception($errMsg);
		}
	}

	public static function sanitizeIssues($data, $userid)
	{
		if(!is_array($data)){
			throw new Exception('Issues sanitization bad input');
		}

		$issues = array();

		foreach ($data as $issue)
		{
			$issue = self::sanitizeIssue($issue, $userid);
			array_push($issues, $issue);
		}
		return $issues;
	}

	public static function sanitizeIssue($data, $userid)
	{
		if(!is_object($data)){
            throw new Exception('Issue sanitization bad input');
        }
        //unset overhead
        unset($data->asset_id);
        unset($data->ordering);
        unset($data->checked_out);
        unset($data->checked_out_time);
        unset($data->access);
        unset($data->language);
        unset($data->note);
        unset($data->modality);
        unset($data->updated_by);
		unset($data->access_level);
		unset($data->editor);

		if($data->category_image != '')
		{
			$data->category_image = JUri::base() . $data->category_image;
		}

        //separate photos and file attachments
        $obj = json_decode($data->photo);
		unset($data->photo);

		$data->photos = array();
		$data->attachments = array();

		if(is_object($obj)) {
			unset($obj->id);
			unset($obj->imagedir);
			foreach ($obj->files as $file) {
				unset($file->deleteType);
				unset($file->deleteUrl);

				if (isset($file->thumbnailUrl))
				{
					$file->url = dirname(JUri::base()) . $file->url;
					$file->mediumUrl = dirname(JUri::base()) . $file->mediumUrl;
					$file->thumbnailUrl = dirname(JUri::base()) . $file->thumbnailUrl;
					array_push($data->photos, $file);
				}
				else
				{
					$file->url = dirname(JUri::base()) . $file->url;
					array_push($data->attachments, $file);
				}
			}
		}

        //set dates to UTC
        $data->created_UTC = $data->created == '0000-00-00 00:00:00' ? $data->created : self::convert2UTC($data->created);
        $data->updated_UTC = $data->updated == '0000-00-00 00:00:00' ? $data->updated : self::convert2UTC($data->updated);
        $data->regdate_UTC = $data->regdate == '0000-00-00 00:00:00' ? $data->regdate : self::convert2UTC($data->regdate);

		$data->moderation = (boolean)$data->moderation;
		$data->myIssue = ($data->created_by == $userid);
        return $data;
	}

	public static function sanitizeSteps($data)
	{
		if(!is_array($data)){
			throw new Exception('Steps sanitization bad input');
		}

		$steps = array();

		foreach ($data as $step)
		{
			$step = self::sanitizeStep($step);
			array_push($steps, $step);
		}
		return $steps;
	}

	public static function sanitizeStep($data)
	{
		if(!is_object($data)){
			throw new Exception('Step sanitization bad input');
		}
		//unset overhead
		unset($data->asset_id);
		unset($data->state);
		unset($data->created);
		unset($data->updated);
		unset($data->checked_out);
		unset($data->checked_out_time);
		unset($data->created_by);
		unset($data->updated_by);
		unset($data->language);
		unset($data->editor);

		return $data;
	}

	public static function getCategories($recursive = false)
	{
		$categories = JCategories::getInstance('imc');
		self::$_parent = $categories->get();
		if (is_object(self::$_parent)) {
			self::$_items = self::$_parent->getChildren($recursive);
		}
		else {
			self::$_items = false;
		}

		return self::loadCats(self::$_items);
	}

	protected static function loadCats($cats = array())
	{
		if(is_array($cats))
		{
			$i = 0;
			$return = array();
			foreach($cats as $JCatNode)
			{
				$return[$i] = new stdClass();
				$return[$i]->title = $JCatNode->title;
				$return[$i]->parentid = $JCatNode->parent_id;
				$return[$i]->path = $JCatNode->get('path');
				$return[$i]->id = $JCatNode->id;
				$params = json_decode($JCatNode->params);

				$return[$i]->image = $params->image;
				if($return[$i]->image)
				{
					$return[$i]->image = JUri::base() . $return[$i]->image;
				}

				if($JCatNode->hasChildren())
					$return[$i]->children = self::loadCats($JCatNode->getChildren());
				else
					$return[$i]->children = null;

				$i++;
			}
			return $return;
		}
		return false;
	}

	/**
	* Get category name using category ID
	* @param integer $category_id Category ID
	* @return mixed category name if category is found, null otherwise
	*/
	public static function getCategoryNameByCategoryId($category_id) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('title')
			->from('#__categories')
			->where('id = ' . intval($category_id));

		$db->setQuery($query);
		return $db->loadResult();
	}

	/**
	* Get step name using step ID
	* @param integer $stepid Step ID
	* @return step name and color if step is found, null otherwise
	*/
	public static function getStepByStepId($stepid) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('a.title AS stepid_title, a.stepcolor AS stepid_color, a.ordering')
			->from('#__imc_steps AS a')
			->where('a.id = ' . intval($stepid));

		$db->setQuery($query);
		return $db->loadAssoc();
	}

	public static function getPrimaryStepId()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('a.id')
			->from('#__imc_steps AS a')
			->order('a.ordering');

		$db->setQuery($query);
		return $db->loadResult();
	}

	public static function getGroupNameById($group_id) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('title')
			->from('#__usergroups')
			->where('id = ' . intval($group_id));

		$db->setQuery($query);
		return $db->loadResult();	
	}

	public static function getRelativeTime($time)
	{
		if(strtotime($time) <= 0)
			return '';
		
		// Load the parameters.
		$app = JFactory::getApplication();
/*		$params	= $app->getParams();
		$showrelativedates = $params->get('showrelativedates');		
		$dateformat = $params->get('dateformat');		
		
		if(!$showrelativedates){
			//$item->reported_rel = date("d/m/Y",strtotime($item->reported));
			return date($dateformat,strtotime($time));
		}
*/		
		$SECOND = 1;
		$MINUTE = 60 * $SECOND;
		$HOUR = 60 * $MINUTE;
		$DAY = 24 * $HOUR;
		$MONTH = 30 * $DAY;
 
		$delta = time() - strtotime($time);
		
		if ($delta < 1 * $MINUTE)
		{
			return $delta == 1 ? JText::_('ONE_SECOND_AGO') : sprintf(JText::_('SECONDS_AGO'), $delta);
		}
		if ($delta < 2 * $MINUTE)
		{
		  return JText::_('A_MINUTE_AGO');
		}
		if ($delta < 45 * $MINUTE)
		{
			return sprintf(JText::_('MINUTES_AGO'), floor($delta / $MINUTE));
		}
		if ($delta < 90 * $MINUTE)
		{
		  return JText::_('AN_HOUR_AGO');
		}
		if ($delta < 24 * $HOUR)
		{
		  return sprintf(JText::_('HOURS_AGO'), floor($delta / $HOUR));
		}
		if ($delta < 48 * $HOUR)
		{
		  return JText::_('YESTERDAY');
		}
		if ($delta < 30 * $DAY)
		{
			return sprintf(JText::_('DAYS_AGO'), floor($delta / $DAY));
		}
		if ($delta < 12 * $MONTH)
		{
		  $months = floor($delta / $DAY / 30);
		  return $months <= 1 ? JText::_('ONE_MONTH_AGO') : sprintf(JText::_('MONTHS_AGO'), $months);
		}
		else
		{
			$years = floor($delta / $DAY / 365);
			if($years < 100)	//TODO: needed for versions older than PHP5.3
				return $years <= 1 ? JText::_('ONE_YEAR_AGO') : sprintf(JText::_('YEARS_AGO'), $years);
			else
				return '';
		}

	}

	public function cutString($title, $max)
	{
	    if($title=='')
	        return '';

	    if(is_array($title)) list($string, $match_to) = $title;
	    else { $string = $title; $match_to = $title{0}; }
	 
	    $match_start = stristr($string, $match_to);
	    $match_compute = strlen($string) - strlen($match_start);
	 
	    if (strlen($string) > $max)
	    {
	        if ($match_compute < ($max - strlen($match_to)))
	        {
	            $pre_string = substr($string, 0, $max);
	            $pos_end = strrpos($pre_string, " ");
	            if($pos_end === false) $string = $pre_string."...";
	            else $string = substr($pre_string, 0, $pos_end)."...";
	        }
	        else if ($match_compute > (strlen($string) - ($max - strlen($match_to))))
	        {
	            $pre_string = substr($string, (strlen($string) - ($max - strlen($match_to))));
	            $pos_start = strpos($pre_string, " ");
	            $string = "...".substr($pre_string, $pos_start);
	            if($pos_start === false) $string = "...".$pre_string;
	            else $string = "...".substr($pre_string, $pos_start);
	        }
	        else
	        {
	            $pre_string = substr($string, ($match_compute - round(($max / 3))), $max);
	            $pos_start = strpos($pre_string, " "); $pos_end = strrpos($pre_string, " ");
	            $string = "...".substr($pre_string, $pos_start, $pos_end)."...";
	            if($pos_start === false && $pos_end === false) $string = "...".$pre_string."...";
	            else $string = "...".substr($pre_string, $pos_start, $pos_end)."...";
	        }
	 
	        $match_start = stristr($string, $match_to);
	        $match_compute = strlen($string) - strlen($match_start);
	    }
	 
	    return $string;

	}	
}
