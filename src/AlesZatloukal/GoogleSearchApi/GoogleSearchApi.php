<?php

namespace AlesZatloukal\GoogleSearchApi;

use Exception;
use stdClass;

class GoogleSearchApi
{
    /**
     * Custom search engine ID
     *
     * @var string
     */
    protected $engineId;

    /**
     * Google console API key
     *
     * @var string
     */
    protected $apiKey;
    
    /**
     * Google Custom Search JSON API url
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * Original response converted to array
     *
     * @var stdClass
     */
    protected $originalResponse;

    /**
     * Constructor
     *
     * GoogleSearchApi constructor.
     *
     * @param $engineId
     * @param $apiKey
     * @param $apiUrl
     */
    public function __construct() {
        $this->engineId = config('googlesearchapi.google_search_engine_id');
        $this->apiKey = config('googlesearchapi.google_search_api_key');
        $this->apiUrl = config('googlesearchapi.google_search_api_url');
    }

    /**
     * Setter for engineId, overrides the value from config
     *
     * @param $engineId
     */
    public function setEngineId($engineId) {
        $this->engineId = $engineId;
    }

    /**
     * Setter for apiKey, overrides the value from config
     *
     * @param $apiKey
     */
    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Setter for apiUrl, overrides the value from config
     *
     * @param $apiUrl
     */
    public function setApiUrl($apiUrl) {
        $this->apiUrl = $apiUrl;
    }

    /**
     * Get search results
     *
     * Gets results from CSE only as array of objects where the most important variables are
     * [title] - page title
     * [htmlTitle] - page title with HTML tags
     * [link] - page URL
     * [displayLink] - displayed part of page URL
     * [snippet] - short text from page with the searched phrase
     * [htmlSnippet] - short text from page with the searched phrase with HTML tags
     * complete list of parameters with description is located at
     * https://developers.google.com/custom-search/json-api/v1/reference/cse/list#response
     *
     * Input parameters are available here
     * https://developers.google.com/custom-search/json-api/v1/reference/cse/list#parameters
     *
     * @param $phrase
     * @param array $parameters
     * @return array
     * @throws Exception
     * @link https://developers.google.com/custom-search/json-api/v1/reference/cse/list#response
     * @link https://developers.google.com/custom-search/json-api/v1/reference/cse/list#parameters
     */
    public function getResults($phrase, $parameters = [])
    {
        /**
         * Check required parameters
         */
        if ($phrase == '') {
            return [];
        }

        if ($this->engineId == '') {
            throw new Exception('You must specify a engineId');
        }

        if ($this->apiKey == '') {
            throw new Exception('You must specify a apiKey');
        }

        /**
         * Create search aray
         */
        $searchArray = http_build_query(
            array_merge(
                ['key' => $this->apiKey],
                ['q' => $phrase],
                $parameters
            )
        );

        /**
         * Add unencoded search engine id
         */
        $searchArray = '?cx=' . $this->engineId . '&' . $searchArray;

        /**
         * Prepare Curl and get result
         */
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $searchArray);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 0);

        $output = curl_exec($ch);

        $info = curl_getinfo($ch);

        /**
         * Check HTTP code of the result
         */
        if ($output === false || $info['http_code'] != 200) {
            throw new Exception("No data returned, code [". $info['http_code']. "] - " . curl_error($ch));
        }

        curl_close($ch);

        /**
         * Convert JSON format to object and save
         */
        $this->originalResponse = json_decode($output);

        /**
         * If there are some results, return them, otherwise return blank array
         */
        if (isset($this->originalResponse->items)) {
            return $this->originalResponse->items;
        } else {
            return array();
        }
    }

    /**
     * Get full original response
     *
     * Gets full originated response converted from JSON to StdClass
     * Full list of parameters is located at
     * complete list of parameters with description is located at
     * https://developers.google.com/custom-search/json-api/v1/reference/cse/list#response
     *
     * @return stdClass
     * @url https://developers.google.com/custom-search/json-api/v1/reference/cse/list#response
     */
    public function getRawResult() {
        return $this->originalResponse;
    }

    /**
     * Get search information
     *
     * Gets basic information about search
     * [searchTime] - time costs of the search
     * [formattedSearchTime] - time costs of the search formatted according to locale style
     * [totalResults] - number of results
     * [formattedTotalResults] - number of results formatted according to locale style
     *
     * @return stdClass
     */
    public function getSearchInformation() {
        return $this->originalResponse->searchInformation;
    }

    /**
     * Get the total number of results where the search phrase is located
     *
     * @return integer
     */
    public function getTotalNumberOfResults() {
        return $this->originalResponse->searchInformation->totalResults;
    }
}
