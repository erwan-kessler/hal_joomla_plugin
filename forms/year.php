<?php
// ffs who though it was a good idea that the file name was the same as the class name??
// please make a proper registry, joomla sucks
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

        // pre-select values 2 and 3 by setting the protected $value property
        $this->value = array( date("Y"));

        return $options;
    }
}