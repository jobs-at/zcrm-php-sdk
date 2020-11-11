<?php

namespace Jobs\ZohoSDK\com\zoho\crm\library\api\handler;

use Jobs\ZohoSDK\com\zoho\crm\library\api\APIRequest;
use Jobs\ZohoSDK\com\zoho\crm\library\common\APIConstants;
use Jobs\ZohoSDK\com\zoho\crm\library\crud\ZCRMEventParticipant;
use Jobs\ZohoSDK\com\zoho\crm\library\crud\ZCRMInventoryLineItem;
use Jobs\ZohoSDK\com\zoho\crm\library\crud\ZCRMPriceBookPricing;
use Jobs\ZohoSDK\com\zoho\crm\library\crud\ZCRMRecord;
use Jobs\ZohoSDK\com\zoho\crm\library\crud\ZCRMTax;
use Jobs\ZohoSDK\com\zoho\crm\library\exception\APIExceptionHandler;
use Jobs\ZohoSDK\com\zoho\crm\library\exception\ZCRMException;
use Jobs\ZohoSDK\com\zoho\crm\library\setup\users\ZCRMUser;

class EntityAPIHandler extends APIHandler
{
    protected $record=null;

    private function __construct($zcrmrecord)
    {
        $this->record=$zcrmrecord;
    }

    public static function getInstance($zcrmrecord)
    {
        return new EntityAPIHandler($zcrmrecord);
    }

    public function getRecord()
    {
        try {
            $this->requestMethod=APIConstants::REQUEST_METHOD_GET;
            $this->urlPath=$this->record->getModuleApiName().'/'.$this->record->getEntityId();
            $this->addHeader('Content-Type', 'application/json');
            $responseInstance=APIRequest::getInstance($this)->getAPIResponse();
            $recordDetails=$responseInstance->getResponseJSON()['data'];
            self::setRecordProperties($recordDetails[0]);
            $responseInstance->setData($this->record);
            return $responseInstance;
        } catch (ZCRMException $exception) {
            APIExceptionHandler::logException($exception);
            throw $exception;
        }
    }

    public function createRecord()
    {
        try {
            $inputJSON=self::getZCRMRecordAsJSON();
            $this->requestMethod=APIConstants::REQUEST_METHOD_POST;
            $this->urlPath=$this->record->getModuleApiName();
            $this->addHeader('Content-Type', 'application/json');
            $this->requestBody=json_encode(array_filter(['data'=>[$inputJSON]]));
            $responseInstance=APIRequest::getInstance($this)->getAPIResponse();
            $responseDataArray=$responseInstance->getResponseJSON()['data'];
            $responseData=$responseDataArray[0];
            $reponseDetails=$responseData['details'];
            $this->record->setEntityId($reponseDetails['id']);
            $this->record->setCreatedTime($reponseDetails['Created_Time']);
            $createdBy=$reponseDetails['Created_By'];
            $this->record->setCreatedBy(ZCRMUser::getInstance($createdBy['id'], $createdBy['name']));

            $responseInstance->setData($this->record);

            return $responseInstance;
        } catch (ZCRMException $exception) {
            APIExceptionHandler::logException($exception);
            throw $exception;
        }
    }

    public function updateRecord()
    {
        try {
            $inputJSON=self::getZCRMRecordAsJSON();
            $this->requestMethod=APIConstants::REQUEST_METHOD_PUT;
            $this->urlPath=$this->record->getModuleApiName().'/'.$this->record->getEntityId();
            $this->addHeader('Content-Type', 'application/json');
            $this->requestBody=json_encode(array_filter(['data'=>[$inputJSON]]));

            $responseInstance=APIRequest::getInstance($this)->getAPIResponse();

            $responseDataArray=$responseInstance->getResponseJSON()['data'];
            $responseData=$responseDataArray[0];
            $reponseDetails=$responseData['details'];
            $this->record->setCreatedTime($reponseDetails['Created_Time']);
            $this->record->setModifiedTime($reponseDetails['Modified_Time']);
            $createdBy=$reponseDetails['Created_By'];
            $this->record->setCreatedBy(ZCRMUser::getInstance($createdBy['id'], $createdBy['name']));
            $modifiedBy=$reponseDetails['Modified_By'];
            $this->record->setModifiedBy(ZCRMUser::getInstance($modifiedBy['id'], $modifiedBy['name']));

            $responseInstance->setData($this->record);

            return $responseInstance;
        } catch (ZCRMException $exception) {
            APIExceptionHandler::logException($exception);
            throw $exception;
        }
    }

