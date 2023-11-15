<?php

namespace Coupons\Xlsx;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Coupons\Xlsx;
use Bitrix\Sale\Internals\DiscountTable;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Bitrix\Sale\Internals\DiscountGroupTable;
use Shuchkin\SimpleXLSX;

/**
 * Class Utils
 * @package Coupons\Xlsx
 */
class Utils
{
    public static $MODULE_ID = "coupons.xlsx";
    public static $path_files;

    public static function isPatchFiles($path_files, $extension_arr = [])
    {
        $res = false;
        if (file_exists($path_files)) {
            if (count($extension_arr)) {
                $path_info = pathinfo($path_files);
                if (in_array($path_info['extension'], $extension_arr))
                    $res = $path_files;
            } else {
                $res = $path_files;
            }
        }

        return $res;
    }

    public static function getArr($path_files)
    {
        $arr = [];
        if ($path_files && Loader::IncludeModule('coupons.xlsx')) {
            $xlsx = SimpleXLSX::parse($path_files);
            $arr =$xlsx->rows();
        }

        return $arr;
    }

    public static function getDiscount()
    {
        $discount_arr = [];
        if (Loader::IncludeModule("sale")) {
            $arDiscount = DiscountTable::getList(
                [
                    'select' => ['ID', 'NAME', 'CURRENCY', 'ACTIVE', 'SORT', 'SHORT_DESCRIPTION_STRUCTURE', 'ACTIVE_FROM', 'ACTIVE_TO', 'USE_COUPONS'],
                    'order' => ['ID' => 'DESC'],
                    'filter' => ['ACTIVE' => 'Y', 'NAME' => 'Скидка %'],
                ]
            );

            while ($arrFields = $arDiscount->fetch()) {
                $arrFields['DISCOUNT_VALUE'] = $arrFields['SHORT_DESCRIPTION_STRUCTURE']['VALUE'];
                $discount_arr[$arrFields['ID']] = $arrFields['DISCOUNT_VALUE'];
            }
        }

        return $discount_arr;
    }

    public static function getCoupons()
    {
        $res = [];
        if (Loader::IncludeModule("sale")) {
            $rsCoupon = DiscountCouponTable::getList(
                [
                    'select' => ['ID','DISCOUNT_ID','ACTIVE','COUPON','TYPE','ACTIVE_FROM','ACTIVE_TO']
                ]
            );

            while ($arCoupon = $rsCoupon->fetch()) {
                $res[] = $arCoupon;
            }
        }

        return $res;
    }

    public static function getDiscountID($key, $discount_arr)
    {
        $r = false;
        if (count($discount_arr)) {
            foreach ($discount_arr as $id => $value) {
                if ($key == $value) {
                    $r = $id;
                    break;
                }
            }
        }

        return $r;
    }

    public static function getCouponArr($arr)
    {
        $coupons_arr = [];
        if (count($arr)) {
            foreach ($arr as $k => $item) {
                if ($k == 0) continue;
                $coupons_arr[intval($item[2])][] = trim($item[12]);
            }
        }

        return $coupons_arr;
    }

    public static function setCouponsArr($id_discount, $arr_coupons)
    {
        $res = [];
        $arr = is_array($arr_coupons)?$arr_coupons:[$arr_coupons];
        if (Loader::IncludeModule("sale") && intval($id_discount) > 0) {
            foreach ($arr as $coup_value) {
                $fields['COUPON'] = [                        // массив $data
                    'DISCOUNT_ID' => $id_discount,           // id правила корзины
                    'ACTIVE_FROM' => null,                   // выставляем без ограничения к началу даты активности купона
                    'ACTIVE_TO' => null,                     // выставляем без ограничения к окончанию даты активности купона
                    'TYPE' => DiscountCouponTable::TYPE_ONE_ORDER, // выставляем тип купона TYPE_ONE_ORDER - использовать на один заказ, TYPE_MULTI_ORDER - использовать на несколько заказов
                    'MAX_USE' => 1,                           // выставляем максимальное кол-во применений купона
                    "COUPON" => $coup_value,
                ];

                $couponsResult = DiscountCouponTable::add($fields['COUPON']);
                if (!$couponsResult->isSuccess()) {
                    // $errors = $couponsResult->getErrorMessages();
                    $res[$coup_value] = false;
                } else {
                    $id_coup = $couponsResult->getId();
                    $res[$coup_value] = $id_coup;
                }
            }
        }

        return $res;
    }

