/**
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 */

/**
 * Simple object that handles toggling visibility of stack traces in the log table.
 * @type Object
 */
var StackTraceToggler = {
    /** @type String CSS class for down arrow icon. */
    get ICON_DOWN() { return 'dashicons-arrow-down-alt2'; },
    /** @type String CSS class for up arrow icon. */
    get ICON_UP() { return 'dashicons-arrow-up-alt2'; },

    /**
     * Toggles visibility of stack trace in the log table.
     * @param {DOMElement} aElm
     * @returns {void}
     */
    toggle: function( aElm ) {
        // 1) check if stack trace is collapsed or visible
        var status = jQuery( aElm ).find( "span.dashicons" ).hasClass( this.ICON_DOWN ) ? "hidden" : "visible";
        // 2) set correct class to the parent <div class="stack-trace"> element
        jQuery( aElm ).parent( "div.stack-trace" )
                .removeClass( status == "hidden" ? "stack-trace--collapsed" : "" )
                .addClass( status == "hidden" ? "" : "stack-trace--collapsed" );
        // 3) set correct class to the <span class="dashicons ..."> element
        jQuery( aElm ).find( "span.dashicons" )
                .removeClass( status == "hidden" ? this.ICON_DOWN : this.ICON_UP )
                .addClass( status == "hidden" ? this.ICON_UP : this.ICON_DOWN );
    }
};
