<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Sale\Internals\OrderPropsTable;

Loc::loadMessages(__FILE__);

class local_modexample extends CModule
{
    /** @var string */
    public $MODULE_ID;

    /** @var string */
    public $MODULE_VERSION;

    /** @var string */
    public $MODULE_VERSION_DATE;

    /** @var string */
    public $MODULE_NAME;

    /** @var string */
    public $MODULE_DESCRIPTION;

    /** @var string */
    public $MODULE_GROUP_RIGHTS;

    /** @var string */
    public $PARTNER_NAME;

    /** @var string */
    public $PARTNER_URI;

    /** @var string */
    public $SHOW_SUPER_ADMIN_GROUP_RIGHTS;

    /** @var string */
    public $MODULE_NAMESPACE;

    protected $exclAdminFiles;
    protected $arModConf;

    protected $PARTNER_CODE;
    protected $MODULE_CODE;
    
    private $arTables = [];

    private $arIndexes = [];

    private $arCstmProps = [];
    
    private $arIblockTypes = [];
    
    private $arIblocks = [];
    
    private $arEmailTypes = [];

    private $arEmailTmpls = [];
    
    private $arSalePersonTypes = [];

    private $arSaleOrderPropsGroups = [];

    private $arSaleOrderProps = [];

    private $siteId;

