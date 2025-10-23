<?php

/**
 * --------------------------------------------------------------------------
 * CodeIgniter Alternative Framework
 * --------------------------------------------------------------------------
 * 
 * Validation Class
 * 
 * This class provides a centralized, extensible validation system for the
 * CodeIgniter Alternative framework. It supports customizable rules,
 * language-based error messages, and flexible validation logic for
 * application-wide data validation.
 * 
 * @package     CodeIgniterAlternative
 * @subpackage  System\Validation
 * @author      Oyatillo Anvarov
 * @license     MIT License
 * @since       Version 1.0.0
 * --------------------------------------------------------------------------
 */

namespace System;

class Validation
{
    protected $rules = [];
    protected $customMessages = [];
    protected $errors = [];
    protected $languageMessages = [];
    
    /**
     * Constructor - Load language file
     */
    public function __construct()
    {
        $this->loadLanguage('en');
    }
    
    /**
     * Load validation messages from language file
     * 
     * @param string $lang Language code
     * @return void
     */
    protected function loadLanguage($lang = 'en')
    {
        $langFile = __DIR__ . "/../app/Language/{$lang}/Validation.php";
        
        if (file_exists($langFile)) {
            $this->languageMessages = require $langFile;
        } else {
            $this->languageMessages = [
                'required' => 'The {field} field is required.',
                'min_length' => 'The {field} field must be at least {param} characters long.',
                'valid_email' => 'The {field} field must contain a valid email address.'
            ];
        }
    }
    
    /**
     * Set validation rules and optional custom messages
     * 
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @return self
     */
    public function setRules(array $rules, array $messages = [])
    {
        $this->rules = $rules;
        $this->customMessages = $messages;
        return $this;
    }
    
    /**
     * Validate data against set rules
     * 
     * @param array $data Data to validate
     * @return bool True if valid, false otherwise
     */
    public function validate(array $data)
    {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $ruleList = is_string($rule) ? explode('|', $rule) : $rule;
            
            foreach ($ruleList as $singleRule) {
                if (!$this->validateField($field, $value, $singleRule, $data)) {
                    break;
                }
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate single field against a rule
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Validation rule
     * @param array $allData All data (for matches rule)
     * @return bool
     */
    protected function validateField($field, $value, $rule, $allData)
    {
        $parts = explode('[', $rule, 2);
        $ruleName = $parts[0];
        $parameter = isset($parts[1]) ? rtrim($parts[1], ']') : null;
        
        $result = true;
        $errorMessage = null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'min_length':
                if (strlen((string)$value) < (int)$parameter) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'max_length':
                if (strlen((string)$value) > (int)$parameter) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'valid_email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'numeric':
                if (!is_numeric($value)) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'integer':
                if (!filter_var($value, FILTER_VALIDATE_INT) && $value !== 0) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'alpha':
                if (!ctype_alpha((string)$value)) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'alpha_numeric':
                if (!ctype_alnum((string)$value)) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'matches':
                if ($value !== ($allData[$parameter] ?? null)) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'differs':
                if ($value === ($allData[$parameter] ?? null)) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'valid_url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'valid_date':
                if (strtotime($value) === false) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'valid_ip':
                if (!filter_var($value, FILTER_VALIDATE_IP)) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'valid_json':
                if ($value !== null) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $result = false;
                        $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                    }
                }
                break;
                
            case 'greater_than':
                if ((float)$value <= (float)$parameter) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'greater_than_equal_to':
                if ((float)$value < (float)$parameter) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'less_than':
                if ((float)$value >= (float)$parameter) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'less_than_equal_to':
                if ((float)$value > (float)$parameter) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'in_list':
                $list = explode(',', $parameter);
                if (!in_array($value, $list, true)) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'regex_match':
                if (!preg_match($parameter, $value)) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'decimal':
                if (!is_numeric($value) || floor($value) == $value) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'is_natural':
                if (!ctype_digit((string)$value)) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
                
            case 'is_natural_no_zero':
                if (!ctype_digit((string)$value) || $value == 0) {
                    $result = false;
                    $errorMessage = $this->getMessage($field, $ruleName, $parameter);
                }
                break;
        }
        
        if (!$result) {
            $this->errors[$field][] = $errorMessage;
        }
        
        return $result;
    }
    
    /**
     * Get error message for a field and rule
     * 
     * @param string $field Field name
     * @param string $rule Rule name
     * @param string|null $param Rule parameter
     * @return string
     */
    protected function getMessage($field, $rule, $param = null)
    {
        $customKey = "{$field}.{$rule}";
        
        if (isset($this->customMessages[$customKey])) {
            $message = $this->customMessages[$customKey];
        } elseif (isset($this->customMessages[$rule])) {
            $message = $this->customMessages[$rule];
        } elseif (isset($this->languageMessages[$rule])) {
            $message = $this->languageMessages[$rule];
        } else {
            $message = "The {field} field is invalid.";
        }
        
        $humanField = ucwords(str_replace(['_', '-'], ' ', $field));
        $message = str_replace('{field}', $humanField, $message);
        $message = str_replace('{param}', (string)$param, $message);
        
        return $message;
    }
    
    /**
     * Get all validation errors
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Get errors for specific field
     * 
     * @param string $field Field name
     * @return array
     */
    public function getError($field)
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Check if there are any errors
     * 
     * @param string|null $field Check specific field or all
     * @return bool
     */
    public function hasError($field = null)
    {
        if ($field === null) {
            return !empty($this->errors);
        }
        return isset($this->errors[$field]);
    }
    
    /**
     * Reset validation state
     * 
     * @return self
     */
    public function reset()
    {
        $this->rules = [];
        $this->customMessages = [];
        $this->errors = [];
        return $this;
    }
}
?>
