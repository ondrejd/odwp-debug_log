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
     * @var array $error_log
     * @since 1.0.0
     */
    protected $error_log;// Should be "Stack Trace"

    /**
     * Construct.
     * @param integer $time
     * @param string $message
     * @param array $error_log
     * @since 1.0.0
     */
    public function __construct( $time, $message, $error_log = [] ) {
        $this->time = $time;
        $this->message = $message;
        $this->error_log = $error_log;
    }

    /**
     * Returns time of the log record.
     * @param boolean $formatted (Optional.)
     * @return integer
     * @since 1.0.0
     */
    public function getTime( $formatted = false ) {
        return $formatted === true ? date( 'j.n.Y H:i:s', $this->time ) : $this->time;
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
    public function getErrorLog() {
        return $this->error_log;
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
     * @param array $error_log
     * @return void
     * @since 1.0.0
     */
    public function setErrorLog( $error_log = [] ) {
        $this->error_log = $error_log;
    }

    /**
     * Adds stack trace message to the log record.
     * @param string $message
     * @return void
     * @since 1.0.0
     */
    public function addErrorLog( $message ) {
        array_push( $this->error_log, $message );
    }
}

endif;
