<?php

namespace Jobs\ZohoSDK\com\zoho\crm\library\common;

use Exception;
use Jobs\ZohoSDK\com\zoho\crm\library\exception\Logger;

class CommonUtil
{
    public static function getFileContentAsMap($fileHandler)
    {
        $reponseMap = [];
        try {
            while (!feof($fileHandler)) {
                $line = fgets($fileHandler);
                $lineAfterSplit = explode('=', $line);
                if (strpos($lineAfterSplit[0], '#') === false && count($lineAfterSplit) > 1) {
                    $reponseMap[trim($lineAfterSplit[0])] = trim($lineAfterSplit[1]);
                }
            }
            fclose($fileHandler);
        } catch (Exception $ex) {
            Logger::warn('Exception occured while converting file content as map (file::ZohoOAuthUtil.php)');
        }
        return $reponseMap;
    }

    public static function getEmptyJSONObject()
    {
        return new ArrayObject();
    }
}
