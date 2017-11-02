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

    private $arTables = [
        'TicketCategory',
        'TicketStatus',
        'TicketRating',
        'TicketCategory',
        'TicketMsg',
        'Ticket',
    ];

    private $arIndexes = [
        ['tableClass' => 'Ticket', 'field' => 'client_id'],
        ['tableClass' => 'Ticket', 'field' => 'manager_id'],
        ['tableClass' => 'Ticket', 'field' => 'ticket_status_id'],
        ['tableClass' => 'Ticket', 'field' => 'ticket_rating_id'],
        ['tableClass' => 'Ticket', 'field' => 'ticket_category_id'],
        ['tableClass' => 'TicketMsg', 'field' => 'ticket_id'],
        ['tableClass' => 'TicketMsg', 'field' => 'sender_id'],
    ];

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

            $this->UnInstallDB();
            
            ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(Loc::getMessage($this->arModConf['name']."_UNINSTALL_TITLE"), $this->getPath()."/install/unstep2.php");
        }

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
            if (!Application::getConnection($tablePath::getConnectionName())
                ->isTableExists(Base::getInstance($tablePath)->getDBTableName())
            ) {
                Base::getInstance($tablePath)->createDbTable();
            }
        }

        // Создаем индексы
        $connection = Application::getConnection();
        foreach ($this->arIndexes as $arIndex) {

            $tblName = Base::getInstance($this->arModConf['nsTables'] . "\\" . $arIndex['tableClass'] . "Table")->getDBTableName();

            $sql = 'CREATE INDEX idx_' . $tblName . '_'. $arIndex['field']
                . ' on ' . $tblName . '('. $arIndex['field'] .')';

            $connection->queryExecute($sql);

        }

    }

    /**
     * Работа с базой данных при удалении модуля
     */
    public function UnInstallDB() {

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        Loader::includeModule($this->MODULE_ID);

        // Удаляем индексы
        $connection = Application::getConnection();
        foreach ($this->arIndexes as $arIndex) {

            $tblName = Base::getInstance($this->arModConf['nsTables'] . "\\" . $arIndex['tableClass'] . "Table")->getDBTableName();

            $sql = 'DROP INDEX idx_' . $tblName . '_'. $arIndex['field']
                . ' on ' . $tblName;

            $connection->queryExecute($sql);

        }

        if($request->get('savedata') != 'Y') {

            foreach ($this->arTables as $tableName) {
                $tablePath = $this->arModConf['ns'] . "\\Tables\\" . $tableName . "Table";

                Application::getConnection($tablePath::getConnectionName())
                    ->queryExecute('drop table if exists ' . Base::getInstance($tablePath)->getDBTableName());
            }

            // удаление сохраненных настроек модуля
            Option::delete($this->MODULE_ID);
        }

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

                    $filePath = $_SERVER["DOCUMENT_ROOT"] .$this->GetPath(true).'/admin/'.$item;

                    if(in_array($item, $this->exclAdminFiles) || !is_file($filePath)) {
                        continue;
                    }

                    $subName = str_replace('.','_',$this->MODULE_ID);
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$subName.'_'.$item,
                        '<'.'? require_once("'.$filePath.'");?'.'>');
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
}
