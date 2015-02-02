<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @subpackage  mod_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
defined('_JEXEC') or die;

// Check for component
if (!JComponentHelper::getComponent('com_imc', true)->enabled)
{
	echo '<div class="alert alert-danger">Improve My City component is not enabled</div>';
	return;
}

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';
//JHtml::_('jquery.framework');
$doc = JFactory::getDocument();
$doc->addStyleSheet(JURI::base() . '/modules/mod_imcmap/assets/css/style.css');

//get parameters
$params = JComponentHelper::getParams('com_imc');	
$api_key = $params->get('api_key');
$lat        = $params->get('latitude');
$lng        = $params->get('longitude');
$zoom 	    = $params->get('zoom');
$language   = $params->get('maplanguage');

if($api_key == ''){
	echo '<span style="color: red; font-weight:bold;">Module IMC Map :: Google Maps API KEY missing</span>';
	$doc->addScript('https://maps.googleapis.com/maps/api/js?language='.$language);
}
else{
	$doc->addScript('https://maps.googleapis.com/maps/api/js?key='.$api_key.'&language='.$language);
}
?>

<script type="text/javascript">
	var lat = <?php echo $lat;?> ;
	var lng = <?php echo $lng;?> ;
	var zoom = <?php echo $zoom;?> ;
	var language = "<?php echo $language;?>" ;
</script>
<script src="<?php echo JURI::base();?>modules/mod_imcmap/assets/js/script.js" type="text/javascript"></script>

<?php
//initialize and load map
$script = array();
$script[] = "jQuery(document).ready(function () {";
$script[] = "  google.maps.event.addDomListener(window, 'load', imc_mod_map_initialize);";
$script[] = "});";
$doc->addScriptDeclaration(implode("\n", $script));

require JModuleHelper::getLayoutPath('mod_imcmap', $params->get('layout_type', 'default'));
