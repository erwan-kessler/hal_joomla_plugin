<?php
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\FormHelper;
JFormHelper::loadFieldClass('list');

class JFormFieldYear extends JFormFieldList
{

    protected $type = 'Year';

    public function getOptions() {
        $years= array();
        for ($i=1800;$i< date("Y");$i++){
            array_push($years,array('value' => $i, 'text' => $i));
        }
        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $years);

        // pre-select values 2 and 3 by setting the protected $value property
        $this->value = array( date("Y"));

        return $options;
    }
}