<?php
/**
 * --------------------------------------------------------------------------
 * CodeIgniter Alternative Framework
 * --------------------------------------------------------------------------
 * 
 * English Validation Language File
 * 
 * This file contains the default validation error messages used by the
 * CodeIgniter Alternative validation library. Developers can customize or
 * extend these messages for application-specific validation rules.
 * 
 * @package     CodeIgniterAlternative
 * @subpackage  App\Language\en
 * @category    Validation
 * @author      Anvarov Oyatillo
 * @license     MIT License
 * @since       Version 1.0.0
 * --------------------------------------------------------------------------
 */

return [
    // Core Rules
    'required'              => 'The {field} field is required.',
    'min_length'            => 'The {field} field must be at least {param} characters in length.',
    'max_length'            => 'The {field} field cannot exceed {param} characters in length.',
    'valid_email'           => 'The {field} field must contain a valid email address.',
    'matches'               => 'The {field} field does not match the {param} field.',
    'differs'               => 'The {field} field must differ from the {param} field.',

    // Type Rules
    'numeric'               => 'The {field} field must contain only numbers.',
    'integer'               => 'The {field} field must contain an integer.',
    'decimal'               => 'The {field} field must contain a decimal number.',
    'is_natural'            => 'The {field} field must only contain digits.',
    'is_natural_no_zero'    => 'The {field} field must only contain digits and must be greater than zero.',

    // Format Rules
    'alpha'                 => 'The {field} field may only contain alphabetical characters.',
    'alpha_numeric'         => 'The {field} field may only contain alpha-numeric characters.',
    'valid_url'             => 'The {field} field must contain a valid URL.',
    'valid_ip'              => 'The {field} field must contain a valid IP.',
    'valid_date'            => 'The {field} field must contain a valid date.',
    'valid_json'            => 'The {field} field must contain a valid JSON string.',
    'regex_match'           => 'The {field} field is not in the correct format.',

    // Size Rules
    'greater_than'          => 'The {field} field must contain a number greater than {param}.',
    'greater_than_equal_to' => 'The {field} field must contain a number greater than or equal to {param}.',
    'less_than'             => 'The {field} field must contain a number less than {param}.',
    'less_than_equal_to'    => 'The {field} field must contain a number less than or equal to {param}.',

    // List Rules
    'in_list'               => 'The {field} field must be one of: {param}.',
];
?>