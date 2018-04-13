<?php
/**
 * Script that renders source codes of given file. Used insed the log viewer.
 *
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 * @since 1.0.0
 */

/**
 * @var string $path Path to the current file.
 */
$path = dirname( __FILE__ );

/**
 * @var string $root Path to the WordPress root.
 */
$root = dirname( dirname( dirname( $path ) ) );

// Initialize WordPress self (we need it because of localization).
define( 'WP_USE_THEMES', false );
require( $root . '/wp-load.php' );
load_plugin_textdomain( 'odwpdl', false, 'odwp-debug_log/languages' );

/**
 * @var string $file
 */
$file = filter_input( INPUT_GET, 'file' );

/**
 * @var string $lang
 */
$lang = 'php';

// Recognize used programming language.
if( strpos( $file, '.phtml' ) > 0 || strpos( $file, '.php' ) > 0 ) {
    $lang = 'php';
}
elseif( strpos( $file, '.html' ) > 0 ) {
    $lang = 'html';
}
elseif( strpos( $file, '.css' ) > 0 ) {
    $lang = 'css';
}
elseif( strpos( $file, '.js' ) > 0 ) {
    $lang = 'js';
}

// Render output.
header( 'Content-Type:text/html;charset=UTF-8' . PHP_EOL );
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $file ?></title>
    </head>
    <body>
        <?php if( ! file_exists( $file ) ) : ?>
            <p><b><?php printf(__( 'Source file ["%1$s"] was not found!', 'odwpdl' ), $file ) ?></b></p>
        <?php elseif( ! is_readable( $file ) ) : ?>
            <p><b><?php printf(__( 'Source file ["%1$s"] can not be read!', 'odwpdl' ), $file ) ?></b></p>
        <?php else :
            // Include Geshi
            include( $path . '/lib/geshi/geshi.php' );

            $source = file_get_contents( $file );
            $geshi = new GeSHi( $source, $lang );
            $geshi->enable_line_numbers( GESHI_FANCY_LINE_NUMBERS );

            echo $geshi->parse_code();
        endif; ?> 
    </body>
</html>
