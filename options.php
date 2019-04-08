<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\Base;

$arModConf = include __DIR__ . '/mod_conf.php';
// нужна для управления правами модуля
$module_id = strtolower($arModConf['name']);

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
Loc::loadMessages(__FILE__);

if($APPLICATION->GetGroupRight($module_id) < "R") {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

Loader::includeModule($module_id);

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$aTabs = [
    [// вкладка "Настройки"
        'DIV' => 'edit1', // Код вкладки
        'TAB' => Loc::getMessage($arModConf['name'] . '_TAB_SETTINGS'), // то что написано на табе
        'TITLE' => Loc::getMessage($arModConf['name'] . '_TAB_TITLE_SETTINGS'), // То что написано в области таба
        'OPTIONS' => [
//            'Разделительный заголовок',
//            [
//                'note' => 'Заметка на желтом фоне',
//            ],
//            [
//                'DB_AVTO_TABLE_NAME', // Имя поля
//                'Название таблицы Автомобилей в файле', // Подпись поля
//                'search_by_vehicle', // Значение по умолчанию
//                [
//                    'text',
//                    50, // длина
//                    'noautocomplete' => 1, // отключение автодополнения в браузере
//                ], // тип с настройками
//                'N', // Деактивировать (ReadOnly)
//            ],


            [
                'note_text', // Имя поля
                'Инструкция', // Подпись поля
                '
                    <b style="color:red;">Блок настроек модуля</b><br>
                    Заглушка страницы настроек модуля
                ', // Значение по умолчанию
                [
                    'statichtml',
                ], // тип с настройками
            ],

        ]
    ],
    [// вкладка "Права"
        'DIV' => 'edit2',
        'TAB' => Loc::getMessage($arModConf['name'] . '_TAB_RIGHTS'),
        'TITLE' => Loc::getMessage($arModConf['name'] . '_TAB_TITLE_RIGHTS'),
    ]
];

// сохранение

if ($request->isPost() && $request['update'] && check_bitrix_sessid()) {
    // Сохраняем настройки
    foreach ($aTabs as $aTab) {
        foreach ($aTab['OPTIONS'] as $arOption) {
            if (!is_array($arOption)) {
                continue;
            }

            // пропустим статические куски
            if ($arOption['note'] || in_array($arOption[3][0], ['statichtml', 'statictext'])) {
                continue;
            }

            $optionName = $arOption[0];
            $optionValue = $request->getPost(str_replace('.', '_', $optionName));

            Option::set($module_id, $optionName, is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
        }
    }

    // Что бы повторно не отправилась форма при обновлении страницы
    LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID . '&mid_menu=1&mid=' . urlencode($module_id) .
        '&tabControl_active_tab=' . urlencode($request['tabControl_active_tab']) . '&sid=' . SITE_ID);
}

// рисуем форму
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>

<?php $tabControl->Begin();?>
    <form method="POST"
          action="<?=$APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($request['mid'])?>&amp;lang=<?=$request['lang']?>"
          name="<?=strtolower($arModConf['name'])?>_settings"
          enctype="multipart/form-data">
        <? foreach($aTabs as $aTab): ?>
            <? if($aTab['OPTIONS']): ?>
                <? $tabControl->BeginNextTab(); ?>
                <? __AdmSettingsDrawList($module_id, $aTab['OPTIONS']); ?>
            <? endif; ?>
        <? endforeach; ?>

        <? /* Свое поле ?>
        <tr>
            <td class="adm-detail-content-cell-l" width="50%">БД АВТО для загрузки</td>
            <td class="adm-detail-content-cell-r" width="50%"><input type="file" name="FILE_SQL_AVTO"></td>
        </tr>
        <tr>
            <td class="adm-detail-content-cell-l" width="50%">БД ДИСКИ для загрузки</td>
            <td class="adm-detail-content-cell-r" width="50%"><input type="file" name="FILE_SQL_DISK"></td>
        </tr>
        <tr>
            <td class="adm-detail-content-cell-l" width="50%">БД ШИНЫ для загрузки</td>
            <td class="adm-detail-content-cell-r" width="50%"><input type="file" name="FILE_SQL_SHINA"></td>
        </tr>
        <? // */?>

        <? $tabControl->BeginNextTab(); ?>

        <? // функционал настройки прав доступа к модулю
        require_once ($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php');?>

        <? $tabControl->Buttons(); ?>

        <input type="submit" name="update" value="<?=Loc::getMessage('MAIN_SAVE')?>">
        <input type="reset" name="reset" value="<?=Loc::getMessage('MAIN_RESET')?>">
        <?=bitrix_sessid_post();?>
    </form>
<?php $tabControl->End();?>
