<?php
/**
 * 时间工具类
 * 用于处理UTC时间到东八区时间的转换
 */
class TimeHelper {
    
    /**
     * 将UTC时间转换为东八区时间
     * @param string $utcTime UTC时间字符串
     * @param string $format 输出格式，默认为 'Y-m-d H:i:s'
     * @return string 东八区时间字符串
     */
    public static function utcToCst($utcTime, $format = 'Y-m-d H:i:s') {
        if (empty($utcTime) || $utcTime === '-') {
            return '-';
        }
        
        try {
            // 创建UTC时区的DateTime对象
            $utcDateTime = new DateTime($utcTime, new DateTimeZone('UTC'));
            
            // 转换为东八区时间
            $utcDateTime->setTimezone(new DateTimeZone('Asia/Shanghai'));
            
            return $utcDateTime->format($format);
        } catch (Exception $e) {
            // 如果转换失败，返回原始时间
            return $utcTime;
        }
    }
    
    /**
     * 将东八区时间转换为UTC时间
     * @param string $cstTime 东八区时间字符串
     * @param string $format 输出格式，默认为 'Y-m-d H:i:s'
     * @return string UTC时间字符串
     */
    public static function cstToUtc($cstTime, $format = 'Y-m-d H:i:s') {
        if (empty($cstTime) || $cstTime === '-') {
            return '-';
        }
        
        try {
            // 创建东八区时区的DateTime对象
            $cstDateTime = new DateTime($cstTime, new DateTimeZone('Asia/Shanghai'));
            
            // 转换为UTC时间
            $cstDateTime->setTimezone(new DateTimeZone('UTC'));
            
            return $cstDateTime->format($format);
        } catch (Exception $e) {
            // 如果转换失败，返回原始时间
            return $cstTime;
        }
    }
    
    /**
     * 获取当前东八区时间
     * @param string $format 输出格式，默认为 'Y-m-d H:i:s'
     * @return string 当前东八区时间
     */
    public static function now($format = 'Y-m-d H:i:s') {
        $now = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
        return $now->format($format);
    }
    
    /**
     * 获取当前UTC时间
     * @param string $format 输出格式，默认为 'Y-m-d H:i:s'
     * @return string 当前UTC时间
     */
    public static function nowUtc($format = 'Y-m-d H:i:s') {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        return $now->format($format);
    }
    
    /**
     * 格式化时间显示，自动转换为东八区
     * @param string $utcTime UTC时间字符串
     * @param string $format 输出格式，默认为 'Y-m-d H:i:s'
     * @return string 格式化后的东八区时间
     */
    public static function format($utcTime, $format = 'Y-m-d H:i:s') {
        return self::utcToCst($utcTime, $format);
    }
    
    /**
     * 获取相对时间描述（如：刚刚、5分钟前等）
     * @param string $utcTime UTC时间字符串
     * @return string 相对时间描述
     */
    public static function timeAgo($utcTime) {
        if (empty($utcTime) || $utcTime === '-') {
            return '-';
        }
        
        try {
            $utcDateTime = new DateTime($utcTime, new DateTimeZone('UTC'));
            $utcDateTime->setTimezone(new DateTimeZone('Asia/Shanghai'));
            
            $now = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
            $diff = $now->diff($utcDateTime);
            
            if ($diff->days > 0) {
                return $diff->days . '天前';
            } elseif ($diff->h > 0) {
                return $diff->h . '小时前';
            } elseif ($diff->i > 0) {
                return $diff->i . '分钟前';
            } else {
                return '刚刚';
            }
        } catch (Exception $e) {
            return $utcTime;
        }
    }
}
