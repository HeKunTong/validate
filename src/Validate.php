<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/7/6
 * Time: 上午12:41
 */

namespace EasySwoole\Validate;


use EasySwoole\Spl\SplArray;

/**
 * 数据验证器
 * Class Validate
 * @package EasySwoole\Validate
 */
class Validate
{
    protected $columns = [];

    protected $error;

    function getError(): ?Error
    {
        return $this->error;
    }

    /**
     * 添加一个待验证字段
     * @param string $name
     * @param null|string $alias
     * @return Rule
     */
    public function addColumn(string $name, ?string $alias = null): Rule
    {
        $rule = new Rule();
        $this->columns[$name] = [
            'alias' => $alias,
            'rule'  => $rule
        ];
        return $rule;
    }

    /**
     * 验证字段是否合法
     * @param array $data
     * @return bool
     */
    function validate(array $data)
    {
        $spl = new SplArray($data);
        foreach ($this->columns as $column => $item) {
            /** @var Rule $rule */
            $rule = $item['rule'];
            $rules = $rule->getRuleMap();

            /*
             * 优先检测是否带有optional选项
             * 如果设置了optional又不存在对应字段，则跳过该字段检测
             */
            if (isset($rules['optional']) && !isset($data[$column])) {
                continue;
            }
            foreach ($rules as $rule => $ruleInfo) {
                if (!call_user_func([ $this, $rule ], $spl, $column, $ruleInfo['arg'])) {
                    $this->error = new Error($column, $spl->get($column), $item['alias'], $rule, $ruleInfo['msg'], $ruleInfo['arg']);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 给定的URL是否可以成功通讯
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function activeUrl(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_string($data)) {
            if (!filter_var($data, FILTER_VALIDATE_URL)) {
                return false;
            }
            return checkdnsrr(parse_url($data, PHP_URL_HOST));
        } else {
            return false;
        }
    }

    /**
     * 给定的参数是否是字母 即[a-zA-Z]
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function alpha(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_string($data)) {
            return preg_match('/^[a-zA-Z]+$/', $data);
        } else {
            return false;
        }
    }

    /**
     * 给定的参数是否在 $min $max 之间
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function between(SplArray $splArray, string $column, $args): bool
    {
        $data = $splArray->get($column);
        $min = array_shift($args);
        $max = array_shift($args);
        if (is_numeric($data) || is_string($data)) {
            if ($data <= $max && $data >= $min) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 给定参数是否为布尔值
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function bool(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (($data == 1) || ($data == 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 给定参数是否在某日期之前
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function dateBefore(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (empty($arg)) {
            $arg = date('ymd');
        }
        $beforeUnixTime = strtotime($arg);
        if (is_string($data)) {
            $unixTime = strtotime($data);
            if ($unixTime < $beforeUnixTime) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 给定参数是否在某日期之后
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function dateAfter(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (empty($arg)) {
            $arg = date('ymd');
        }
        $afterUnixTime = strtotime($arg);
        if (is_string($data)) {
            $unixTime = strtotime($data);
            if ($unixTime > $afterUnixTime) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证值是否相等
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function equal(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if ($data !== $arg) {
            return false;
        }
        return true;
    }

    /**
     * 验证值是否一个浮点数
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function float(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        return filter_var($data, FILTER_VALIDATE_FLOAT);
    }

    /**
     * 调用自定义的闭包验证
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function func(SplArray $splArray, string $column, $arg): bool
    {
        return call_user_func($arg, $splArray, $column);
    }

    /**
     * 值是否在数组中
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function inArray(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        return in_array($data, $arg);
    }

    /**
     * 是否一个整数值
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function integer(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        return filter_var($data, FILTER_VALIDATE_INT);
    }

    /**
     * 是否一个有效的IP
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function isIp(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        return filter_var($data, FILTER_VALIDATE_IP);
    }

    /**
     * 是否不为空
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function notEmpty(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if ($data === 0 || $data === '0') {
            return true;
        } else {
            return !empty($data);
        }
    }

    /**
     * 是否一个数字值
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function numeric(SplArray $splArray, string $column, $arg): bool
    {
        return is_numeric($splArray->get($column));
    }

    /**
     * 不在数组中
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function notInArray(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        return !in_array($data, $arg);
    }

    /**
     * 验证数组或字符串的长度
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function length(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data) || is_string($data)) {
            if (strlen($data) == $arg) {
                return true;
            } else {
                return false;
            }
        } else if (is_array($data)) {
            if (count($data) == $arg) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证数组或字符串的长度是否超出
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function lengthMax(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data) || is_string($data)) {
            if (strlen($data) <= $arg) {
                return true;
            } else {
                return false;
            }
        } else if (is_array($data)) {
            if (count($data) <= $arg) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证数组或字符串的长度是否达到
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function lengthMin(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data) || is_string($data)) {
            if (strlen($data) >= $arg) {
                return true;
            } else {
                return false;
            }
        } else if (is_array($data)) {
            if (count($data) >= $arg) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证值不大于(相等视为不通过)
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function max(SplArray $splArray, string $column, $arg): bool
    {
        if (!$this->numeric($splArray, $column, $arg)) {
            return false;
        }
        $data = $splArray->get($column);
        if ($data > intval($arg)) {
            return false;
        }
        return true;
    }

    /**
     * 验证值不小于(相等视为不通过)
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function min(SplArray $splArray, string $column, $arg): bool
    {
        if (!$this->numeric($splArray, $column, $arg)) {
            return false;
        }
        $data = $splArray->get($column);
        if ($data < intval($arg)) {
            return false;
        }
        return true;
    }

    /**
     * 设置值为可选参数
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function optional(SplArray $splArray, string $column, $arg)
    {
        return true;
    }

    /**
     * 正则表达式验证
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function regex(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data) || is_string($data)) {
            return preg_match($arg, $data);
        } else {
            return false;
        }
    }

    /**
     * 必须存在值
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function required(SplArray $splArray, string $column, $arg): bool
    {
        return isset($splArray[$column]);
    }

    /**
     * 值是一个合法的时间戳
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function timestamp(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        if (is_numeric($data)) {
            if (strtotime(date("d-m-Y H:i:s", $data)) === (int)$data) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 值是一个合法的链接
     * @param SplArray $splArray
     * @param string $column
     * @param $arg
     * @return bool
     */
    private function url(SplArray $splArray, string $column, $arg): bool
    {
        $data = $splArray->get($column);
        return filter_var($data, FILTER_VALIDATE_URL);
    }
}