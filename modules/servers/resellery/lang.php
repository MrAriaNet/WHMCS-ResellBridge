<?php
/**
 * Language helper for the WHMCS ResellBridge server module.
 *
 * @author  Aria Jahangiri Far <https://github.com/MrAriaNet>
 */

if (!function_exists('resellery_lang')) {
    /**
     * @param string $key
     * @return string
     */
    function resellery_lang($key)
    {
        static $strings = null;
        static $loadedLang = null;

        $lang = resellery_detect_language();
        if ($strings === null || $loadedLang !== $lang) {
            $base = dirname(__FILE__) . '/lang';
            $file = $base . '/' . $lang . '.php';
            if (!is_readable($file)) {
                $file = $base . '/english.php';
                $lang = 'english';
            }
            $strings = include $file;
            if (!is_array($strings)) {
                $strings = [];
            }
            $loadedLang = $lang;
        }

        return isset($strings[$key]) ? $strings[$key] : $key;
    }
}

if (!function_exists('resellery_detect_language')) {
    /**
     * @return string english|farsi
     */
    function resellery_detect_language()
    {
        $candidates = [];

        if (isset($GLOBALS['CONFIG']['Language'])) {
            $candidates[] = $GLOBALS['CONFIG']['Language'];
        }
        if (isset($_SESSION['Language'])) {
            $candidates[] = $_SESSION['Language'];
        }
        if (isset($_SESSION['adminlang'])) {
            $candidates[] = $_SESSION['adminlang'];
        }

        foreach ($candidates as $lang) {
            $lang = strtolower((string) $lang);
            if (in_array($lang, ['farsi', 'persian', 'fa', 'فارسی'], true)) {
                return 'farsi';
            }
        }

        return 'english';
    }
}

if (!function_exists('resellery_api_language_param')) {
    /**
     * Language code to send to the main API.
     *
     * @return string
     */
    function resellery_api_language_param()
    {
        return resellery_detect_language() === 'farsi' ? 'farsi' : 'english';
    }
}
