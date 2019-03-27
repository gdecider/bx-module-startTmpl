<?php
namespace Local\Modexample\Tables;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type;
use Local\Modexample\Func;

class TicketTable extends Entity\DataManager
{
    private static $modConf = [];
    
    private static function getTableNameFromClassName()
    {
        $className = end(explode('\\', get_class(new self())));

        return Func::camel2snake(str_replace('Table', '', $className));
    }

    public static function getTableName() 
    {
        self::$modConf = include __DIR__ . '/../../mod_conf.php';

        $tableName = self::getTableNameFromClassName();
        if (!empty(self::$modConf['prefix'])) {
            $tableName = self::$modConf['prefix'] . $tableName;
        }

        return $tableName;
    }

    public static function getMap() 
    {
        return [
            // ID
            new Entity\IntegerField('id', [
                'primary' => true,
                'autocomplete' => true
            ]),

            // client_id
            new Entity\IntegerField('client_id', [
                'required' => true
            ]),

            // LINK on clients
            new Entity\ReferenceField(
                'client',
                '\Bitrix\Main\User',
                array('=this.client_id' => 'ref.ID'),
                array('join_type' => 'LEFT')
            ),

            // manager_id
            new Entity\IntegerField('manager_id', [
                'required' => true
            ]),

            // LINK on managers
            new Entity\ReferenceField(
                'manager',
                '\Bitrix\Main\User',
                array('=this.manager_id' => 'ref.ID'),
                array('join_type' => 'LEFT')
            ),

            // ticket_status_id
            new Entity\IntegerField('ticket_status_id', [
                'required' => true
            ]),

            // LINK on statuses
            new Entity\ReferenceField(
                'status',
                self::$modConf['nsTables'] . '\TicketStatus',
                array('=this.ticket_status_id' => 'ref.id'),
                array('join_type' => 'LEFT')
            ),

            // ticket_rating_id
            new Entity\IntegerField('ticket_rating_id', [
                'required' => true
            ]),

            // LINK on ratings
            new Entity\ReferenceField(
                'rating',
                self::$modConf['nsTables'] . '\TicketRating',
                array('=this.ticket_rating_id' => 'ref.id'),
                array('join_type' => 'LEFT')
            ),

            // ticket_category_id
            new Entity\IntegerField('ticket_category_id', [
                'required' => true
            ]),

            // LINK on categories
            new Entity\ReferenceField(
                'category',
                self::$modConf['nsTables'] . '\TicketCategory',
                array('=this.ticket_category_id' => 'ref.id'),
                array('join_type' => 'LEFT')
            ),

            // put_date
            new Entity\DatetimeField('put_date', [
                'required' => true
            ]),

            // upd_date
            new Entity\DatetimeField('upd_date', [
                'required' => true
            ]),

            // end_date
            new Entity\DatetimeField('end_date', [
                'required' => true
            ]),

            // name
            new Entity\StringField('name', [
                'required' => true
            ]),
        ];
    }
}
