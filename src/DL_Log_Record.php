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

if ( ! class_exists( 'DL_Log_Record' ) ) :

/**
 * Log record.
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @since 1.0.0
 */
class DL_Log_Record {

    /**
     * @since 1.0.0
     * @var string
     */
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @since 1.0.0
     * @var string
     */
    const TYPE_ERROR   = 'PHP Fatal error';

    /**
     * @since 1.0.0
     * @var string
     */
    const TYPE_NOTICE  = 'PHP Notice';

    /**
     * @since 1.0.0
     * @var string
     */
    const TYPE_OTHER   = 'Other';

    /**
     * @since 1.0.0
     * @var string
     */
    const TYPE_PARSER  = 'PHP Parse error';

    /**
     * @since 1.0.0
     * @var string
     */
    const TYPE_WARNING = 'PHP Warning';

    /**
     * @since 1.0.0
     * @var string
     */
    const TYPE_DLPARSER = 'DL_Log_Parser';

    /**
     * @since 1.0.0
     * @var int $id
     */
    protected $id;

    /**
     * @since 1.0.0
     * @var int $time
     */
    protected $time;

    /**
     * @since 1.0.0
     * @var string $message
     */
    protected $message;

    /**
     * @var array $trace
     * @since 1.0.0
     */
    protected $trace;

    /**
     * @since 1.0.0
     * @var string $type Log record type (PHP Fatal Error, PHP Notice, PHP Warning).
     */
    protected $type;

    /**
     * @since 1.0.0
     * @var bool $display Show log record?
     */
    protected $display = true;

    /**
     * @since 1.0.0
     * @var int $start_line
     */
    protected $start_line;

    /**
     * @since 1.0.0
     * @var int $end_line
     */
    protected $end_line;

    /**
     * @since 1.0.0
     * @var bool $was_today Was record created today?
     */
    protected $was_today = false;

    /**
     * @var bool $was_yesterday Was record created yesterday?
     * @since 1.0.0
     */
    protected $was_yesterday = false;

    /**
     * Construct.
     * 
     * @param int $id
     * @param int $time
     * @param string $message
     * @param array $trace (Optional.)
     * @param string $type
     * @return void
     * @since 1.0.0
     */
    public function __construct( int $id, int $time, string $message, array $trace = [], string $type = self::TYPE_OTHER ) {
        $this->set_id( $id );
        $this->set_time( $time );
        $this->set_message( $message );
        $this->set_trace( $trace );
        $this->set_type( $type );
    }

    /**
     * Return index of the log record.
     * 
     * @return int
     * @since 1.0.0
     */
    public function get_id() : int {
        return $this->id;
    }

    /**
     * Return time of the log record.
     *
     * @param bool $formatted (Optional.)
     * @return string
     * @since 1.0.0
     */
    public function get_time( bool $formatted = false ) : string {
        return ( $formatted === true )
            ? (string) date( self::DATE_FORMAT, $this->time )
            : (string) $this->time;
    }

    /**
     * Return time of the log record.
     *
     * @return string
     * @since 1.0.0
     */
    public function get_message() : string {
        return $this->message;
    }

    /**
     * Return stack trace of the log record (error in this case).
     *
     * @return array
     * @since 1.0.0
     */
    public function get_trace() : array {
        return $this->trace;
    }

    /**
     * Return log record type.
     *
     * @return string
     * @since 1.0.0
     */
    public function get_type() : string {
        return $this->type;
    }

    /**
     * Return TRUE if record should be displayed.
     *
     * @return bool
     * @since 1.0.0
     */
    public function get_display() : bool {
        return $this->display;
    }

    /**
     * Return number of line where log record starts.
     *
     * @return int
     * @since 1.0.0
     */
    public function get_start_line() : int {
        return $this->start_line;
    }

    /**
     * Return number of line where log record ends.
     *
     * @return int
     * @since 1.0.0
     */
    public function get_end_line() : int {

        // If end line is not set it means that current record takes just one line
        if ( empty( $this->end_line ) ) {
            return $this->get_start_line();
        }

        return $this->end_line;
    }

    /**
     * Set index of the log record.
     *
     * @param int $id
     * @return void
     * @since 1.0.0
     */
    public function set_id( int $id ) {
        $this->id = $id;
    }

    /**
     * Set time of the log record.
     *
     * @param int $time
     * @return void
     * @since 1.0.0
     */
    public function set_time( int $time ) {
        $this->time = $time;

        // Re-calculate values of `was_today`/`was_yesterday`
        // Note: We calculate this boolean values here because of performance...
        $today     = strtotime( '00:00:01' );
        $yesterday = strtotime( '-1day', $today );

        $this->was_today     = ( $time >= $today );
        $this->was_yesterday = ( ( $time < $today) && ( $time >= $yesterday ) );
    }

    /**
     * Set message of the log record.
     *
     * @param string $message
     * @return void
     * @since 1.0.0
     */
    public function set_message( string $message ) {
        $this->message = $message;
    }

    /**
     * Set stack trace of the log record.
     *
     * @param array $trace
     * @return void
     * @since 1.0.0
     */
    public function set_trace( array $trace = [] ) {
        $this->trace = $trace;
    }

    /**
     * Add stack trace message to the log record.
     *
     * @param string $trace
     * @return void
     * @since 1.0.0
     */
    public function add_trace( string $trace ) {
        array_push( $this->trace, $trace );
    }

    /**
     * Return TRUE if error record has a stack trace.
     *
     * @return bool
     * @since 1.0.0
     */
    public function has_trace() : bool {
        return ( count( $this->trace ) > 0 );
    }

    /**
     * Set log record type.
     *
     * @param string $type
     * @return void
     * @since 1.0.0
     */
    public function set_type( string $type ) {
        $this->type = $type;
    }

    /**
     * Set if record should be displayed.
     *
     * @param bool $display
     * @return void
     * @since 1.0.0
     */
    public function set_display( bool $display ) {
        $this->display = $display;
    }

    /**
     * Return number of line where log record starts.
     *
     * @param int $line_num
     * @return void
     * @since 1.0.0
     */
    public function set_start_line( int $line_num ) {
        $this->start_line = $line_num;
    }

    /**
     * Return number of line where log record ends.
     *
     * @param int $line_num
     * @return void
     * @since 1.0.0
     */
    public function set_end_line( int $line_num ) {
        $this->end_line = $line_num;
    }

    /**
     * Was the log record created today?
     *
     * @return bool
     * @since 1.0.0
     */
    public function was_today() : bool {
        return $this->was_today;
    }

    /**
     * Was the log record created yesterday?
     *
     * @return bool
     * @since 1.0.0
     */
    public function was_yesterday() : bool {
        return $this->was_yesterday;
    }
}

endif;
