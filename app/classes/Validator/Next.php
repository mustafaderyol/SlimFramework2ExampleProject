<?php

namespace Validator;

use Closure;

/**
 * Parse rule parameters
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Next
{
    /**
     * Validator
     * 
     * @var object
     */
    protected $validator;

    /**
     * Set validator object
     * 
     * @param object $validator validator
     *
     * @return void
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * Returns to validator object
     *
     * @return void
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Call next rule
     * 
     * @param Field $field field
     * 
     * @return object|boolean
     */
    public function __invoke(FieldInterface $field)
    {
        $rule = $field->getRule();
        $ruleName = $rule->getNext();

        if (! empty($ruleName) && is_string($ruleName)) {
                              
            $ruleName = strtolower($ruleName);

            if (strpos($ruleName, '(') > 0) {
                $ruleName = $rule->parse($ruleName);
            }
            if (substr($ruleName, 0, 9) == 'callback_') {

                $callbacks = $this->getValidator()->getCallbacks(); // Is the rule has a callback?

                if (! array_key_exists($ruleName, $callbacks)) {

                    $error = sprintf(
                        "%s rule is not defined as callback method.",
                        $ruleName
                    );
                    $field->setError($error);
                }
                $result = $this->callNextClosure($field, $ruleName, $callbacks);

            } else {

                $result = $this->callNextClass($field, $ruleName);
            }

            if (false === $result) {
                $this->getValidator()->dispatchErrors($field, $ruleName);
            }
            return $result;
        }
        return true;
    }

    /**
     * Run next class
     * 
     * @param object $field    field
     * @param string $ruleName rule
     * 
     * @return mixed
     */
    protected function callNextClass(FieldInterface $field, $ruleName)
    {
        $ruleNames = $this->getValidator()->getRules();

        if (! array_key_exists($ruleName, $ruleNames)) {  // If rule does not exist.

            $error = sprintf(
                "%s rule is not defined in configuration file.",
                $ruleName
            );
            $field->setError($error);
            return false;

        } else {

            $Class = "\\".trim($ruleNames[$ruleName], '\\');
            $nextRule = new $Class;
            // $nextRule->setContainer($this->getValidator()->getContainer());
            
            return $nextRule($field, $this);
        }
    }

    /**
     * Run next callback
     * 
     * @param object $field     field
     * @param string $ruleName  rule
     * @param array  $callbacks callback stack
     * 
     * @return mixed
     */
    protected function callNextClosure(FieldInterface $field, $ruleName, $callbacks)
    {
        $validator = $this->getValidator();
        
        $next = Closure::bind(
            $callbacks[$ruleName],
            $validator,
            get_class($validator)
        );
        return $next($field, $this);
    }

}