<?php
/**
 * --------------------------------------------------------------------------
 * CodeIgniter Alternative Framework
 * --------------------------------------------------------------------------
 * 
 * O'zbekcha Validatsiya Xabarlar Fayli
 * 
 * Ushbu fayl CodeIgniter Alternative validatsiya tizimi tomonidan ishlatiladigan
 * standart xato xabarlarini o'z ichiga oladi. Dasturchilar bu xabarlarni
 * loyihaga moslab o'zgartirishi yoki kengaytirishi mumkin.
 * 
 * @package     CodeIgniterAlternative
 * @subpackage  App\Language\uz
 * @category    Validation
 * @author      Anvarov Oyatillo
 * @license     MIT License
 * @since       Versiya 1.0.0
 * --------------------------------------------------------------------------
 */

return [
    // Asosiy qoidalar
    'required'              => '{field} maydoni majburiy.',
    'min_length'            => '{field} maydoni kamida {param} ta belgidan iborat bo‘lishi kerak.',
    'max_length'            => '{field} maydoni {param} ta belgidan oshmasligi kerak.',
    'valid_email'           => '{field} maydoni to‘g‘ri email manzilni o‘z ichiga olishi kerak.',
    'matches'               => '{field} maydoni {param} maydoniga mos kelmayapti.',
    'differs'               => '{field} maydoni {param} maydonidan farq qilishi kerak.',

    // Turdagi qoidalar
    'numeric'               => '{field} maydoni faqat raqamlarni o‘z ichiga olishi kerak.',
    'integer'               => '{field} maydoni butun son bo‘lishi kerak.',
    'decimal'               => '{field} maydoni o‘nlik son bo‘lishi kerak.',
    'is_natural'            => '{field} maydoni faqat raqamlardan iborat bo‘lishi kerak.',
    'is_natural_no_zero'    => '{field} maydoni nol bo‘lmagan raqamdan iborat bo‘lishi kerak.',

    // Formatdagi qoidalar
    'alpha'                 => '{field} maydoni faqat harflardan iborat bo‘lishi kerak.',
    'alpha_numeric'         => '{field} maydoni faqat harflar va raqamlardan iborat bo‘lishi kerak.',
    'valid_url'             => '{field} maydoni to‘g‘ri URL manzilni o‘z ichiga olishi kerak.',
    'valid_ip'              => '{field} maydoni to‘g‘ri IP manzilni o‘z ichiga olishi kerak.',
    'valid_date'            => '{field} maydoni to‘g‘ri sanani o‘z ichiga olishi kerak.',
    'valid_json'            => '{field} maydoni to‘g‘ri JSON formatida bo‘lishi kerak.',
    'regex_match'           => '{field} maydoni to‘g‘ri formatda emas.',

    // Hajmdagi qoidalar
    'greater_than'          => '{field} maydoni {param} dan katta bo‘lishi kerak.',
    'greater_than_equal_to' => '{field} maydoni {param} dan katta yoki teng bo‘lishi kerak.',
    'less_than'             => '{field} maydoni {param} dan kichik bo‘lishi kerak.',
    'less_than_equal_to'    => '{field} maydoni {param} dan kichik yoki teng bo‘lishi kerak.',

    // Ro'yxatdagi qoidalar
    'in_list'               => '{field} maydoni quyidagi qiymatlardan biri bo‘lishi kerak: {param}.',
];
?>