    public static function getCouponsArrID()
    {
        $res = [];
        $arr = self::getCoupons();
        if (count($arr)) {
            foreach ($arr as $value) {
                $res[$value['ID']] = $value['COUPON'];
            }
        }

        return $res;
    }

    public static function getCouponsXlsx($arr)
    {
        $coupons_arr = [];
        if (count($arr)) {
            foreach ($arr as $k => $item) {
                if ($k == 0) continue;
                $coupons_arr[] = trim($item[12]);
            }
        }

        return $coupons_arr;
    }

    public static function setDiscount($percent)
    {
        Loader::IncludeModule("iblock");
        Loader::IncludeModule("sale");

        $name_format = Option::get('coupons.xlsx', "CX_NAME_FORMAT", 'Скидка #PERCENT#% по промокоду');
        $name = strtr($name_format, ['#PERCENT#'=>$percent]);
        $siteId = 's1';
        $userGroupIds = explode(',', Option::get('coupons.xlsx', "CX_USER_GROUP", '1'));

        $discountFields = [
            'LID' => $siteId,
            'NAME' => $name,
            'ACTIVE_FROM' => '',
            'ACTIVE_TO' => '',
            'ACTIVE' => 'Y',
            'SORT' => '100',
            'MODIFIED_BY' => '1',
            'CREATED_BY' => '1',
            'PRIORITY' => '1',
            'LAST_DISCOUNT' => 'N',
            'LAST_LEVEL_DISCOUNT' => 'N',
            'XML_ID' => '',
            'CONDITIONS_LIST' => [
                'CLASS_ID' => 'CondGroup',
                'DATA' => [
                    'All'=>'AND',
                    'True'=>'True',
                ],
                'CHILDREN' => [],
            ],
            'UNPACK'=> 'function($arOrder){return ((1 == 1)); };',
            'ACTIONS_LIST'=>[
                'CLASS_ID'=>'CondGroup',
                'DATA'=>['All'=>'AND'],
                'CHILDREN'=>[
                    0=>[
                        'CLASS_ID'=>'ActSaleBsktGrp',
                        'DATA'=>[
                            'Type'=>'Discount',
                            'Value'=>$percent,
                            'Unit'=>'Perc',
                            'Max'=>'0',
                            'All'=>'AND',
                            'True'=>'True',
                        ],
                        'CHILDREN'=>[
                            0=>[
                                'CLASS_ID' => 'CondBsktFldPrice',
                                'DATA' => ['logic'=>'Not', 'value'=>0]
                            ]
                        ]
                    ],
                ],
            ],
            'APPLICATION'=>'function (&$arOrder){$saleact_0_0=function($row){return (isset($row[\'PRICE\']) && $row[\'PRICE\'] != 0);};\Bitrix\Sale\Discount\Actions::applyToBasket($arOrder, array (\'VALUE\' => -'.$percent.'.0, \'UNIT\' => \'P\', \'LIMIT_VALUE\' => 0,), $saleact_0_0);};',
            'USE_COUPONS'=>'Y',
            'EXECUTE_MODULE'=>'sale',
            'EXECUTE_MODE'=>'0',
            'HAS_INDEX'=>'N',
            'SHORT_DESCRIPTION_STRUCTURE'=>[
                'TYPE' => 'Discount',
                'VALUE' => $percent,
                'LIMIT_VALUE' => 0,
                'VALUE_TYPE' => 'P',
            ],
        ];

        $res = false;
        $resultDiscountAdd = DiscountTable::add($discountFields);

        if ($resultDiscountAdd->isSuccess()) {
            $res = $resultDiscountAdd->getId();
            foreach($userGroupIds as $groupId)
                DiscountGroupTable::add(['ACTIVE' => 'Y', 'GROUP_ID' => $groupId, 'DISCOUNT_ID' => $res,]);
        }

        return $res;
    }
}