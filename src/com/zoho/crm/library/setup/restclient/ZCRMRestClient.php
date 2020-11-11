<?php
namespace Jobs\ZohoSDK\com\zoho\crm\library\setup\restclient;

use Jobs\ZohoSDK\com\zoho\crm\library\api\common\ZCRMConfigUtil;
use Jobs\ZohoSDK\com\zoho\crm\library\api\handler\MetaDataAPIHandler;
use Jobs\ZohoSDK\com\zoho\crm\library\api\handler\OrganizationAPIHandler;
use Jobs\ZohoSDK\com\zoho\crm\library\common\APIConstants;
use Jobs\ZohoSDK\com\zoho\crm\library\crud\ZCRMModule;
use Jobs\ZohoSDK\com\zoho\crm\library\crud\ZCRMRecord;
use Jobs\ZohoSDK\com\zoho\crm\library\setup\metadata\ZCRMOrganization;

class ZCRMRestClient
{
	private function __construct()
	{
		
	}
	
	public static function getInstance()
	{
		return new ZCRMRestClient();
	}
	
	public static function initialize($configuration=null)
	{
	    ZCRMConfigUtil::initialize(true,$configuration);
	}
	
	public function getAllModules()
	{
		return MetaDataAPIHandler::getInstance()->getAllModules();
	}
	
	public function getModule($moduleName)
	{
		return MetaDataAPIHandler::getInstance()->getModule($moduleName);
	}
	
	public function getOrganizationInstance()
	{
		return ZCRMOrganization::getInstance();
	}
	
	public function getModuleInstance($moduleAPIName)
	{
		return ZCRMModule::getInstance($moduleAPIName);
	}
	
	public function getRecordInstance($moduleAPIName,$entityId)
	{
		return ZCRMRecord::getInstance($moduleAPIName, $entityId);
	}
	
	public function getCurrentUser()
	{
		return OrganizationAPIHandler::getInstance()->getCurrentUser();
	}
	
	public static function getCurrentUserEmailID()
	{
		return isset($_SERVER[APIConstants::USER_EMAIL_ID])?$_SERVER[APIConstants::USER_EMAIL_ID]:null;
	}
	
	public static function getOrganizationDetails()
	{
		return OrganizationAPIHandler::getInstance()->getOrganizationDetails(); 
	}
}
?>