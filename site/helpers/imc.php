<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
defined('_JEXEC') or die;

class ImcFrontendHelper {

    public static function convert2UTC($date)
    {
        //get timezone from settings
        $offset = JFactory::getConfig()->get('offset');

        $utc = new DateTime($date, new DateTimeZone($offset));
        $utc->setTimezone(new DateTimeZone('UTC'));
        return $utc->format('Y-m-d H:i:s');
    }

	public static function sanitizeIssues($data, $userid)
	{
		if(!is_array($data)){
			throw new Exception('Issues sanitization bad input');
		}

		foreach ($data as $issue)
		{
			$issue = self::sanitizeIssue($issue, $userid);
			unset($issue->access_level);
			unset($issue->editor);
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

		if($data->category_image != '')
		{
			$data->category_image = JUri::base() . $data->category_image;
		}

        //separate photos and file attachments
        $photos = json_decode($data->photo, true);
		$attachments = $photos;
        $i=0;
		if(is_object($photos)) {
			foreach ($photos->files as $photo) {
				if (!isset($photo->thumbnailUrl)) {
					unset($photos->files[$i]);
				} else {
					unset($photos->files[$i]->deleteType);
					unset($photos->files[$i]->deleteUrl);
					$photos->files[$i]->url = dirname(JUri::base()) . $photos->files[$i]->url;
					$photos->files[$i]->mediumUrl = dirname(JUri::base()) . $photos->files[$i]->mediumUrl;
					$photos->files[$i]->thumbnailUrl = dirname(JUri::base()) . $photos->files[$i]->thumbnailUrl;
				}
				$i++;
			}

			$i = 0;
			foreach ($attachments->files as $attachment) {
				if (isset($attachment->thumbnailUrl)) {
					unset($attachments->files[$i]);
				} else {
					unset($attachments->files[$i]->deleteType);
					unset($attachments->files[$i]->deleteUrl);
					$attachments->files[$i]->url = dirname(JUri::base()) . $attachments->files[$i]->url;
				}
				$i++;
			}
			unset($photos->id);
			unset($photos->imagedir);
			unset($attachments->id);
			unset($attachments->imagedir);
		}
        unset($data->photo);

        $data->photos = (is_array($photos) ? $photos['files'] : array());
        $data->attachments = (is_array($photos) ? $attachments['files'] : array());

        //set dates to UTC
        $data->created_UTC = $data->created == '0000-00-00 00:00:00' ? $data->created : self::convert2UTC($data->created);
        $data->updated_UTC = $data->updated == '0000-00-00 00:00:00' ? $data->updated : self::convert2UTC($data->updated);
        $data->regdate_UTC = $data->regdate == '0000-00-00 00:00:00' ? $data->regdate : self::convert2UTC($data->regdate);

		$data->moderation = (boolean)$data->moderation;
		$data->myIssue = ($data->created_by == $userid);
        return $data;
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
