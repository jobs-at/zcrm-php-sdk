<?php
require_once realpath(dirname(__FILE__) . "/../api/response/APIResponse.php");
require_once realpath(dirname(__FILE__) . "/../common/APIConstants.php");

/**
 * Purpose of this class is to trigger API call and fetch the response
 *
 * @author sumanth-3058
 */
class ZohoHTTPConnector
{
    /**
     * @var string
     */
    private $url = '';

    /**
     * @var array
     */
    private $requestParams = [];

    /**
     * @var array
     */
    private $requestHeaders = [];

    /**
     * @var int
     */
    private $requestParamCount = 0;

    /**
     * @var string|array
     */
    private $requestBody;

    /**
     * @var string
     */
    private $requestType = APIConstants::REQUEST_METHOD_GET;

    /**
     * @var string
     */
    private $userAgent = "ZohoCRM PHP SDK";

    /**
     * @var string
     */
    private $apiKey = '';

    /**
     * @var bool
     */
    private $isBulkRequest = false;

    /**
     * @return ZohoHTTPConnector
     */
    public static function getInstance(): ZohoHTTPConnector
    {
        return new ZohoHTTPConnector();
    }

    /**
     * @return array
     */
    public function fireRequest(): array
    {
        $curl_pointer = curl_init();

        if (count($this->getRequestParamsMap()) > 0) {
            $url = $this->getUrl() . "?" . $this->getUrlParamsAsString($this->getRequestParamsMap());
            curl_setopt($curl_pointer, CURLOPT_URL, $url);
        } else {
            curl_setopt($curl_pointer, CURLOPT_URL, $this->getUrl());
        }

        curl_setopt($curl_pointer, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_pointer, CURLOPT_HEADER, 1);
        curl_setopt($curl_pointer, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($curl_pointer, CURLOPT_HTTPHEADER, $this->getRequestHeadersAsArray());
        curl_setopt($curl_pointer, CURLOPT_CUSTOMREQUEST, APIConstants::REQUEST_METHOD_GET);

        if ($this->requestType === APIConstants::REQUEST_METHOD_POST) {
            curl_setopt($curl_pointer, CURLOPT_CUSTOMREQUEST, APIConstants::REQUEST_METHOD_POST);
            curl_setopt($curl_pointer, CURLOPT_POST, true);
            $requestBody = $this->isBulkRequest ? json_encode($this->getRequestBody()) : $this->getRequestBody();
            curl_setopt($curl_pointer, CURLOPT_POSTFIELDS, $requestBody);
        } elseif ($this->requestType === APIConstants::REQUEST_METHOD_PUT) {
            curl_setopt($curl_pointer, CURLOPT_CUSTOMREQUEST, APIConstants::REQUEST_METHOD_PUT);
            $requestBody = $this->isBulkRequest ? json_encode($this->getRequestBody()) : $this->getRequestBody();
            curl_setopt($curl_pointer, CURLOPT_POSTFIELDS, $requestBody);
        } elseif ($this->requestType === APIConstants::REQUEST_METHOD_DELETE) {
            curl_setopt($curl_pointer, CURLOPT_CUSTOMREQUEST, APIConstants::REQUEST_METHOD_DELETE);
        }

        $result = curl_exec($curl_pointer);
        $responseInfo = curl_getinfo($curl_pointer);
        curl_close($curl_pointer);

        return [$result, $responseInfo];
    }

    /**
     * @return array
     */
    public function downloadFile(): array
    {
        $curl_pointer = curl_init();
        curl_setopt($curl_pointer, CURLOPT_URL, $this->getUrl());
        curl_setopt($curl_pointer, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_pointer, CURLOPT_HEADER, 1);
        curl_setopt($curl_pointer, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($curl_pointer, CURLOPT_HTTPHEADER, $this->getRequestHeadersAsArray());
        $result = curl_exec($curl_pointer);
        $responseInfo = curl_getinfo($curl_pointer);
        curl_close($curl_pointer);

        return [$result, $responseInfo];
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    /**
     * @param $key
     * @param $value
     */
    public function addParam($key, $value)
    {
        if ($this->requestParams[$key] == null) {
            $this->requestParams[$key] = [$value];
        } else {
            $valArray = $this->requestParams[$key];
            array_push($valArray, $value);
            $this->requestParams[$key] = $valArray;
        }
    }

    /**
     * @param $key
     * @param $value
     */
    public function addHeader($key, $value)
    {
        if ($this->requestHeaders[$key] == null) {
            $this->requestHeaders[$key] = [$value];
        } else {
            $valArray = $this->requestHeaders[$key];
            array_push($valArray, $value);
            $this->requestHeaders[$key] = $valArray;
        }
    }

    /**
     * @param $urlParams
     * @return string
     */
    public function getUrlParamsAsString($urlParams)
    {
        $params_as_string = "";

        foreach ($urlParams as $key => $valueArray) {
            foreach ($valueArray as $value) {
                $params_as_string = $params_as_string . $key . "=" . urlencode($value) . "&";
                $this->requestParamCount++;
            }
        }

        $params_as_string = rtrim($params_as_string, "&");
        $params_as_string = str_replace(PHP_EOL, '', $params_as_string);

        return $params_as_string;
    }

    /**
     * @param array $headers
     */
    public function setRequestHeadersMap(array $headers)
    {
        $this->requestHeaders = $headers;
    }

    /**
     * @return array
     */
    public function getRequestHeadersMap(): array
    {
        return $this->requestHeaders;
    }

    /**
     * @param array $params
     */
    public function setRequestParamsMap(array $params)
    {
        $this->requestParams = $params;
    }

    /**
     * @return array
     */
    public function getRequestParamsMap(): array
    {
        return $this->requestParams;
    }

    /**
     * @param string|array $requestBody
     */
    public function setRequestBody($requestBody)
    {
        $this->requestBody = $requestBody;
    }

    /**
     * @return array|string
     */
    public function getRequestBody()
    {
        return $this->requestBody;
    }

    /**
     * @param string $requestType
     */
    public function setRequestType(string $requestType)
    {
        $this->requestType = $requestType;
    }

    /**
     * @return string
     */
    public function getRequestType(): string
    {
        return $this->requestType;
    }

    /**
     * @return array
     */
    public function getRequestHeadersAsArray(): array
    {
        $headersArray = [];
        $headersMap = $this->getRequestHeadersMap();

        foreach ($headersMap as $key => $value) {
            $headersArray[] = $key . ":" . $value;
        }

        return $headersArray;
    }

    /**
     * Get the API Key used in the input json data(like 'modules', 'data','layouts',..etc)
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Set the API Key used in the input json data(like 'modules', 'data','layouts',..etc)
     *
     * @param string $apiKey
     */
    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * isBulkRequest
     *
     * @return bool
     */
    public function isBulkRequest()
    {
        return $this->isBulkRequest;
    }

    /**
     * isBulkRequest
     *
     * @param bool $isBulkRequest
     */
    public function setBulkRequest($isBulkRequest)
    {
        $this->isBulkRequest = $isBulkRequest;
    }
}


