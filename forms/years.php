<?php
/*
# mod_hal_pub - Hal Module by Erwan KESSLER
# -----------------------------------------------
# Author    Erwan KESSLER erwankessler.com
# license - MIT
# Website: https://www.erwankessler.com
*/
defined('_JEXEC') or die('Restricted access');

JFormHelper::loadFieldClass('list');

class JFormFieldYears extends JFormFieldList
{
    protected $type = 'years';

    public function getOptions() {
        $years= array();
        array_push($years,array('value' => 'latest', 'text' => 'latest'));
        for ($i= date("Y");$i>1800;$i--){
            array_push($years,array('value' => $i, 'text' => $i));
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $years);
        // pre-select last year
        if (!is_array($this->value)){
            $this->value=array('latest');
        }
        return $options;
    }
}