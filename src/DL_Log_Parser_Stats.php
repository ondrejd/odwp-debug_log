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


if ( ! class_exists( 'DL_Log_Parser_Stats' ) ) :

/**
 * Statistics for log parsing action.
 *
 * @author Ondřej Doněk <ondrejd@gmail.com>
 * @since 1.0.0
 */
class DL_Log_Parser_Stats {

    /**
     * @since 1.0.0
     * @var int $total_count
     */
    protected $total_count = 0;

    /**
     * @since 1.0.0
     * @var array $count_by_type
     */
    protected $count_by_type = [
        DL_Log_Record::TYPE_DLPARSER => 0,
        DL_Log_Record::TYPE_ERROR    => 0,
        DL_Log_Record::TYPE_NOTICE   => 0,
        DL_Log_Record::TYPE_OTHER    => 0,
        DL_Log_Record::TYPE_PARSER   => 0,
        DL_Log_Record::TYPE_WARNING  => 0,
    ];

    /**
     * @since 1.0.0
     * @var array $count_by_period
     */
    protected $count_by_period = [
        DL_Log_Table::VIEW_TODAY     => 0,
        DL_Log_Table::VIEW_YESTERDAY => 0,
        DL_Log_Table::VIEW_EARLIER   => 0,
    ];

    /**
     * Increment selected `count_by_type` by one.
     *
     * @param string $type
     * @return void
     * @since 1.0.0
     */
    public function increment_count_by_type( string $type ) {

        if ( array_key_exists( $type, $this->count_by_type ) ) {
            $this->count_by_type[$type]++;
        }
    }

    /**
     * Increment selected `count_by_period` by one.
     *
     * @param string $period
     * @return void
     * @since 1.0.0
     */
    public function increment_count_by_period( string $period ) {

        if ( array_key_exists( $period, $this->count_by_period ) ) {
            $this->count_by_period[$period]++;
        }
    }

    /**
     * Return total count of log records.
     *
     * @return int
     * @since 1.0.0
     */
    public function get_total_count() : int {
        return $this->total_count;
    }

    /**
     * Return count of log records by their type.
     *
     * @param string $type
     * @return int
     * @since 1.0.0
     */
    public function get_count_by_type( string $type ) : int {

        switch ( $type ) {
            case DL_Log_Record::TYPE_DLPARSER:
            case DL_Log_Record::TYPE_ERROR:
            case DL_Log_Record::TYPE_NOTICE:
            case DL_Log_Record::TYPE_OTHER:
            case DL_Log_Record::TYPE_PARSER:
            case DL_Log_Record::TYPE_WARNING:
                return $this->count_by_type[$type];

            default:
                return $this->total_count;
        }
    }

    /**
     * Return count of log records by period when they did happen.
     *
     * @param string $period
     * @return int
     * @since 1.0.0
     */
    public function get_count_by_period( string $period ) : int {

        switch ( $period ) {
            case DL_Log_Table::VIEW_TODAY:
            case DL_Log_Table::VIEW_YESTERDAY:
            case DL_Log_Table::VIEW_EARLIER:
                return $this->count_by_period[$period];

            case DL_Log_Table::VIEW_ALL:
            default:
                return $this->total_count;
        }
    }

    /**
     * Set total count of log records.
     *
     * @param int $total_count
     * @return void
     * @since 1.0.0
     */
    public function set_total_count( int $total_count ) {
        $this->total_count = $total_count;
    }

}

endif;