    public function deleteRecord()
    {
        try {
            $this->requestMethod=APIConstants::REQUEST_METHOD_DELETE;
            $this->urlPath=$this->record->getModuleApiName().'/'.$this->record->getEntityId();
            $this->addHeader('Content-Type', 'application/json');

            $responseInstance=APIRequest::getInstance($this)->getAPIResponse();

            return $responseInstance;
        } catch (ZCRMException $exception) {
            APIExceptionHandler::logException($exception);
            throw $exception;
        }
    }

    public function convertRecord($potentialRecord, $assignToUser)
    {
        try {
            $this->requestMethod=APIConstants::REQUEST_METHOD_POST;
            $this->urlPath=$this->record->getModuleApiName().'/'.$this->record->getEntityId().'/actions/convert';
            $this->addHeader('Content-Type', 'application/json');

            $dataObject=[];
            if ($assignToUser!=null) {
                $dataObject['assign_to']=$assignToUser->getId();
            }
            if ($potentialRecord!=null) {
                $dataObject['Deals']=self::getInstance($potentialRecord)->getZCRMRecordAsJSON();
            }
            if (sizeof($dataObject)>0) {
                $dataArray=json_encode([APIConstants::DATA=>[array_filter($dataObject)]]);
            } else {
                $dataArray=json_encode([APIConstants::DATA=>[new ArrayObject()]]);
            }
            $this->requestBody=$dataArray;

            $responseInstance=APIRequest::getInstance($this)->getAPIResponse();

            $responseJSON=$responseInstance->getResponseJSON();


            //Process Response JSON
            $convertedIdsJSON = $responseJSON[APIConstants::DATA][0];
            $convertedIds = [];
            $convertedIds[APIConstants::CONTACTS]=isset($convertedIdsJSON[APIConstants::CONTACTS]) ? $convertedIdsJSON[APIConstants::CONTACTS] : null;
            if (isset($convertedIdsJSON[APIConstants::ACCOUNTS]) && $convertedIdsJSON[APIConstants::ACCOUNTS]!=null) {
                $convertedIds[APIConstants::ACCOUNTS]=$convertedIdsJSON[APIConstants::ACCOUNTS];
            }
            if (isset($convertedIdsJSON[APIConstants::DEALS]) && $convertedIdsJSON[APIConstants::DEALS]!=null) {
                $convertedIds[APIConstants::DEALS]=$convertedIdsJSON[APIConstants::DEALS];
            }

            return $convertedIds;
        } catch (ZCRMException $exception) {
            APIExceptionHandler::logException($exception);
            throw $exception;
        }
    }

    public function uploadPhoto($filePath)
    {
        try {
            $fileContent=file_get_contents($filePath);
            $filePathArray=explode('/', $filePath);
            $fileName=$filePathArray[sizeof($filePathArray)-1];
            if (function_exists('curl_file_create')) { // php 5.6+
                $cFile = curl_file_create($filePath);
            } else { //
                $cFile = '@' . realpath($filePath);
            }
            $post = ['file'=> $cFile];

            $this->requestMethod=APIConstants::REQUEST_METHOD_POST;
            $this->urlPath=$this->record->getModuleApiName().'/'.$this->record->getEntityId().'/photo';
            $this->requestBody=$post;

            $responseInstance=APIRequest::getInstance($this)->getAPIResponse();

            return $responseInstance;
        } catch (ZCRMException $exception) {
            APIExceptionHandler::logException($exception);
            throw $exception;
        }
    }
    public function downloadPhoto()
    {
        try {
            $this->requestMethod=APIConstants::REQUEST_METHOD_GET;
            $this->urlPath=$this->record->getModuleApiName().'/'.$this->record->getEntityId().'/photo';

            return APIRequest::getInstance($this)->downloadFile();
        } catch (ZCRMException $exception) {
            APIExceptionHandler::logException($exception);
            throw $exception;
        }
    }

