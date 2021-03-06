<?php

define('SYSPATH', dirname(__FILE__).'/../../../framework/');
define('APPPATH', dirname(__FILE__).'/../../../protected/');

require_once 'phing/Task.php';
require_once 'phing/tasks/system/PropertyTask.php';

class QuickphpDBCTask extends PropertyTask
{
    /**
    * Execute lint check against PhingFile or a FileSet
    */
    public function main()
    {
        $config = $this->getConfig();

        $this->addProperty('quickphp.db.driver',   $config['type']);
        $this->addProperty('quickphp.db.hostname', $config['connection']['hostname']);
        $this->addProperty('quickphp.db.username', $config['connection']['username']);
        $this->addProperty('quickphp.db.password', $config['connection']['password']);
        $this->addProperty('quickphp.db.database', $config['connection']['database']);
    }

    /**
     * @return string Quickphp database config
     */
    private function getConfig()
    {
        $coreFile = APPPATH . 'config/database.php';
        $config   = include $coreFile;

        if(isset($config['default']))
        {
            return $config['default'];
        }
    }
}
