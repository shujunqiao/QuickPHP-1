<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 +----------------------------------------------------------------------+
 | QuickPHP Framework Version 0.10                                      |
 +----------------------------------------------------------------------+
 | Copyright (c) 2010 QuickPHP.net All rights reserved.                 |
 +----------------------------------------------------------------------+
 | Licensed under the Apache License, Version 2.0 (the 'License');      |
 | you may not use this file except in compliance with the License.     |
 | You may obtain a copy of the License at                              |
 | http://www.apache.org/licenses/LICENSE-2.0                           |
 | Unless required by applicable law or agreed to in writing, software  |
 | distributed under the License is distributed on an 'AS IS' BASIS,    |
 | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or      |
 | implied. See the License for the specific language governing         |
 | permissions and limitations under the License.                       |
 +----------------------------------------------------------------------+
 | Author: BoPo <ibopo@126.com>                                         |
 +----------------------------------------------------------------------+
*/

/** 系统常量 IN_PRODUCTION,产品模式开关，如果设置成 FALSE 则为开发模式 */
define('IN_PRODUCTION', false);

// 调试时使用，生产模式删除改行
if(isset($_GET['phpinfo']) and !IN_PRODUCTION) exit(phpinfo());

// 判断是否产品模式，产品模式不显示任何错误提示
if((bool) IN_PRODUCTION == false)
{
    error_reporting(E_ALL & ~ E_NOTICE);
    ini_set('error_displays', 'on');
}
else
{
    error_reporting(0);
    ini_set('error_displays', 'off');
}

if (defined('THIRDPARTY'))
{
    set_include_path(get_include_path().';'. THIRDPARTY);
}

version_compare(PHP_VERSION, '5.2', '<') and exit('QuickPHP requires PHP 5.2 or newer.');
version_compare(PHP_VERSION, '5.3', '<') and set_magic_quotes_runtime(0);

date_default_timezone_set('Asia/Shanghai'); // 设置默认时区
setlocale(LC_ALL, 'zh_CN.utf-8');           // 设置默认编码

require_once (SYSPATH . 'QuickPHP.php');

spl_autoload_register(array('QuickPHP', 'autoloader'));

/**
 * Enable the QuickPHP autoloader for unserialization.
 *
 * @see  http://php.net/spl_autoload_call
 * @see  http://php.net/manual/var.configuration.php#unserialize-callback-func
 */
ini_set('unserialize_callback_func', 'spl_autoload_call');

$settings = array(
    'profiling'  => false,                  // 开启分析器
    'log_error'  => true,                   // 开启log分析
    'errors'     => true,                   // 开启错误分析
    'caching'    => false,                   // 开启高速缓存
    'frontend'   => '',                     // 入口文件名(默认为index.php)
    'url_suffix' => 'html',
    'language'   => 'zh_CN',
    'domain'     => '/quickphp/',           // 网站域名
);

define('QUICKPHP_START_TIME', microtime(true));
define('QUICKPHP_START_MEMORY', memory_get_usage());

QuickPHP::instance($settings)->dispatch();
