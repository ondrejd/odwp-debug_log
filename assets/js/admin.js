/**
 * @author Ondrej Donek <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 */



jQuery( document ).ready( function() {

    const ICON_DOWN = "dashicons-arrow-down-alt2";
    const ICON_UP   = "dashicons-arrow-up-alt2";

    // Initialize stack trace toggling
    jQuery( ".stack-trace-toggling" ).click( function() {
        let old_cls, new_cls;
        let $target = jQuery( this );
        let status  = $target.find( "span.dashicons" ).hasClass( ICON_DOWN ) ? "hidden" : "visible";

        // set correct class to the container element
        old_cls = ( status == "hidden" ) ? "stack-trace--collapsed" : "";
        new_cls = ( status == "hidden" ) ? "" : "stack-trace--collapsed";

        $target.parent( "div.stack-trace" ).removeClass( old_cls ).addClass( new_cls );

        // set correct class to the stack trace toggling element
        old_cls = ( status == "hidden" ) ? ICON_DOWN : ICON_UP;
        new_cls = ( status == "hidden" ) ? ICON_UP : ICON_DOWN;

        $target.find( "span.dashicons" ).removeClass( old_cls ).addClass( new_cls );
    } );

} );
