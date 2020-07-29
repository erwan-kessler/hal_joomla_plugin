<?php
/*
# mod_sp_tweet - Hal Module by erwankessler.com
# -----------------------------------------------
# Author    Erwan KESSLER
# license - GNU/GPL V2 or Later
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

include_once('library/HALAPIExchange.php');

class modSPTwitter
{
    private $moduleID;
    private $params;
    private $cacheParams = array();
    private $api;

    //Initiate configurations
    public function __construct($params, $id)
    {
        jimport('joomla.filesystem.file');
        $this->moduleID = $id;
        $this->params = $params;

        return $this;
    }


    /**
     * Simple catching functionn
     *
     * @param string $file
     * @param string $url
     * @param array $args
     * @param int $time default is 900/60 = 15 min
     * @param mixed $onError string function or array(object, method )
     * @return string
     */
    private function Cache($file, $time = 900, $onerror = '')
    {
        // check joomla cache dir writable
        $dir = basename(dirname(__FILE__));
        if (is_writable(JPATH_CACHE)) {
            // check cache dir or create cache dir
            if (!file_exists(JPATH_CACHE . '/' . $dir)) mkdir(JPATH_CACHE . '/' . $dir . '/', 0755);
            $cache_file = JPATH_CACHE . '/' . $dir . '/' . $this->moduleID . '-' . $file;
            // check cache file, if not then write cache file
            if (!file_exists($cache_file)) {
                $data = $this->getData();
                JFile::write($cache_file, $data);
            } //if cache file expires, then write cache
            elseif (filesize($cache_file) == 0 || ((filemtime($cache_file) + (int)$time) < time())) {
                $data = $this->getData();
                JFile::write($cache_file, $data);
            }
            $data = JFile::read($cache_file);
            $params['file'] = $cache_file;
            $params['data'] = $data;
            if (!empty($onerror)) {
                if (call_user_func($onerror, $params)) {
                    return $this->getData();
                };
            }
            return $data;
        } else {
            return $this->getData();
        }
    }

    /*
    * get hal datas
    */
    private function getData()
    {

        $settings = array(
            'url' => $this->params->get('url'),
            'division' => $this->params->get('division')
        );

        if (empty( $this->params->get('url'))) {
            JError::raiseNotice(100, 'URL not defined, use https://api.archives-ouvertes.fr/search/.');
            return NULL;
        }
        if (empty( $this->params->get('division'))) {
            JError::raiseNotice(100, 'Division not defined, use LECOB or NULL for all.');
            return NULL;
        }
        if (empty( $this->params->get('query'))) {
            JError::raiseNotice(100, 'Query not defined, use *.');
            return NULL;
        }
        if (empty( $this->params->get('date')) && is_int( $this->params->get('date'))) {
            JError::raiseNotice(100, 'Date not correctly defined, please input only the year (like 2020).');
            return NULL;
        }
        if (empty( $this->params->get('type'))) {
            JError::raiseNotice(100, 'The type of publication to display was not defined, use ART for articles (https://api.archives-ouvertes.fr/search/?q=*%3A*&rows=0&wt=xml&indent=true&facet=true&facet.field=docType_s).');
            return NULL;
        }
        if (empty( $this->params->get('number_per_page')) && is_int( $this->params->get('number_per_page'))) {
            JError::raiseNotice(100, 'The number per page was not defined please use 10.');
            return NULL;
        }

        $getfield = '?q=' .  $this->params->get('query') . // the main query this is for example to restrict to one person
            '&wt=json' . // the return type, we handle json only here
            'fq=docType_s:"' .  $this->params->get('type') .'"'. // the type of publication, that's to decided whether to display an article (ART), ouvrage (COUV)... See docType_s fmi.
            '&fq=submittedDateY_i:[' .  $this->params->get('date') . '%20TO%20' . ((int) $this->params->get('date') + 1) . ']' . // the limit on date so we dont get old results
            '&sort=publicationDate_tdate%20desc' . // sort the publication by date so newer ones pops up
            '&rows=' . $this->params->get('number_per_page'). // restrict to only so much by page
            '&fl=title_s,publicationDate_tdate,label_s,fileMain_s'; // the field we need to display data, knowing that label_s is actually the core part
        $requestMethod = 'GET'; // we only GET, no need to POST PUT or whatever
        $this->api = new HALApiExchange($settings);
        return $this->api->setGetfield($getfield)->buildRequest($requestMethod)->performRequest();

    }

    public function decodeJSON($data){
        $data = json_decode($data, true);
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                break;
            case JSON_ERROR_DEPTH:
                JError::raiseNotice(100, 'JSON Error: Profondeur maximale atteinte');;
                break;
            case JSON_ERROR_STATE_MISMATCH:
                JError::raiseNotice(100, 'JSON Error: Inadéquation des modes ou underflow');
                break;
            case JSON_ERROR_CTRL_CHAR:
                JError::raiseNotice(100, 'JSON Error: Erreur lors du contrôle des caractères');
                break;
            case JSON_ERROR_SYNTAX:
                JError::raiseNotice(100, 'JSON Error: Erreur de syntaxe, JSON malformé');
                break;
            case JSON_ERROR_UTF8:
                JError::raiseNotice(100, 'JSON Error: Caractères UTF-8 malformés, probablement une erreur d\'encodage');
                break;
            default:
                JError::raiseNotice(100, 'JSON Error: Erreur inconnue');
                break;
        }
        return $data;
    }

    /*
    * function onError
    */
    public function onError($params){
        if (is_null($params["data"]) or empty($params["data"])){
            JFile::Delete($params['file']);
            return true;
        }
        $data = $this->decodeJSON($params["data"]);

        if (is_null($data) or isset($data['errors']) or isset($data['error'])) {
            JFile::Delete($params['file']);
            return true;
        }
        return false;
    }

    /*
    * Function to get articles
    */
    public function articles()
    {
        if ($this->params->get('module_cache') === '1') {
            $data = $this->Cache('hal.json', $this->params->get('cache_time'), array($this, 'onError'));
        } else {
            $data = $this->getData();
        }
        if (is_null($data) or empty($data)){
            return null; // we make sure to not decode json, it will be handled later
        }
        return $data = $this->decodeJSON($data);
    }

    /*
    * Prepare feeds
    */
    public function prepareArticles($string)
    {
        /*//Url
        $pattern = '/((ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?)/i';
        $replacement = '<a target="' . $this->params->get('target') . '" class="tweet_url" href="$1">$1</a>';
        $string = preg_replace($pattern, $replacement, $string);

        //Search
        if ($this->params->get('linked_search')==1) {
            $pattern = '/[\#]+([A-Za-z0-9-_]+)/i';
            $replacement = ' <a target="' . $this->params->get('target') . '" class="tweet_search" href="http://search.twitter.com/search?q=$1">#$1</a>';
            $string = preg_replace($pattern, $replacement, $string);
        }

        //Mention
        if ($this->params->get('linked_mention')==1) {
            $pattern = '/\s[\@]+([A-Za-z0-9-_]+)/i';
            $replacement = ' <a target="' . $this->params->get('target') . '" class="tweet_mention" href="http://twitter.com/$1">@$1</a>';
            $string = preg_replace($pattern, $replacement, $string);
        }

        //Mention
        if ($this->params->get('email_linked')==1) {
            $pattern = '/\s([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4})/i';
            $replacement = ' <a target="' . $this->params->get('target') . '" class="tweet_email" href="mailto:$1">$1</a>';
            $string = preg_replace($pattern, $replacement, $string);
        }*/
        return $string;
    }

    //Function for converting time
    public function timeago($timestamp)
    {
        $time_arr = explode(" ", $timestamp);
        $year = $time_arr[5];
        $day = $time_arr[2];
        $time = $time_arr[3];
        $time_array = explode(":", $time);
        $month_name = $time_arr[1];
        $month = array(
            'Jan' => 1,
            'Feb' => 2,
            'Mar' => 3,
            'Apr' => 4,
            'May' => 5,
            'Jun' => 6,
            'Jul' => 7,
            'Aug' => 8,
            'Sep' => 9,
            'Oct' => 10,
            'Nov' => 11,
            'Dec' => 12
        );

        $delta = gmmktime(0, 0, 0, 0, 0) - mktime(0, 0, 0, 0, 0);
        $timestamp = mktime($time_array[0], $time_array[1], $time_array[2], $month[$month_name], $day, $year);
        $etime = time() - ($timestamp + $delta);
        if ($etime < 1) {
            return '0 seconds';
        }

        $a = array(12 * 30 * 24 * 60 * 60 => 'YEAR',
            30 * 24 * 60 * 60 => 'MONTH',
            24 * 60 * 60 => 'DAY',
            60 * 60 => 'HOUR',
            60 => 'MINUTE',
            1 => 'SECOND'
        );

        foreach ($a as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                $r = round($d);
                return $r . ' ' . JText::_($str . ($r > 1 ? 'S' : ''));
            }
        }
    }
}