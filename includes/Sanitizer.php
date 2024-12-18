<?php
class Sanitizer {
    /**
     * Sanitize a string value
     */
    public static function string($value) {
        if (is_string($value)) {
            // Remove HTML and PHP tags
            $value = strip_tags($value);
            // Convert special characters to HTML entities
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            // Remove any null bytes
            $value = str_replace(chr(0), '', $value);
            return $value;
        }
        return '';
    }

    /**
     * Sanitize an email address
     */
    public static function email($email) {
        $email = self::string($email);
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize a numeric value
     */
    public static function number($value) {
        if (is_numeric($value)) {
            return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, 
                FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
        }
        return 0;
    }

    /**
     * Sanitize an integer value
     */
    public static function integer($value) {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize an array recursively
     */
    public static function array($array) {
        if (!is_array($array)) {
            return [];
        }

        $result = [];
        foreach ($array as $key => $value) {
            // Sanitize the key
            $key = self::string($key);

            // Recursively sanitize the value based on its type
            if (is_array($value)) {
                $result[$key] = self::array($value);
            } elseif (is_numeric($value)) {
                $result[$key] = self::number($value);
            } else {
                $result[$key] = self::string($value);
            }
        }
        return $result;
    }

    /**
     * Sanitize a URL
     */
    public static function url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize a filename
     */
    public static function filename($filename) {
        // Remove any directory components
        $filename = basename($filename);
        // Remove any null bytes and special characters
        $filename = str_replace([chr(0), '/', '\\', '..', ':'], '', $filename);
        // Only allow alphanumeric characters, dots, dashes and underscores
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    }

    /**
     * Sanitize SQL identifiers (table names, column names)
     */
    public static function sqlIdentifier($identifier) {
        // Only allow alphanumeric characters and underscores
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }
}
