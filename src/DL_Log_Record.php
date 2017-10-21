<?php
/**
 * @author Ondrej Donek <ondrejd@gmail.com>
 * @link https://github.com/ondrejd/odwp-debug_log for the canonical source repository
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 * @package odwp-debug_log
 * @since 1.0.0
 */

if( ! defined( 'ABSPATH' ) ) {
    exit;
}

if( ! class_exists( 'DL_Log_Record' ) ) :

/**
 * Log record.
 * @since 1.0.0
 */
class DL_Log_Record {
    /**
     * @var string
     * @since 1.0.0
     */
    const DATE_FORMAT = 'Y n. j. H:i:s';

    /**
     * @var string
     * @since 1.0.0
     */
    const TYPE_ERROR   = 'PHP Fatal error';

    /**
     * @var string
     * @since 1.0.0
     */
    const TYPE_NOTICE  = 'PHP Notice';

    /**
     * @var string
     * @since 1.0.0
     */
    const TYPE_OTHER   = 'Other';

    /**
     * @var string
     * @since 1.0.0
     */
    const TYPE_PARSER  = 'PHP Parse error';

    /**
     * @var string
     * @since 1.0.0
     */
    const TYPE_ODWPDL  = 'Log Parser error';

    /**
     * @var string
     * @since 1.0.0
     */
    const TYPE_WARNING = 'PHP Warning';

    /**
     * @var integer $id
     * @since 1.0.0
     */
    protected $id;

    /**
     * @var integer $time
     * @since 1.0.0
     */
    protected $time;

    /**
     * @var string $message
     * @since 1.0.0
     */
    protected $message;

    /**
     * @var array $trace
     * @since 1.0.0
     */
    protected $trace;

    /**
     * @var string $type Log record type (PHP Fatal Error, PHP Notice, PHP Warning).
     * @since 1.0.0
     */
    protected $type;

    /**
     * @var boolean Show log record?
     * @since 1.0.0
     */
    protected $display = true;

    /**
     * @var boolean Was record created today?
     * @since 1.0.0
     */
    protected $was_today = false;

    /**
     * @var boolean Was record created yesterday?
     * @since 1.0.0
     */
    protected $was_yesterday = false;

    /**
     * Construct.
     * @param integer $id
     * @param integer $time
     * @param string  $message
     * @param array   $trace (Optional.)
     * @param string  $type
     * @since 1.0.0
     */
    public function __construct( $id, $time, $message, $trace = [], $type = self::TYPE_OTHER ) {
        $this->set_id( $id );
        $this->set_time( $time );
        $this->set_message( $message );
        $this->set_trace( $trace );
        $this->set_type( $type );
    }

    /**
     * Returns index of the log record.
     * @return integer
     * @since 1.0.0
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Returns time of the log record.
     * @param boolean $formatted (Optional.)
     * @return integer
     * @since 1.0.0
     */
    public function get_time( $formatted = false ) {
        return ( $formatted === true ) ? date( self::DATE_FORMAT, $this->time ) : $this->time;
    }

    /**
     * Returns time of the log record.
     * @return string
     * @since 1.0.0
     */
    public function get_message() {
        return $this->message;
    }

    /**
     * Returns stack trace of the log record (error in this case).
     * @return array
     * @since 1.0.0
     */
    public function get_trace() {
        return $this->trace;
    }

    /**
     * Returns log record type.
     * @return array
     * @since 1.0.0
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Returns TRUE if record should be displayed.
     * @return boolean
     * @since 1.0.0
     */
    public function get_display() {
        return $this->display;
    }

    /**
     * Sets index of the log record.
     * @param integer $id
     * @return void
     * @since 1.0.0
     */
    public function set_id( $id ) {
        $this->id = $id;
    }

    /**
     * Sets time of the log record.
     * @param integer $time
     * @return void
     * @since 1.0.0
     */
    public function set_time( $time ) {
        $this->time = $time;

        // Re-calculate values of `was_today`/`was_yesterday`
        // Note: We calculate this boolean values here because of performance...
        $today     = strtotime( '00:00:01' );
        $yesterday = strtotime( '-1day', $today );
        $this->was_today     = ( $time >= $today );
        $this->was_yesterday = ( ( $time < $today) && ( $time >= $yesterday ) );
    }

    /**
     * Sets message of the log record.
     * @param string $message
     * @return void
     * @since 1.0.0
     */
    public function set_message( $message ) {
        $this->message = $message;
    }

    /**
     * Sets stack trace of the log record.
     * @param array $trace
     * @return void
     * @since 1.0.0
     */
    public function set_trace( $trace = [] ) {
        $this->trace = $trace;
    }

    /**
     * Adds stack trace message to the log record.
     * @param string $trace
     * @return void
     * @since 1.0.0
     */
    public function add_trace( $trace ) {
        array_push( $this->trace, $trace );
    }

    /**
     * Returns <em>TRUE</em> if error record has a stack trace.
     * @return boolean
     * @since 1.0.0
     */
    public function has_trace() {
        return ( count( $this->trace ) > 0 );
    }

    /**
     * Sets log record type.
     * @param string $type
     * @return void
     * @since 1.0.0
     */
    public function set_type( $type ) {
        $this->type = $type;
    }

    /**
     * Sets if record should be displayed.
     * @param boolean $display
     * @return void
     * @since 1.0.0
     */
    public function set_display( $display ) {
        $this->display = ( bool ) $display;
    }

    /**
     * Was the log record created today?
     * @return boolean
     * @since 1.0.0
     */
    public function was_today() {
        return $this->was_today;
    }

    /**
     * Was the log record created yesterday?
     * @return boolean
     * @since 1.0.0
     */
    public function was_yesterday() {
        return $this->was_yesterday;
    }
}

endif;
