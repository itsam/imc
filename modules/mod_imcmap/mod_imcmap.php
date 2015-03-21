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
$com_imc_params = JComponentHelper::getParams('com_imc');	
$api_key 	= $com_imc_params->get('api_key');
$lat        = $com_imc_params->get('latitude');
$lng        = $com_imc_params->get('longitude');
$zoom 	    = $com_imc_params->get('zoom');
$language   = $com_imc_params->get('maplanguage');
$clusterer 	= ($com_imc_params->get('clusterer') == 1 ? true : false);

if($api_key == ''){
	echo '<span style="color: red; font-weight:bold;">Module IMC Map :: Google Maps API KEY missing</span>';
	$doc->addScript('https://maps.googleapis.com/maps/api/js?language='.$language);
}
else{
	$doc->addScript('https://maps.googleapis.com/maps/api/js?key='.$api_key.'&language='.$language);
}

//clusterer
if($clusterer){
	$doc->addScript('http://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerclusterer/src/markerclusterer.js');
}
?>

<?php
// Check if we allow to display the module on details (issue) page
$jinput = JFactory::getApplication()->input;
$option = $jinput->get('option', null);
$view = $jinput->get('view', null);

//Show module only on issues list view
if ($option == 'com_imc' && $view != 'issues') {
	
	$s = "
	    jQuery(document).ready(function() {
	 		//mod_imcmap advanced settings
	 		".
	 		stripcslashes($params->get('execute_js'))
	 		."
	    });
	";
	JFactory::getDocument()->addScriptDeclaration($s);

	$module->showtitle = false;
	return;
}
?>

<script type="text/javascript">
	var lat = <?php echo $lat;?> ;
	var lng = <?php echo $lng;?> ;
	var zoom = <?php echo $zoom;?> ;
	var clusterer = "<?php echo $clusterer;?>" ;
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

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));
require JModuleHelper::getLayoutPath('mod_imcmap', $params->get('layout', 'default'));
