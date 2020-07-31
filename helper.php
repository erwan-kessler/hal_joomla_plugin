<?php
/*
# mod_hal_pub - Hal Module by Erwan KESSLER
# -----------------------------------------------
# Author    Erwan KESSLER erwankessler.com
# license - MIT
# Website: https://www.erwankessler.com
*/
defined('_JEXEC') or die('Restricted access');

include_once('library/HALAPIExchange.php');
include_once('tmpl/svg.php');

class modHalPub
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
        if (empty($this->params->get('url'))) {
            JError::raiseNotice(100, 'URL not defined, use https://api.archives-ouvertes.fr/search/.');
            return null;
        }
        if (empty($this->params->get('division'))) {
            JError::raiseNotice(100, 'Division not defined, use LECOB or NULL for all.');
            return null;
        }
        if (empty($this->params->get('query'))) {
            JError::raiseNotice(100, 'Query not defined, use *.');
            return null;
        }
        if (empty($this->params->get('type'))) {
            JError::raiseNotice(100, 'The type of publication to display was not defined, use ART for articles (https://api.archives-ouvertes.fr/search/?q=*%3A*&rows=0&wt=xml&indent=true&facet=true&facet.field=docType_s).');
            return null;
        }
        if (empty($this->params->get('number_per_page')) or !ctype_digit($this->params->get('number_per_page'))) {
            JError::raiseNotice(100, 'The number per page was not defined please use 10.');
            return null;
        }
        if (empty($this->params->get('limit_query')) or !ctype_digit($this->params->get('limit_query'))) {
            JError::raiseNotice(100, 'The number of result was not defined please use 10.');
            return null;
        }
        if (empty($this->params->get('date_type'))) {
            JError::raiseNotice(100, 'The date type was not set.');
            return null;
        }
        $date = "";
        if ($this->params->get('date_type') === "range") {
            if (empty($this->params->get('date_range_from')) or !ctype_digit($this->params->get('date_range_from'))) {
                JError::raiseNotice(100, 'The date from was not defined.');
                return null;
            }
            if (empty($this->params->get('date_range_to')) or !ctype_digit($this->params->get('date_range_to'))) {
                JError::raiseNotice(100, 'The date from was not defined.');
                return null;
            }
            if ((int)$this->params->get('date_range_to') < (int)$this->params->get('date_range_from')) {
                JError::raiseNotice(100, 'The date order is incorrect.');
                return null;
            }
            $date = $date . '[' . $this->params->get('date_range_from') . '%20TO%20' . $this->params->get('date_range_to') . ']';
        } else {
            if (empty($this->params->get('date_selection'))) {
                JError::raiseNotice(100, 'The date selection is empty.');
                return null;
            }
            $date = $date . '(' . implode('%20OR%20', $this->params->get('date_selection')) . ')';
        }

        $getfield = '?q=' . $this->params->get('query') . // the main query this is for example to restrict to one person
            '&wt=json' . // the return type, we handle json only here
            '&fq=docType_s:(' . implode('%20OR%20', $this->params->get('type')) . ')' . // the type of publication, that's to decided whether to display an article (ART), ouvrage (COUV)... See docType_s fmi.
            '&fq=publicationDateY_i:' . $date . // the limit on date so we dont get old results
            '&sort=publicationDate_tdate%20desc' . // sort the publication by date so newer ones pops up
            '&rows=' . $this->params->get('limit_query') . // restrict to only so much by page
            '&fl=title_s,publicationDate_s,label_s,fileMain_s,authFullName_s,uri_s,journalTitle_s'; // the field we need to display data, knowing that label_s is actually the core part
        // the data will be displayed as
        // publicationDate_tdate : (authFullName_s)+
        // title_s[uri_s] download[fileMain_s]
        // journalTitle_s

        $requestMethod = 'GET'; // we only GET, no need to POST PUT or whatever
        $this->api = new HALApiExchange($settings);
        return $this->api->setGetfield($getfield)->buildRequest($requestMethod)->performRequest();

    }

    public function decodeJSON($data)
    {
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
    public function onError($params)
    {
        if (is_null($params["data"]) or empty($params["data"])) {
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
        if (is_null($data) or empty($data)) {
            return null; // we make sure to not decode json, it will be handled later
        }
        return $data = $this->decodeJSON($data);
    }

    /*
    * Prepare feeds
    */
    public function prepareArticles($array)
    {
        $target = $this->params->get('target', '_blank');
        // the data will be displayed as
        // publicationDate_s : (authFullName_s)+
        // title_s[uri_s] download[fileMain_s]
        // journalTitle_s

        // weird case for ppl that didnt fill up correctly, easier to display that part
        if (!isset($array['uri_s']) or !isset($array['title_s']) or is_null($array['title_s'][0]) or
            !isset($array['authFullName_s']) or empty($array['authFullName_s']) or !isset($array['publicationDate_s'])) {
            return '<div class="hal-label">' . $array["label_s"] . '</div>';
        }

        // those are safe
        $uri = $array["uri_s"];
        $authors_array = $array["authFullName_s"];
        $publication_date = $array["publicationDate_s"];
        $title = $array["title_s"][0]; // we only get the first title, ppl should not give many title, since we cant sort on language
        if (!isset($array["fileMain_s"])) {
            $download = null;
        } else {
            $download = $array["fileMain_s"];
        }
        if (!isset($array["journalTitle_s"])) {
            $journal_title = null;
        } else {
            $journal_title = $array["journalTitle_s"];
        }


        // first column
        $string = '<div class="hal-first-column">';

        // first line: date and authors
        $string = $string . '<div class="hal-first-line">';
        //date
        $string = $string . '<div class="hal-date"><div class="hal-icon">' . CALENDAR . '</div>' . $publication_date . '</div>';
        // authors.
        $flag_et_al = false;
        $string = $string . '<div class="hal-icon">' . AUTHORS . '</div><div class=hal-authors>';
        foreach ($authors_array as $i => $name) {
            if ($i > 3) {
                $flag_et_al = true;
                break;
            }
            $string = $string . '<div class="hal-author">' . $name . '</div>';
        }
        $string = $string . ($flag_et_al ? '<i class="hal-more-authors">et al.</i>' : '') . '</div>';
        $string = $string . '</div>';

        //second line: title with link
        $string = $string . '<div class="hal-second-line">';
        $string = $string . '<div class="hal-title"><a class="hal-link" target="' . $target . '" href="' . $uri . '">' . $title . '</a></div>';
        $string = $string . '</div>';

        // optional third line
        if (!is_null($journal_title)) {
            $string = $string . '<div class="hal-third-line">';
            $string = $string . '<i class="hal-journal">' . $journal_title . '</i>';
            $string = $string . '</div>';
        }

        $string = $string . '</div>';

        // optional second column
        if (!is_null($download)) {
            $string = $string . '<div class="hal-second-column">';
            $string = $string . '<div class="hal-download-button download"><a href="' . $download . '" download><span>HAL</span><span>Document</span></a></div>';
            $string = $string . '</div>';
        }
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