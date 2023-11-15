<?php

namespace Coupons\Xlsx;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Coupons\Xlsx;
use Coupons\Xlsx\Utils;
use Bitrix\Main\SystemException;

/**
 * Class Agent
 * @package Coupons\Xlsx
 */
class Agent
{
    public static $MODULE_ID = "coupons.xlsx";

    public static function handler()
    {
        //$date = new \DateTime();
        //$df = $date->format(Main\Type\DateTime::getFormat());
        try {

            if ($f = Utils::isPatchFiles(Option::get('coupons.xlsx', "CX_PATH_FILES"), ['xlsx'])) {
                $arr = Utils::getArr($f);
                $discount_arr = Utils::getDiscount();
                $coupons_arr = Utils::getCouponArr($arr);

                // добавление купонов
                if (count($coupons_arr)) {
                    foreach ($coupons_arr as $key => $item) {
                        $id_discount = Utils::getDiscountID($key, $discount_arr);
                        if (!$id_discount)
                            $id_discount = Utils::setDiscount($key);

                        Utils::setCouponsArr($id_discount, $item);
                    }
                }

                // дэактивация купонов
                $arCoupon_ = Utils::getCouponsArrID();
                $arr_xlsx = Utils::getCouponsXlsx($arr);

                if (count($arCoupon_) && count($arr_xlsx)) {
                    foreach ($arCoupon_ as $kid => $value) {
                        if (!in_array($value, $arr_xlsx))
                            DiscountCouponTable::update($kid, ['ACTIVE'=>'N']);
                    }
                }
            }

        } catch (SystemException $exception) {
            $er = $exception->getMessage();
            // AddMessage2Log("\n".var_export($er, true). " \n \r\n ", "_error_\Coupons\Xlsx\Agent::handler_");
        }

        // тут можно добавить алгоритм логгирования
        // $AGENT_ID = Option::get(self::$MODULE_ID, "CX_AGENT_ID");
        // AddMessage2Log("\n".var_export($df, true). " \n \r\n ", "_dateTime_handler_");

        return '\Coupons\Xlsx\Agent::handler();';
    }
}