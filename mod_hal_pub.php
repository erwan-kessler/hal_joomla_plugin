<?php
/*
# mod_hal_pub - Hal Module by Erwan KESSLER
# -----------------------------------------------
# Author    Erwan KESSLER erwankessler.com
# license - MIT
# Website: https://www.erwankessler.com
*/

// no direct access
defined('_JEXEC') or die('Restricted access');
JForm::addFieldPath(JPATH_COMPONENT,'/forms');
//Parameters
$style = 'default';
$moduleName = basename(dirname(__FILE__));
$moduleID = $module->id;
$document = JFactory::getDocument();
$cssFile = JPATH_THEMES . '/' . $document->template . '/css/' . $moduleName . '.css';

// Include helper.php
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper.php');
$helper = new modHalPub($params, $moduleID);
$data = $helper->articles();
if (!is_null($data)) {
    if (!isset($data['response'])){
        echo "API Fail";
        return;
    }
    $data = $data["response"];

    if (JVERSION < 3) {
        JHtml::_('behavior.framework');
    } else {
        JHtml::_('jquery.framework');
    }

    if (isset($data["docs"]) and is_array($data["docs"])) {
        if (file_exists($cssFile)) {
            $document->addStylesheet(JURI::base(true) . '/templates/' . $document->template . '/css/' . $moduleName . '.' . $style . '.css');
        } else {
            $document->addStylesheet(JURI::base(true) . '/modules/' . $moduleName . '/assets/css/' . $moduleName . '.' . $style . '.css');
        }
        echo '<div id="hal-pub-id' . $moduleID . '">';
        require(JModuleHelper::getLayoutPath($moduleName, $style));
        echo '</div>';
    }
}else{
    echo '<p> There has been a loading error, please contact your webmaster, the recovered data content is as follows:</p>';
    var_dump($data);
}