    public function __construct(){

        $arModuleVersion = [];
        include __DIR__.'/version.php';

        $this->arModConf = include __DIR__ . '/../mod_conf.php';

        $this->exclAdminFiles = [
            '..',
            '.',
            'menu.php',
            'operation_description.php',
            'task_description.php',
        ];
        
        if ($this->arModConf['arCstmProps']) {
            $this->arCstmProps = $this->arModConf['arCstmProps'];
        }

        if ($this->arModConf['arTables']) {
            $this->arTables = $this->arModConf['arTables'];
        }

        if ($this->arModConf['arIndexes']) {
            $this->arIndexes = $this->arModConf['arIndexes'];
        }
        
        if ($this->arModConf['arIblockTypes']) {
            $this->arIblockTypes = $this->arModConf['arIblockTypes'];
        }
        
        if ($this->arModConf['arIblocks']) {
            $this->arIblocks = $this->arModConf['arIblocks'];
        }
        
        if ($this->arModConf['arEmailTypes']) {
            $this->arEmailTypes = $this->arModConf['arEmailTypes'];
        }

        if ($this->arModConf['arEmailTmpls']) {
            $this->arEmailTmpls = $this->arModConf['arEmailTmpls'];
        }
        
        if ($this->arModConf['arSalePersonTypes']) {
            $this->arSalePersonTypes = $this->arModConf['arSalePersonTypes'];
        }

        if ($this->arModConf['arSaleOrderPropsGroups']) {
            $this->arSaleOrderPropsGroups = $this->arModConf['arSaleOrderPropsGroups'];
        }

        if ($this->arModConf['arSaleOrderProps']) {
            $this->arSaleOrderProps = $this->arModConf['arSaleOrderProps'];
        }

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_ID = strtolower($this->arModConf['name']);
        $this->MODULE_NAME = Loc::getMessage($this->arModConf['name'].'_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage($this->arModConf['name'].'_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage($this->arModConf['name'].'_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage($this->arModConf['name'].'_PARTNER_URI');
        $this->MODULE_NAMESPACE = $this->arModConf['ns'];

        $this->MODULE_GROUP_RIGHTS = 'Y';
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = 'Y';

        $this->PARTNER_CODE = $this->getPartnerCodeByModuleID();
        $this->MODULE_CODE = $this->getModuleCodeByModuleID();
        
        $rsSites = CSite::GetList($by="sort", $order="desc", ['ACTIVE' => 'Y']);
        $arSite = $rsSites->Fetch();
        $this->siteId = $arSite['ID'];
    }

    /**
     * Получение актуального пути к модулю с учетом многосайтовости
     * Как вариант можно использовать более производительную функцию str_pos
     * Недостатком данного метода является возможность "ложных срабатываний".
     * В том случае если в пути встретится два раза последовательность
     * local/modules или bitrix/modules.
     *
     * @param bool $notDocumentRoot
     * @return mixed|string
     */
    protected function getPath($notDocumentRoot = false) {
        return  ($notDocumentRoot)
            ? preg_replace('#^(.*)\/(local|bitrix)\/modules#','/$2/modules',dirname(__DIR__))
            : dirname(__DIR__);
    }

    /**
     * Получение кода партнера из ID модуля
     * @return string
     */
    protected function getPartnerCodeByModuleID() {
        $delimeterPos = strpos($this->MODULE_ID, '.');
        $pCode = substr($this->MODULE_ID, 0, $delimeterPos);

        if (!$pCode) {
            $pCode = $this->MODULE_ID;
        }

        return $pCode;
    }

    /**
     * Получение кода модуля из ID модуля
     * @return string
     */
    protected function getModuleCodeByModuleID() {
        $delimeterPos = strpos($this->MODULE_ID, '.') + 1;
        $mCode = substr($this->MODULE_ID, $delimeterPos);

        if (!$mCode) {
            $mCode = $this->MODULE_ID;
        }

        return $mCode;
    }

    /**
     * Проверка версии ядра системы
     *
     * @return bool
     */
    protected function isVersionD7() {
        return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
    }

    /**
     * Установка модуля
     */
    public function DoInstall() {
        global $APPLICATION;

        if ($this->isVersionD7()) {

            ModuleManager::registerModule($this->MODULE_ID);

            try {
                $this->InstallDB();
                $this->InstallIblocks();
                $this->InstallProps();
                $this->InstallSalePersonTypes();
                $this->InstallSaleOrderPropsGroups();
                $this->InstallSaleOrderProps();
                $this->InstallEmails();
                $this->InstallEvents();
                $this->InstallFiles();
                $this->InstallTasks();

                $APPLICATION->IncludeAdminFile(Loc::getMessage($this->arModConf['name'].'_INSTALL_TITLE'), $this->getPath() . "/install/step.php");

            } catch (Exception $e) {
                ModuleManager::unRegisterModule($this->MODULE_ID);
                $APPLICATION->ThrowException('Произошла ошибка при установке ');
            }

        } else {
            $APPLICATION->ThrowException(Loc::getMessage($this->arModConf['name']."_INSTALL_ERROR_WRONG_VERSION"));
        }

    }

    /**
     * Удаление модуля
     */
    public function DoUnInstall() {
        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        if ($request->get('step') < 2) {
            $APPLICATION->IncludeAdminFile(Loc::getMessage($this->arModConf['name']."_UNINSTALL_TITLE"), $this->getPath()."/install/unstep1.php");
        } elseif($request->get('step') == 2) {

            $this->UnInstallEvents();
            $this->UnInstallFiles();
            $this->UnInstallTasks();

            if($request->get('savedata') != 'Y') {
                $this->UnInstallDB();
            }
            
            if($request->get('saveprops') != 'Y') {
                $this->UnInstallProps();
            }
            
            if($request->get('savesaleprops') != 'Y') {
                $this->UnInstallSalePersonTypes();
                $this->UnInstallSaleOrderPropsGroups();
                $this->UnInstallSaleOrderProps();
            }
            
            if($request->get('saveiblocks') != 'Y') {
                $this->UnInstallIblocks();
                $this->UnInstallEmails();
            }
            
            ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(Loc::getMessage($this->arModConf['name']."_UNINSTALL_TITLE"), $this->getPath()."/install/unstep2.php");
        }

    }
    
    /**
     * Проверка индекса на существование
     * @param $tblName
     * @param $idxName
     * @param null $connection
     * @return bool
     */
    static public function isIdxExists($tblName, $idxName, $connection = null) {
        if (!$connection) {
            $connection = Application::getConnection();
        }

        // получим имя БД
        // как вариант можно так
        // $arCons = \Bitrix\Main\Config\Configuration::getValue('connections');
        // $dbName = $arCons['default']['database'];

        // но мы получим так
        $dbName = $connection->getDatabase();

        $sqlCheck = "SELECT count(1) idx_exist
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE table_schema = '".$dbName."'
                AND   table_name   = '".$tblName."'
                AND   index_name   = '".$idxName."'";

        $idxCnt = $connection->queryScalar($sqlCheck);

        return $idxCnt > 0;
    }

    /**
     * Работа с базой данных при установке модуля
     */
    public function InstallDB() {

        Loader::includeModule($this->MODULE_ID);

        // Создание таблицы обращений
        // TODO: переписать на получение файлов из папки lib\tables, все что в ней - таблицы

        foreach ($this->arTables as $tableName) {
            $tablePath = $this->arModConf['ns'] . "\\Tables\\" . $tableName . "Table";
            $connection = Application::getConnection($tablePath::getConnectionName());

            if (!$connection->isTableExists(Base::getInstance($tablePath)->getDBTableName())
            ) {
                Base::getInstance($tablePath)->createDbTable();
            }

            // Создаем индексы
            foreach ($this->arIndexes as $arIndex) {
                if ($arIndex[0] !== $tableName) {
                    continue;
                }

                $tblName = Base::getInstance($this->arModConf['nsTables'] . "\\" . $arIndex[0] . "Table")->getDBTableName();
                $idxName = 'idx_' . $tblName . '_'. $arIndex[1];
                if (self::isIdxExists($tblName, $idxName, $connection)) {
                    continue;
                }

                $sql = 'CREATE INDEX ' . $idxName
                    . ' on ' . $tblName . '(`'. $arIndex[1] .'`)';

                $connection->queryExecute($sql);

            }
        }
    }

    /**
     * Работа с базой данных при удалении модуля
     */
    public function UnInstallDB() {

        Loader::includeModule($this->MODULE_ID);

        // Удаляем индексы
        $connection = Application::getConnection();
        foreach ($this->arIndexes as $arIndex) {
            $tblName = Base::getInstance($this->arModConf['nsTables'] . "\\" . $arIndex[0] . "Table")->getDBTableName();
            $idxName = 'idx_' . $tblName . '_'. $arIndex[1];

            if (!self::isIdxExists($tblName, $idxName, $connection)) {
                continue;
            }

            $sql = 'DROP INDEX ' . $idxName
                . ' on ' . $tblName;

            $connection->queryExecute($sql);

        }

        // удаляем таблицы
        foreach ($this->arTables as $tableName) {
            $tablePath = $this->arModConf['ns'] . "\\Tables\\" . $tableName . "Table";

            Application::getConnection($tablePath::getConnectionName())
                ->queryExecute('drop table if exists ' . Base::getInstance($tablePath)->getDBTableName());
        }

        // удаление сохраненных настроек модуля
        Option::delete($this->MODULE_ID);

        return true;

    }

    /**
     * Работа с файлами при установке модуля
     */
    public function InstallFiles() {
        // Копируем компоненты в папки ядра, переименовывая их по шаблону КОД_МОДУЛЯ.ИМЯ_КОМПОНЕНТА
        if (Directory::isDirectoryExists($path = $this->GetPath() . '/install/components')) {
            if ($dir = opendir($path)) {
                while(false !== ($item = readdir($dir))) {

                    $compPath = $path .'/'. $item;

                    if(in_array($item, ['.', '..']) || !is_dir($compPath)) {
                        continue;
                    }
                    $newPath = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/components/" . $this->PARTNER_CODE . '/' . $this->MODULE_CODE  . '.' . $item;
                    CopyDirFiles($compPath, $newPath, true, true);
                }
                closedir($dir);
            }
        }

        // Копируем и создаем файлы с включениями административных страниц в ядро
        if (Directory::isDirectoryExists($path = $this->GetPath() . '/admin')) {
            CopyDirFiles($this->GetPath() . "/install/admin", $_SERVER['DOCUMENT_ROOT'] . "/bitrix/admin");

            if ($dir = opendir($path)) {

                while(false !== $item = readdir($dir)) {

                    $filePathRelative = $this->GetPath(true).'/admin/'.$item;
                    $filePathFull = $_SERVER["DOCUMENT_ROOT"] . $filePathRelative;

                    if (in_array($item, $this->exclAdminFiles) || !is_file($filePathFull)) {
                        continue;
                    }

                    $subName = str_replace('.','_',$this->MODULE_ID);
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$subName.'_'.$item,
                        '<'.'? require_once($_SERVER[\'DOCUMENT_ROOT\'] . "'.$filePathRelative.'");?'.'>');
                }
                closedir($dir);
            }
        }
    }

    /**
     * Работа с файлами при удалении модуля
     * @return bool
     */
    public function UnInstallFiles() {

        // Удалим файлы компонентов модуля, основываясь на принцепе их именования по шаблону КОД_МОДУЛЯ.ИМЯ_КОМПОНЕНТА
        if($this->PARTNER_CODE && $this->MODULE_CODE) {

            if (Directory::isDirectoryExists($partnerPath = $_SERVER['DOCUMENT_ROOT']. '/bitrix/components/' . $this->PARTNER_CODE)) {
                if ($dir = opendir($partnerPath)) {

                    while (false !== ($item = readdir($dir))) {
                        // имя папки компонента начитается с кода нашего модуля?
                        $isModuleComponent = (0 === strpos($item, $this->MODULE_CODE . '.'));
                        $compPath = $partnerPath . '/' . $item;

                        if (!$isModuleComponent || in_array($item, ['.', '..']) || !is_dir($compPath)) {
                            continue;
                        }

                        Directory::deleteDirectory($compPath);
                    }
                }
            }
        }

        // Удалим файлы подключений административных страниц
        if (Directory::isDirectoryExists($path = $this->GetPath() . '/admin')) {
            DeleteDirFiles($_SERVER["DOCUMENT_ROOT"] . $this->getPath() . '/install/admin/', $_SERVER["DOCUMENT_ROOT"] . '/bitrix/admin');

            if ($dir = opendir($path)) {
                while (false !== $item = readdir($dir)) {
                    if (in_array($item, $this->exclAdminFiles)) {
                        continue;
                    }

                    $subName = str_replace('.','_',$this->MODULE_ID);
                    File::deleteFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$subName.'_'.$item);
                }
                closedir($dir);
            }
        }

        return true;

    }

