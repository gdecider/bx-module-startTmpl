<?php
namespace Local\Modexample\Tables;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type;

class TicketCategoryTable extends Entity\DataManager
{
    private static $modConf = [];

    public static function getTableName() {
        self::$modConf = include __DIR__ . '/../../mod_conf.php';

        return self::$modConf['prefix'] . '_ticket_categories';
    }

    public static function getMap() {
        return [
            // ID
            new Entity\IntegerField('id', [
                'primary' => true,
                'autocomplete' => true
            ]),

            // name
            new Entity\StringField('name', [
                'required' => true
            ]),

            // info
            new Entity\TextField('info', [
                'required' => false
            ]),
        ];
    }
}