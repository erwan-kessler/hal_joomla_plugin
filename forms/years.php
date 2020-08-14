<?php
defined('_JEXEC') or die('Restricted access');

JFormHelper::loadFieldClass('list');

class JFormFieldYears extends JFormFieldList
{
    protected $type = 'years';

    public function getOptions() {
        $years= array();
        for ($i= date("Y");$i>1800;$i--){
            array_push($years,array('value' => $i, 'text' => $i));
        }
        array_push($years,array('value' => 'latest', 'text' => 'latest'));
        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $years);
        // pre-select last year
        if (!is_array($this->value)){
            $this->value=array('latest');
        }
        return $options;
    }
}