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

	public static function convertFromUTC($date)
	{
		//get timezone from settings
		$offset = JFactory::getConfig()->get('offset');

		$tzDate = new DateTime($date, new DateTimeZone('UTC'));
		$tzDate->setTimezone(new DateTimeZone($offset));
		return $tzDate->format('Y-m-d H:i:s');
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

	public static function sanitizeIssues($data, $userid, $extensive = false)
	{
		if(!is_array($data)){
			throw new Exception('Issues sanitization bad input');
		}

		$issues = array();

		//get vote model
		$votesModel = JModelLegacy::getInstance( 'Votes', 'ImcModel', array('ignore_request' => true) );

		foreach ($data as $issue)
		{
			$issue = self::sanitizeIssue($issue, $userid);
			$issue->hasVoted = $votesModel->hasVoted($issue->id, $userid);
			if($extensive)
			{
				unset($issue->created_TZ);
				unset($issue->updated_TZ);
				unset($issue->regdate_TZ);
				unset($issue->updated_ts);
				unset($issue->description);
				unset($issue->hits);
				unset($issue->regdate);
				unset($issue->responsible);
				unset($issue->extra);
				unset($issue->subgroup);
				unset($issue->catid_title);
				unset($issue->stepid_title);
				unset($issue->stepid_color);
				unset($issue->category_image);
				foreach ($issue->photos as &$photo) {
					unset($photo->name);
					//unset($photo->size);
					//unset($photo->mediumUrl);
				}
				foreach ($issue->attachments as &$attachment) {
					unset($attachment->name);
					unset($attachment->size);
				}

			}
			array_push($issues, $issue);
		}
		return $issues;
	}

	public static function sanitizeComment($data, $userid)
	{
		if(!is_object($data)){
			throw new Exception('Comment sanitization bad input');
		}
		//unset overhead
		unset($data->asset_id);
		unset($data->ordering);
		unset($data->checked_out);
		unset($data->checked_out_time);
		unset($data->language);
		unset($data->modality);
		unset($data->editor);
		unset($data->photo);
		unset($data->updated);
		unset($data->updated_by);
		unset($data->issue_title);
		if(isset($data->profile_picture_url))
		{
			unset($data->profile_picture_url);
		}
		if(isset($data->created_by_admin))
		{
			unset($data->creted_by_admin);
		}
		if(isset($data->created_by_current_user))
		{
			unset($data->created_by_currentJ_user);
		}

		//set dates to server timezone
		$data->created_TZ = $data->created == '0000-00-00 00:00:00' ? $data->created : self::convertFromUTC($data->created);
		$data->created_ts = $data->created == '0000-00-00 00:00:00' ? 1 :  strtotime($data->created_TZ);

		$data->myComment = ($data->created_by == $userid);

		//do the casting
		$data->moderation = (boolean)$data->moderation;
		$data->isAdmin = (boolean)$data->isAdmin;
		$data->id = (int)$data->id;
		$data->issueid = (int)$data->issueid;
		if(isset($data->parentid))
		{
			$data->parentid = (int)$data->parentid;
		}
		$data->state = (int)$data->state;
		$data->created_by = (int)$data->created_by;

		return $data;
	}

	public static function sanitizeComments($data, $userid)
	{
		if(!is_array($data)){
			throw new Exception('Comments sanitization bad input');
		}

		foreach ($data as &$comment)
		{
			self::sanitizeComment($comment, $userid);
		}

		return $data;
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
			$data->category_image = JUri::base() . str_replace('%2F', '/', rawurlencode($data->category_image));
		}

        //separate photos and file attachments
        $obj = json_decode($data->photo);
		unset($data->photo);

		$data->photos = array();
		$data->attachments = array();

		if(is_object($obj)) {
			foreach ($obj->files as $file) {
				unset($file->deleteType);
				unset($file->deleteUrl);

				if (isset($file->thumbnailUrl))
				{
					$file->url = JUri::base() . 'images/imc/' . $data->id . '/' . rawurlencode($file->name);
					$file->mediumUrl = JUri::base() . 'images/imc/' . $data->id . '/medium/' . rawurlencode($file->name);
					$file->thumbnailUrl = JUri::base() . 'images/imc/' . $data->id . '/thumbnail/' . rawurlencode($file->name);
					array_push($data->photos, $file);
				}
				elseif(isset($file->url))
				{
					$file->url = JUri::base() . 'images/imc/' . $data->id . '/' . rawurlencode($file->name);
					array_push($data->attachments, $file);
				}
			}
			unset($obj->id);
			unset($obj->imagedir);
		}

        //set dates to server timezone
        $data->created_TZ = $data->created == '0000-00-00 00:00:00' ? $data->created : self::convertFromUTC($data->created);
        $data->updated_TZ = $data->updated == '0000-00-00 00:00:00' ? $data->updated : self::convertFromUTC($data->updated);
        $data->regdate_TZ = $data->regdate == '0000-00-00 00:00:00' ? $data->regdate : self::convertFromUTC($data->regdate);
		$data->created_ts = $data->created == '0000-00-00 00:00:00' ? 1 :  strtotime($data->created_TZ);
		$data->updated_ts = $data->updated == '0000-00-00 00:00:00' ? 1 :  strtotime($data->updated_TZ);

		$data->myIssue = ($data->created_by == $userid);

		$params = JFactory::getApplication()->getParams('com_imc');

		//do the casting
		$data->moderation = (boolean)$data->moderation;
		$data->id = (int)$data->id;
		$data->stepid = (int)$data->stepid;
		$data->catid = (int)$data->catid;
		$data->state = (int)$data->state;
		$data->created_by = (int)$data->created_by;
		$data->hits = (int)$data->hits;
		$data->votes = (int)$data->votes;
		$data->subgroup = (int)$data->subgroup;
		if(isset($data->children_count))
		{
			$data->children_count = (int)$data->children_count;
		}
		if(isset($data->comments))
		{
			//check if comments are allowed
			$showComments = self::showComments(JFactory::getUser($userid), $data);
			if (!$showComments)
			{
				$data->comments = -1;
			}
			$data->comments = (int)$data->comments;
		}

		//check confidentiality and sanitize logs


		if(isset($data->timeline))
		{
			$data->timeline = self::sanitizeLogs($data->timeline);
		}

		if ($params->get('showuserdetailstimeline') == 0)
		{
			$data->created_by_name = null;
		}

		return $data;
	}

	public static function sanitizeLogs($data)
	{
		if(!is_array($data)){
			throw new Exception('Logs sanitization bad input');
		}
		$params = JFactory::getApplication()->getParams('com_imc');
		$showName = (boolean) $params->get('showadmindetailstimeline');

		foreach ($data as &$tl)
		{
			$tl['created_TZ'] = $tl['created'] == '0000-00-00 00:00:00' ? $tl['created'] : self::convertFromUTC($tl['created']);
			$tl['created_ts'] = $tl['created'] == '0000-00-00 00:00:00' ? 1 :  strtotime($tl['created']);
			if(!$showName){
				$tl['created_by'] = null;
			}
		}

		return $data;
	}

	public static function sanitizeVotes($data)
	{
		if(!is_array($data)){
			throw new Exception('Votes sanitization bad input');
		}

		foreach ($data as &$vote)
		{
			$vote->created_ts = $vote->created == '0000-00-00 00:00:00' ? 1 :  strtotime($vote->created);

			unset($vote->id);
			unset($vote->asset_id);
			unset($vote->created);
			unset($vote->updated);
			unset($vote->ordering);
			unset($vote->state);
			unset($vote->checked_out);
			unset($vote->checked_out_time);
			unset($vote->updated_by);
			unset($vote->modality);
			unset($vote->issue_title);
			unset($vote->created_by_name);

			//do the casting
			$vote->issueid = (int) $vote->issueid;
			$vote->created_by = (int) $vote->created_by;

		}

		return $data;
	}

	public static function sanitizeModifiedVotes($data)
	{
		if(!is_array($data)){
			throw new Exception('Modified votes sanitization bad input');
		}

		foreach ($data as &$vote)
		{
			//do the casting
			$vote['issueid'] = (int) $vote['issueid'];
			$vote['votes'] = (int) $vote['votes'];
		}

		return $data;
	}

	public static function sanitizeSteps($data, $extensive = false)
	{
		if(!is_array($data)){
			throw new Exception('Steps sanitization bad input');
		}

		$steps = array();

		foreach ($data as $step)
		{
			$step = self::sanitizeStep($step);
			if($extensive)
			{
				unset($step->updated_ts);
				unset($step->description);
			}
			array_push($steps, $step);
		}
		return $steps;
	}

	public static function sanitizeCategories($data)
	{
		if(!is_array($data)){
			throw new Exception('Categories sanitization bad input');
		}

		foreach ($data as &$category)
		{
			$category['id'] = (int) $category['id'];
			$category['state'] = (int) $category['state'];
		}
		return $data;
	}

	public static function sanitizeStep($data)
	{
		if(!is_object($data)){
			throw new Exception('Step sanitization bad input');
		}
		//unset overhead
		unset($data->asset_id);
		unset($data->created);
		unset($data->checked_out);
		unset($data->checked_out_time);
		unset($data->created_by);
		unset($data->updated_by);
		unset($data->language);
		unset($data->editor);

		$data->updated_ts = $data->updated == '0000-00-00 00:00:00' ? 1 :  strtotime($data->updated);
		unset($data->updated);

		//do the casting
		$data->id = (int)$data->id;
		$data->state = (int)$data->state;
		$data->ordering = (int)$data->ordering;

		return $data;
	}

	public static function sanitizeCalendar($data)
	{
		if(!is_array($data)){
			throw new Exception('Calendar sanitization bad input');
		}

		//do the casting
		foreach ($data as &$d) {
			$d['Year'] = (int)$d['Year'];
			$d['Jan'] = (int)$d['Jan'];
			$d['Feb'] = (int)$d['Feb'];
			$d['Mar'] = (int)$d['Mar'];
			$d['Apr'] = (int)$d['Apr'];
			$d['May'] = (int)$d['May'];
			$d['Jun'] = (int)$d['Jun'];
			$d['Jul'] = (int)$d['Jul'];
			$d['Aug'] = (int)$d['Aug'];
			$d['Sep'] = (int)$d['Sep'];
			$d['Oct'] = (int)$d['Oct'];
			$d['Nov'] = (int)$d['Nov'];
			$d['Dec'] = (int)$d['Dec'];
			if(isset($d['stepid']))
			{
				$d['stepid'] = (int)$d['stepid'];
			}
			if(isset($d['catid']))
			{
				$d['catid'] = (int)$d['catid'];
			}
		}

		return $data;
	}

	public static function sanitizeDailyCalendar($data, $year, $month)
	{
		if(!is_array($data)){
			throw new Exception('Daily Calendar sanitization bad input');
		}

		//do the casting
		foreach ($data as &$d) {
			$d['Month'] = (int)$d['Month'];
			$d['1']  = (int)$d['1'];
			$d['2']  = (int)$d['2'];
			$d['3']  = (int)$d['3'];
			$d['4']  = (int)$d['4'];
			$d['5']  = (int)$d['5'];
			$d['6']  = (int)$d['6'];
			$d['7']  = (int)$d['7'];
			$d['8']  = (int)$d['8'];
			$d['9']  = (int)$d['9'];
			$d['10'] = (int)$d['10'];
			$d['11'] = (int)$d['11'];
			$d['12'] = (int)$d['12'];
			$d['13'] = (int)$d['13'];
			$d['14'] = (int)$d['14'];
			$d['15'] = (int)$d['15'];
			$d['16'] = (int)$d['16'];
			$d['17'] = (int)$d['17'];
			$d['18'] = (int)$d['18'];
			$d['19'] = (int)$d['19'];
			$d['20'] = (int)$d['20'];
			$d['21'] = (int)$d['21'];
			$d['22'] = (int)$d['22'];
			$d['23'] = (int)$d['23'];
			$d['24'] = (int)$d['24'];
			$d['25'] = (int)$d['25'];
			$d['26'] = (int)$d['26'];
			$d['27'] = (int)$d['27'];
			$d['28'] = (int)$d['28'];
			$d['29'] = (int)$d['29'];
			$d['30'] = (int)$d['30'];
			$d['31'] = (int)$d['31'];

			if(isset($d['stepid']))
			{
				$d['stepid'] = (int)$d['stepid'];
			}
			if(isset($d['catid']))
			{
				$d['catid'] = (int)$d['catid'];
			}

			$has30 = array(4, 6, 9, 11);
			$leap = array(2000, 2004, 2008,2012, 2016, 2020, 2024, 2028, 2032, 2036, 2040);
			//I'll be retired by then... let someone else add the next leap year or implement a decent algorithm :)

			if(in_array($month, $has30))
			{
				unset($d['31']);
			}
			if($month == 2)
			{
				unset($d['31']);
				unset($d['30']);
				if(!in_array($year, $leap))
				{
					unset($d['29']);
				}
			}
		}

		return $data;
	}

	public static function getModifiedCategories($ts)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('a.id, a.title, a.parent_id, a.published AS state, a.params')
			->from('#__categories AS a')
			->where('extension = ' . $db->quote('com_imc'))
			->order('lft asc')
            ->where('a.modified_time >= "' . $ts . '"');

		$db->setQuery($query);
		$result = $db->loadAssocList();
		foreach ($result as &$category) {
			$params = json_decode($category['params']);
			$category['image'] = $params->image;
			if($category['image'])
			{
				$category['image'] = JUri::base() . $category['image'];
			}
			$category['parentid'] = $category['parent_id'] == "root" ? 1 : (int) $category['parent_id'];
			unset($category['parent_id']);
			unset($category['params']);
		}

		return $result;
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
				$return[$i]->parentid = $JCatNode->parent_id == "root" ? 1 : (int) $JCatNode->parent_id;
				$return[$i]->path = $JCatNode->get('path');
				$return[$i]->id = (int) $JCatNode->id;
				$return[$i]->state = (int) $JCatNode->published;
				$return[$i]->updated_ts = $JCatNode->modified_time == '0000-00-00 00:00:00' ? 1 :  strtotime($JCatNode->modified_time);
				$params = json_decode($JCatNode->params);

				$return[$i]->image = $params->image;
				if($return[$i]->image)
				{
					$return[$i]->image = JUri::base() . $return[$i]->image;
				}

				if($JCatNode->hasChildren())
					$return[$i]->children = self::loadCats($JCatNode->getChildren());
				else
					$return[$i]->children = array();

				$i++;
			}
			return $return;
		}
		return false;
	}

	public static function isValidTimeStamp($timestamp)
	{
		return ((string) (int) $timestamp === $timestamp)
		&& ($timestamp <= PHP_INT_MAX)
		&& ($timestamp >= ~PHP_INT_MAX);
	}

	/**
	* Get category name using category ID
	* @param integer $category_id Category ID
	* @param boolean $publishedOnly Take into account category state
	* @return mixed category name if category is found, null otherwise
	*/
	public static function getCategoryNameByCategoryId($category_id, $publishedOnly = false) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('title')
			->from('#__categories')
			->where('extension = ' . $db->quote('com_imc'))
			->where('id = ' . intval($category_id));

		if($publishedOnly)
		{
			$query->where('published = 1');
		}

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

	public static function getGroupNameById($group_id)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('title')
			->from('#__usergroups')
			->where('id = ' . intval($group_id));

		$db->setQuery($query);
		return $db->loadResult();	
	}

	public static function emailExists($email)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(*)');
		$query->from($db->quoteName('#__users'));
		$query->where($db->quoteName('email')." = ".$db->quote($email));

		$db->setQuery($query);
		$count = $db->loadResult();

		return (bool) $count;
	}

	public static function setLanguage($language, $extensions = array('com_imc'))
	{
		$lang = JFactory::getLanguage();
		$joomlaLang = null;

		//try to align input with available language
		$availLanguages = $lang->getKnownLanguages();
		foreach ($availLanguages as $key => $value) {
			if($language == substr($key, 0, 2))
			{
				$joomlaLang = $key;
			}
		}

		if(is_null($joomlaLang))
		{
			$joomlaLang = JFactory::getLanguage()->getDefault();
		}

		$base_dir = JPATH_SITE;
		$language_tag = $joomlaLang;
		$reload = true;
		//for each extension load the appropriate language
		foreach ($extensions as $extension) {
			$lang->load($extension, $base_dir, $language_tag, $reload);
		}
	}

	public static function getRelativeTime($time)
	{
		if(strtotime($time) <= 0)
			return '';

		$time = ImcFrontendHelper::convertFromUTC($time);

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

	public static function cutString($title, $max)
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

	public static function getModifiedVotes($ts = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.id AS issueid, a.votes');
		$query->from('#__imc_issues AS a');
		$query->where('
			a.id IN (
				SELECT DISTINCT b.issueid
				FROM #__imc_votes AS b
				WHERE b.updated >= "'.$ts.'"
			)
		');

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	/* Analytics */

	public static function getTopUsers($limit = null, $ts = null, $prior_to = null, $ids = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(*) AS `count_issues`, a.created_by, b.name');
		$query->from('`#__imc_issues` AS a');
		$query->join('LEFT', '#__users AS b ON b.id = a.created_by');
		$query->where('a.state = 1');
		$query->group('a.created_by');
		$query->order('count_issues DESC');
		if(!is_null($limit) && $limit > 0)
		{
			$query->setlimit($limit);
		}
		if(!is_null($ts))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) >= ' . $ts);
			$query->where('a.created >= "' . $ts .'"');
		}
		if(!is_null($prior_to))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) <= ' . $prior_to);
			$query->where('a.created <= "' . $prior_to .'"');
		}
		if(!is_null($ids))
		{
			$query->where('a.id IN ('.$ids.')');
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function getTopCategories($limit = null, $ts = null, $prior_to = null, $ids = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(*) AS `count_issues`, a.catid, b.title');
		$query->from('`#__imc_issues` AS a');
		$query->join('LEFT', '#__categories AS b ON b.id = a.catid');
		$query->where('a.state = 1');
		$query->group('a.catid');
		$query->order('count_issues DESC');
		if(!is_null($limit) && $limit > 0)
		{
			$query->setlimit($limit);
		}
		if(!is_null($ts))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) >= ' . $ts);
			$query->where('a.created >= "' . $ts .'"');
		}
		if(!is_null($prior_to))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) <= ' . $prior_to);
			$query->where('a.created <= "' . $prior_to .'"');
		}
		if(!is_null($ids))
		{
			$query->where('a.id IN ('.$ids.')');
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function getTopSteps($limit = null, $ts = null, $prior_to = null, $ids = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(*) AS `count_issues`, a.stepid, b.stepcolor, b.title');
		$query->from('`#__imc_issues` AS a');
		$query->join('LEFT', '#__imc_steps AS b ON b.id = a.stepid');
		$query->where('a.state = 1');
		$query->group('a.stepid');
		$query->order('count_issues DESC');
		if(!is_null($limit) && $limit > 0)
		{
			$query->setlimit($limit);
		}
		if(!is_null($ts))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) >= ' . $ts);
			$query->where('a.created >= "' . $ts .'"');
		}
		if(!is_null($prior_to))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) <= ' . $prior_to);
			$query->where('a.created <= "' . $prior_to .'"');
		}
		if(!is_null($ids))
		{
			$query->where('a.id IN ('.$ids.')');
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function getTopVoters($limit = null, $ts = null, $prior_to = null, $ids = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(*) AS `count_votes`, a.created_by, b.name');
		$query->from('`#__imc_votes` AS a');
		$query->join('LEFT', '#__users AS b ON b.id = a.created_by');
		$query->where('a.state = 1');
		$query->group('a.created_by');
		$query->order('count_votes DESC');

		if(!is_null($limit) && $limit > 0)
		{
			$query->setlimit($limit);
		}
		if(!is_null($ts))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) >= ' . $ts);
			$query->where('a.created >= "' . $ts .'"');
		}
		if(!is_null($prior_to))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) <= ' . $prior_to);
			$query->where('a.created <= "' . $prior_to .'"');
		}
		if(!is_null($ids))
		{
			$query->where('a.id IN ('.$ids.')');
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function getTopCommenters($limit = null, $ts = null, $prior_to = null, $ids = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(*) AS `count_comments`, a.created_by, b.name');
		$query->from('`#__imc_comments` AS a');
		$query->join('LEFT', '#__users AS b ON b.id = a.created_by');
		$query->where('a.state = 1');
		$query->group('a.created_by');
		$query->order('count_comments DESC');

		if(!is_null($limit) && $limit > 0)
		{
			$query->setlimit($limit);
		}
		if(!is_null($ts))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) >= ' . $ts);
			$query->where('a.created >= "' . $ts .'"');
		}
		if(!is_null($prior_to))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) <= ' . $prior_to);
			$query->where('a.created <= "' . $prior_to .'"');
		}
		if(!is_null($ids))
		{
			$query->where('a.id IN ('.$ids.')');
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function getTotals($limit = null, $ts = null, $prior_to = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('
		  (SELECT COUNT(*) FROM #__imc_issues WHERE state=1) as total_issues,
		  (SELECT COUNT(*) FROM #__imc_votes WHERE state=1) as total_votes,
		  (SELECT COUNT(*) FROM #__imc_comments WHERE state=1) as total_comments,
		  (SELECT COUNT(*) FROM #__users WHERE 1) as total_users,
		  (SELECT created FROM #__imc_issues WHERE state=1 ORDER BY created ASC LIMIT 1) as oldest_issue_date,
		  (SELECT created FROM #__imc_issues WHERE state=1 ORDER BY created DESC LIMIT 1) as newest_issue_date
		');

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function searchIssuesByComments($keywords, $limit = null, $ts = null, $prior_to = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('DISTINCT a.*, b.params AS catid_params');
		$query->from('#__imc_issues AS a');
		$query->join('LEFT', '#__imc_comments AS c ON a.id = c.issueid');
		$query->join('LEFT', '#__categories AS b ON b.id = a.catid');
		$query->where('a.state=1');
		$keywords = $db->Quote('%' . $db->escape($keywords, true) . '%');
		$query->where('( c.description LIKE '.$keywords.' )');
		if(!is_null($ts))
		{
			$query->where('a.created >= "' . $ts .'"');
		}
		if(!is_null($prior_to))
		{
			$query->where('a.created <= "' . $prior_to .'"');
		}
		if(!is_null($limit) && $limit > 0)
		{
			$query->setlimit($limit);
		}
		$db->setQuery($query);

		$items = $db->loadAssocList();

		//replicate issues models getItems()
		foreach ($items as &$item)
		{
			$item['created_by_name'] = JFactory::getUser($item['created_by'])->name;
			$prms = json_decode($item['catid_params']);
			unset($item['catid_params']);
			if (isset($prms->image))
			{
				$item['category_image'] = $prms->image;
			}
			else
			{
				$item['category_image'] = '';
			}
		}

		return $items;
	}

	public static function searchIssues($keywords, $field, $limit = null, $ts = null, $prior_to = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('DISTINCT a.*, b.params AS catid_params');
		$query->from('#__imc_issues AS a');
		$query->join('LEFT', '#__categories AS b ON b.id = a.catid');
		$query->where('a.state=1');
		$keywords = $db->Quote('%' . $db->escape($keywords, true) . '%');
		$query->where('( a.' . $field .' LIKE '.$keywords.' )');
		if(!is_null($ts))
		{
			$query->where('a.created >= "' . $ts .'"');
		}
		if(!is_null($prior_to))
		{
			$query->where('a.created <= "' . $prior_to .'"');
		}
		if(!is_null($limit) && $limit > 0)
		{
			$query->setlimit($limit);
		}
		$db->setQuery($query);

		$items = $db->loadAssocList();

		//replicate issues models getItems()
		foreach ($items as &$item)
		{
			$item['created_by_name'] = JFactory::getUser($item['created_by'])->name;
			$prms = json_decode($item['catid_params']);
			unset($item['catid_params']);
			if (isset($prms->image))
			{
				$item['category_image'] = $prms->image;
			}
			else
			{
				$item['category_image'] = '';
			}
		}

		return $items;
	}

	public static function array2obj($in)
	{
		foreach ($in as &$comment) {
			$obj = new stdClass();
			foreach ($comment as $key => $value)
			{
				$obj->$key = $value;
			}
			$comment = $obj;
		}

		return $in;
	}

	public static function calendar($field = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('
		  YEAR(a.created) AS `Year`,
		  COUNT(CASE WHEN MONTH(a.created) = 1 THEN a.id END) AS `Jan`,
		  COUNT(CASE WHEN MONTH(a.created) = 2 THEN a.id END) AS `Feb`,
		  COUNT(CASE WHEN MONTH(a.created) = 3 THEN a.id END) AS `Mar`,
		  COUNT(CASE WHEN MONTH(a.created) = 4 THEN a.id END) AS `Apr`,
		  COUNT(CASE WHEN MONTH(a.created) = 5 THEN a.id END) AS `May`,
		  COUNT(CASE WHEN MONTH(a.created) = 6 THEN a.id END) AS `Jun`,
		  COUNT(CASE WHEN MONTH(a.created) = 7 THEN a.id END) AS `Jul`,
		  COUNT(CASE WHEN MONTH(a.created) = 8 THEN a.id END) AS `Aug`,
		  COUNT(CASE WHEN MONTH(a.created) = 9 THEN a.id END) AS `Sep`,
		  COUNT(CASE WHEN MONTH(a.created) = 10 THEN a.id END) AS `Oct`,
		  COUNT(CASE WHEN MONTH(a.created) = 11 THEN a.id END) AS `Nov`,
		  COUNT(CASE WHEN MONTH(a.created) = 12 THEN a.id END) AS `Dec`
		');
		$query->from('#__imc_issues AS a');
		$query->where('a.state=1');
		$query->group('YEAR(a.created)');

		switch($field)
		{
			case 'stepid':
				$query->select('b.title, b.stepcolor, a.stepid');
				$query->join('LEFT', '#__imc_steps AS b ON b.id = a.stepid');
				$query->group('a.stepid');
				break;
			case 'catid':
				$query->select('b.title, a.catid');
				$query->join('LEFT', '#__categories AS b ON b.id = a.catid');
				$query->group('a.catid');
				break;
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function dailyCalendar($year, $month, $field = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('
		  MONTH(a.created) AS `Month`,
		  COUNT(CASE WHEN DAY(a.created) = 1 THEN a.id END) AS `1`,
		  COUNT(CASE WHEN DAY(a.created) = 2 THEN a.id END) AS `2`,
		  COUNT(CASE WHEN DAY(a.created) = 3 THEN a.id END) AS `3`,
		  COUNT(CASE WHEN DAY(a.created) = 4 THEN a.id END) AS `4`,
		  COUNT(CASE WHEN DAY(a.created) = 5 THEN a.id END) AS `5`,
		  COUNT(CASE WHEN DAY(a.created) = 6 THEN a.id END) AS `6`,
		  COUNT(CASE WHEN DAY(a.created) = 7 THEN a.id END) AS `7`,
		  COUNT(CASE WHEN DAY(a.created) = 8 THEN a.id END) AS `8`,
		  COUNT(CASE WHEN DAY(a.created) = 9 THEN a.id END) AS `9`,
		  COUNT(CASE WHEN DAY(a.created) = 10 THEN a.id END) AS `10`,
		  COUNT(CASE WHEN DAY(a.created) = 11 THEN a.id END) AS `11`,
		  COUNT(CASE WHEN DAY(a.created) = 12 THEN a.id END) AS `12`,
		  COUNT(CASE WHEN DAY(a.created) = 13 THEN a.id END) AS `13`,
		  COUNT(CASE WHEN DAY(a.created) = 14 THEN a.id END) AS `14`,
		  COUNT(CASE WHEN DAY(a.created) = 15 THEN a.id END) AS `15`,
		  COUNT(CASE WHEN DAY(a.created) = 16 THEN a.id END) AS `16`,
		  COUNT(CASE WHEN DAY(a.created) = 17 THEN a.id END) AS `17`,
		  COUNT(CASE WHEN DAY(a.created) = 18 THEN a.id END) AS `18`,
		  COUNT(CASE WHEN DAY(a.created) = 19 THEN a.id END) AS `19`,
		  COUNT(CASE WHEN DAY(a.created) = 20 THEN a.id END) AS `20`,
		  COUNT(CASE WHEN DAY(a.created) = 21 THEN a.id END) AS `21`,
		  COUNT(CASE WHEN DAY(a.created) = 22 THEN a.id END) AS `22`,
		  COUNT(CASE WHEN DAY(a.created) = 23 THEN a.id END) AS `23`,
		  COUNT(CASE WHEN DAY(a.created) = 24 THEN a.id END) AS `24`,
		  COUNT(CASE WHEN DAY(a.created) = 25 THEN a.id END) AS `25`,
		  COUNT(CASE WHEN DAY(a.created) = 26 THEN a.id END) AS `26`,
		  COUNT(CASE WHEN DAY(a.created) = 27 THEN a.id END) AS `27`,
		  COUNT(CASE WHEN DAY(a.created) = 28 THEN a.id END) AS `28`,
		  COUNT(CASE WHEN DAY(a.created) = 29 THEN a.id END) AS `29`,
		  COUNT(CASE WHEN DAY(a.created) = 30 THEN a.id END) AS `30`,
		  COUNT(CASE WHEN DAY(a.created) = 31 THEN a.id END) AS `31`
		');
		$query->from('#__imc_issues AS a');
		$query->where('a.state=1');
		$query->where('YEAR(a.created) = ' . $year);
		$query->where('MONTH(a.created) = ' . $month);
		$query->group('MONTH(a.created)');

		switch($field)
		{
			case 'stepid':
				$query->select('b.title, b.stepcolor, a.stepid');
				$query->join('LEFT', '#__imc_steps AS b ON b.id = a.stepid');
				$query->group('a.stepid');
				break;
			case 'catid':
				$query->select('b.title, a.catid');
				$query->join('LEFT', '#__categories AS b ON b.id = a.catid');
				$query->group('a.catid');
				break;
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function intervals($by_step = false, $by_category = false, $ts = null, $prior_to = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('AVG(days_diff) AS avg_days, MIN(days_diff) AS min_days, MAX(days_diff) AS max_days, COUNT(issueid) AS count_issues');
		$query->from('
			(
				SELECT DISTINCT * FROM (

					SELECT a.issueid, a.stepid, b.catid, a.created,
					  CASE WHEN (a.created - f.created) IS NULL THEN a.created + INTERVAL 1 SECOND ELSE f.created END AS vcreated,
					  CASE WHEN (a.created - f.created) IS NULL THEN 0 ELSE ABS(DATEDIFF(a.created, f.created)) END AS days_diff

					FROM #__imc_log AS a
					  LEFT JOIN #__imc_log AS f ON a.created > f.created AND a.issueid = f.issueid
					  LEFT JOIN #__imc_issues AS b ON b.id = a.issueid
					WHERE a.issueid IN (
					  SELECT id
					  FROM #__imc_issues AS p
					  WHERE p.state = 1 AND p.stepid >= '. self::getPrimaryStepId() .
						(!is_null($ts) ? ' AND p.created >= "' . $ts .'"' : '').
						(!is_null($prior_to) ? ' AND p.created <= "' . $prior_to .'"' : '').'
					)
					AND a.action = "step"
					AND a.state = 1
					GROUP BY vcreated

				) AS dis
				GROUP BY issueid

			) AS intervals
		');

		if($by_step && !$by_category)
		{
			$query->select('stepid, s.title AS steptitle, s.stepcolor');
			$query->join('LEFT', '#__imc_steps AS s ON s.id = intervals.stepid');
			$query->group('stepid');
		}
		if($by_category && !$by_step)
		{
			$query->select('catid, c.title AS category');
			$query->join('LEFT', '#__categories AS c ON c.id = intervals.catid');
			$query->group('catid');
		}
		if($by_category && $by_step)
		{
			$query->select('stepid, s.title AS steptitle, s.stepcolor');
			$query->select('catid, c.title AS category');
			$query->join('LEFT', '#__imc_steps AS s ON s.id = intervals.stepid');
			$query->join('LEFT', '#__categories AS c ON c.id = intervals.catid');
			$query->group('catid, stepid');

			//nest steps by category
			$db->setQuery($query);
			$results =  $db->loadAssocList();

			$nested = array();
			$categories = array();
			$cat = 'any';
			foreach ($results as $ar)
			{
				if($ar['catid'] != $cat)
				{
					array_push($categories, $ar['catid']);
				}
				$cat = $ar['catid'];
			}
			foreach ($results as $ar)
			{
				$nested[$ar['catid']][] = $ar;
			}

			return $nested;
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function getIds($data)
	{
		$ids = array();
		foreach ($data as $item) {
			array_push($ids, $item['id']);
		}

		return implode(',', $ids);
	}

	public static function checkUniqueName($username)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$uname = $username;
		$i = 0;
		$name = '';
		while($uname)
		{
			$name = ($i == 0) ? $username : $username.'-'.$i;

			$query->clear();
			$query->select($db->quoteName('username'));
			$query->from($db->quoteName('#__users'));
			$query->where($db->quoteName('username') . ' = ' . $db->quote($name));
			$db->setQuery($query, 0, 1);
			$uname = $db->loadResult();

			$i++;
		}
		return $name;
	}

	public static function getFreeMail($email){
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$umail = $email;
		$parts = explode('@', $email);

		$i = 0;
		while($umail){
			$mail = ($i == 0) ? $email : $parts[0].'-'.$i.'@'.$parts[1];

			$query->clear();
			$query->select($db->quoteName('email'));
			$query->from($db->quoteName('#__users'));
			$query->where($db->quoteName('email') . ' = ' . $db->quote($mail));
			$db->setQuery($query, 0, 1);
			$umail = $db->loadResult();

			$i++;
		}
		return $mail;
	}

	public static function getSocialUser($slogin_id)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__slogin_users'));
		$query->where($db->quoteName('slogin_id') . ' = ' . $db->quote($slogin_id));
		$db->setQuery($query);

		return $db->loadAssoc();
	}

	public static function getSocialEmail($userid)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('email');
		$query->from($db->quoteName('#__plg_slogin_profile'));
		$query->where($db->quoteName('user_id') . ' = ' . $db->quote($userid));
		$db->setQuery($query);

		return $db->loadResult();
	}

	public static function updateSocialProfile($userid, $slogin_id, $f_name, $l_name, $email, $phone = null)
	{
		$object = new stdClass();
		$object->user_id = $userid;
		$object->slogin_id = $slogin_id;
		$object->f_name = $f_name;
		$object->l_name = $l_name;
		$object->email = $email;
		if(!is_null($phone))
		{
			$object->phone = $phone;
		}
		$result = JFactory::getDbo()->updateObject('#__plg_slogin_profile', $object, array('user_id', 'slogin_id') );
		return $result;
	}

    public static function updateUserUsername($userid, $newUsername)
    {
	    $object = new stdClass();
	    $object->id = $userid;
	    $object->username = $newUsername;
	    $result = JFactory::getDbo()->updateObject('#__users', $object, 'id');
	    return $result;
    }

	public static function updateUserName($userid, $newName)
	{
		$object = new stdClass();
		$object->id = $userid;
		$object->name = $newName;
		$result = JFactory::getDbo()->updateObject('#__users', $object, 'id');
		return $result;
	}

	public static function updateUserEmail($userid, $newEmail)
	{
		$object = new stdClass();
		$object->id = $userid;
		$object->email = $newEmail;
		$result = JFactory::getDbo()->updateObject('#__users', $object, 'id');
		return $result;
	}

	public static function createSloginUser($userid, $slogin_id, $provider)
	{
		$db = JFactory::getDbo();

		// Create and populate an object.
		$object = new stdClass();
		$object->user_id = $userid;
		$object->slogin_id = $slogin_id;
		$object->provider = $provider;

		$result = $db->insertObject('#__slogin_users', $object);
		if(!$result)
		{
			throw new Exception('Cannot store new social user');
		}

		return $result;
	}

	public static function createSocialProfile($userid, $slogin_id, $provider, $f_name, $l_name, $email, $phone = '')
	{
		$object = new stdClass();
		$object->user_id = $userid;
		$object->slogin_id = $slogin_id;
		$object->provider = $provider;
		$object->f_name = $f_name;
		$object->l_name = $l_name;
		$object->email = $email;
		$object->phone = $phone;

		$result = JFactory::getDbo()->insertObject('#__plg_slogin_profile', $object);
		if(!$result)
		{
			throw new Exception('Cannot store new social user profile');
		}
		return $result;
	}

	public static function getUserId($username, $email)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('`id`')
				->from('`#__users`')
				->where('`username` = '.$db->quote($username))
				->where('`email` = '.$db->quote($email))
		;
		$userid	= (int)$db->setQuery($query,0,1)->loadResult();
		return $userid;
	}

	public static function getRawIssues($ts, $prior_to, $minLat, $maxLat, $minLng, $maxLng)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('`id`,`latitude`,`longitude`,`votes`')
			->from('`#__imc_issues` AS a')
			->where('state = 1')
		;

		if(!is_null($minLat) && !is_null($maxLat) && !is_null($minLng) && !is_null($maxLng))
		{
			$query->where('a.latitude BETWEEN ' . $minLat . ' AND ' . $maxLat );
			$query->where('a.longitude BETWEEN ' . $minLng . ' AND ' . $maxLng );
		}

		if(!is_null($ts))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) >=' . $ts);
			$query->where('a.updated >= "' . $ts .'"');

		}

		if(!is_null($prior_to))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) <=' . $prior_to);
			$query->where('a.updated <= "' . $prior_to .'"');
		}

		$db->setQuery($query);
		$result = $db->loadAssocList();
		return $result;

	}

	public static function showComments($user, $issue)
	{
		$showComments = true;

		$params = JFactory::getApplication()->getParams('com_imc');
		$commentsEnabled = $params->get('enablecomments', false);
		$commentsMode = $params->get('commentsmode');
		$ownIssue = $user->id == $issue->created_by;

		//if mode is private and user is the owner of the issue then show comments
		if ($commentsMode == 'private' && $ownIssue)
		{
			$showComments = true;
		}
		else
		{
			$showComments = false;
		}

		//if user is comments-administrator then show the comments in any case
		if(ImcHelper::getActions($user)->get('imc.manage.comments'))
		{
			$showComments = true;
		}

		//also if mode is public then show the comments in any case
		if ($commentsMode == 'public')
		{
			$showComments = true;
		}

		//finally, if comments are disabled then do not show comments in any case
		if(!$commentsEnabled)
		{
			$showComments = false;
		}

		return $showComments;
	}

    public static function issuesByCategory($ts = null, $catid = null)
    {
        if(is_null($ts))
        {
            $ts = 0;
        }
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query
            ->select('a.id, a.title, a.stepid, a.description, a.address, a.latitude, a.longitude, a.state, a.created, a.updated')
            ->from('#__imc_issues AS a')
            ->where('a.state=1')
            ->where('a.updated >= "' . $ts . '"');

        if(!is_null($catid))
        {
            $query->where('a.catid='.$catid);
        }

        $db->setQuery($query);
        $result = $db->loadAssocList();

        return $result;
    }

    public static function countModifiedIssues($ts = 0, $limit = 0)
    {
	    $db = JFactory::getDbo();
	    $query = "SELECT COUNT(b.id) FROM (SELECT a.id FROM #__imc_issues AS a WHERE a.updated >= ".$ts." LIMIT ".$limit.") AS b";

	    $db->setQuery($query);
	    $result = $db->loadResult();
	    return $result;
    }
}
