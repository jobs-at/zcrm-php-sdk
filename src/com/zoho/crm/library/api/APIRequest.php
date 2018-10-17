<?php
require_once realpath(dirname(__FILE__) . "/../common/ZCRMConfigUtil.php");
require_once realpath(dirname(__FILE__) . "/../common/ZohoHTTPConnector.php");
require_once realpath(dirname(__FILE__) . "/../common/APIConstants.php");
require_once realpath(dirname(__FILE__) . "/../exception/ZCRMException.php");
require_once 'response/APIResponse.php';
require_once 'response/BulkAPIResponse.php';
require_once realpath(dirname(__FILE__) . "/response/FileAPIResponse.php");

/**
 * This class is to construct the API requests and initiate the request
 *
 * @author sumanth-3058
 */

class APIRequest
{
    /**
     * @var null|string
     */
    private $url = null;

    /**
     * @var array
     */
    private $requestParams = [];

    /**
     * @var array
     */
    private $requestHeaders = [];

    private $requestBody;

    private $requestMethod;

    private $apiKey = null;

    private $response = null;

    private $responseInfo = null;

    /**
     * @param $apiHandler
     */
    private function __construct(APIHandlerInterface $apiHandler)
    {
        self::constructAPIUrl();
        self::setUrl($this->url . $apiHandler->getUrlPath());

        if (substr($apiHandler->getUrlPath(), 0, 4) !== "http") {
            self::setUrl("https://" . $this->url);
        }

        self::setRequestParams($apiHandler->getRequestParams());
        self::setRequestHeaders($apiHandler->getRequestHeaders());
        self::setRequestBody($apiHandler->getRequestBody());
        self::setRequestMethod($apiHandler->getRequestMethod());
        self::setApiKey($apiHandler->getApiKey());
    }

    /**
     * @param $apiHandler
     * @return APIRequest
     */
    public static function getInstance(APIHandlerInterface $apiHandler): APIRequest
    {
        return new APIRequest($apiHandler);
    }

    /**
     * Method to construct the API Url
     */
    public function constructAPIUrl()
    {
        $hitSandbox = ZCRMConfigUtil::getConfigValue('sandbox');

        if (strcasecmp($hitSandbox, "true") == 0) {
            $baseUrl = str_replace('www', 'sandbox', ZCRMConfigUtil::getAPIBaseUrl());
        } else {
            $baseUrl = ZCRMConfigUtil::getAPIBaseUrl();
        }

        $this->url = $baseUrl . "/crm/" . ZCRMConfigUtil::getAPIVersion() . "/";
        $this->url = str_replace(PHP_EOL, '', $this->url);
    }

    /**
     * @throws ZCRMException
     * @throws ZohoOAuthException
     */
    private function authenticateRequest()
    {
        $accessToken = ZCRMConfigUtil::getAccessToken();
        $this->requestHeaders[APIConstants::AUTHORIZATION] = APIConstants::OAUTH_HEADER_PREFIX . $accessToken;
    }

    /**
     * @return APIResponse
     * @throws ZCRMException
     * @throws ZohoOAuthException
     */
    public function getAPIResponse(): APIResponse
    {
        $connector = ZohoHTTPConnector::getInstance();
        $connector->setUrl($this->url);
        self::authenticateRequest();
        $connector->setRequestHeadersMap($this->requestHeaders);
        $connector->setRequestParamsMap($this->requestParams);
        $connector->setRequestBody($this->requestBody);
        $connector->setRequestType($this->requestMethod);
        $connector->setApiKey($this->apiKey);
        $response = $connector->fireRequest();
        $this->response = $response[0];
        $this->responseInfo = $response[1];

        return new APIResponse($this->response, $this->responseInfo['http_code']);
    }

    /**
     * @return BulkAPIResponse
     * @throws ZCRMException
     * @throws ZohoOAuthException
     */
    public function getBulkAPIResponse(): BulkAPIResponse
    {
        $connector = ZohoHTTPConnector::getInstance();
        $connector->setUrl($this->url);
        self::authenticateRequest();
        $connector->setRequestHeadersMap($this->requestHeaders);
        $connector->setRequestParamsMap($this->requestParams);
        $connector->setRequestBody($this->requestBody);
        $connector->setRequestType($this->requestMethod);
        $connector->setApiKey($this->apiKey);
        $connector->setBulkRequest(true);
        $response = $connector->fireRequest();
        $this->response = $response[0];
        $this->responseInfo = $response[1];

        return new BulkAPIResponse($this->response, $this->responseInfo['http_code']);
    }

