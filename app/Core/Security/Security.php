<?php

namespace App\Core\Security;

class Security
{
    /**
     * Cross Site Scripting (XSS) Filter.
     * 
     * Sanitizes data to prevent XSS attacks.
     * Works recursively on arrays.
     *
     * @param string|array $data Data to sanitize
     * @return string|array Sanitized data
     */
    public static function xssClean($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = self::xssClean($val);
            }
            return $data;
        }

        if ($data === null) {
            return null;
        }

        // Basic XSS cleanup: remove invisible characters, encode HTML entities
        $data = self::removeInvisibleCharacters($data);
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Strip dangerous tags (script, iframe, object, embed, etc.)
        $dangerousTags = [
            'javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 
            'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 
            'ilayer', 'layer', 'bgsound', 'title', 'base'
        ];
        
        foreach ($dangerousTags as $tag) {
            $data = preg_replace("/<$tag\b[^>]*>(.*?)<\/$tag>/is", "", $data);
            $data = preg_replace("/<$tag\b[^>]*>/is", "", $data);
        }

        return $data;
    }

    /**
     * Remove arbitrary invisible characters (nulls, backspaces, etc.)
     */
    protected static function removeInvisibleCharacters($str)
    {
        $nonDisplayables = [
            '/%0[0-8bcef]/i',            // url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/i',             // url encoded 16-31
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'   // 00-08, 11, 12, 14-31, 127
        ];

        do {
            $str = preg_replace($nonDisplayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }
}
