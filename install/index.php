<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main;
use \Bitrix\Main\SystemException;

loc::loadMessages(__FILE__);

Class coupons_xlsx extends CModule
{
    public $MODULE_ID = "coupons.xlsx";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_CSS;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__.'/version.php');
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("cx_module_name");
        $this->MODULE_DESCRIPTION = Loc::getMessage("cx_module_desc");
        $this->PARTNER_NAME = 'itb';
        $this->PARTNER_URI = '//itb-company.com';
    }

    public function setCouponsOrderFields()
    {
        Loader::IncludeModule("iblock");
        Loader::IncludeModule("sale");

        try {
            $PERSON_TYPE_ARR = [];
            $db_ptype = \CSalePersonType::GetList(["SORT" => "ASC"], ["LID"=>'s1', 'ACTIVE'=>'Y']);
            while ($ptype = $db_ptype->Fetch())
                $PERSON_TYPE_ARR[] = $ptype['ID'];

            $COUPON_ARR = [];
            $db_props = \CSaleOrderProps::GetList([], ['CODE'=>'COUPON'], false, false, ['ID','PERSON_TYPE_ID','CODE']);
            while ($props = $db_props->Fetch())
                $COUPON_ARR[intval($props['PERSON_TYPE_ID'])] = intval($props['ID']);

            $arFields = [
                "PERSON_TYPE_ID" => 1,
                "PROPS_GROUP_ID" => 1,
                "NAME" => 'Купон',
                "CODE" => 'COUPON',
                "TYPE" => 'TEXT',
                "REQUIED" => 'N',
                "USER_PROPS" => 'N',
                "SORT" => '350',
                "XML_ID" => 'bx_314159pipi',
                'IS_LOCATION'     => 'N',
                'DESCRIPTION'     => '',
                'IS_EMAIL'        => 'N',
                'IS_PROFILE_NAME' => 'N',
                'IS_PAYER'        => 'N',
                'IS_LOCATION4TAX' => 'N',
                'IS_FILTERED'     => 'Y',
                'IS_ZIP'          => 'N',
                'UTIL'            => 'Y',
            ];

            foreach ($PERSON_TYPE_ARR as $pid) {
                if (empty($COUPON_ARR[$pid])) {
                    $arFields['PERSON_TYPE_ID'] = $pid;
                    \CSaleOrderProps::Add($arFields);
                }
            }

        } catch (SystemException $exception) {
            $error = $exception->getMessage();
        }
    }

    public function getPageLocal($page)
    {
        return str_replace('index.php', $page, Loader::getLocal('modules/'.$this->MODULE_ID.'/install/index.php'));
    }

    public function InstallFiles($arParams = [])
    {
        CopyDirFiles($this->getPageLocal('admin'), $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
        return true;
    }

    public function UnInstallFiles()
    {
        DeleteDirFiles($this->getPageLocal('admin'), $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
        return true;
    }

    public function DoInstall()
    {
        global $APPLICATION;
        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallFiles();
        Option::set($this->MODULE_ID, 'CX_PATH_FILES', '/var/www/www-root/data/obmen/skidka_63828373222665.xlsx');
        Option::set($this->MODULE_ID, 'CX_USER_GROUP', '1,5');
        Option::set($this->MODULE_ID, 'CX_NAME_FORMAT', 'Скидка #PERCENT#% по промокоду');
        Option::set($this->MODULE_ID, 'CX_TOKEN', '3,1415926535897932384626433832795');
        Option::set($this->MODULE_ID, 'CX_CODE_FIELD', 'COUPON'); // xml_id = bx_314159pipi
        Option::set($this->MODULE_ID, 'CX_AGENT_NAME', '\Coupons\Xlsx\Agent::handler();');
        Option::set($this->MODULE_ID, 'CX_AGENT_TIME', 60*60);  // 60 * 60 = 1 hours,   5 * 60 = 5 minute
        $this->setCouponsOrderFields();
        // add agent
        $date = new \DateTime();
        $date->modify('+2 minute');
        $nextDate = $date->format(Main\Type\DateTime::getFormat());
        $AGENT_ID = CAgent::AddAgent("\Coupons\Xlsx\Agent::handler();", $this->MODULE_ID, "Y", 60*60, $nextDate, "Y", $nextDate);
        Option::set($this->MODULE_ID, 'CX_AGENT_ID', $AGENT_ID);
        // end agent
        $APPLICATION->IncludeAdminFile("Установка модуля ".$this->MODULE_ID, $this->getPageLocal('step.php'));
        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->UnInstallFiles();
        Option::delete($this->MODULE_ID); // Will remove all module variables
        CAgent::RemoveModuleAgents($this->MODULE_ID); // dell oll agent this modules
        $APPLICATION->IncludeAdminFile("Деинсталляция модуля ".$this->MODULE_ID, $this->getPageLocal('unstep.php'));
        return true;
    }
}