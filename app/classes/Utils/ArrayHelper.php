<?php

namespace Utils;

/**
 * Api array helper
 */
class ArrayHelper
{
    /**
     * Check array contains element(s)
     * 
     * @param string|array $needle   needle
     * @param array        $haystack haystack
     * 
     * @return boolean
     */
    public function contains($needle, array $haystack)
    {
        $return = false;
        if (is_string($needle) || is_object($needle)) {
            if (in_array($needle, $haystack, true)) {
                $return = true;
            }
        }
        if (is_array($needle) && count(array_intersect($needle, $haystack)) == count($needle)) {
            $return = true;
        }
        return $return;
    }
}