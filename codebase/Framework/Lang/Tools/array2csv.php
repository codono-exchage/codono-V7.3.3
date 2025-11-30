<?php
if(!defined('ACCESS')){die('NO ACCESS');}
$lang = include_once('../en.php');
/**
 * PHP version 5.4
 * @category Export
 * @package  Language
 * @author   Tim Neutkens <tim@weprovide.com>
 * @license  MIT <https://opensource.org/licenses/MIT>
 * @link     <weprovide.com>
 */

define('ENVIRONMENT', 'production');
/**
 * Placeholder for base_url function
 *
 * @param string $path path part of url
 *
 * @return string
 */
function base_url($path = '')
{
    return '/' . $path;
}

/**
 * Placeholder for base_url function
 *
 * @param string $path path part of url
 *
 * @return string
 */
function site_url($path = '')
{
    return '/' . $path;
}

/**
 * Export csv to file location
 *
 * @param array $data csv data
 * @param string $location location to put file
 *
 * @return string
 */
function export_csv($data = [], $location = '')
{
    $file_name = 'export-lang-' . date('Y-m-d_H') . '.csv';
    $file_path = $location . '/' . $file_name;
    $file = @fopen($file_path, 'w');

    fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

    foreach ($data as $key => $value) {
        fputcsv($file, [$key, $value]);
    }

    fclose($file);

    return '<a href="'.$file_name.'"><strong>Download</strong></a>';
}

/**
 * Load language
 *
 * @param string $path path to language directory.
 *
 * @return array
 */
function load_language($lang,$path = '')
{
    // Loop through dutch translations
    $files = glob($path . '/*.php');
    foreach ($files as $filename) {
        include_once $filename;
    }

    return $lang;
}

//$lang = load_language('lang/');

// Lang gets filled by the above includes
echo export_csv($lang, getcwd());