    /**
     * Работа с событиями при установке модуля
     * @return bool
     */
    public function InstallEvents() {
        return true;
    }

    /**
     * Работа с событиями при удалении модуля
     * @return bool
     */
    public function UnInstallEvents() {
        return true;
    }

    /**
     * Работа со списками задач при установке модуля
     * @return bool
     */
    public function InstallTasks() {
        return true;
    }

    /**
     * Работа со списками задач при удалении модуля
     * @return bool
     */
    public function UnInstallTasks() {
        return true;
    }
    
    /**
     * Работа с полями инфоблоков и сущностей
     * @return bool
     */
    public function InstallProps() {

        // получим список пользовательских полей
        $arSort = ['ENTITY_ID' => 'ASC'];
        $arAddCstmProps = [];
        foreach ($this->arCstmProps as $arCstmProp) {

            $arFilter = [
                'ENTITY_ID' => $arCstmProp[0],
                'FIELD_NAME' => $arCstmProp[1],
            ];

            $dbRes = CUserTypeEntity::GetList( $arSort, $arFilter );
            $arRes = $dbRes->Fetch();

            if(!$arRes) {
                $arAddCstmProps[] = $arCstmProp;
            }
        }

        $oUserTypeEntity = new CUserTypeEntity();

        foreach ($arAddCstmProps as $arAddCstmProp) {

            $aUserFields = [
                'ENTITY_ID' => $arAddCstmProp[0],
                'FIELD_NAME' => $arAddCstmProp[1],
                'USER_TYPE_ID' => $arAddCstmProp[2],
                'XML_ID' => 'XML_ID_' . $arAddCstmProp[0] . '_' . $arAddCstmProp[1] . '_FIELD',
                'SORT' => 500,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => '',
                'EDIT_IN_LIST' => '',
                'IS_SEARCHABLE' => 'N',
                /*
                * Дополнительные настройки поля (зависят от типа).
                * В нашем случае для типа string
                */
                'SETTINGS' => array(
                    'DEFAULT_VALUE' => '',
                    'SIZE' => '20',
                    'ROWS' => '1',
                    'MIN_LENGTH' => '0',
                    'MAX_LENGTH' => '0',
                    'REGEXP' => '',
                ),
                /* Подпись в форме редактирования */
                'EDIT_FORM_LABEL' => array(
                    'ru' => $arAddCstmProp[3],
                    'en' => $arAddCstmProp[3],
                ),
                /* Заголовок в списке */
                'LIST_COLUMN_LABEL' => array(
                    'ru' => $arAddCstmProp[3],
                    'en' => $arAddCstmProp[3],
                ),
                /* Подпись фильтра в списке */
                'LIST_FILTER_LABEL' => array(
                    'ru' => $arAddCstmProp[3],
                    'en' => $arAddCstmProp[3],
                ),
                /* Сообщение об ошибке (не обязательное) */
                'ERROR_MESSAGE' => array(
                    'ru' => 'Ошибка при заполнении пользовательского свойства ' . $arAddCstmProp[3],
                    'en' => 'Ошибка при заполнении пользовательского свойства ' . $arAddCstmProp[3],
                ),
                /* Помощь */
                'HELP_MESSAGE' => array(
                    'ru' => '',
                    'en' => '',
                ),
            ];

            $iUserFieldId = $oUserTypeEntity->Add($aUserFields);
        }

        return true;
    }

