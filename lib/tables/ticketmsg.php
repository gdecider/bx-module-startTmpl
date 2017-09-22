<?php
namespace Local\Modexample\Tables;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type;

class TicketMsgTable extends Entity\DataManager
{

    private static $modConf = [];

    public static function getTableName() {
        self::$modConf = include __DIR__ . '/../../mod_conf.php';

        return self::$modConf['prefix'] . '_ticket_msgs';
    }

    public static function getMap() {
        return [
            // ID
            new Entity\IntegerField('id', [
                'primary' => true,
                'autocomplete' => true
            ]),

            // ticket_id
            new Entity\IntegerField('ticket_id', [
                'required' => true
            ]),

            // LINK on tickets
            new Entity\ReferenceField(
                'ticket',
                self::$modConf['nsTables'] . '\Ticket',
                array('=this.ticket_id' => 'ref.id'),
                array('join_type' => 'LEFT')
            ),

            // sender_id
            new Entity\IntegerField('sender_id', [
                'required' => true
            ]),

            // LINK on senders
            new Entity\ReferenceField(
                'sender',
                '\Bitrix\Main\User',
                array('=this.sender_id' => 'ref.ID'),
                array('join_type' => 'LEFT')
            ),

            // put_date
            new Entity\DatetimeField('put_date', [
                'required' => true
            ]),

            // msg
            new Entity\TextField('msg', [
                'required' => true
            ]),
        ];
    }
}