<?php
if ($_SERVER['SERVER_NAME'] === 'clashscout.com') {
    function __($string, $args = []) {
        // Declare a static variable to store the translations.
        // This allows the translations to be loaded only once per request,
        // improving performance by avoiding reading the same data from the CSV file multiple times.
        static $translations = null;
        
        // Determine the language based on the 'lang' cookie. If the cookie is not set, default to 'en_US'.
        $lang = 'en_US';
        if (isset($_COOKIE['lang'])) {
            $lang = $_COOKIE['lang'];
        }
        
        // Load the translation data from the CSV file only once per request.
        if ($translations === null) {
            $translations = [];
            // Open the CSV file for the current language.
            $handle = fopen('/hdd1/clashapp/lang/' . $lang . '.csv', 'r');
            // Read the translations from the file and store them in an array.
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $translations[$data[0]] = $data[1];
            }
            // Close the CSV file.
            fclose($handle);
        }
        
        // Check if a translation for the string is available, and if so, replace the string with the translation.
        if (isset($translations[$string])) {
            $string = $translations[$string];
        }
        
        // Replace any placeholders in the string with the provided arguments, if any.
        if (!empty($args)) {
            $string = vsprintf($string, $args);
        }
        
        // Return the translated string.
        return $string;
    }
}
?>