    /**
     * Работа с полями инфоблоков и сущностей
     * @return bool
     */
    public function UnInstallProps()
    {
        // получим список пользовательских полей
        $arSort = ['ENTITY_ID' => 'ASC'];
        $oUserTypeEntity = new CUserTypeEntity();

        foreach ($this->arCstmProps as $arCstmProp) {

            $arFilter = [
                'ENTITY_ID' => $arCstmProp[0],
                'FIELD_NAME' => $arCstmProp[1],
            ];

            $dbRes = CUserTypeEntity::GetList( $arSort, $arFilter );
            $arRes = $dbRes->Fetch();

            if(!$arRes) {
                continue;
            }

            $oUserTypeEntity->Delete( $arRes["ID"] );
        }

        return true;
    }
    
    /**
     * Работа с инфоблоками
     * @return bool
     * @throws Exception
     */
    public function InstallIblocks() {
        $db = $this->getDB();

        // создаем типы инфоблоков
        foreach ($this->arIblockTypes as $IBTypeCODE => $arIblockType) {

            $ibtCode = strtolower($this->arModConf['prefix'] . '_' . $IBTypeCODE);
            $dbIbt = CIBlockType::GetByID($ibtCode);
            $arIbt = $dbIbt->GetNext();

            if($arIbt) {
                continue;
            }

            $arFields = [
                'ID' => $ibtCode,
                'SECTIONS' => $arIblockType['SECTIONS'],
                'IN_RSS' => 'N',
                'SORT' => $arIblockType['SORT'],
                'LANG' => $arIblockType['LANG'],
            ];

            $obBlocktype = new \CIBlockType();
            $db->StartTransaction();
            $res = $obBlocktype->Add($arFields);

            if(!$res) {
                $db->Rollback();

                // TODO: изменить возврат сообщения об ошибке
                echo 'Error: '.$obBlocktype->LAST_ERROR.'<br>';
            } else {
                $db->Commit();
            }

        }

        // создаем инфоблоки
        foreach ($this->arIblocks as $IBCODE => $arIblock) {

            $ibCode = strtolower($this->arModConf['prefix'] . '_' . $IBCODE);
            $ibtCode = strtolower($this->arModConf['prefix'] . '_' . $arIblock['TYPE']);

            $ib = new CIBlock();
            $arFields = Array(
                "ACTIVE" => 'Y',
                "NAME" => $arIblock['NAME'],
                "CODE" => $ibCode,
                "IBLOCK_TYPE_ID" => $ibtCode,
                "SITE_ID" => [$this->siteId],
                "LID" => $this->siteId,
                "SORT" => 1000,
                "WORKFLOW" => 'N',
                //"GROUP_ID" => Array("2"=>"D", "3"=>"R")
            );

            $ibId = $ib->Add($arFields);

            if ($ibId > 0) {
                // добавляем свойства
                foreach ($arIblock['PROPS'] as $arProp) {

                    $dbProperties = CIBlockProperty::GetList([], ["IBLOCK_ID" => $ibId, 'CODE' => $arProp['CODE']]);
                    if ($dbProperties->SelectedRowsCount() > 0) {
                        continue;
                    }

                    $ibp = new CIBlockProperty;

                    $arFields = Array(
                        "NAME" => $arProp['NAME'],
                        "ACTIVE" => "Y",
                        "SORT" => 100, // Сортировка
                        "CODE" => $arProp['CODE'],
                        "PROPERTY_TYPE" => "S", // Строка
                        "ROW_COUNT" => 1, // Количество строк
                        "COL_COUNT" => 60, // Количество столбцов
                        "IBLOCK_ID" => $ibId
                    );
                    $propId = $ibp->Add($arFields);

                    if (!$propId) {
                        \Bitrix\Main\Diag\Debug::dump($ibp->LAST_ERROR);
                        die();
                    }
                }
            } else {
                \Bitrix\Main\Diag\Debug::dump($ib->LAST_ERROR);
                die();
            }

        }

        return true;
    }

