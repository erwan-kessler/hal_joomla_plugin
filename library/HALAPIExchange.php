<?php

/**
 * Hal-API-PHP : Simple PHP wrapper for the v1.1 API
 *
 * PHP version 5.3.10
 *
 * @category Awesomeness
 * @package  HAL-API-PHP
 * @author   Erwan Kessler <me@erwankessler.com>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * How to use
 * $settings = array(
 *     /// might need a secret key later on
 * );
 *
 * ///  https://api.archives-ouvertes.fr/docs/search
 *
 * $url = 'https://api.archives-ouvertes.fr/search/';
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

class HALApiExchange
{
    private $division;
    private $postfields;
    private $getfield;
    public $url;

    /**
     * Create the API access object. Requires an array of settings::
     *
     * Requires the cURL library
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        if (!in_array('curl', get_loaded_extensions())) {
            throw new Exception('You need to install cURL, see: http://curl.haxx.se/docs/install.html');
        }
        if (!isset($settings['division'])) {
            throw new Exception('Make sure you are passing a division');
        }
        if (!isset($settings['url'])) {
            throw new Exception('Make sure you are passing an url');
        }

        $this->division = $settings['division'];
        $this->url = $settings['url'];
    }

    /**
     * Set postfields array, example: array('screen_name' => 'J7mbo')
     *
     * @param array $array Array of parameters to send to API
     *
     * @return HALApiExchange Instance of self for method chaining
     */
    public function setPostfields(array $array)
    {
        if (!is_null($this->getGetfield())) {
            throw new Exception('You can only choose get OR post fields.');
        }

        if (isset($array['status']) && substr($array['status'], 0, 1) === '@') {
            $array['status'] = sprintf(" %s", $array['status']);
        }

        $this->postfields = $array;

        return $this;
    }

    /**
     * Set getfield string, example: '?screen_name=J7mbo'
     *
     * @param string $string Get key and value pairs as string
     *
     * @return HALApiExchange Instance of self for method chaining
     */
    public function setGetfield($string)
    {
        if (!is_null($this->getPostfields())) {
            throw new Exception('You can only choose get OR post fields.');
        }

        $search = array('#', ',', '+', ':');
        $replace = array('%23', '%2C', '%2B', '%3A');
        $string = str_replace($search, $replace, $string);

        $this->getfield = $string;

        return $this;
    }

    /**
     * Get getfield string (simple getter)
     *
     * @return string $this->getfields
     */
    public function getGetfield()
    {
        return $this->getfield;
    }

    /**
     * Get postfields array (simple getter)
     *
     * @return array $this->postfields
     */
    public function getPostfields()
    {
        return $this->postfields;
    }

    /**
     * Build the Oauth object using params set in construct and additionals
     * passed to this method.
     *
     * @param string $url The API url to use. Example: https://api.archives-ouvertes.fr/docs/search
     * @param string $requestMethod Either POST or GET
     * @return \HALApiExchange Instance of self for method chaining
     */
    public function buildRequest($requestMethod)
    {
        if (!in_array(strtolower($requestMethod), array('post', 'get'))) {
            throw new Exception('Request method must be either POST or GET');
        }

        $getfield = $this->getGetfield();

        if (!is_null($getfield)) {
            $getfields = str_replace('?', '', explode('&', $getfield));
            foreach ($getfields as $g) {
                $split = explode('=', $g);
                $oauth[$split[0]] = $split[1];
            }
        }
        if (is_null($this->division)){
            $this->url = $this->url .'/';
        }else{
            $this->url = $this->url .'/'. $this->division;
        }

        return $this;
    }

    /**
     * Perform the actual data retrieval from the API
     *
     * @param boolean $return If true, returns data.
     *
     * @return string
     */
    public function performRequest($return = true)
    {
        if (!is_bool($return)) {
            throw new Exception('performRequest parameter must be true or false');
        }

        $getfield = $this->getGetfield();
        $postfields = $this->getPostfields();
        $options = array(
            CURLOPT_HEADER => false,
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        );

        if (!is_null($postfields)) {
            $options[CURLOPT_POSTFIELDS] = $postfields;
        } else {
            if ($getfield !== '') {
                $options[CURLOPT_URL] .= $getfield;
            }
        }

        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $json = curl_exec($feed);
        var_dump($feed);
        /*

        var_dump($json);
        var_dump($options);
        var_dump($feed);
        */

        curl_close($feed);


        if ($return) {
            return $json;
        }
        return null;
    }

}
