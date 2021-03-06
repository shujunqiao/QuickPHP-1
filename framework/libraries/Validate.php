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
 * 数组和变量的验证。
 *
 * @category    QuickPHP
 * @package     Validate
 * @author      BoPo <ibopo@126.com>
 * @copyright   Copyright &copy; 2010 QuickPHP
 * @license     http://www.quickphp.net/license/
 * @version     $Id: Validate.php 8761 2012-01-15 05:10:59Z bopo $
 */
class QuickPHP_Validate extends ArrayObject
{

    // 字段过滤器
    protected $_filters   = array();

    // 字段规则
    protected $_rules     = array();

    // 字段回调函数
    protected $_callbacks = array();

    // 字段标签
    protected $_labels    = array();

    // 错误列表，格式: field     => rule
    protected $_errors    = array();

    // 即使执行规则的价值是空的
    protected $_empty_rules = array('not_empty', 'matches');

    /**
     * 创建一个验证实例.
     *
     * @param   array   要验证的数组数据
     * @return  Validate
     */
    public static function factory(array $array)
    {
        return new Validate($array);
    }

    /**
     * 验证一个字段是否非空.
     *
     * @return  boolean
     */
    public static function not_empty($value)
    {
        if(is_object($value) and $value instanceof ArrayObject)
        {
            $value = $value->getArrayCopy();
        }

        return ($value === '0' or ! empty($value));
    }

    /**
     * 验证一个字段是否符合正则规则
     *
     * @param   string  要验证的内容
     * @param   string  正则表达式
     * @return  boolean
     */
    public static function regex($value, $expression)
    {
        return (bool) preg_match($expression, (string) $value);
    }

    /**
     * 验证一个字段长度是否满足最小长度
     *
     * @param   string   需要判断的值
     * @param   integer  需要验证的最小长度
     * @return  boolean
     */
    public static function min_length($value, $length)
    {
        return Unicode::strlen($value) >= $length;
    }

    /**
     * 验证一个字段长度是否满足最大长度
     *
     * @param   string   需要判断的值
     * @param   integer  需要验证的最大长度
     * @return  boolean
     */
    public static function max_length($value, $length)
    {
        return Unicode::strlen($value) <= $length;
    }

    /**
     * 验证一个字段长度是否满足指定长度
     *
     * @param   string   需要判断的值
     * @param   integer  需要验证的长度
     * @return  boolean
     */
    public static function exact_length($value, $length)
    {
        return Unicode::strlen($value) === $length;
    }

    /**
     * 检查电子邮件地址是否为正确的格式。
     *
     * @link  http://www.iamcal.com/publish/articles/php/parsing_email/
     * @link  http://www.w3.org/Protocols/rfc822/
     *
     * @param   string   电子邮件地址
     * @param   boolean  是否严格兼容RFC值
     * @return  boolean
     */
    public static function email($email, $strict = false)
    {
        if($strict === true)
        {
            $qtext          = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
            $dtext          = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
            $atom           = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
            $pair           = '\\x5c[\\x00-\\x7f]';
            $domain_literal = "\\x5b($dtext|$pair)*\\x5d";
            $quoted_string  = "\\x22($qtext|$pair)*\\x22";
            $sub_domain     = "($atom|$domain_literal)";
            $word           = "($atom|$quoted_string)";
            $domain         = "$sub_domain(\\x2e$sub_domain)*";
            $local_part     = "$word(\\x2e$word)*";
            $expression     = "/^$local_part\\x40$domain$/D";
        }
        else
        {
            $expression = '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD';
        }

        return (bool) preg_match($expression, (string) $email);
    }

    /**
     * 检查该域是否存在有效MX记录
     *
     * @link  http://php.net/checkdnsrr  not added to Windows until PHP 5.3.0
     *
     * @param   string   电子邮件地址
     * @return  boolean
     */
    public static function email_domain($email)
    {
        return (bool) checkdnsrr(preg_replace('/^[^@]++@/', '', $email), 'MX');
    }

