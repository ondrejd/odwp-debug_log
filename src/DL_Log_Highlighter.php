<?php
/**
 * @author Ondrej Donek <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'DL_Log_Highlighter' ) ) :

/**
 * Class that serves highlighting of log text.
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @since 1.0.0
 */
class DL_Log_Highlighter {

    /**
     * Highlights numbers.
     *
     * @param string $part
     * @param string $line
     * @return string
     * @since 1.0.0
     */
    public static function highlight_numbers( string $part, string $line ) : string {
        $regexp      = '/([0-9]{1,})/';
        $replacement = '<span class="log--numeric">${1}</span>';
        $highlighted = preg_replace( $regexp, $replacement, $part );

        return str_replace( $part, $highlighted, $line );
    }

    /**
     * Highlights PHP function names.
     *
     * @param string $part
     * @param string $line
     * @return string
     * @since 1.0.0
     */
    public static function highlight_functions( string $part, string $line ) : string {
        $regexp = '/([a-zA-Z\_:\/\-\>]*\([a-zA-Z\'\,\s…\/\._]*\))/';
        $replacement = '<span class="log--function">${1}</span>';
        $highlighted = preg_replace( $regexp, $replacement, $part );

        return str_replace( $part, $highlighted, $line );
    }

    /**
     * Highlight some others that pass through the other regexp's.
     *
     * @param string $part
     * @param string $line
     * @return string
     * @since 1.0.0
     */
    public static function highlight_others( string $part, string $line ) : string {
        $ret = $line;
        $ret = str_replace( ' \']\'' , ' <span class="log--string">\']\'</span>', $ret );
        $ret = str_replace( ' \'[\'' , ' <span class="log--string">\'[\'</span>', $ret );
        $ret = str_replace( ' \'if\'', ' <span class="log--string">\'if\'</span>', $ret );
        $ret = str_replace( ' \'=>\'', ' <span class="log--string">\'if\'</span>', $ret );
        $ret = str_replace( ' null'  , ' <span class="log--null">null</span>', $ret);

        // Error,TypeError
        // Undefined variable: *

        return $ret;
    }

    /**
     * Highlights strings in brackets.
     *
     * @param string $part
     * @param string $line
     * @return string
     * @since 1.0.0
     */
    public static function highlight_strings( string $part, string $line ) : string {
        $regexp = '/(\'[a-zA-Z0-9\(\);,\[\]]{1,}\')/';
        $replacement = '<span class="log--string">${1}</span>';
        $highlighted = preg_replace( $regexp, $replacement, $part );

        return str_replace( $part, $highlighted, $line );
    }

    /**
     * Highlights "undefined variable: *".
     *
     * @param string $part
     * @param string $line
     * @return string
     * @since 1.0.0
     */
    public static function highlight_undefined_variable( string $part, string $line ) : string {
        $regexp      = '/(Undefined\svariable:\s)([a-zA-Z_]*)/';
        $replacement = '${1}<span class="log--variable">${2}</span>';
        $highlighted = preg_replace( $regexp, $replacement, $part );

        return str_replace( $part, $highlighted, $line );
    }
}
endif;
