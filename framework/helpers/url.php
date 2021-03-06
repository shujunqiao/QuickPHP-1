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
/**
 * QuickPHP URL相关助手
 *
 * @category    QuickPHP
 * @package     Helpers
 * @subpackage  url
 * @author      BoPo <ibopo@126.com>
 * @copyright   Copyright &copy; 2010 QuickPHP
 * @license     http://www.quickphp.net/license/
 * @version     $Id: url.php 8320 2011-10-05 14:59:55Z bopo $
 */
class QuickPHP_url
{
    /**
     * 获取网站的首页URL
     *
     * // 相对路径,不带主机或协议URL
     * echo url::base();
     *
     * // 绝对路径，带主机和协议
     * echo url::base(true, true);
     *
     * // 绝对路径，带主机和自定义“https”协议
     * echo url::base(true, 'https');
     *
     * @param   boolean  是否带URL的索引文件
     * @param   mixed    协议字符串,不写则为默认
     * @return  string
     * @uses    QuickPHP::$frontend
     * @uses    QuickPHP::$protocol
     */
    public static function base($index = true, $protocol = false)
    {
        $domain = QuickPHP::$domain;

        if($protocol === true)
        {
            $protocol = QuickPHP::$protocol;
        }

        if($index === true and ! empty(QuickPHP::$frontend))
        {
            $domain .= QuickPHP::$frontend;
        }

        if(is_string($protocol))
        {
            if(parse_url($domain, PHP_URL_HOST))
            {
                $domain = parse_url($domain, PHP_URL_PATH);
            }

            $domain = $protocol . '://' . $domain;
        }

        return rtrim($domain, '/');
    }

    /**
     * 将字符串转换成含有网站根目录的URI
     *
     * echo url::site('foo/bar'); 则返回 url::base()加上'foo/bar'的值
     *
     * @param   string  要转换的url
     * @param   mixed   协议字符串,不写则为默认
     * @return  string
     * @uses    url::base
     */
    public static function site($uri = '', $protocol = false)
    {
        $path   = '/' . trim(parse_url($uri, PHP_URL_PATH), '/');
        $query  = parse_url($uri, PHP_URL_QUERY);
        $suffix = '.' . trim(QuickPHP::$url_suffix, '.');

        if( ! empty($query))
        {
            $query = '?' . $query;
        }

        $fragment = parse_url($uri, PHP_URL_FRAGMENT);

        if( ! empty($fragment))
        {
            $fragment = '#' . $fragment;
        }

        return url::base(true, $protocol) . $path . $suffix . $query . $fragment;
    }

    /**
     * 目标数组于$_GET合并，并转换成请求字符串
     *
     * // 要得到这样的字符串： "?sort=title&limit=10"
     * $query = url::query(array('sort' => 'title', 'limit' => 10));
     *
     * 通常使用这个方法进行排序查询等场合.
     *
     * [!!] 参数为空则自动排除
     *
     * @param   array   array $params
     * @return  string
     */
    public static function query(array $params = null)
    {
        if($params === null)
        {
            $params = $_GET;
        }
        else
        {
            $params = array_merge($_GET, $params);
        }

        if(empty($params))
        {
            return '';
        }

        $query = http_build_query($params, '', '&');
        return ($query === '') ? '' : '?' . $query;
    }

    public static function http_bind()
    {
        $args = func_get_args();
        $uri  = implode("/", $args);

        return url::site($uri, 'http');
    }

    public static function https_bind()
    {
        $args = func_get_args();
        $uri  = implode("/", $args);

        return url::site($uri, 'https');
    }

    public static function bind()
    {
        $args = func_get_args();
        $uri  = implode("/", $args);

        return url::site($uri);
    }

    public static function domain($url)
    {
        $pattern = '/[\w-]+\.(com|net|org|gov|cc|biz|info|cn|co)(\.(cn|hk))*/';
        $pattern = preg_match($pattern, $url, $matches);

        if(count($matches) > 0)
        {
            return $matches[0];
        }
        else
        {
            $rs = parse_url($url);
            $main_url = $rs["host"];

            if(! strcmp((sprintf("%u", ip2long($main_url))), $main_url))
            {
                return $main_url;
            }
            else
            {
                $arr    = explode(".", $main_url);
                $count  = count($arr);
                $endarr = array("com", "net", "org", "3322"); //com.cn  net.cn 等情况

                if(in_array($arr[$count - 2], $endarr))
                {
                    $domain = $arr[$count - 3] . "." . $arr[$count - 2] . "." . $arr[$count - 1];
                }
                else
                {
                    $domain = $arr[$count - 2] . "." . $arr[$count - 1];
                }

                return $domain;
            }
        }
    }

    public static function encode($url)
    {
        return urlencode($url);
    }

    /**
     * 发送一个重定向页面
     *
     * @param  mixed   重定向URI
     * @param  string  HTTP 方法
     * @return void
     */
    public static function redirect($uri = '', $method = '')
    {
        if(strpos($uri, '://') === false)
        {
            $uri = url::site($uri, true);
        }

        switch ($method)
        {
            case 'refresh' :
                header("Refresh:0;url=" . $uri);
                break;
            default :
                header('HTTP/1.1 301 Moved Permanently');
                header("Location:" . $uri);
                break;
        }
    }
}