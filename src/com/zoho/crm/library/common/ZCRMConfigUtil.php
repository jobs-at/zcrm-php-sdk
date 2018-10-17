<?php

require_once 'CommonUtil.php';
require_once realpath(dirname(__FILE__) . "/../../../oauth/client/ZohoOAuth.php");

class ZCRMConfigUtil
{
    /**
     * @var array
     */
    private static $configProperties = [];

    /**
     * @return ZCRMConfigUtil
     */
    public static function getInstance(): ZCRMConfigUtil
    {
        return new ZCRMConfigUtil();
    }

    /**
     * @param $initializeOAuth
     * @param $configuration
     * @throws ZohoOAuthException
     */
    public static function initialize(bool $initializeOAuth, $configuration)
    {
        $mandatory_keys = [
            ZohoOAuthConstants::CLIENT_ID,
            ZohoOAuthConstants::CLIENT_SECRET,
            ZohoOAuthConstants::REDIRECT_URL,
            APIConstants::CURRENT_USER_EMAIL
        ];

        if (!is_array($configuration)) {
            $path = realpath(dirname(__FILE__) . "/../../../../../resources/configuration.properties");
            $fileHandler = fopen($path, "r");

            if (!$fileHandler) {
                return;
            }

            self::$configProperties = CommonUtil::getFileContentAsMap($fileHandler);
        } else {
            //check if user input contains all mandatory values
            foreach ($mandatory_keys as $key) {
                if (!array_key_exists($key, $configuration)) {
                    if ($key != APIConstants::CURRENT_USER_EMAIL) {
                        throw new ZohoOAuthException($key . " is mandatory");
                    } else {
                        if ($_SERVER[APIConstants::USER_EMAIL_ID] == null) {
                            throw new ZohoOAuthException($key . " is mandatory");
                        }
                    }
                } elseif (array_key_exists($key, $configuration) && $configuration[$key] == "") {
                    throw new ZohoOAuthException($key . " value is missing");
                }
            }

            self::setConfigValues($configuration);
        }

        if ($initializeOAuth) {
            ZohoOAuth::initializeWithOutInputStream($configuration);
        }
    }

    /**
     * @param $configuration
     */
    private static function setConfigValues($configuration)
    {
        $config_keys = [
            APIConstants::CURRENT_USER_EMAIL,
            ZohoOAuthConstants::SANDBOX,
            APIConstants::API_BASEURL,
            APIConstants::API_VERSION,
            APIConstants::APPLICATION_LOGFILE_PATH
        ];

        if (!array_key_exists(ZohoOAuthConstants::SANDBOX, $configuration)) {
            self::$configProperties[ZohoOAuthConstants::SANDBOX] = "false";
        }

        if (!array_key_exists(APIConstants::API_BASEURL, $configuration)) {
            self::$configProperties[APIConstants::API_BASEURL] = "www.zohoapis.com";
        }

        if (!array_key_exists(APIConstants::API_VERSION, $configuration)) {
            self::$configProperties[APIConstants::API_VERSION] = "v2";
        }

        foreach ($config_keys as $key) {
            if (array_key_exists($key, $configuration)) {
                self::$configProperties[$key] = $configuration[$key];
            }
        }
    }

    /**
     * @param $fileHandler
     */
    public static function loadConfigProperties($fileHandler)
    {
        $configMap = CommonUtil::getFileContentAsMap($fileHandler);

        foreach ($configMap as $key => $value) {
            self::$configProperties[$key] = $value;
        }
    }

    /**
     * @param $key
     * @return mixed|string
     */
    public static function getConfigValue($key)
    {
        return isset(self::$configProperties[$key]) ? self::$configProperties[$key] : '';
    }

    /**
     * @param $key
     * @param $value
     */
    public static function setConfigValue($key, $value)
    {
        self::$configProperties[$key] = $value;
    }

    /**
     * @return mixed|string
     */
    public static function getAPIBaseUrl()
    {
        return self::getConfigValue("apiBaseUrl");
    }

    /**
     * @return mixed|string
     */
    public static function getAPIVersion()
    {
        return self::getConfigValue("apiVersion");
    }

    /**
     * @return mixed
     * @throws ZCRMException
     * @throws ZohoOAuthException
     */
    public static function getAccessToken()
    {
        $currentUserEmail = ZCRMRestClient::getCurrentUserEmailID();

        if ($currentUserEmail == null && self::getConfigValue("currentUserEmail") == null) {
            throw new ZCRMException(
                "Current user should either be set in ZCRMRestClient or in configuration.properties file"
            );
        } elseif ($currentUserEmail == null) {
            $currentUserEmail = self::getConfigValue("currentUserEmail");
        }

        $oAuthCliIns = ZohoOAuth::getClientInstance();

        return $oAuthCliIns->getAccessToken($currentUserEmail);
    }

    /**
     * @return array
     */
    public static function getAllConfigs()
    {
        return self::$configProperties;
    }
}