    public function deletePhoto()
    {
        try {
            $this->requestMethod=APIConstants::REQUEST_METHOD_DELETE;
            $this->urlPath=$this->record->getModuleApiName().'/'.$this->record->getEntityId().'/photo';

            return APIRequest::getInstance($this)->getAPIResponse();
        } catch (ZCRMException $exception) {
            APIExceptionHandler::logException($exception);
            throw $exception;
        }
    }

    public function getZCRMRecordAsJSON()
    {
        $recordJSON=[];
        $apiNameVsValues=$this->record->getData();
        if ($this->record->getOwner()!=null) {
            $recordJSON['Owner']=''.$this->record->getOwner()->getId();
        }
        if ($this->record->getLayout()!=null) {
            $recordJSON['Layout']=''.$this->record->getLayout()->getId();
        }
        foreach ($apiNameVsValues as $key=>$value) {
            if ($value instanceof ZCRMRecord) {
                $value = ''.$value->getEntityId();
            } elseif ($value instanceof ZCRMUser) {
                $value = ''. $value->getId();
            }
            $recordJSON[$key]=$value;
        }
        if (sizeof($this->record->getLineItems())>0) {
            $recordJSON['Product_Details']=self::getLineItemJSON($this->record->getLineItems());
        }
        if (sizeof($this->record->getParticipants())>0) {
            $recordJSON['Participants']=self::getParticipantsAsJSONArray();
        }
        if (sizeof($this->record->getPriceDetails())>0) {
            $recordJSON['Pricing_Details']=self::getPriceDetailsAsJSONArray();
        }
        if (sizeof($this->record->getTaxList())>0) {
            $recordJSON['Tax']=self::getTaxListAsJSON();
        }
        return $recordJSON;
    }

    public function getTaxListAsJSON()
    {
        $taxes = [];
        $taxList = $this->record->getTaxList();
        foreach ($taxList as $taxIns) {
            array_push($taxes, $taxIns->getTaxName());
        }
        return $taxes;
    }
    public function getPriceDetailsAsJSONArray()
    {
        $priceDetailsArr = [];
        $priceDetailsList = $this->record->getPriceDetails();
        foreach ($priceDetailsList as $priceDetailIns) {
            array_push($priceDetailsArr, self::getZCRMPriceDetailAsJSON($priceDetailIns));
        }
        return $priceDetailsArr;
    }
    public function getZCRMPriceDetailAsJSON(ZCRMPriceBookPricing $priceDetailIns)
    {
        $priceDetailJSON = [];
        if ($priceDetailIns->getId() != null) {
            $priceDetailJSON['id']=$priceDetailIns->getId();
        }
        $priceDetailJSON['discount']=$priceDetailIns->getDiscount();
        $priceDetailJSON['to_range']=$priceDetailIns->getToRange();
        $priceDetailJSON['from_range']=$priceDetailIns->getFromRange();
        return $priceDetailJSON;
    }

    public function getParticipantsAsJSONArray()
    {
        $participantsArr = [];
        $participantsList = $this->record->getParticipants();
        foreach ($participantsList as $participantIns) {
            array_push($participantsArr, self::getZCRMParticipantAsJSON($participantIns));
        }
        return $participantsArr;
    }