    /**
     * Удаление инфоблоков
     * @return bool
     */
    public function UnInstallIblocks() {
        $db = $this->getDB();

        // удаляем инфоблоки
        foreach ($this->arIblocks as $IBCODE => $arIblock) {

            $ibCode = strtolower($this->arModConf['prefix'] . '_' . $IBCODE);
            $ibtCode = strtolower($this->arModConf['prefix'] . '_' . $arIblock['TYPE']);
            $arOrder = [];
            $arFilter = ['TYPE' => $ibtCode, 'CODE' => $ibCode];
            $dbIBList = CIBlock::GetList($arOrder, $arFilter);

            if ($dbIBList->SelectedRowsCount() == 1) {

                $arIBList = $dbIBList->GetNext();

                $db->StartTransaction();
                if (!CIBlock::Delete($arIBList['ID'])) {
                    $db->Rollback();
                    echo 'Delete error!';
                } else {
                    $db->Commit();
                }
            }
        }

        // удаляем типы инфоблоков
        foreach ($this->arIblockTypes as $IBTypeCODE => $arIblockType) {

            $ibtCode = strtolower($this->arModConf['prefix'] . '_' . $IBTypeCODE);
            $dbIbt = CIBlockType::GetByID($ibtCode);
            $arIbt = $dbIbt->GetNext();

            if(!$arIbt) {
                continue;
            }

            $db->StartTransaction();
            if(!CIBlockType::Delete($ibtCode)) {
                $db->Rollback();
                echo 'Delete error!';
            } else {
                $db->Commit();
            }
        }

        return true;
    }
    
