<?php

namespace Jobs\ZohoSDK\com\zoho\crm\library\api\response;

use Jobs\ZohoSDK\com\zoho\crm\library\common\APIConstants;
use Jobs\ZohoSDK\com\zoho\crm\library\exception\APIExceptionHandler;
use Jobs\ZohoSDK\com\zoho\crm\library\exception\ZCRMException;

class BulkAPIResponse extends CommonAPIResponse
{
    private $bulkData=null;
    private $status=null;
    private $info=null;
    private $bulkEntitiesResponse=null;
    
    public function __construct($httpResponse, $httpStatusCode)
    {
        parent::__construct($httpResponse, $httpStatusCode);
        $this->setInfo();
    }
    
    
    public function handleForFaultyResponses()
    {
        $statusCode=self::getHttpStatusCode();
        if (in_array($statusCode, APIExceptionHandler::getFaultyResponseCodes())) {
            if ($statusCode==APIConstants::RESPONSECODE_NO_CONTENT) {
                $exception=new ZCRMException('No Content', $statusCode);
                $exception->setExceptionCode('NO CONTENT');
                throw $exception;
            } else {
                $responseJSON=$this->getResponseJSON();
                $exception=new ZCRMException($responseJSON['message'], $statusCode);
                $exception->setExceptionCode($responseJSON['code']);
                $exception->setExceptionDetails($responseJSON['details']);
                throw $exception;
            }
        }
    }
    public function processResponseData()
    {
        $this->bulkEntitiesResponse =[];
        $bulkResponseJSON=$this->getResponseJSON();
        if (array_key_exists(APIConstants::DATA, $bulkResponseJSON)) {
            $recordsArray = $bulkResponseJSON[APIConstants::DATA];
            foreach ($recordsArray as $record) {
                if ($record!=null && array_key_exists(APIConstants::STATUS, $record)) {
                    array_push($this->bulkEntitiesResponse, new EntityResponse($record));
                }
            }
        }
    }

    public function getData()
    {
        return $this->bulkData;
    }

    public function setData($bulkData)
    {
        $this->bulkData = $bulkData;
    }

    /**
     * status
     * @return String
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * status
     * @param String $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * info
     * @return ResponseInfo
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * info
     */
    public function setInfo()
    {
        if (array_key_exists(APIConstants::INFO, $this->getResponseJSON())) {
            $this->info = new ResponseInfo($this->getResponseJSON()[APIConstants::INFO]);
        }
    }

    /**
     * bulkEntitiesResponse
     * @return EntityResponse[]
     */
    public function getEntityResponses()
    {
        return $this->bulkEntitiesResponse;
    }
}
