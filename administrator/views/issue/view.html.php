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
 * View to edit
 */
class ImcViewIssue extends JViewLegacy {

    protected $state;
    protected $item;
    protected $form;
    //protected $logs;

    /**
     * Display the view
     */
    public function display($tpl = null) {
        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');
        //$this->logs = $this->get('Logs');
        if($this->item->id > 0)
            $this->logs = $this->getModel('Logs')->getItemsByIssue($this->item->id);
        else
            $this->logs = array();

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     */
    protected function addToolbar() {
        JFactory::getApplication()->input->set('hidemainmenu', true);

        $user = JFactory::getUser();
        $isNew = ($this->item->id == 0);
        if (isset($this->item->checked_out)) {
            $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
        } else {
            $checkedOut = false;
        }
        $canDo = ImcHelper::getActions();

        JToolBarHelper::title(JText::_('COM_IMC_TITLE_ISSUE'), 'issue.png');

        // If not checked out, can save the item.
        if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create')))) {

            JToolBarHelper::apply('issue.apply', 'JTOOLBAR_APPLY');
            JToolBarHelper::save('issue.save', 'JTOOLBAR_SAVE');
        }
        // if (!$checkedOut && ($canDo->get('core.create'))) {
        //     JToolBarHelper::custom('issue.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
        // }
        // If an existing item, can save to a copy.
        //if (!$isNew && $canDo->get('core.create')) {
        //    JToolBarHelper::custom('issue.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
        //}

        if(!empty($this->item->id)) {
            //on existing allow printing
            //JToolBarHelper::custom('issue.printIssue', 'print.png', 'print.png', 'COM_IMC_PRINT', false);

            $bar = JToolBar::getInstance('toolbar');
            $layout = new JLayoutFile('joomla.toolbar.popup');
            $dhtml = $layout->render(
                array(
                    'doTask' => 'print', //$url,
                    'class' => 'icon-print',
                    'text' => JText::_('COM_IMC_PRINT'),
                    'name' => 'imc-print'
            ));
            $bar->appendButton('Custom', $dhtml);
            
            //$url = 'index.php?option=com_imc&amp;view=issue&amp;task=issue.printIssue&amp;id='.$this->item->id.'&amp;tmpl=component';
            //$bar->appendButton('Popup', 'print', 'JTOOLBAR_EXPORT', $url);
        }

        if (empty($this->item->id)) {
            JToolBarHelper::cancel('issue.cancel', 'JTOOLBAR_CANCEL');
        } else {
            JToolBarHelper::cancel('issue.cancel', 'JTOOLBAR_CLOSE');
        }
    }

}