    /**
     * Создание почтовых событий и шаблонов
     * @return bool
     */
    public function InstallEmails() {

        // Создаем почтовые события
        $obEventType = new CEventType();
        foreach ($this->arEmailTypes as $arEmailType) {
            $eid = $obEventType->Add($arEmailType);

            if(!$eid) {
                $obEventType->LAST_ERROR;
                die();
            }
        }
        unset($obEventType);

        // Создаем почтовые шаблоны
        $obTemplate = new CEventMessage();
        foreach ($this->arEmailTmpls as $arEmailTmpl) {
            $arEmailTmpl["LID"] = $this->siteId;
            $tid = $obTemplate->Add($arEmailTmpl);

            if(!$tid) {
                $obTemplate->LAST_ERROR;
                die();
            }
        }
        unset($obTemplate);

        return true;
    }

    /**
     * Удаление почтовых событий и шаблонов
     * @return bool
     */
    public function UnInstallEmails() {

        // Удаляем почтовые шаблоны
        $obTemplate = new CEventMessage();
        foreach ($this->arEmailTmpls as $arEmailTmpl) {
            $arFilter = [
                "TYPE_ID" => $arEmailTmpl["EVENT_NAME"],
                "SUBJECT" => $arEmailTmpl["SUBJECT"],
            ];
            $rsMess = CEventMessage::GetList($by="site_id", $order="desc", $arFilter);

            while ($arMess = $rsMess->GetNext()) {
                $obTemplate->Delete($arMess["ID"]);
            }
        }
        unset($obTemplate);

        // Удаляем почтовые события
        $obEventType = new CEventType();
        foreach ($this->arEmailTypes as $arEmailType) {
            $obEventType->Delete($arEmailType["EVENT_NAME"]);
        }
        unset($obEventType);


        return true;
    }
    
    /**
     * Создание типы плательщиков
     * @return bool
     */
    public function InstallSalePersonTypes()
    {
        if( !Loader::includeModule('sale') ) {
            return true;
        }

        foreach ($this->arSalePersonTypes as $arPersTypeFields) {
            $arPersTypeFields['ACTIVE'] = 'Y';
            $arPersTypeFields['SORT'] = 100;
            $arPersTypeFields['LID'] = $this->siteId;

            $arOrder = [];
            $arFilter = ['ACTIVE' => 'Y', 'NAME' => $arPersTypeFields['NAME']];
            $arSelect = ['ID', 'NAME'];

            $dbPersTypes = CSalePersonType::GetList($arOrder, $arFilter, false, false, $arSelect);

            $arPersType = $dbPersTypes->GetNext();

            if ($arPersType) {
                $persTypeID = $arPersType['ID'];
            } else {
                $persTypeID = (new CSalePersonType())->Add($arPersTypeFields);
            }
        }

        return true;
    }

