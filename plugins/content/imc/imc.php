<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');

class plgContentimc extends JPlugin
{
	function plgContentImc(& $subject, $config)
	{
		parent::__construct( $subject, $config);
		if($this->params===false) 
		{
			$this->_plugin = JPluginHelper::getPlugin( 'content', 'content_imc' );
			$this->params = new JParameter( $jPlugin->params);
		}

	}

	function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}
		// Check we are manipulating a valid form.
		$name = $form->getName();
		//if (!in_array($name, array('com_content.article','com_categories.categorycom_content'))) {
		if (!in_array($name, array('com_categories.categorycom_imc'))) {
			return true;
		}		

		JForm::addFormPath(dirname(__FILE__).'/category_fields');
		$form->loadFile('imc', false);
		
		return true;
	}	

}
