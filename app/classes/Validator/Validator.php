<?php

namespace Validator;

use Closure;
use RuntimeException;
use Interop\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Validator
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Validator implements ValidatorInterface
{
    protected $config;
    protected $logger;
    protected $container;
    protected $requestParams;
    protected $fieldData  = array();
    protected $errorArray = array();
    protected $formErrors = array(); 
    protected $errorPrefix = '<div>';
    protected $errorSuffix = '</div>';
    protected $errorString = '';
    protected $safeFormData = false;
    protected $validation = false;
    protected $callbackFunctions = array();
    protected $ruleArray = array();

    /**
     * Constructor
     * 
     * @param Container $container ContainerInterface
     * @param Request   $request   ServerRequestInterface
     */
    public function __construct(Container $container, Request $request)
    {
        mb_internal_encoding('UTF-8');

        $this->container = $container;
        $this->requestParams = $request->getParsedBody();
        $this->ruleArray = [
            'alpha' => 'Validator\Rules\Alpha',
            'alphaunicode' => 'Validator\Rules\AlphaUnicode',
            'alphadash' => 'Validator\Rules\AlphaDash',
            'alnum' => 'Validator\Rules\Alnum',
            'alnumunicode' => 'Validator\Rules\AlnumUnicode',
            'alnumdash' => 'Validator\Rules\AlnumDash',
            'alnumdashunicode' => 'Validator\Rules\AlnumDashUnicode',
            'csrf' => 'Validator\Rules\Csrf',
            'captcha' => 'Validator\Rules\Captcha',
            'contains' => 'Validator\Rules\Contains',
            'recaptcha' => 'Validator\Rules\ReCaptcha',
            'date' => 'Validator\Rules\Date',
            'email' => 'Validator\Rules\Email',
            'exact' => 'Validator\Rules\Exact',
            'iban' => 'Validator\Rules\Iban',
            'isbool' => 'Validator\Rules\IsBool',
            'isdecimal' => 'Validator\Rules\IsDecimal',
            'isjson' => 'Validator\Rules\IsJson',
            'isnumeric' => 'Validator\Rules\IsNumeric',
            'matches' => 'Validator\Rules\Matches',
            'max' => 'Validator\Rules\Max',
            'md5' => 'Validator\Rules\Md5',
            'min' => 'Validator\Rules\Min',
            'required' => 'Validator\Rules\Required',
            'trim' => 'Validator\Rules\Trim'
        ];
    }

    /**
     * Clear object variables 
     * 
     * @return void
     */
    public function clear()
    {
        $this->fieldData     = array();
        $this->formErrors    = array();
        $this->errorArray    = array();
        $this->errorPrefix   = '';
        $this->errorSuffix   = '';
        $this->errorString   = '';
        $this->safeFormData  = false;
        $this->validation    = false;
    }

    /**
     * Set Rules
     *
     * This function takes an array of field names && validation
     * rules as input, validates the info, && stores it
     *
     * @param mixed  $field input fieldname
     * @param string $label input label
     * @param mixed  $rules rules string
     * 
     * @return void
     */
    public function setRules($field, $label = '', $rules = '')
    {        
        if (count($this->requestParams) == 0) {  // No reason to set rules if we have no POST data
            return;
        }
                                 // If an array was passed via the first parameter instead of indidual string
        if (is_array($field)) {  // values we cycle through it && recursively call this function.
            foreach ($field as $row) {
                if (! isset($row['field']) || ! isset($row['rules'])) { //  if we have a problem...
                    continue;
                }
                $label = ( ! isset($row['label'])) ? $this->createLabel($row['field']) : $row['label']; // If the field label wasn't passed we use the field's name
                $this->setRules($row['field'], $label, $row['rules']);  // Here we go!
            }
            return;
        }
        if (! is_string($field) || ! is_string($rules) || $field == '') { // No fields ? Nothing to do...
            return;
        }
        $label = ($label == '') ? $this->createLabel($field) : $label;  // If the field label wasn't passed we use the field name

        // Is the field name an array?  We test for the existence of a bracket "(" in
        // the field name to determine this.  If it is an array, we break it apart
        // into its components so that we can fetch the corresponding POST data later
        // 
        $this->fieldData[$field] = array(
                                            'field'    => $field, 
                                            'label'    => $label, 
                                            'rules'    => trim($rules, '|'),  // remove uneccessary pipes
                                            'postdata' => null,
                                            'error'    => '',
                                        );
    }

    /**
     * Run the Validator
     *
     * This function does all the work.
     *
     * @return bool
     */        
    public function isValid()
    {
        if (empty($this->requestParams)) { // Do we even have any data to process ?
            return false;
        }
        if (count($this->fieldData) == 0) {    // We're we able to set the rules correctly ?
            $this->setMessage('Unable to find validation rules');
            return true;
        }
        // Cycle through the rules for each field, match the 
        // corresponding $this->requestParams item && test for errors
        // 
        foreach ($this->fieldData as $row) {
            if (isset($row['rules'])) {
                $row['rules'] = explode('|', $row['rules']);
                $this->execute($row);
            } 
        }
        $totalErrors = count($this->errorArray) + count($this->formErrors);         // Did we end up with any errors?
        if ($totalErrors > 0) {
            $this->safeFormData = true;
        }
        $this->resetPostArray();    // Now we need to re-set the POST data with the new, processed data

        if ($totalErrors == 0) {    // No errors, validation passes !
            $this->validation = true;
            return true;
        }
        return false;         // Validation fails
    }

    /**
     * Re-populate the _POST array with our finalized && processed data
     *
     * @return void
     */        
    protected function resetPostArray()
    {
        foreach ($this->fieldData as $row) {

            if (isset($row['postdata']) && ! is_null($row['postdata'])) {

                $field = $row['field'];

                if (isset($this->requestParams[$field])) {
                    $this->requestParams[$field] = $this->prepForForm($row['postdata']);
                }
            }
        }
    }

    /**
     * Prep data for form
     *
     * This function allows HTML to be safely shown in a form.
     * Special characters are converted.
     *
     * @param array $data prep data
     * 
     * @return string
     */
    public function prepForForm($data = '')
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = $this->prepForForm($val);
            }
            return $data;
        }
        if ($this->safeFormData == false || $data === '') {
            return $data;
        }
        return str_replace(
            array("'", '"', '<', '>'),
            array("&#39;", "&quot;", '&lt;', '&gt;'),
            stripslashes($data)
        );
    }

    /**
     * Executes the Validation routines
     * 
     * @param array $row field row 
     * 
     * @return void
     */
    protected function execute($row)
    {                   
        $field = $row['field'];
        if (strpos($field, '[') > 0) {
            $newField = str_replace('[]', '', $field);
            if (isset($this->requestParams[$newField]) && $this->requestParams[$newField] != '') {
                $row['postdata'] = $this->fieldData[$field]['postdata'] = $this->requestParams[$newField];
            } 
            
        } else {
            if (isset($this->requestParams[$field]) && $this->requestParams[$field] != '') {
                $row['postdata'] = $this->fieldData[$field]['postdata'] = $this->requestParams[$field];
            }
        }
        $field = new Field($row);
        $field->setValidator($this);

        $next = new Next;
        $next->setValidator($this);
        $next($field);
    }

    /**
     * Returns to callback functions
     * 
     * @return array
     */
    public function getCallbacks()
    {
        return $this->callbackFunctions;
    }

    /**
     * Dispatch errors
     * 
     * @param Field  $field object
     * @param string $rule  name
     * 
     * @return void
     */
    public function dispatchErrors(FieldInterface $field, $rule)
    {        
        $fieldName = $field->getName();
        $label     = $field->getLabel();
        $param     = $field->getRule()->getParam(0, '');

        if (! isset($this->errorArray[$rule])) {
            $RULE = strtoupper($rule);
            $line = $this->getTranslate('VALIDATOR:'.$RULE);
        } else {
            $line = $this->errorArray[$rule];
        }
        $message = sprintf(
            $line,
            $label,
            $param
        );
        $this->fieldData[$fieldName]['error'] = $message;   // Save the error message
        if (! isset($this->errorArray[$fieldName])) {
            $this->errorArray[$fieldName] = $message;
        }
    }

    /**
     * Returns to translation of error
     * 
     * @param strig $rule rule
     * 
     * @return string translated error
     */
    public function getTranslate($rule)
    {
        $translate = array(
            'VALIDATOR:REQUIRED'        => 'The %s field is required.',
            'VALIDATOR:EMAIL'           => "The %s field must contain a valid email address.",
            'VALIDATOR:MIN'             => "The %s field must be at least %s characters in length.",
            'VALIDATOR:MAX'             => "The %s field can not exceed %s characters in length.",
            'VALIDATOR:EXACT'           => "The %s field must be exactly %s characters in length.",
            'VALIDATOR:ALPHA'           => "The %s field may only contain alphabetical characters.",
            'VALIDATOR:ALPHAUNICODE'    => "The %s field may only contain alphabetical characters.",
            'VALIDATOR:ALNUM'           => "The %s field may only contain alpha-numeric characters.",
            'VALIDATOR:ALNUMUNICODE'    => "The %s field may only contain alpha-numeric characters.",
            'VALIDATOR:ALPHADASH'       => "The %s field may only contain alphabetic characters, underscores, and dashes.",
            'VALIDATOR:ALNUMDASH'       => "The %s field may only contain alpha-numeric characters, underscores, and dashes.",
            'VALIDATOR:ISNUMERIC'       => "The %s field must contain only numeric characters.",
            'VALIDATOR:MATCHES'         => "The %s field does not match the %s field.",
            'VALIDATOR:DATE'            => "The %s field must contain a valid date.",
            'VALIDATOR:ISDECIMAL'       => "The %s field must contain only decimal characters.",
            'VALIDATOR:ISJSON'          => "The %s field must contain a valid json data.",
            'VALIDATOR:CONTAINS'        => "The %s field may only contain (%s) values.",
            'VALIDATOR:CSRF'            => "Invalid csrf code.",
            'VALIDATOR:CSRF:INVALID'    => "The form submitted did not originate from the expected site.",
            'VALIDATOR:CSRF:REQUIRED'   => "The csrf token does not exist in post data.",

            'VALIDATOR:CAPTCHA:LABEL' => "Captcha",
            'VALIDATOR:CAPTCHA:NOT_FOUND' => "The captcha failure code not found.",
            'VALIDATOR:CAPTCHA:EXPIRED' => "The captcha code has been expired.",
            'VALIDATOR:CAPTCHA:INVALID' => "Invalid captcha code.",
            'VALIDATOR:CAPTCHA:SUCCESS' => "Captcha code verified.",
            'VALIDATOR:CAPTCHA:VALIDATION' => "The captcha field validation is wrong.",
            'VALIDATOR:CAPTCHA:REFRESH_BUTTON_LABEL' => "Refresh Captcha",
            
            'VALIDATOR:RECAPTCHA:MISSING_INPUT_SECRET' => "The secret parameter is missing.",
            'VALIDATOR:RECAPTCHA:INVALID_INPUT_SECRET' => "The secret parameter is invalid or malformed.",
            'VALIDATOR:RECAPTCHA:MISSING_INPUT_RESPONSE' => "The response parameter is missing.",
            'VALIDATOR:RECAPTCHA:INVALID_INPUT_RESPONSE' => "The response parameter is invalid or malformed.",

            'VALIDATOR:IBAN:NOTSUPPORTED' => 'Unknown country within the IBAN.',
            'VALIDATOR:IBAN:SEPANOTSUPPORTED' => 'Countries outside the Single Euro Payments Area (SEPA) are not supported.',
            'VALIDATOR:IBAN:FALSEFORMAT' => 'The input has a false IBAN format.',
            'VALIDATOR:IBAN:CHECKFAILED' => 'The input has failed the IBAN check.',
        );
        return isset($translate[$rule]) ? $translate[$rule] : $rule;
    }

    /**
     * Set form message
     * 
     * @param string $error errors
     *
     * @return void
     */
    public function setMessage($error)
    {
        $value = (string)$error;
        $this->formErrors[] = $value;
    }

    /**
     * Get form messages
     * 
     * @return array
     */
    public function getMessages()
    {
        return $this->formErrors;
    }

    /**
     * Set error(s) to form validator
     * 
     * @param mixed  $key key
     * @param string $val value
     * 
     * @return void
     */
    public function setError($key, $val = '')
    {
        if (is_array($key)) {
            $this->setErrors($key);
        } else {
            $this->fieldData[$key]['error'] = $val;
            $this->errorArray[$key] = $val;
        }
    }

    /**
     * Set validator errors as array
     * 
     * @param array $errors key - value
     * 
     * @return void
     */
    public function setErrors(array $errors)
    {
        foreach ($errors as $k => $v) {
            $this->fieldData[$k]['error'] = $v;
            $this->errorArray[$k] = $v;
        }
    }

    /**
     * Create a callback function
     * for validator
     * 
     * @param string  $func    name
     * @param closure $closure anonymous function
     * 
     * @return void
     */
    public function callback($func, Closure $closure)
    {
        $this->callbackFunctions[$func] = $closure;
    }

    /**
     * Returns true if field name exists in validator
     * 
     * @return array
     */
    public function getFieldData()
    {
        return $this->fieldData;
    }

    /**
     * Get filtered value from validator data
     *
     * Permits you to repopulate a form field with the value it was submitted
     * with, or, if that value doesn't exist, with the default
     *
     * @param string $field   the field name
     * @param string $default value
     * 
     * @return void
     */    
    public function getValue($field = '', $default = '')
    {
        if (! isset($this->fieldData[$field])) {
            return $default;
        }
        if (isset($this->fieldData[$field]['postdata'])) { 
            return $this->fieldData[$field]['postdata'];
        } elseif (isset($this->requestParams[$field])) {
            return $this->requestParams[$field];
        }
        return;
    }

    /**
     * Set filtered value to field
     * 
     * @param string $field the field name
     * @param string $value value
     * 
     * @return void
     */    
    public function setValue($field = '', $value = '')
    {
        $this->fieldData[$field]['postdata'] = $value;
    }

    /**
     * Get Error Message
     *
     * Gets the error message associated with a particular field
     *
     * @return void
     */    
    public function getErrors()
    {    
        return $this->errorArray;
    }

    /**
     * Returns to all output of validator
     * 
     * @return array
     */
    public function getOutputArray()
    {
        return [
            'messages' =>  $this->getMessages(),
            'errors' => $this->getErrors()
        ];
    }

    /**
     * Get error
     * 
     * @param string $field  fieldname
     * @param string $prefix error html tag start
     * @param string $suffix error html tag end
     * 
     * @return string
     */
    public function getError($field = '', $prefix = '', $suffix = '')
    {
        if ($prefix == '' && $suffix == '') {
            $prefix = $this->errorPrefix;
            $suffix = $this->errorSuffix;
        }
        if ($this->isError($field)) {
            return $prefix.$this->errorArray[$field].$suffix;
        }
        return '';
    }

    /**
     * Check field has error
     * 
     * @param string $field fieldname
     * 
     * @return boolean
     */
    public function isError($field)
    {
        if (! isset($this->fieldData[$field]['error']) || $this->fieldData[$field]['error'] == '') {
            return false;
        }
        return true;
    }

    /**
     * Error String
     *
     * Returns the error messages as a string, wrapped in the error delimiters
     * 
     * @param string $prefix error html tag start
     * @param string $suffix error html tag end
     * 
     * @return string
     */    
    public function getErrorString($prefix = '', $suffix = '')
    {
        if (sizeof($this->errorArray) === 0) { // No errrors, validation passes !
            return '';
        }
        $str = '';        
        foreach ($this->errorArray as $val) { // Generate the error string
            if ($val != '') {
                if ($prefix == '' && $suffix == '') {
                    $str .= $this->errorPrefix.$val.$this->errorSuffix;
                } else {
                    $str .= $prefix.$val.$suffix."\n";
                }
            }
        }
        return $str;
    }

    /**
     * Set The Error Delimiter
     *
     * Permits a prefix/suffix to be added to each error message
     *
     * @param string $prefix html
     * @param string $suffix html
     * 
     * @return void
     */    
    public function setErrorDelimiters($prefix = '<p>', $suffix = '</p>')
    {
        $this->errorPrefix = $prefix;
        $this->errorSuffix = $suffix;
    }

    /**
     * Create label automatically.
     * 
     * @param string $field field name
     * 
     * @return string label
     */
    protected function createLabel($field)
    {
        $label = ucfirst($field);
        if (strpos($field, '_') > 0) {
            $words = explode('_', strtolower($field));
            $ucwords = array_map('ucwords', $words);
            $label = implode(' ', $ucwords);
        }
        return $label;
    }

    /**
     * Returns to validator rule configuration array
     * 
     * @return array
     */
    public function getRules()
    {
        return $this->ruleArray;
    }

    /**
     * Returns to container 
     * 
     * @return object
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param array|null|object $requestParams
     */
    public function setRequestParams($requestParams)
    {
        $this->requestParams = $requestParams;
    }


}