    /**
     * Удаление типы плательщиков
     * @return bool
     */
    public function UnInstallSalePersonTypes()
    {
        if( !Loader::includeModule('sale') ) {
            return true;
        }

        foreach ($this->arSalePersonTypes as $arPersTypeFields) {
            $arOrder = [];
            $arFilter = ['ACTIVE' => 'Y', 'NAME' => $arPersTypeFields['NAME']];
            $arSelect = ['ID', 'NAME'];

            $dbPersTypes = CSalePersonType::GetList($arOrder, $arFilter, false, false, $arSelect);

            while ($arPersType = $dbPersTypes->GetNext()) {
                (new CSalePersonType())->Delete($arPersType['ID']);
            }
        }

        return true;
    }

    /**
     * Создание группы свойств заказа
     * @return bool
     */
    public function InstallSaleOrderPropsGroups()
    {
        if( !Loader::includeModule('sale') ) {
            return true;
        }

        $arPTs = $this->getPersonTypesArray();
        $arOPGs = $this->getSaleOrderPropsGroupsArray();

        foreach ($this->arSaleOrderPropsGroups as $arPropGroupFields) {

            if(isset($arOPGs[$arPropGroupFields['NAME']]) || !isset($arPTs[$arPropGroupFields['PERSON_TYPE_NAME']])) {
                continue;
            }

            $arPropGroupFields["PERSON_TYPE_ID"] = $arPTs[$arPropGroupFields['PERSON_TYPE_NAME']]["ID"];
            $propGroupID = (new CSaleOrderPropsGroup())->Add($arPropGroupFields);
        }

        return true;
    }

    /**
     * Удаление группы свойств заказа
     * @return bool
     */
    public function UnInstallSaleOrderPropsGroups()
    {
        if( !Loader::includeModule('sale') ) {
            return true;
        }

        $arOPGs = $this->getSaleOrderPropsGroupsArray();

        foreach ($this->arSaleOrderPropsGroups as $arPropGroupFields) {

            if(!isset($arOPGs[$arPropGroupFields['NAME']])) {
                continue;
            }

            (new CSaleOrderPropsGroup())->Delete($arOPGs[$arPropGroupFields['NAME']]['ID']);
        }

        return true;
    }

    public function getPersonTypesArray($arOrder = [], $arFilter = [], $arSelect = ['ID', 'NAME'])
    {
        if( !Loader::includeModule('sale') ) {
            return true;
        }

        $dbPTs = CSalePersonType::GetList($arOrder, $arFilter, false, false, $arSelect);

        $arPTs = [];
        while ($arPersType = $dbPTs->GetNext()) {
            $arPTs[$arPersType['NAME']] = $arPersType;
        }

        return $arPTs;
    }

    public function getSaleOrderPropsGroupsArray($arOrder = [], $arFilter = [], $arSelect = ['ID', 'NAME'])
    {
        if( !Loader::includeModule('sale') ) {
            return true;
        }

        $dbOPGs = CSaleOrderPropsGroup::GetList($arOrder, $arFilter, false, false, $arSelect);

        $arOPGs = [];
        while ($arOrderPropsGroup = $dbOPGs->GetNext()) {
            $arOPGs[$arOrderPropsGroup['NAME']] = $arOrderPropsGroup;
        }

        return $arOPGs;
    }

