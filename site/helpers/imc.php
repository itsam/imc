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
					unset($photo->size);
					unset($photo->mediumUrl);
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
			foreach ($obj->files as $file) {
				unset($file->deleteType);
				unset($file->deleteUrl);

				if (isset($file->thumbnailUrl))
				{
					$file->url = JUri::base() . 'images/imc/' . $data->id . '/' . $file->name;
					$file->mediumUrl = JUri::base() . 'images/imc/' . $data->id . '/medium/' . $file->name;
					$file->thumbnailUrl = JUri::base() . 'images/imc/' . $data->id . '/thumbnail/' . $file->name;
					array_push($data->photos, $file);
				}
				elseif(isset($file->url))
				{
					$file->url = JUri::base() . 'images/imc/' . $data->id . '/' . $file->name;
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
		$data->updated_ts = $data->updated == '0000-00-00 00:00:00' ? 1 :  strtotime($data->updated);

		$data->myIssue = ($data->created_by == $userid);

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
		if(isset($data->comments))
		{
			$data->comments = (int)$data->comments;
		}

		//check confidentiality and sanitize logs
		$params = JFactory::getApplication()->getParams('com_imc');


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

	public static function getModifiedCategories($ts)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('a.id, a.title, a.parent_id, a.published AS state, a.params')
			->from('#__categories AS a')
			->where('extension = ' . $db->quote('com_imc'))
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
			throw new Exception('Language is not available');
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

	public static function getTopUsers($limit = null, $ts = null, $prior_to = null)
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
			$query->where('a.updated >= "' . $ts .'"');
		}
		if(!is_null($prior_to))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) <= ' . $prior_to);
			$query->where('a.updated <= "' . $prior_to .'"');
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function getTopCategories($limit = null, $ts = null, $prior_to = null)
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
			$query->where('a.updated >= "' . $ts .'"');
		}
		if(!is_null($prior_to))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) <= ' . $prior_to);
			$query->where('a.updated <= "' . $prior_to .'"');
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function getTopSteps($limit = null, $ts = null, $prior_to = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(*) AS `count_issues`, a.stepid, b.title');
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
			$query->where('a.updated >= "' . $ts .'"');
		}
		if(!is_null($prior_to))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) <= ' . $prior_to);
			$query->where('a.updated <= "' . $prior_to .'"');
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}

	public static function getTopVoters($limit = null, $ts = null, $prior_to = null)
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
			$query->where('a.updated >= "' . $ts .'"');
		}
		if(!is_null($prior_to))
		{
			//$query->where('UNIX_TIMESTAMP(a.updated) <= ' . $prior_to);
			$query->where('a.updated <= "' . $prior_to .'"');
		}

		$db->setQuery($query);
		return $db->loadAssocList();
	}
}