    public function getZCRMParticipantAsJSON(ZCRMEventParticipant $participantIns)
    {
        $participantJSON = [];
        $participantJSON['participant']= ''.$participantIns->getId();
        $participantJSON['type']=''.$participantIns->getType();
        $participantJSON['name']=''.$participantIns->getName();
        $participantJSON['Email']=''.$participantIns->getEmail();
        $participantJSON['invited']=(boolean)$participantIns->isInvited();
        $participantJSON['status']=''.$participantIns->getStatus();

        return $participantJSON;
    }

    public function getLineItemJSON($lineItemsArray)
    {
        $lineItemsAsJSONArray=[];
        foreach ($lineItemsArray as $lineItem) {
            $lineItemData=[];
            if ($lineItem->getQuantity()==null) {
                throw new ZCRMException("Mandatory Field 'quantity' is missing.", APIConstants::RESPONSECODE_BAD_REQUEST);
            }
            if ($lineItem->getId()!=null) {
                $lineItemData['id']=''.$lineItem->getId();
            }
            if ($lineItem->getProduct()!=null) {
                $lineItemData['product']=''.$lineItem->getProduct()->getEntityId();
            }
            if ($lineItem->getDescription()!=null) {
                $lineItemData['product_description']=$lineItem->getDescription();
            }
            if ($lineItem->getListPrice()!=null) {
                $lineItemData['list_price']=$lineItem->getListPrice();
            }
            $lineItemData['quantity']=$lineItem->getQuantity();
            /*
             *  Either discount percentage can be 0 or discount value can be 0. So if percentage is 0, set value and vice versa.
             *	If the intended discount is 0, then both percent and value will be 0. Hence setting either of this to 0, will be enough.
            */
            if ($lineItem->getDiscountPercentage() == null) {
                $lineItemData['Discount']=$lineItem->getDiscount();
            } else {
                $lineItemData['Discount']=$lineItem->getDiscountPercentage().'%';
            }
            $lineTaxes=$lineItem->getLineTax();
            $lineTaxArray=[];
            foreach ($lineTaxes as $lineTaxInstance) {
                $tax=[];
                $tax['name']=$lineTaxInstance->getTaxName();
                $tax['value']=$lineTaxInstance->getValue();
                $tax['percentage']=$lineTaxInstance->getPercentage();
                array_push($lineTaxArray, $tax);
            }
            $lineItemData['line_tax']=$lineTaxArray;

            array_push($lineItemsAsJSONArray, array_filter($lineItemData));
        }
        return array_filter($lineItemsAsJSONArray);
    }

    public function setRecordProperties($recordDetails)
    {
        foreach ($recordDetails as $key=>$value) {
            if ('id'==$key) {
                $this->record->setEntityId($value);
            } elseif ('Product_Details'==$key) {
                $this->setInventoryLineItems($value);
            } elseif ('Participants'==$key) {
                $this->setParticipants($value);
            } elseif ('Pricing_Details'==$key) {
                $this->setPriceDetails($value);
            } elseif ('Created_By'==$key) {
                $createdBy = ZCRMUser::getInstance($value['id'], $value['name']);
                $this->record->setCreatedBy($createdBy);
            } elseif ('Modified_By'==$key) {
                $modifiedBy = ZCRMUser::getInstance($value['id'], $value['name']);
                $this->record->setModifiedBy($modifiedBy);
            } elseif ('Created_Time'==$key) {
                $this->record->setCreatedTime(''.$value);
            } elseif ('Modified_Time'==$key) {
                $this->record->setModifiedTime(''.$value);
            } elseif ('Last_Activity_Time'==$key) {
                $this->record->setLastActivityTime(''.$value);
            } elseif ('Owner'==$key) {
                $owner =ZCRMUser::getInstance($value['id'], $value['name']);
                $this->record->setOwner($owner);
            } elseif ('Layout'==$key) {
                $layout = null;
                if ($value!=null) {
                    $layout = ZCRMLayout::getInstance($value['id']);
                    $layout->setName($value['name']);
                }
                $this->record->setLayout($layout);
            } elseif ('Handler'==$key && $value!=null) {
                $handler = ZCRMUser::getInstance($value['id'], $value['name']);
                $this->record->setFieldValue($key, $handler);
            } elseif ('Tax'===$key && is_array($value)) {
                foreach ($value as $taxName) {
                    $taxIns=ZCRMTax::getInstance($taxName);
                    $this->record->addTax($taxIns);
                }
            } elseif (substr($key, 0, 1)=='$') {
                $this->record->setProperty(str_replace('$', '', $key), $value);
            } elseif (is_array($value)) {
                if (isset($value['id'])) {
                    $lookupRecord = ZCRMRecord::getInstance($key, $value['id'] ?? '0');
                    $lookupRecord->setLookupLabel($value['name'] ?? null);
                    $this->record->setFieldValue($key, $lookupRecord);
                } else {
                    $this->record->setFieldValue($key, $value);
                }
            } else {
                $this->record->setFieldValue($key, $value);
            }
        }
    }

