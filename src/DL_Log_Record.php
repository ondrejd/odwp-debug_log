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
     * @var array $stack_trace
     * @since 1.0.0
     */
    protected $stack_trace;

    /**
     * Construct.
     * @param integer $id
     * @param integer $time
     * @param string $message
     * @param array $stack_trace (Optional.)
     * @since 1.0.0
     */
    public function __construct( $id, $time, $message, $stack_trace = [] ) {
        $this->id = $id;
        $this->time = $time;
        $this->message = $message;
        $this->stack_trace = $stack_trace;
    }

    /**
     * Returns index of the log record.
     * @return integer
     * @since 1.0.0
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns time of the log record.
     * @param boolean $formatted (Optional.)
     * @return integer
     * @since 1.0.0
     */
    public function getTime( $formatted = false ) {
        return ( $formatted === true ) ? date( self::DATE_FORMAT, $this->time ) : $this->time;
    }

    /**
     * Returns time of the log record.
     * @return string
     * @since 1.0.0
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Returns stack trace of the log record (error in this case).
     * @return array
     * @since 1.0.0
     */
    public function getStackTrace() {
        return $this->stack_trace;
    }

    /**
     * Sets index of the log record.
     * @param integer $id
     * @return void
     * @since 1.0.0
     */
    public function setId( $id ) {
        $this->id = $id;
    }

    /**
     * Sets time of the log record.
     * @param integer $time
     * @return void
     * @since 1.0.0
     */
    public function setTime( $time ) {
        $this->time = $time;
    }

    /**
     * Sets message of the log record.
     * @param string $message
     * @return void
     * @since 1.0.0
     */
    public function setMessage( $message ) {
        $this->message = $message;
    }

    /**
     * Sets stack trace of the log record.
     * @param array $stack_trace
     * @return void
     * @since 1.0.0
     */
    public function setStackTrace( $stack_trace = [] ) {
        $this->error_log = $stack_trace;
    }

    /**
     * Adds stack trace message to the log record.
     * @param string $trace
     * @return void
     * @since 1.0.0
     */
    public function addStackTrace( $trace ) {
        array_push( $this->stack_trace, $trace );
    }
}

endif;
