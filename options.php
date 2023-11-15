<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;
use \Coupons\Xlsx\Agent;

loc::loadMessages(__FILE__);

$module_id = "coupons.xlsx";
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
$RIGHT = $APPLICATION->GetGroupRight($module_id);
if($RIGHT >= "R") :

$aTabs = [
    [
        "DIV" => "edit1",
        "TAB" => Loc::getMessage("MAIN_TAB_SET"),
        "ICON" => "perfmon_settings",
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET"),
        "OPTIONS" => [
            "Параметры импорта",
            ["CX_PATH_FILES", "Путь до файла *.xlsx", null, ["text",50]],
            ["CX_USER_GROUP", 'Доступ - id группы пользователей (через ",")', null, ["text",10]],
            ["CX_NAME_FORMAT", 'Формат имени правила/скидки', null, ["text",50]],
            "Параметры: поля, апи",
            ["CX_CODE_FIELD", 'Код поля/свойства заказа', null, ["text",10], 'Y'],
            ["CX_TOKEN", 'токен для api', null, ["textarea",2, 50]],
            "Настройки для агента",
            ["CX_AGENT_ID", "ID агента", null, ["text",5], 'Y'],
            ["CX_AGENT_NAME", "Имя агента", null, ["text",50], 'Y'],
            ["CX_AGENT_TIME", "Интервал агента (сек.)", null, ["text",5]],
            ["CX_AGENT_UPDATE", "Обновить скидки/купоны (при сохранении этой формы)", null, ["checkbox",10]],
        ]
    ],
    [
        "DIV" => "edit2",
        "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"),
        "ICON" => "perfmon_settings",
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS"),
    ],
];

$tabControl = new CAdminTabControl("tabControl", $aTabs);

Loader::IncludeModule($module_id);

if ($_SERVER["REQUEST_METHOD"] == "POST" && strlen($Update.$Apply.$RestoreDefaults) > 0 && $RIGHT=="W" && check_bitrix_sessid())
{
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/perfmon/prolog.php");

    // изменеяет интервал агента
    $CX_AGENT_TIME =  intval($_REQUEST['CX_AGENT_TIME']);
    if ($CX_AGENT_TIME > 300) {
        $CX_AGENT_ID = Option::get("coupons.xlsx", "CX_AGENT_ID");
        CAgent::Update($CX_AGENT_ID, ['AGENT_INTERVAL'=>$CX_AGENT_TIME]);
    }
    // end интервал

    if (isset($_REQUEST['CX_AGENT_UPDATE']) && $_REQUEST['CX_AGENT_UPDATE'] == 'Y') {
        Agent::handler();
    }

    if(strlen($RestoreDefaults)>0)
        COption::RemoveOption("WE_ARE_CLOSED_TEXT_TITLE");
    else
    {
        foreach ($aTabs as $aTab)
        {
            __AdmSettingsSaveOptions($module_id, $aTab['OPTIONS']);
        }
    }

    $Update = $Update.$Apply;
    ob_start();
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
    ob_end_clean();

    LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&".$tabControl->ActiveTabParam());
} ?>

<h1><?='Настройка импорта купонов'?></h1>
<form method="post" action="<?=$APPLICATION->GetCurPage()?>?mid=<?=urlencode($module_id)?>&amp;lang=<?=LANGUAGE_ID?>">
    <?
    $tabControl->Begin();
    foreach ($aTabs as $aTab)
    {
        $tabControl->BeginNextTab();
        __AdmSettingsDrawList($module_id, $aTab['OPTIONS']);
    }
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
    $tabControl->Buttons(); ?>
    <input <?if ($RIGHT<"W") echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save">
    <input <?if ($RIGHT<"W") echo "disabled" ?> type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
    <?if(strlen($_REQUEST["back_url_settings"])>0):?>
        <input <?if ($RIGHT<"W") echo "disabled" ?> type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?=htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
        <input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
    <?endif?>
    <input type="submit" name="RestoreDefaults" title="<?=GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="confirm('<?=AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?=GetMessage("MAIN_RESTORE_DEFAULTS")?>">
    <?=bitrix_sessid_post();?>
    <?$tabControl->End();?>
</form>
<?endif;?>

<? // info help for users
echo BeginNote();
echo 'Внимание! <a href="/bitrix/admin/sale_order_props.php?lang=ru">В свойствах заказа</a> должно быть строковое свойство с кодом COUPON'.'<br>';
echo 'для записи использываемого купона в заказе.<br>';
echo 'REST API внешний: http://ВАШ_САЙТ/api/coupons/?token=ВАШ_ТОКЕН'.'<br>';
echo 'Формат имени правила/скидки: должен содержать слова Скидка и код #PERCENT#'.'<br>';
echo 'Доступ - id группы пользователей (через ",") например: 1,5 '.'<br>';
echo EndNote();
