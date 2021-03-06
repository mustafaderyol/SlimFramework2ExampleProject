<?php

namespace Validator\Rules;

use Validator\FieldInterface as Field;

/**
 * Alnum unicode (https://github.com/vlucas/valitron/issues/79)
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class AlnumDashUnicode
{
    /**
     * Call next
     * 
     * @param Field    $field object
     * @param Callable $next  object
     * 
     * @return object
     */
    public function __invoke(Field $field, Callable $next)
    {
        if (preg_match("/^[\s0-9_\-\pL]+$/u", $field->getValue())) {

            return $next($field);
        }
        return false;
    }
}