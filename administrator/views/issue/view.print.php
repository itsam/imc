<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View to print
  This class is (currently) not used
  since printing is decided to take place directly in the edit tmpl.
  To be used in the future to support exporting in multiple formats...
 */
class ImcViewIssue extends JViewLegacy {

    protected $state;

    /**
     * Display the view
     */
    public function display($tpl = null) {

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     */
    protected function addToolbar() {
        JFactory::getApplication()->input->set('hidemainmenu', true);
        JToolBarHelper::title(JText::_('COM_IMC_PRINT').' '.$this->state->printid, 'print.png');
        //on existing allow printing
        $url = JRoute::_('index.php?option=com_imc&view=issue&layout=edit&id='.$this->state->printid, false);
        //$toolbar->appendButton('Back', 'back.png', 'JTOOLBAR_BACK', $url);
     
        JToolBarHelper::back('Back2', $url);
        // Add a back button.
        JToolBarHelper::back('Print', 'print.png', 'print.png', 'javascript:window.print()');

        $bar = JToolBar::getInstance('toolbar');
        $bar->appendButton('Link', 'back', 'Go back', $url);
        $layout = new JLayoutFile('joomla.toolbar.popup');
        $dhtml = $layout->render(
            array(
                'doTask' => 'javascript:window.print()',
                'class' => 'icon-print',
                'text' => JText::_('COM_IMC_PRINT'),
                'name' => 'collapseModal'
        ));

        $bar->appendButton('Custom', $dhtml, 'print');
    }

}
