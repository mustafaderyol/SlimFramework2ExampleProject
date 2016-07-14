<?php

namespace Utils;

/**
 * Api text helper
 */
class Text
{
    /**
     * Slugify - Supported languages: (TR)
     * 
     * @param string $text text
     * 
     * @see http://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string
     * 
     * @return string
     */
    public static function slugify($text)
    {
        $search  = array("ş","Ş","ı","ü","Ü","ö","Ö","ç","Ç","ş","Ş","ı","ğ","Ğ","İ","ö","Ö","Ç","ç","ü","Ü");
        $replace = array("s","S","i","u","U","o","O","c","C","s","S","i","g","G","I","o","O","C","c","u","U");
        $text    = str_replace($search, $replace, $text);

        $text = preg_replace('~[^\pL\d]+~u', '-', $text);     // replace non letter or digits by -
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);  // transliterate
        $text = preg_replace('~[^-\w]+~', '', $text);         // remove unwanted characters

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }

}