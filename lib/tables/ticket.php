<?php
namespace Local\Modexample\Tables;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type;

class TicketTable extends Entity\DataManager
{
    private static $modConf = [];

    public static function getTableName() {
        self::$modConf = include __DIR__ . '/../../mod_conf.php';

        return self::$modConf['prefix'] . '_tickets';
    }

    public static function getMap() {
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