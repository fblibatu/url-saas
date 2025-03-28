<?php
namespace App\Helpers;

class Config {
    private static $config = [];
    
    public static function load($file) {
        $path = __DIR__ . '/../../config/' . $file . '.php';
        
        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: {$file}");
        }
        
        self::$config[$file] = require $path;
    }
    
    public static function get($key, $default = null) {
        $parts = explode('.', $key);
        $file = array_shift($parts);
        
        if (!isset(self::$config[$file])) {
            self::load($file);
        }
        
        $value = self::$config[$file];
        
        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }
        
        return $value;
    }
    
    public static function set($key, $value) {
        $parts = explode('.', $key);
        $file = array_shift($parts);
        
        if (!isset(self::$config[$file])) {
            self::$config[$file] = [];
        }
        
        $pointer = &self::$config[$file];
        
        foreach ($parts as $part) {
            if (!isset($pointer[$part])) {
                $pointer[$part] = [];
            }
            $pointer = &$pointer[$part];
        }
        
        $pointer = $value;
    }
}