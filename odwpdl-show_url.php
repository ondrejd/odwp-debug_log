<?php
/**
 * Script that renders source codes of given file.
 *
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 * @since 1.0.0
 */

/**
 * @var string $root Path to the WordPress root.
 */
$root = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );

// Initialize WordPress self (we need it because of localization).
define( 'WP_USE_THEMES', false );
require( $root . '/wp-load.php' );

// Initialize localization
load_plugin_textdomain( 'odwpdl', false, 'odwp-debug_log/languages' );

// Passed file (should be full path)
$file = filter_input( INPUT_GET, 'file' );
$error_msg = '';

if ( ! file_exists( $file ) ) {
	$error_msg = sprintf( __( 'Source file <code>%1$s</code> was not found!', 'odwpdl' ), $file );
}
if ( ! is_readable( $file ) ) {
	$error_msg = sprintf( __( 'Source file <code>%1$s</code> can not be read!', 'odwpdl' ), $file );
}

// Get file sources
if ( empty( $error_msg ) ) {
	$contents = file_get_contents( $file );
}

// Plugin's URL
$base_url = plugin_dir_url( __FILE__ );

// Render output.
header( 'Content-Type:text/html;charset=UTF-8' . PHP_EOL );
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $file ?></title>
        <link rel="stylesheet" href="<?php echo $base_url ?>assets/vendor/highlight.js/default.css">
        <script src="<?php echo $base_url ?>assets/vendor/highlight.js/highlight.pack.js"></script>
    </head>
    <body>
        <?php if ( ! empty( $error_msg) ) : ?>
            <p><strong><?php echo $error_msg ?></strong></p>
        <?php else : ?>
            <pre><code class="php"><?php echo htmlentities( $contents ) ?></code></pre>
        <?php endif ?>
        <script type="text/javascript">hljs.initHighlightingOnLoad();</script>
    </body>
</html>