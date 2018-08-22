/**
 * @author Ondrej Donek <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 */

jQuery( document ).ready( function() {

    console.log( "Hello from `assets/js/screen-log.js`!", odwpdl );

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

    // Screen action "Delete log"
    jQuery( ".odwpdl-screen_action-delete" ).click( function() {

        console.log( "Screen action \"Delete log\"...", odwpdl.ajax_url );

        // TODO Show confirmation dialog!
        // TODO Use jQueryUI dialog!
        if ( ! confirm( odwpdl.i18n.confirm_delete_log_msg ) ) {
            // TODO Show admin notice? (made just by JS...)
            return false;
        }

        // Function that handles Ajax call response
        let handle_delete_log_action = function( response ) {
            console.log( response );
        };

        // Perform Ajax call
        jQuery.post( odwpdl.ajax.url, { action: odwpdl.ajax.actions.delete_log }, handle_delete_log_action, "json" );

        // Target was a link so return FALSE.
        return false;
    } );

    // Row action "Delete record"
    jQuery( ".odwpdl-delete_single" ).click( function() {

        console.log( "Row action \"Delete record\"...", odwpdl.ajax_url );

        // TODO Show confirmation dialog!
        // TODO Use jQueryUI dialog!
        if ( ! confirm( odwpdl.i18n.confirm_delete_record_msg ) ) {
            // TODO Show admin notice? (made just by JS...)
            return false;
        }

        // Function that handles Ajax call response
        let handle_delete_record_action = function( response ) {
            console.log( response );
        };

        // Perform Ajax call
        jQuery.post( odwpdl.ajax.url, { action: odwpdl.ajax.actions.delete_record }, handle_delete_record_action, "json" );

        // Target was a link so return FALSE.
        return false;
    } );

} );
