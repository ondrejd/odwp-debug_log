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

if( ! class_exists( 'DL_Log_Parser' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Parser.php' );
}

if( ! class_exists( 'DL_Log_Table' ) ) :

/**
 * Table with log. User options for the table are implemented partially in {@see DL_Log_Screen}.
 * @since 1.0.0
 * @todo Default column and direction for sorting should be set via user preferences.
 * @todo Default per page items count should be set via user preferences.
 * @todo Now we get all data at the first place and than we set pagination but it means that we are parsing data we don't need to - so this must be implemented other way.
 * @todo We should parse only rows that going to be displayed not all of them.
 */
class DL_Log_Table extends WP_List_Table {

    /**
     * @var string Ascendant direction of the sorting.
     * @since 1.0.0
     */
    const DEFAULT_SORT_DIR_ASC  = 'asc';

    /**
     * @var string Descendant direction of the sorting.
     * @since 1.0.0
     */
    const DEFAULT_SORT_DIR_DESC = 'desc';

    /**
     * @var string Comma-separated list of defaultly hidden columns.
     * @since 1.0.0
     */
    const DEFAULT_HIDDEN_COLS = 'id';

    /**
     * @var string Default per page items count.
     * @since 1.0.0
     */
    const DEFAULT_PER_PAGE = 10;

    /**
     * @var string Defaultly sorted column.
     * @since 1.0.0
     */
    const DEFAULT_SORT_COL = 'time';

    /**
     * @var string Default sorting direction.
     * @since 1.0.0
     */
    const DEFAULT_SORT_DIR = self::DEFAULT_SORT_DIR_DESC;

    /**
     * @var boolean Default settings for displaying icons instead of text in record type column.
     * @since 1.0.0
     */
    const DEFAULT_SHOW_ICONS = true;

    /**
     * @var boolean Default settings for displaying file names as HTML links to real source files.
     * @since 1.0.0
     */
    const DEFAULT_SHOW_LINKS = true;

    /**
     * @var DL_Log_Parser $parser
     * @since 1.0.0
     */
    protected $parser = null;

    /**
     * Returns default options for the table.
     * @return array
     * @since 1.0.0
     */
    public static function get_default_options() {
        return [
            'hidden_cols' => self::DEFAULT_HIDDEN_COLS,
            'per_page'    => self::DEFAULT_PER_PAGE,
            'show_icons'  => self::DEFAULT_SHOW_ICONS,
            'show_links'  => self::DEFAULT_SHOW_LINKS,
            'sort_col'    => self::DEFAULT_SORT_COL,
            'sort_dir'    => self::DEFAULT_SORT_DIR,
        ];
    }

    /**
     * Returns options for the table.
     * @return array
     * @since 1.0.0
     */
    public static function get_options() {
        $user = get_current_user_id();

        $hidden_cols = get_user_meta( $user, DL_Log_Screen::SLUG . '-hidden_cols', true );
        if( strlen( $hidden_cols ) == 0 ) {
            $hidden_cols = self::DEFAULT_HIDDEN_COLS;
        }

        $per_page = get_user_meta( $user, DL_Log_Screen::SLUG . '-per_page', true );
        if( strlen( $per_page ) == 0 ) {
            $per_page = self::DEFAULT_PER_PAGE;
        }
;
        $show_icons = get_user_meta( $user, DL_Log_Screen::SLUG . '-show_icons', true );
        if( strlen( $show_icons ) == 0 ) {
            $show_icons = self::DEFAULT_SHOW_ICONS;
        }

        $show_links = get_user_meta( $user, DL_Log_Screen::SLUG . '-show_links', true );
        if( strlen( $show_links ) == 0 ) {
            $show_links = self::DEFAULT_SHOW_LINKS;
        }

        $sort_col = get_user_meta( $user, DL_Log_Screen::SLUG . '-sort_col', true );
        if( strlen( $sort_col ) == 0 ) {
            $sort_col = self::DEFAULT_SORT_COL;
        }

        $sort_dir = get_user_meta( $user, DL_Log_Screen::SLUG . '-sort_dir', true );
        if( strlen( $sort_dir ) == 0 ) {
            $sort_dir = self::DEFAULT_SORT_DIR;
        }

        $defaults = self::get_default_options();
        $currents = [
            'hidden_cols' => $hidden_cols,
            'per_page'    => ( int ) $per_page,
            'show_icons'  => ( bool ) $show_icons,
            'show_links'  => ( bool ) $show_links,
            'sort_col'    => $sort_col,
            'sort_dir'    => $sort_dir,
        ];

        return array_merge( $defaults, $currents );
    }

    /**
     * Renders checkbox column.
     * @param DL_Log_Record $item
     * @return string
     * @since 1.0.0
     */
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="log_item[]" value="%s">', $item->getId()
        );
    }

    /**
     * Default function for rendering columns.
     * @param DL_Log_Record $item
     * @param string $column_name
     * @return string
     * @since 1.0.0
     */
    public function column_default( $item, $column_name ) {
        switch( $column_name ) { 
            case 'id':
                return $item->getId();

            case 'time':
                return $item->getTime( true );

            case 'text':
                return $item->getMessage();

            case 'type':
                return $item->getType();

            default:
                return '';
        }
    }

    /**
     * Renders column with log record type.
     * @param DL_Log_Record $item
     * @return string
     * @since 1.0.0
     */
    public function column_type( DL_Log_Record $item ) {
        $show_icons = self::get_options()['show_icons'];

        switch( $item->getType() ) {
            case DL_Log_Record::TYPE_ERROR:
                $lbl = __( 'Chyba', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-warning" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_NOTICE:
                $lbl = __( 'Upozornění', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-format-status" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_OTHER:
                $lbl = __( 'Ostatní', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-editor-help" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_PARSER:
                $lbl = __( 'Chyba kódu', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons  dashicons-thumbs-down" title="' . $lbl . '"></span>' : $lbl;

            case DL_Log_Record::TYPE_WARNING:
                $lbl = __( 'Varování', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-flag" title="' . $lbl .'"></span>' : $lbl;

            case DL_Log_Record::TYPE_ODWPDL:
                $lbl = __( 'Chyba log parseru', DL_SLUG );
                return ( $show_icons === true ) ? '<span class="dashicons dashicons-no" title="' . $lbl .'"></span>' : $lbl;
        }
    }

    /**
     * Renders `text` column with the `delete` and `view` action.
     * @param DL_Log_Record $item
     * @return string
     * @since 1.0.0
     * @todo Display also stack trace!
     */
    public function column_text( DL_Log_Record $item ) {
        $id      = ( int ) $item->getId();
        $page    = ( int ) filter_input( INPUT_GET, 'page' );
        $text    = $item->getMessage();
        $actions = [
            'view'   => sprintf( __( '<a href="?page=%s&amp;action=%s&amp;record=%s">Zobrazit</a>', DL_SLUG ), $page, 'view', $id ),
            'delete' => sprintf( __( '<a href="?page=%s&amp;action=%s&amp;record=%s">Smazat</a>', DL_SLUG ), $page, 'delete', $id ),
        ];

        return sprintf('%1$s %2$s', $text, $this->row_actions( $actions ) );
    }

    /**
     * Custom method for displaying rows.
     * @return void
     * @since 1.0.0
     */
    public function display_rows() {
        foreach( $this->items as $item ) {
            if( ! ( $item instanceof \DL_Log_Record ) ) {
                continue;
            }

            $this->single_row( $item );
        }
    }

    /**
     * Returns array describing bulk actions available for the table.
     * @return array
     * @since 1.0.0
     */
    public function get_bulk_actions() {
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
            'time' => __( 'Datum a čas', DL_LOG ),
            'type' => __( 'Typ', DL_LOG ),
            'text' => __( 'Záznam', DL_LOG ),
        ];
        return $columns;
    }

    /**
     * Returns array with table columns that can be hidden.
     * @return array
     * @since 1.0.0
     */
    public function get_hideable_columns() {
        $columns = [
            'id'   => ['id', true],
            'type' => ['type', false],
        ];
        return $columns;
    }

    /**
     * Returns array with table columns that are hidden.
     * @return array
     * @since 1.0.0
     * @todo Get really hidden columns from user meta!
     */
    public function get_hidden_columns() {
        // Prepare defaultly hidden columns
        $defaults = [];
        foreach( $this->get_hideable_columns() as $key => $spec ) {
            if( ( bool ) $spec[1] === true ) {
                $defaults[] = $key;
            }
        }

        // Get hidden columns by user
        $hidden   = []; // TODO Get it from user_meta!

        // Returns it
        return array_merge( $defaults, $hidden );
    }

    /**
     * Returns array with sortable table columns.
     * @return array
     * @since 1.0.0
     */
    public function get_sortable_columns() {
        $columns = [
            'id'   => ['id', false],
            'time' => ['time', false],
            'text' => ['text', false],
            'type' => ['type', false],
        ];
        return $columns;
    }

    /**
     * Prepares data items for the table.
     * @return void
     * @since 1.0.0
     */
    public function prepare_items() {
        $options  = self::get_options();

        // Set up column headers
        $this->_column_headers = [
            $this->get_columns(),
            $this->get_hidden_columns(),
            $this->get_sortable_columns(),
        ];

        // Prepare data
        $this->parser = new DL_Log_Parser( null, $options );
        $this->parser->sort( [
            'sort_col'    => $options['sort_col'],
            'sort_dir'    => $options['sort_dir'],
        ] );

        $this->set_pagination_args( [
            'total_items' => $this->parser->get_total_count(),
            'per_page'    => $this->parser->get_options( 'per_page', self::DEFAULT_PER_PAGE ),
        ] );

        $this->items = $this->parser->get_data( [
            'page' => $this->get_pagenum()
        ] );
    }
}

endif;
