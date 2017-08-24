<?php
/**
 * Script that renders source codes of given file. Used insed the log viewer.
 *
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 * @since 1.0.0
 *
 * @todo Localize this script!
 */

header( 'Content-Type: text/html;charset=UTF-8' . PHP_EOL );

include( dirname( __FILE__ ) . '/lib/geshi/geshi.php' );

$file = filter_input( INPUT_GET, 'file' );
$language = 'php';

// Recognize used programming language
if( strpos( $file, '.phtml' ) > O || strpos( $file, '.php' ) > O ) {
    $language = 'php';
}
elseif( strpos( $file, '.html' ) > O ) {
    $language = 'html';
}
elseif( strpos( $file, '.css' ) > O ) {
    $language = 'css';
}
elseif( strpos( $file, '.js' ) > O ) {
    $language = 'js';
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $file ?></title>
    </head>
    <body>
        <?php if( ! file_exists( $file ) ) : ?>
            <p><b>Source file ['<?php echo $file ?>'] was not found!</b></p>
        <?php elseif( ! is_readable( $file ) ) : ?>
            <p><b>Source file ['<?php echo $file ?>'] can not be read!</b></p>
        <?php else :
            $source = file_get_contents( $file );
            $geshi = new GeSHi( $source, $language );
            $geshi->enable_line_numbers( GESHI_FANCY_LINE_NUMBERS );

            echo $geshi->parse_code();
            ?>
        <?php endif ?> 
    </body>
</html>
