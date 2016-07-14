<?php

namespace Validator\Rules;

use Validator\FieldInterface as Field;

/**
 * Matches
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Matches
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
        $matchField = $field->getRule()->getParam(0, '');

        global $container;
        $request = $container->get('request');
        $post = $request->getParsedBody();

        if (empty($post[$matchField]) || empty($matchField)) {
            return false;
        }
        if ($field->getValue() !== $post[$matchField]) {
            return false;
        }
        return $next($field);
    }
}