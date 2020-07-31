<?php
defined('_JEXEC') or die('Restricted access');

JFormHelper::loadFieldClass('list');

class JFormFieldYear extends JFormFieldList
{
    protected $type = 'year';

    public function getOptions() {
        $years= array();
        for ($i= date("Y");$i>1800;$i--){
            array_push($years,array('value' => $i, 'text' => $i));
        }
        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $years);
        // pre-select last year
        if (is_null($this->value)){
            $this->value = date("Y");
        }
        return $options;
    }
}