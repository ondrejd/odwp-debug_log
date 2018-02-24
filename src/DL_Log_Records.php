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

/**
 * Class that holds all records for {@see DL_Log_Table}.
 * @since 1.0.0
 */
class DL_Log_Records implements ArrayAccess, Countable {
    /**
     * @var array $records
     * @since 1.0.0
     */
    private $records;

    /**
     * Constructor.
     * @param array $records (Optional.)
     * @return void
     * @since 1.0.0
     */
    public function __construct( $records = [] ) {
        $this->records = $records;
    }

    /**
     * Part of {@see ArrayAccess} implementation.
     * @param int $offset
     * @return boolean Returns TRUE if given offset exists.
     * @see ArrayAccess::offsetExists
     * @since 1.0.0
     */
    public function offsetExists( $offset ) {
        return isset( $this->records[$offset] );
    }

    /**
     * Part of {@see ArrayAccess} implementation.
     * @param int $offset
     * @return DL_Log_Record|null Returns correct record.
     * @see ArrayAccess::offsetExists
     * @since 1.0.0
     */
    public function offsetGet( $offset ) {
        if( $this->offsetExists( $offset ) ) {
            return $this->records[$offset];
        }

        return null;
    }

    /**
     * Part of {@see ArrayAccess} implementation.
     * @param int $offset
     * @param DL_Log_Record $value
     * @return void
     * @see ArrayAccess::offsetExists
     * @since 1.0.0
     */
    public function offsetSet( $offset, $value ) {
        if( ! ( $value instanceof DL_Log_Record ) ) {
            // TODO Throw an error?
            return;
        }

        if( empty( $offset ) ) {
            $this->records[] = $value;
        } else {
            $this->records[$offset] = $value;
        }
    }

    /**
     * Part of {@see ArrayAccess} implementation.
     * @param int $offset
     * @return void
     * @see ArrayAccess::offsetExists
     * @since 1.0.0
     */
    public function offsetUnset( $offset ) {
        unset( $this->records[$offset] );
    }

    /**
     * Part of {@see Countable} implementation.
     * @return int Returns count of records.
     * @see Countable::count
     * @since 1.0.0
     */
    public function count() {
        return count( $this->records );
    }

    /**
     * @return array Returns array with data.
     * @since 1.0.0
     */
    public function getRecords() {
        return $this->records;
    }
}