    private function setParticipants($participants)
    {
        foreach ($participants as $participantDetail) {
            $this->record->addParticipant(self::getZCRMParticipant($participantDetail));
        }
    }


    private function setPriceDetails($priceDetails)
    {
        foreach ($priceDetails as $priceDetail) {
            $this->record->addPriceDetail(self::getZCRMPriceDetail($priceDetail));
        }
    }

    public function getZCRMParticipant($participantDetail)
    {
        $participant = ZCRMEventParticipant::getInstance($participantDetail['type'], $participantDetail['participant']);
        $participant->setName($participantDetail['name']);
        $participant->setEmail($participantDetail['Email']);
        $participant->setInvited((boolean)$participantDetail['invited']);
        $participant->setStatus($participantDetail['status']);

        return $participant;
    }

    public function getZCRMPriceDetail($priceDetails)
    {
        $priceDetailIns = ZCRMPriceBookPricing::getInstance($priceDetails['id']);
        $priceDetailIns->setDiscount((double)$priceDetails['discount']);
        $priceDetailIns->setToRange((double)$priceDetails['to_range']);
        $priceDetailIns->setFromRange((double)$priceDetails['from_range']);

        return $priceDetailIns;
    }

    public function setInventoryLineItems($lineItems)
    {
        foreach ($lineItems as $lineItem) {
            $this->record->addLineItem(self::getZCRMLineItemInstance($lineItem));
        }
    }

    public function getZCRMLineItemInstance($lineItemDetails)
    {
        $productDetails = $lineItemDetails['product'];
        $lineItemId = $lineItemDetails['id'];
        $lineItemInstance = ZCRMInventoryLineItem::getInstance($lineItemId);
        $product = ZCRMRecord::getInstance('Products', $productDetails['id']);
        $product->setLookupLabel($productDetails['name']);
        if (isset($productDetails['Product_Code'])) {
            $product->setFieldValue('Product_Code', $productDetails['Product_Code']);
        }
        $lineItemInstance->setProduct($product);
        $lineItemInstance->setDescription($lineItemDetails['product_description']);
        $lineItemInstance->setQuantity($lineItemDetails['quantity']+0);
        $lineItemInstance->setListPrice($lineItemDetails['list_price']+0);
        $lineItemInstance->setTotal($lineItemDetails['total']+0);
        $lineItemInstance->setDiscount($lineItemDetails['Discount']+0);
        $lineItemInstance->setTotalAfterDiscount($lineItemDetails['total_after_discount']+0);
        $lineItemInstance->setTaxAmount($lineItemDetails['Tax']+0);
        $lineTaxes = $lineItemDetails['line_tax'];
        foreach ($lineTaxes as $lineTax) {
            $taxInstance=ZCRMTax::getInstance($lineTax['name']);
            $taxInstance->setPercentage($lineTax['percentage']);
            $taxInstance->setValue($lineTax['value']+0);
            $lineItemInstance->addLineTax($taxInstance);
        }
        $lineItemInstance->setNetTotal($lineItemDetails['net_total']+0);

        return $lineItemInstance;
    }
}