    /**
     * Создание свойства заказа
     * @return bool
     */
    public function InstallSaleOrderProps()
    {
        if( !Loader::includeModule('sale') ) {
            return true;
        }

        // получим список всех типов плательщиков
        $arPTs = $this->getPersonTypesArray();

        // получим список всех групп заказа
        $arOPGs = $this->getSaleOrderPropsGroupsArray();

        $arOrderPropDefaults = [
            "REQUIRED" => "N",
            "DEFAULT_VALUE" => "N",
            "SORT" => 100,

            "USER_PROPS" => "N",
            "IS_LOCATION" => "N",
            "IS_LOCATION4TAX" => "N",
            "IS_EMAIL" => "N",
            "IS_PROFILE_NAME" => "N",
            "IS_PAYER" => "N",
            "IS_FILTERED" => "N",
            "IS_ZIP" => "N",
            "IS_PHONE" => "N",
            "IS_ADDRESS" => "N",
            "DESCRIPTION" => "",
            "MULTIPLE" => "N",
            "UTIL" => "N",
        ];

        $arFilter = [
            'LOGIC' => 'OR',
        ];

        $arSelect = ['ID', 'CODE', 'NAME', 'TYPE'];

        foreach ($this->arSaleOrderProps as $key => $arOrderProp) {
            if(!isset($arPTs[$arOrderProp["PERSON_TYPE_NAME"]]) || !isset($arOPGs[$arOrderProp["PROPS_GROUP_NAME"]])) {
                continue;
            }
            $this->arSaleOrderProps[$key]["PERSON_TYPE_ID"] = $arPTs[$arOrderProp["PERSON_TYPE_NAME"]]["ID"];
            $this->arSaleOrderProps[$key]["PROPS_GROUP_ID"] = $arOPGs[$arOrderProp["PROPS_GROUP_NAME"]]["ID"];

            unset($this->arSaleOrderProps[$key]["PERSON_TYPE_NAME"]);
            unset($this->arSaleOrderProps[$key]["PROPS_GROUP_NAME"]);

            $this->arSaleOrderProps[$key] = array_merge($arOrderPropDefaults, $this->arSaleOrderProps[$key]);

            // собираем фильтр для определения наличия свойств
            $arFilter[] = [
                'PERSON_TYPE_ID' => $this->arSaleOrderProps[$key]["PERSON_TYPE_ID"],
                'TYPE' => $arOrderProp["TYPE"],
                'CODE' => $arOrderProp["CODE"],
            ];
        }

        $dbSOPs = OrderPropsTable::getList([
            'select' => $arSelect,
            'filter' => $arFilter,
        ]);

        $arSOPs = [];
        while ($arSOP = $dbSOPs->fetch()) {
            $arSOPs[$arSOP['CODE']] = $arSOP;
        }

        foreach ($this->arSaleOrderProps as $arOrderProp) {

            if(isset($arSOPs[$arOrderProp['CODE']])) {
                continue;
            }
            
            $arOrderProp = CSaleOrderPropsAdapter::convertOldToNew($arOrderProp);
            $arOrderProp = array_intersect_key($arOrderProp, CSaleOrderPropsAdapter::$allFields);

            $res = OrderPropsTable::add($arOrderProp);

            if(!$res->isSuccess()) {
                echo $res->getErrorMessages();
            }
        }

        return true;
    }

    /**
     * Удаление свойства заказа
     * @return bool
     */
    public function UnInstallSaleOrderProps()
    {
        if( !Loader::includeModule('sale') ) {
            return true;
        }

        // получим список всех типов плательщиков
        $arPTs = $this->getPersonTypesArray();

        // получим список всех групп заказа
        $arOPGs = $this->getSaleOrderPropsGroupsArray();

        $arFilter = [
            'LOGIC' => 'OR',
        ];

        $arSelect = ['ID', 'CODE', 'NAME', 'TYPE'];

        foreach ($this->arSaleOrderProps as $key => $arOrderProp) {
            if(!isset($arPTs[$arOrderProp["PERSON_TYPE_NAME"]]) || !isset($arOPGs[$arOrderProp["PROPS_GROUP_NAME"]])) {
                continue;
            }
            $this->arSaleOrderProps[$key]["PERSON_TYPE_ID"] = $arPTs[$arOrderProp["PERSON_TYPE_NAME"]]["ID"];
            $this->arSaleOrderProps[$key]["PROPS_GROUP_ID"] = $arOPGs[$arOrderProp["PROPS_GROUP_NAME"]]["ID"];

            // собираем фильтр для определения наличия свойств
            $arFilter[] = [
                'PERSON_TYPE_ID' => $this->arSaleOrderProps[$key]["PERSON_TYPE_ID"],
                'TYPE' => $arOrderProp["TYPE"],
                'CODE' => $arOrderProp["CODE"],
            ];
        }

        $dbSOPs = OrderPropsTable::getList([
            'select' => $arSelect,
            'filter' => $arFilter,
        ]);

        $arSOPs = [];
        while ($arSOP = $dbSOPs->fetch()) {
            $arSOPs[$arSOP['CODE']] = $arSOP;
        }

        foreach ($this->arSaleOrderProps as $arOrderProp) {

            if(!isset($arSOPs[$arOrderProp['CODE']])) {
                continue;
            }

            OrderPropsTable::delete($arSOPs[$arOrderProp['CODE']]['ID']);

        }

        return true;
    }
}
