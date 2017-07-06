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

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if( ! class_exists( 'DL_Log_Table' ) ) :

/**
 * Table with log.
 * @return string
 * @since 1.0.0
 */
class DL_Log_Table extends WP_List_Table {
    /**
     * Renders checkbox column.
     * @param array $row
     * @return string
     * @since 1.0.0
     */
    function column_cb( $row ) {
        return sprintf(
            '<input type="checkbox" name="log_item[]" value="%s">', $row['id']
        );
    }

    /**
     * Default function for rendering columns.
     * @param array $row
     * @param string $column
     * @return string
     * @since 1.0.0
     */
    public function column_default( $row, $column ) {
        switch( $column ) { 
            case 'id':
            case 'date':
            case 'text':
                return array_key_exists( $column, $row ) ? $row[$column] : '';
            default:
                return '';
                //return print_r( $row, true );
        }
    }

    /**
     * Renders `text` column with the `delete` and `view` action.
     * @param array $row
     * @return string
     * @since 1.0.0
     */
    public function column_text( $row ) {
        $id   = ( int ) $item['id'];
        $page = ( int ) filter_input( INPUT_REQUEST, 'page' );
        $text = $row['text'];

        $actions = [
            'view'   => sprintf( __( '<a href="?page=%s&action=%s&book=%s">Edit</a>', DL_SLUG ), $pg, 'edit', $id ),
            'delete' => sprintf( __( '<a href="?page=%s&action=%s&book=%s">Delete</a>', DL_SLUG ), $pg, 'delete', $id ),
        ];

        return sprintf('%1$s %2$s', $text, $this->row_actions( $actions ) );
    }

    /**
     * Returns array describing bulk actions available for the table.
     * @return array
     * @since 1.0.0
     */
    function get_bulk_actions() {
        $actions = [
            'delete' => __( 'Smaž', DL_SLUG ),
        ];
        return $actions;
    }

    /**
     * Returns array with table columns.
     * @return array
     * @since 1.0.0
     */
    public function get_columns() {
        $columns = [
            'cb'   => '<input type="checkbox">',
            'id'   => __( '<abbr title="Pořadové číslo">P.č.</abbr>', DL_LOG ),
            'date' => __( 'Datum a čas', DL_LOG ),
            'text' => __( 'Záznam', DL_LOG ),
        ];
        return $columns;
    }

    /**
     * Returns array with sortable table columns.
     * @return array
     * @since 1.0.0
     */
    public function get_sortable_columns() {
        $columns = [
            'id'   => ['id', false],
            'date' => ['date', false],
            'text' => ['text', false],
        ];
        return $columns;
    }

    /**
     * Prepares data items for the table.
     * @return void
     * @since 1.0.0
     */
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        // Set table items
        $data = $this->get_data();
        usort( $data, [__CLASS__, 'usort_reorder'] );
        $this->items = $data;
    }

    /**
     * Returns data from the `debug.log` file.
     * @return array
     * @since 1.0.0
     * @todo We should probably not read debug.log file directly in here but use some sort of cache instead.
     */
    protected function get_data() {
        $log_raw = @file( DL_LOG, FILE_SKIP_EMPTY_LINES );
        $log = [];
        $record = null;

        foreach( $log_raw as $log_line ) {
            $matches = preg_split(
                '/(\[[0-9]{2}-[a-zA-Z]{3}-[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2} [a-zA-Z]{0,3}\]) ([a-zA-Z\s\d:\/.\-\_\(\)]*)/',
                $log_line,
                -1,
                PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
            );

            if( is_array( $matches ) ) {
                if( count( $matches ) == 2 ) {
                    // This is normal log row (date and details)
                    if( ( $record instanceof DLLogRecord ) ) {
                        array_push( $log, $record );
                        $record = null;
                        $record = new DLLogRecord( '', '' );
                    } else {
                        $record = new DLLogRecord( '', '' );
                    }

                    $record->setTime( $matches[0] );
                    $record->setMessage( $matches[1] );
                }
                elseif( count( $matches ) == 1 && ( $record instanceof DLLogRecord ) ) {
                    if( strpos( $matches[0], '#' ) === 0 ) {
                        // This is just continue of of previous line (debug details)
                        $record->addErrorLog( $matches[0] );
                    }
                }
            }
        }

        if( ( $record instanceof DLLogRecord ) ) {
            array_push( $log, $record );
        }

        return $log;
    }

    /**
     * @internal Sorting method for the table data.
     * @param array $a The first row.
     * @param array $b The second row.
     * @return int
     * @since 1.0.0
     */
    protected function usort_reorder( $a, $b ) {
        $orderby = filter_input( INPUT_GET, 'orderby' );
        if( empty( $orderby ) ) {
            $orderby = 'id';
        }

        $order = filter_input( INPUT_GET, 'order' );
        if( empty( $order ) ) {
            $order = 'asc';
        }

        $result = strcmp( $a[$orderby], $b[$orderby] );

        return ( $order === 'asc' ) ? $result : -$result;
    }

}

endif;

//$myListTable = new My__List_Table();

