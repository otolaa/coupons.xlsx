<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;

loc::loadMessages(__FILE__);

$module_id = 'coupons.xlsx';
if ($APPLICATION->GetGroupRight($module_id) > "D") {

    require_once(Loader::getLocal('modules/'.$module_id.'/prolog.php'));

    /* the types menu  dev.1c-bitrix.ru/api_help/main/general/admin.section/menu.php
    global_menu_content - раздел "Контент"
    global_menu_marketing - раздел "Маркетинг"
    global_menu_store - раздел "Магазин"
    global_menu_services - раздел "Сервисы"
    global_menu_statistics - раздел "Аналитика"
    global_menu_marketplace - раздел "Marketplace"
    global_menu_settings - раздел "Настройки"
 */
    $aMenu = [
        "parent_menu" => "global_menu_marketing",
        "section" => $module_id,
        "sort" => 450,
        "module_id" => $module_id,
        "text" => 'Импорт купонов',
        "title"=> 'Модуль для импорта купонов',
        "icon" => "fileman_menu_icon", // sys_menu_icon bizproc_menu_icon util_menu_icon
        "page_icon" => "fileman_menu_icon", // sys_menu_icon bizproc_menu_icon util_menu_icon
        "items_id" => "menu_".str_replace('.', '_', $module_id),
        "items" => [
            [
                "text" => 'Настройки',
                "title" => 'Настройки',
                "url" => "settings.php?mid=".$module_id."&lang=".LANGUAGE_ID,
            ],
        ]
    ];

    return $aMenu;
}

return false;