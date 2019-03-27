<?php

namespace Local\Modexample;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\UserTable;
use Local\Tp\TicketCategoryTable;
use Bitrix\Main\Type;

class Func
{
    public static function camel2snake($str)
    {
        return strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $str));
    }
}