    /**
     * 验证是否是URL格式
     *
     * @param   string   URL
     * @return  boolean
     */
    public static function url($url)
    {
        if( ! preg_match('~^

            # scheme
            [-a-z0-9+.]++://

            # username:password (optional)
            (?:
                    [-a-z0-9$_.+!*\'(),;?&=%]++   # username
                (?::[-a-z0-9$_.+!*\'(),;?&=%]++)? # password (optional)
                @
            )?

            (?:
                # ip address
                \d{1,3}+(?:\.\d{1,3}+){3}+

                | # or

                # hostname (captured)
                (
                         (?!-)[-a-z0-9]{1,63}+(?<!-)
                    (?:\.(?!-)[-a-z0-9]{1,63}+(?<!-)){0,126}+
                )
            )

            # port (optional)
            (?::\d{1,5}+)?

            # path (optional)
            (?:/.*)?

            $~iDx', $url, $matches))
        {
            return false;
        }

        if( ! isset($matches[1]))
        {
            return true;
        }

        if(strlen($matches[1]) > 253)
        {
            return false;
        }

        $tld = ltrim(substr($matches[1], (int) strrpos($matches[1], '.')), '.');

        return ctype_alpha($tld[0]);
    }

    /**
     * 验证是否是一个IP格式
     *
     * @param   string   IP
     * @param   boolean  是否允许验证私有 IP
     * @return  boolean
     */
    public static function ip($ip, $allow_private = true)
    {
        $flags = FILTER_FLAG_NO_RES_RANGE;

        if($allow_private === false)
        {
            $flags = $flags | FILTER_FLAG_NO_PRIV_RANGE;
        }

        return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags);
    }


    /**
     * 检查判断是否是一个手机号码(中国格式)
     *
     * @param   string   需要验证的手机号码
     * @return  boolean
     */
    public static function mobile($number)
    {
    }

    /**
     * 测试是否是时间字符串格式
     *
     * @param   string   要验证的时间字符串
     * @return  boolean
     */
    public static function date($str)
    {
        return (strtotime($str) !== false);
    }

    /**
     * 检查一个字符串是否由字母的人物而已。
     *
     * @param   string   输入字符串
     * @param   boolean  trigger UTF-8 compatibility
     * @return  boolean
     */
    public static function alpha($str, $utf8 = false)
    {
        $str = (string) $str;

        if($utf8 === true)
        {
            return (bool) preg_match('/^\pL++$/uD', $str);
        }

        return ctype_alpha($str);
    }

    /**
     * 检查一个字符串是否由字母文字和数字而已。
     *
     * @param   string   输入字符串
     * @param   boolean  trigger UTF-8 compatibility
     * @return  boolean
     */
    public static function alpha_numeric($str, $utf8 = false)
    {
        if($utf8 === true)
        {
            return (bool) preg_match('/^[\pL\pN]++$/uD', $str);
        }

        return ctype_alnum($str);
    }

    /**
     * 检查一个字符串是否由字母字符,数字,强调、划而已。
     *
     * @param   string   输入字符串
     * @param   boolean  trigger UTF-8 compatibility
     * @return  boolean
     */
    public static function alpha_dash($str, $utf8 = false)
    {
        if($utf8 === true)
        {
            $regex = '/^[-\pL\pN_]++$/uD';
        }
        else
        {
            $regex = '/^[-a-z0-9_]++$/iD';
        }

        return (bool) preg_match($regex, $str);
    }

    /**
     * 检查一个字符串是否由数字组成的只有(没有点或破折号)。
     *
     * @param   string   输入字符串
     * @param   boolean  trigger UTF-8 compatibility
     * @return  boolean
     */
    public static function digit($str, $utf8 = false)
    {
        if($utf8 === true)
        {
            return (bool) preg_match('/^\pN++$/uD', $str);
        }

        return (is_int($str) and $str >= 0) or ctype_digit($str);
    }

    /**
     * 检查一个字符串是否为一个有效的数字
     *
     * Uses {@link http://www.php.net/manual/en/function.localeconv.php locale conversion}
     *
     * @param   string   输入字符串
     * @return  boolean
     */
    public static function numeric($str)
    {
        list($decimal) = array_values(localeconv());
        return (bool) preg_match('/^-?[0-9' . $decimal . ']++$/D', (string) $str);
    }

    /**
     * 考验,如果一个号码是在一定范围内。
     *
     * @param   string   number to check
     * @param   integer  minimum value
     * @param   integer  maximum value
     * @return  boolean
     */
    public static function range($number, $min, $max)
    {
        return ($number >= $min and $number <= $max);
    }

    /**
     * 判断一个字符串是否为指定位数的小数格式。 并且可以过滤置整数位数
     *
     * @param   string   number to check
     * @param   integer  number of decimal places
     * @param   integer  number of digits
     * @return  boolean
     */
    public static function decimal($str, $places = 2, $digits = null)
    {
        if($digits > 0)
        {
            $digits = '{' . (int) $digits . '}';
        }
        else
        {
            $digits = '+';
        }

        list($decimal) = array_values(localeconv());
        return (bool) preg_match('/^[0-9]' . $digits . preg_quote($decimal) . '[0-9]{' . (int) $places . '}$/D', $str);
    }

    /**
     * 判断一个字符串是否为一个正确的十六进制HTML颜色值。
     *
     * @param   string   输入字符串
     * @return  boolean
     */
    public static function color($str)
    {
        return (bool) preg_match('/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $str);
    }

    /**
     * 构造函数
     *
     * @param   array   要验证的数组
     * @return  void
     */
    public function __construct(array $array)
    {
        parent::__construct($array, ArrayObject::STD_PROP_LIST);
    }

    /**
     * 复制当前的验证器的规则,过滤器等内容到一个数组
     *
     * $copy = $array->copy($new_data);
     *
     * @param   array   new data set
     * @return  Validation
     * @since   3.0.5
     */
    public function copy(array $array)
    {
        $copy = clone $this;
        $copy->exchangeArray($array);

        return $copy;
    }

    /**
     * 返回数组表示当前对象。
     *
     * @return  array
     */
    public function as_array()
    {
        return $this->getArrayCopy();
    }

    /**
     * 将字段名标注为人更容易理解的注释
     *
     * @param   string  字段名称
     * @param   string  标注内容
     * @return  $this
     */
    public function label($field, $label)
    {
        $this->_labels[$field] = $label;
        return $this;
    }

    /**
     * 多重将字段名标注为人更容易理解的注释
     *
     * @param   array  字段名 =>  标注
     * @return  $this
     */
    public function labels(array $labels)
    {
        $this->_labels = $labels + $this->_labels;
        return $this;
    }

    /**
     * 设置过滤器
     *
     * // 例如全部字段使用 trim() 函数过滤
     * $validation->filter(true, 'trim');
     *
     * @param   string  字段名
     * @param   mixed   验证回调用的PHP函数
     * @param   array   回调函数所用的额外参数
     * @return  $this
     */
    public function filter($field, $filter, array $params = null)
    {
        if($field !== true and ! isset($this->_labels[$field]))
        {
            $this->_labels[$field] = preg_replace('/[^\pL]+/u', ' ', $field);
        }

        $this->_filters[$field][$filter] = (array) $params;
        return $this;
    }

    /**
     * 数组方式加入过滤器。
     *
     * @param   string  字段名称
     * @param   array   函数或静态的方法名列表
     * @return  $this
     */
    public function filters($field, array $filters)
    {
        foreach ($filters as $filter => $params)
        {
            $this->filter($field, $filter, $params);
        }

        return $this;
    }

    /**
     * 设置验证规则
     *
     * // The "username" must not be empty and have a minimum length of 4
     * $validation->rule('username', 'not_empty')
     * ->rule('username', 'min_length', array(4));
     *
     * @param   string  字段名称
     * @param   string  函数或静态方法名
     * @param   array   回调的额外参数
     * @return  $this
     */
    public function rule($field, $rule, array $params = null)
    {
        if($field !== true and ! isset($this->_labels[$field]))
        {
            $this->_labels[$field] = preg_replace('/[^\pL]+/u', ' ', $field);
        }

        $this->_rules[$field][$rule] = (array) $params;
        return $this;
    }

    /**
     * 多重设置验证规则
     *
     * @param   string  字段名称
     * @param   array   函数或静态方法名列表
     * @return  $this
     */
    public function rules($field, array $rules)
    {
        foreach ($rules as $rule => $params)
        {
            $this->rule($field, $rule, $params);
        }

        return $this;
    }

    /**
     * Adds a callback to a field. Each callback will be executed only once.
     * No extra parameters can be passed as the format for callbacks is
     * predefined as (Validate $array, $field, array $errors).
     *
     * // The "username" must be checked with a custom method
     * $validation->callback('username', array($this, 'check_username'));
     *
     * To add a callback to every field already set, use true for the 字段名称.
     *
     * @param   string  字段名称
     * @param   mixed   要增加的回调
     * @return  $this
     */
    public function callback($field, $callback, array $params = array())
    {
        if( ! isset($this->_callbacks[$field]))
        {
            $this->_callbacks[$field] = array();
        }

        if($field !== true and ! isset($this->_labels[$field]))
        {
            $this->_labels[$field] = preg_replace('/[^\pL]+/u', ' ', $field);
        }

        if( ! in_array($callback, $this->_callbacks[$field], true))
        {
            $this->_callbacks[$field][] = array($callback, $params);
        }

        return $this;
    }

    /**
     * 加回调用数组。
     *
     * @param   string  字段名称
     * @param   array   回调列表
     * @return  $this
     */
    public function callbacks($field, array $callbacks)
    {
        foreach ($callbacks as $callback)
        {
            $this->callback($field, $callback);
        }

        return $this;
    }

    /**
     * Executes all validation filters, rules, and callbacks. This should
     * typically be called within an if/else block.
     *
     * if ($validation->check())
     * {
     * // The data is valid, do something here
     * }
     *
     * @param   boolean   是否允许空的数组?
     * @return  boolean
     */
    public function check($allow_empty = false)
    {
        if(QuickPHP::$profiling === true)
        {
            $benchmark = Profiler::start('Validation', __FUNCTION__);
        }

        $data       = $this->_errors = array();
        $rules      = $this->_rules;
        $filters    = $this->_filters;
        $expected   = array_keys ($this->_labels);
        $submitted  = false;
        $callbacks  = $this->_callbacks;

        foreach ($expected as $field)
        {
            if(isset($this[$field]))
            {
                $submitted = true;
                $data[$field] = $this[$field];
            }
            else
            {
                $data[$field] = null;
            }

            if(isset($filters[true]))
            {
                if( ! isset($filters[$field]))
                {
                    $filters[$field] = array();
                }

                $filters[$field] += $filters[true];
            }

            if(isset($rules[true]))
            {
                if( ! isset($rules[$field]))
                {
                    $rules[$field] = array();
                }

                $rules[$field] += $rules[true];
            }

            if(isset($callbacks[true]))
            {
                if( ! isset($callbacks[$field]))
                {
                    $callbacks[$field] = array();
                }

                $callbacks[$field] += $callbacks[true];
            }
        }

        $this->exchangeArray($data);

        if($submitted === false)
        {
            return (boolean) $allow_empty;
        }

        unset($filters[true], $rules[true], $callbacks[true]);

        foreach ($filters as $field => $set)
        {
            $value = $this[$field];

            foreach ($set as $filter => $params)
            {
                array_unshift($params, $value);

                if(strpos($filter, '::') === false)
                {
                    $function = new ReflectionFunction($filter);
                    $value    = $function->invokeArgs($params);
                }
                else
                {
                    list($class, $method) = explode('::', $filter, 2);
                    $method = new ReflectionMethod($class, $method);
                    $value  = $method->invokeArgs(null, $params);
                }
            }

            $this[$field] = $value;
        }

        foreach ($rules as $field => $set)
        {
            $value = $this[$field];

            foreach ($set as $rule => $params)
            {
                if( ! in_array($rule, $this->_empty_rules) and ! Validate::not_empty($value))
                {
                    continue;
                }

                array_unshift($params, $value);

                if(method_exists($this, $rule))
                {
                    $method = new ReflectionMethod($this, $rule);

                    if($method->isStatic())
                    {
                        $passed = $method->invokeArgs(null, $params);
                    }
                    else
                    {
                        $passed = call_user_func_array(array($this, $rule), $params);
                    }
                }
                elseif(strpos($rule, '::') === false)
                {
                    $function = new ReflectionFunction($rule);
                    $passed   = $function->invokeArgs($params);
                }
                else
                {
                    list($class, $method) = explode('::', $rule, 2);
                    $method = new ReflectionMethod($class, $method);
                    $passed = $method->invokeArgs(null, $params);
                }

                if($passed === false)
                {
                    array_shift($params);
                    $this->error($field, $rule, $params);
                    break;
                }
            }
        }

        foreach ($callbacks as $field => $set)
        {
            if(isset($this->_errors[$field]))
            {
                continue;
            }

            foreach ($set as $callback_array)
            {
                list($callback, $params) = $callback_array;

                if(is_string($callback) and strpos($callback, '::') !== false)
                {
                    $callback = explode('::', $callback, 2);
                }

                if(is_array($callback))
                {
                    list($object, $method) = $callback;
                    $method = new ReflectionMethod($object, $method);

                    if( ! is_object($object))
                    {
                        $object = null;
                    }

                    $method->invoke($object, $this, $field, $params);
                }
                else
                {
                    $function = new ReflectionFunction($callback);
                    $function->invoke($this, $field, $params);
                }

                if(isset($this->_errors[$field]))
                {
                    break;
                }
            }
        }

        if(isset($benchmark))
        {
            Profiler::stop($benchmark);
        }

        return empty($this->_errors);
    }

    /**
     * 增加一个错误信息。
     *
     * @param   string  字段名称
     * @param   string  错误信息
     * @return  $this
     */
    protected function error($field, $error, array $params = null)
    {
        $this->_errors[$field] = array($error, $params);
        return $this;
    }

    /**
     * 返回错误信息集合
     *
     * $errors = $validate->errors('username');
     *
     * @uses    QuickPHP::message
     * @param   string  获取错误信息的字段名
     * @return  array
     */
    public function errors($index = null)
    {
        $messages = array();

        if(is_array($this->_errors) && count($this->_errors) > 0)
        {
            foreach ($this->_errors as $field => $set)
            {
                list($error, $params) = $set;
                $label = $this->_labels[$field];
                $values = array(':field' => $label, ':value' => $this[$field]);

                if(is_array($values[':value']))
                {
                    $values[':value'] = implode(', ', arr::flatten($values[':value']));
                }

                if($params)
                {
                    foreach ($params as $key => $value)
                    {
                        if(is_array($value))
                        {
                            $value = implode(', ', arr::flatten($value));
                        }

                        if(isset($this->_labels[$value]))
                        {
                            $value = $this->_labels[$value];
                        }

                        $values[':param' . ($key + 1)] = $value;
                    }
                }

                if($message = QuickPHP::message('validate', $error))
                {
                }
                else
                {
                    $message = "{$file}.{$field}.{$error}";
                }

                $message = strtr($message, $values);
                $messages[$field] = $message;
            }
        }

        if(!empty($index) && isset($messages[$index]))
            return $messages[$index];

        return $messages;
    }

    /**
     * 判断一个字段值是否匹配一个值。
     *
     * @param   string   字段值
     * @param   string   字段名称的匹配值
     * @return  boolean
     */
    protected function matches($value, $match)
    {
        return ($value === $this[$match]);
    }

}