    /**
     * @param $filePath
     * @return APIResponse
     * @throws ZCRMException
     * @throws ZohoOAuthException
     */
    public function uploadFile($filePath): APIResponse
    {
        if (function_exists('curl_file_create')) { // php 5.6+
            $cFile = curl_file_create($filePath);
        } else { //
            $cFile = '@' . realpath($filePath);
        }

        $post = ['file' => $cFile];

        $connector = ZohoHTTPConnector::getInstance();
        $connector->setUrl($this->url);
        self::authenticateRequest();
        $connector->setRequestHeadersMap($this->requestHeaders);
        $connector->setRequestParamsMap($this->requestParams);
        $connector->setRequestBody($post);
        $connector->setRequestType($this->requestMethod);
        $connector->setApiKey($this->apiKey);
        $response = $connector->fireRequest();
        $this->response = $response[0];
        $this->responseInfo = $response[1];

        return new APIResponse($this->response, $this->responseInfo['http_code']);
    }

    /**
     * @param $linkURL
     * @return APIResponse
     * @throws ZCRMException
     * @throws ZohoOAuthException
     */
    public function uploadLinkAsAttachment($linkURL): APIResponse
    {
        $post = ['attachmentUrl' => $linkURL];

        $connector = ZohoHTTPConnector::getInstance();
        $connector->setUrl($this->url);
        self::authenticateRequest();
        $connector->setRequestHeadersMap($this->requestHeaders);
        $connector->setRequestBody($post);
        $connector->setRequestType($this->requestMethod);
        $connector->setApiKey($this->apiKey);
        $response = $connector->fireRequest();
        $this->response = $response[0];
        $this->responseInfo = $response[1];

        return new APIResponse($this->response, $this->responseInfo['http_code']);
    }

    /**
     * @return FileAPIResponse
     * @throws ZCRMException
     * @throws ZohoOAuthException
     */
    public function downloadFile(): FileAPIResponse
    {
        $connector = ZohoHTTPConnector::getInstance();
        $connector->setUrl($this->url);
        self::authenticateRequest();
        $connector->setRequestHeadersMap($this->requestHeaders);
        $connector->setRequestParamsMap($this->requestParams);
        $connector->setRequestType($this->requestMethod);
        $response = $connector->downloadFile();

        return (new FileAPIResponse())->setFileContent($response[0], $response[1]['http_code']);
    }

    /**
     * Get the request url
     *
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the request url
     *
     * @param String $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get the request parameters
     *
     * @return array
     */
    public function getRequestParams()
    {
        return $this->requestParams;
    }

    /**
     * Set the request parameters
     *
     * @param array $requestParams
     */
    public function setRequestParams($requestParams)
    {
        $this->requestParams = $requestParams;
    }

    /**
     * Get the request headers
     *
     * @return array
     */
    public function getRequestHeaders()
    {
        return $this->requestHeaders;
    }

    /**
     * Set the request headers
     *
     * @param array $requestHeaders
     */
    public function setRequestHeaders($requestHeaders)
    {
        $this->requestHeaders = $requestHeaders;
    }

    /**
     * Get the request body
     *
     * @return JSON
     */
    public function getRequestBody()
    {
        return $this->requestBody;
    }

    /**
     * Set the request body
     *
     * @param JSON $requestBody
     */
    public function setRequestBody($requestBody)
    {
        $this->requestBody = $requestBody;
    }

    /**
     * Get the request method
     *
     * @return String
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * Set the request method
     *
     * @param String $requestMethod
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;
    }

    /**
     * Get the API Key used in the input json data(like 'modules', 'data','layouts',..etc)
     *
     * @return String
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     *  Set the API Key used in the input json data(like 'modules', 'data','layouts',..etc)
     *
     * @param String $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }
}


