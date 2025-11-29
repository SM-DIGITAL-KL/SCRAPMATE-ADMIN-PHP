<?php

namespace App\Helpers;

class EnvReader
{
    /**
     * Read environment variable from env.txt file
     * Falls back to .env if env.txt doesn't exist
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $envFile = base_path('env.txt');
        $fallbackFile = base_path('.env');
        
        // Try env.txt first
        if (file_exists($envFile)) {
            $value = self::readFromFile($envFile, $key);
            if ($value !== null) {
                return $value;
            }
        }
        
        // Fallback to .env
        if (file_exists($fallbackFile)) {
            $value = self::readFromFile($fallbackFile, $key);
            if ($value !== null) {
                return $value;
            }
        }
        
        // Fallback to env() helper (Laravel's default)
        return env($key, $default);
    }
    
    /**
     * Read a specific key from an env file
     * 
     * @param string $filePath
     * @param string $key
     * @return string|null
     */
    public static function readFromFile($filePath, $key)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Check if line contains the key
            if (strpos($line, $key . '=') === 0) {
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $value = trim($parts[1]);
                    // Remove quotes if present
                    $value = trim($value, '"\'');
                    return $value;
                }
            }
        }
        
        return null;
    }
}

