<?php
/**
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 * @since 1.0.0
 */

if( ! defined( 'ABSPATH' ) ) {
    exit;
}

if( ! class_exists( 'DL_Log_Highlighter' ) ) :

/**
 * Class that serves highlighting of log text.
 * @since 1.0.0
 */
class DL_Log_Highlighter {
    /**
     * Highlights numbers.
     * @param string $part
     * @param string $line
     * @return string
     * @since 1.0.0
     */
    public static function highlight_numbers( $part, $line ) {
        $regexp      = '/([0-9]{1,})/';
        $replacement = '<span class="log--numeric">${1}</span>';
        $highlighted = preg_replace( $regexp, $replacement, $part );

        return str_replace( $part, $highlighted, $line );
    }
    /**
     * Highlights numbers in given part of the line.
     * @param string $part
     * @param string $line
     * @return string
     * @since 1.0.0
     */
    public static function highlight_functions( $part, $line ) {
        //...
        return $line;
    }

    /**
     * Highlights strings in brackets.
     * @param string $part
     * @param string $line
     * @return string
     * @since 1.0.0
     */
    public static function highlight_strings( $part, $line ) {
        $regexp = '/([\'\"][a-zA-Z\)][\'\"])/';
        $replacement = '<span class="log--string">${1}</span>';
        $highlighted = preg_replace( $regexp, $replacement, $part );

        return str_replace( $part, $highlighted, $line );
    }
}
endif;
