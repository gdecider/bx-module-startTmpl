<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arCstmProps
 * пользовательские поля
 * элемент массива ["объект для добавления поля", "UF_код", "тип", "название"]
 */
$arCstmProps = [
    // кастомные поля пользователя
    ['USER', 'UF_FIO_DIR', 'string', 'ФИО Директора'],
    ['USER', 'UF_UR_ADR', 'string', 'Юридический адрес'],
];

/**
 * @var array $arTables
 * ORM таблицы
 * Элемент массива - "имя класса таблицы", сама таблица должна быть описана в /lib/tables/
 */
$arTables = [
    'TicketCategory',
    'TicketStatus',
    'TicketRating',
    'TicketCategory',
    'TicketMsg',
    'Ticket',
];

/**
 * @var array $arIndexes
 * индексы ORM таблиц
 * элемент массива - ["имя класса таблицы", "имя поля таблицы"]
 */
$arIndexes = [
    ['Ticket', 'client_id'],
    ['Ticket', 'manager_id'],
    ['Ticket', 'ticket_status_id'],
    ['Ticket', 'ticket_rating_id'],
    ['Ticket', 'ticket_category_id'],
    ['TicketMsg', 'ticket_id'],
    ['TicketMsg', 'sender_id'],
];

/**
 * @var array $arIblockTypes
 * типы инфоблоков
 */
$arIblockTypes = [
    'FORMS' => [
        'SECTIONS' => 'N',
        'SORT' => '100',
        'LANG' => [
            'ru' => [
                'NAME'=>'Формы модуля обмена',
//                'SECTION_NAME'=>'Sections',
                'ELEMENT_NAME'=>'Формы'
            ]
        ]
    ],
];

/**
 * @var array $arIblocks
 * инфоблоки
 */
$arIblocks = [
    'REGREQUESTS' => [
        'TYPE' => 'FORMS',
        'NAME' => 'Запросы на регистрацию',

        'PROPS' => [
            ['NAME' => 'ФИО', 'CODE' => 'FIO'],
            ['NAME' => 'Телефон', 'CODE' => 'PHONE'],
        ]
    ],
];

/**
 * @var array $arEmailTypes
 * почтовые события
 */
$arEmailTypes = [
    [
        "EVENT_NAME"  => "LOCALEXCH1C_REGREQUEST",
        "NAME"        => "Запрос на регистрацию",
        "LID"         => "ru",
        "SORT"        => 100,
        "DESCRIPTION" => "
            #FIO# - ФИО
            #PHONE# - Телефон
        "
    ]
];

/**
 * @var array $arEmailTmpls
 * почтовые шаблоны
 */
$arEmailTmpls = [
    [
        "ACTIVE" => "Y",
        "EVENT_NAME" => "LOCALEXCH1C_REGREQUEST",
        "EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
        "EMAIL_TO" => "#DEFAULT_EMAIL_FROM#",
        "BCC" => "",
        "SUBJECT" => "Запрос на регистрацию с сайта #SITE_NAME#",
        "BODY_TYPE" => "text",
        "MESSAGE" => "
ФИО: #FIO#
Телефон: #PHONE#
",
    ],
];

/**
 * @var array $arSalePersonTypes
 * типы плательщиков
 */
$arSalePersonTypes = [
    ['NAME' => 'Тестовый тип плательщика',],
];

/**
 * @var array $arSaleOrderPropsGroups
 * группы свойств заказа
 */
$arSaleOrderPropsGroups = [
    [
        "PERSON_TYPE_NAME" => 'Физическое лицо',
        'NAME' => 'Служебные',
    ],
];

/**
 * @var array $arSaleOrderProps
 * свойства заказа
 * Допустимые ключи:

    NAME - название свойства (тип плательщика зависит от сайта, а сайт - от языка; название должно быть на соответствующем языке);
    CODE - символьный код свойства.
    TYPE - тип свойства. Допустимые значения:
    CHECKBOX - флаг;
    TEXT - строка текста;
    SELECT - выпадающий список значений;
    MULTISELECT - список со множественным выбором;
    TEXTAREA - многострочный текст;
    LOCATION - местоположение;
    RADIO - переключатель.
    REQUIRED - флаг (Y/N) обязательное ли поле;
    DEFAULT_VALUE - значение по умолчанию;
    SORT - индекс сортировки;
    USER_PROPS - флаг (Y/N) входит ли это свойство в профиль покупателя;
    IS_LOCATION - флаг (Y/N) использовать ли значение свойства как местоположение покупателя для расчёта стоимости доставки (только для свойств типа LOCATION);
    IS_EMAIL - флаг (Y/N) использовать ли значение свойства как E-Mail покупателя;
    IS_PROFILE_NAME - флаг (Y/N) использовать ли значение свойства как название профиля покупателя;
    IS_PAYER - флаг (Y/N) использовать ли значение свойства как имя плательщика;
    IS_LOCATION4TAX - флаг (Y/N) использовать ли значение свойства как местоположение покупателя для расчёта налогов (только для свойств типа LOCATION);

    IS_FILTERED - свойство доступно в фильтре по заказам. С версии 10.0.
    IS_ZIP - использовать как почтовый индекс. С версии 10.0.
    IS_PHONE
    IS_ADDRESS

    DESCRIPTION - описание свойства;
    MULTIPLE

    UTIL - позволяет использовать свойство только в административной части. С версии 11.0.
 */
$arSaleOrderProps = [
    [
        "PERSON_TYPE_NAME" => 'Физическое лицо',
        "PROPS_GROUP_NAME" => 'Служебные',
        "NAME" => "Служебное Требуется передать в 1С",
        "TYPE" => "TEXT",
        "CODE" => "EXPORT_DO",
    ],

    [
        "PERSON_TYPE_NAME" => 'Физическое лицо',
        "PROPS_GROUP_NAME" => 'Служебные',
        "NAME" => "Служебное Получен из 1С",
        "TYPE" => "TEXT",
        "CODE" => "IS_IMPORTED",
    ],

    [
        "PERSON_TYPE_NAME" => 'Физическое лицо',
        "PROPS_GROUP_NAME" => 'Служебные',
        "NAME" => "Служебное дата запроса",
        "TYPE" => "TEXT",
        "CODE" => "EDIT_REQUEST_DT",
    ],

    [
        "PERSON_TYPE_NAME" => 'Физическое лицо',
        "PROPS_GROUP_NAME" => 'Служебные',
        "NAME" => "Служебное дата подтверждения",
        "TYPE" => "TEXT",
        "CODE" => "EDIT_RESPONS_DT",
    ],
];

$baseDir = basename(__DIR__);
$moduleName = strtoupper($baseDir);
$baseNS = 'Local';
$parts = explode('.', $baseDir);
$moduleNS = $baseNS . '\\' . ucfirst($parts[1]);

$arConfig = [
    'id' => strtolower($moduleName),
    'name' => $moduleName,
    'ns' => $moduleNS,
    'nsTables' => $moduleNS . '\Tables',
    'prefix' => 'local_modulename',
    'arCstmProps' => $arCstmProps,
    'arTables' => $arTables,
    'arIndexes' => $arIndexes,
    'arIblockTypes' => $arIblockTypes,
    'arIblocks' => $arIblocks,
    'arEmailTypes' => $arEmailTypes,
    'arEmailTmpls' => $arEmailTmpls,
    'arSalePersonTypes' => $arSalePersonTypes,
    'arSaleOrderPropsGroups' => $arSaleOrderPropsGroups,
    'arSaleOrderProps' => $arSaleOrderProps,
];

return $arConfig;
