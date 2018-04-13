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

if( ! class_exists( 'DL_Log_Record' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Record.php' );
}

if( ! class_exists( 'DL_Log_Highlighter' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Highlighter.php' );
}

if( ! class_exists( 'DL_Log_Parser' ) ) :

/**
 * Parser for WordPress debug.log files.
 * Using the parser:
 * <pre>
 * // Initialize parser:
 * $options =  [
 *     'per_page'   => 10,
 *     'show_links' => true,
 *     'sort_col'   => 'time',
 *     'sort_dir'   => DL_Log_Table::DEFAULT_SORT_DIR_DESC,
 * ];
 * // 1) Where 'log' => '...' should be raw log text.
 * $parser = new DL_Log_Parser( ['log' => '...'], $options );
 * // 2) Where 'file' => '...' should be full path to debug.log file
 * $parser = new DL_Log_Parser( ['file' => '...'], $options );
 * // 3) Using default debug.log file path.
 * $parser = new DL_Log_Parser( null, $options );
 *
 * // Using parser:
 *
 * // We can either use just {@see DL_Log_Parser::prepare()} if we just need count of total items:
 * $parser->prepare();
 * // But if we need also filtering we should parse whole log immediately:
 * $parser->parse();
 * // Commonly we also need to sort data:
 * $parser->sort();
 * // Anyway at the end we need data items; here from the eleventh page:
 * $items = $parser->get_data( ['page' => 11] );
 * // here from starting to ending index:
 * $items = $parser->get_data( ['from' => 0, 'to' => 35] );
 *
 * // Filtering data
 * $this->parse();
 * $parser->filter( ['type' => DL_Log_Record::TYPE_ERROR] );
 * $items = $parser->get_data( ['page' => 3] );
 * </pre>
 * @since 1.0.0
 */
class DL_Log_Parser {
    /**
     * @var array $options
     * @since 1.0.0
     */
    protected $options;

    /**
     * @internal If is <em>TRUE</em> the log data are already prepared.
     * @var boolean $is_prepared
     * @since 1.0.0
     */
    private $is_prepared = false;

    /**
     * @internal If is <em>TRUE</em> the log data are already parsed.
     * @var boolean $is_parsed
     * @since 1.0.0
     */
    private $is_parsed = false;

    /**
     * @var array $log_raw Prepared log.
     * @since 1.0.0
     */
    protected $log_raw = [];

    /**
     * @var array $log Parsed log.
     * @since 1.0.0
     */
    protected $log;

    /**
     * @var array $log Parsed log (copy for canceling filters).
     * @since 1.0.0
     */
    private $log_unfiltered;

    /**
     * @internal Used as temporary variable when parsing data.
     * @var \DL_Log_Record $_record
     * @since 1.0.0
     */
    private $_record = null;

    /**
     * @var string "Order" sorting parameter.
     * @since 1.0.0
     */
    private $_order;

    /**
     * @var string "Order by" sorting parameter.
     * @since 1.0.0
     */
    private $_orderby;

    /**
     * @var string View name.
     * @since 1.0.0
     */
    private $_view;

    /**
     * @var boolean $saved
     * @since 1.0.0
     */
    private $saved = true;

    /**
     * Constructor.
     * @param array $args Array with arguments (can contains either "log" or "file" properties).
     * @param boolean $options Options for parsing.
     * @return void
     * @since 1.0.0
     */
    public function __construct( $args = [], $options = [] ) {
        $this->parse_args( $args );
        $this->set_options( $options );
        $this->prepare();
        $this->parse();
    }

    /**
     * @internal Destructor - ensures that log file is saved.
     * @return void
     * @since 1.0.0
     */
    public function __destruct() {
        if( $this->is_saved() ) {
            $this->save();
        }
    }

    /**
     * @internal Parse constructor arguments
     * @param array $args
     * @return void
     * @since 1.0.0
     */
    private function parse_args( $args = [] ) {
        if( ! is_array( $args ) ) {
            return;
        }

        if( array_key_exists( 'log', $args ) ) {
            if( is_array( $args['log'] ) ) {
                $this->log_raw = $args['log'];
            }
        }

        if( array_key_exists( 'file', $args ) ) {
            $this->log_file = $args['file'];
        }
    }

    /**
     * Returns parser options.
     * @param string $option (Optional.)
     * @param mixed $default (Optional.)
     * @return array
     * @since 1.0.0
     */
    public function get_options( $option = null, $default = null ) {
        if( empty( $option ) || is_null( $option ) ) {
            return $this->options;
        }

        if( array_key_exists( $option, $this->options ) ) {
            return $this->options[$option];
        }

        return $default;
    }

    /**
     * @internal Returns "Order by" parameter for sorting.
     * @return string
     * @since 1.0.0
     */
    public function get_sort_order() {
        if( empty( $this->_order ) ) {
            $this->_order = filter_input( INPUT_GET, 'order' );

            if( empty( $this->_order ) ) {
                $this->_order = self::get_options()['sort_dir'];
            }
        }

        return $this->_order;
    }

    /**
     * @internal Returns "Order by" parameter for sorting.
     * @return string
     * @since 1.0.0
     */
    public function get_sort_orderby() {
        if( empty( $this->_orderby ) ) {
            $this->_orderby = filter_input( INPUT_GET, 'orderby' );

            if( empty( $this->_orderby ) ) {
                $this->_orderby = self::get_options()['sort_col'];
            }
        }

        return $this->_orderby;
    }

    /**
     * Returns total count of log items.
     * @return integer
     * @since 1.0.0
     */
    public function get_total_count() {
        if( $this->is_parsed === true ) {
            return count( $this->log );
        }
        else if( $this->is_prepared === true ) {
            return count( $this->log_raw );
        }

        return 0;
    }

    /**
     * Returns name of used view.
     * @return string
     * @since 1.0.0
     */
    public function get_view() {
        return $this->view;
    }

    /**
     * Sets parser options.
     * @param array $options
     * @since 1.0.0
     */
    public function set_options( $options = [] ) {
        $this->options = $options;
    }

    /**
     * Prepares log data from raw data. After this is possible to get total count of items.
     * @return void
     * @since 1.0.0
     */
    public function prepare() {
        if( $this->is_prepared === true ) {
            return;
        }

        $this->log_raw = file( DL_LOG, FILE_SKIP_EMPTY_LINES );
        $this->is_prepared = true;
    }

    /**
     * Parses prepared log data.
     * @return void
     * @since 1.0.0
     */
    public function parse() {
        if( $this->is_parsed === true ) {
            return;
        }

        if( $this->is_prepared !== true ) {
            $this->prepare();
        }

        $this->log = array();

        foreach( $this->log_raw as $index => $log_line ) {
            $this->parse_line( $log_line, $index );
        }

        if( ( $this->_record instanceof DL_Log_Record ) ) {
            $this->log[] = $this->_record;
        }

        $this->log_unfiltered = $this->log;
        $this->is_parsed = true;
    }

    /**
     * @internal Parse single log line.
     * @param string $line
     * @param integer $line_num
     * @return void
     * @since 1.0.0
     */
    private function parse_line( $line, $line_num ) {
        $matches = preg_split(
            '/(\[[0-9]{2}-[a-zA-Z]{3}-[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2} [a-zA-Z]{0,3}\])/',
            $line,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );

        if( ! is_array( $matches ) ) {
            //odwpdl_write_log( 'DL_Log_Parser: Parser error (1).' );
            /**/echo '<pre>';
            echo 'WRONG MATCHES [1]:'.PHP_EOL;
            var_dump( $line );
            var_dump( $line_num );
            var_dump( $matches );
            echo '</pre>';
            return;
        }

        if( count( $matches ) == 2 ) {
            // This is normal log row (date and details)
            if( ( $this->_record instanceof DL_Log_Record ) ) {
                $this->log[] = $this->_record;
            }

            $_msg = trim( $matches[1] );
            $type = $this->parse_type( $_msg );
            $msg  = str_replace( $type . ': ', '', $_msg );

            $this->_record = new DL_Log_Record( 0, '', '' );
            $this->_record->set_id( $line_num + 1 );
            $this->_record->set_time( strtotime( trim( $matches[0], '[]' ) ) );
            $this->_record->set_type( $type );
            $this->_record->set_message( $msg );
        }
        elseif( count( $matches ) == 1 && ( $this->_record instanceof DL_Log_Record ) ) {
            if( strpos( $matches[0], '#' ) === 0 ) {
                // This is just continue of of previous line (debug details)
                $this->_record->add_trace( $matches[0] );
            }
        }
        else {
            if( empty( trim( $matches[0] ) ) ) {
                return;
            }

            //odwpdl_write_log( 'DL_Log_Parser: Parser error (2).' );
            /**/echo '<pre>';
            echo 'WRONG MATCHES [2]:'.PHP_EOL;
            var_dump( $line );
            var_dump( $line_num );
            var_dump( $matches );
            echo '</pre>';
            return;
        }
    }

    /**
     * Filtres parsed log data.
     * @param function $filter_func
     * @return void
     * @since 1.0.0
     */
    public function filter( $filter_func ) {
        if( $this->is_parsed !== true ) {
            $this->parse();
        }

        $temp = array_filter( $this->log, $filter_func );
        $this->log = $temp;
    }

    /**
     * Sets view.
     * @param string $view
     * @return void
     * @since 1.0.0
     */
    public function set_view( $view ) {
        if( $this->_view === $view ) {
            return;
        }

        if( $this->is_parsed !== true ) {
            $this->parse();
        }

        $log_by_view = [];

        foreach( $this->log as $item ) {
            if( $view === 'today' && $item->was_today() ) {
                $log_by_view[] = $item;
            }
            elseif( $view === 'yesterday' && $item->was_yesterday() ) {
                $log_by_view[] = $item;
            }
            elseif( $view === 'earlier' && ( ! $item->was_today() && ! $item->was_yesterday() ) ) {
                $log_by_view[] = $item;
            }
            elseif( $view === 'all' ) {
                $log_by_view[] = $item;
            }
        }

        $this->log = $log_by_view;
    }

    /**
     * Sort parsed (or parsed and filtered) data.
     * @param array $args (Optional.) Sorting arguments ('sort_col' and 'sort_dir').
     * @return void
     * @since 1.0.0
     */
    public function sort( $args = [] ) {
        if( $this->is_parsed !== true ) {
            $this->parse();
        }

        if( array_key_exists( 'sort_col', $args ) ) {
            $this->_orderby = $args['sort_col'];
        }

        if( array_key_exists( 'sort_dir', $args ) ) {
            $this->_order = $args['sort_dir'];
        }

        usort( $this->log, [$this, 'usort_reorder'] );
    }

    /**
     * Reset used sorting or filters.
     * @return void
     * @since 1.0.0
     */
    public function reset() {
        $this->log = $this->log_unfiltered;
    }

    /**
     * Returns data from the log.
     * @return array
     * @since 1.0.0
     */
    public function get_data( $options = ['page' => -1] ) {
        if( $this->is_parsed !== true ) {
            $this->parse();
        }

        $data = [];

        if( $options['page'] == -1 ) {
            $data = $this->log;
        } else {
            $per_page = $this->get_options( 'per_page', DL_Log_Table::DEFAULT_PER_PAGE );
            $current  = array_key_exists( 'page', $options ) ? $options['page'] : 1;
            $data     = array_slice( $this->log, ( ( $current - 1 ) * $per_page ), $per_page );
        }

        return $data;
    }

    /**
     * @internal Recognizes and returns type of log record from the given string.
     * @param string $str
     * @return string
     * @see DL_Log_Table::get_data()
     * @since 1.0.0
     */
    public function parse_type( $str ) {
        if( strpos( $str, DL_Log_Record::TYPE_ERROR ) === 0 ) {
            return DL_Log_Record::TYPE_ERROR;
        }
        else if( strpos( $str, DL_Log_Record::TYPE_NOTICE ) === 0 ) {
            return DL_Log_Record::TYPE_NOTICE;
        }
        else if( strpos( $str, DL_Log_Record::TYPE_PARSER ) === 0 ) {
            return DL_Log_Record::TYPE_PARSER;
        }
        else if( strpos( $str, DL_Log_Record::TYPE_WARNING ) === 0 ) {
            return DL_Log_Record::TYPE_WARNING;
        }

        return DL_Log_Record::TYPE_OTHER;
    }

    /**
     * @internal Returns the same text as given but the mentioned PHP source files are made accessible as links.
     * @link http://php.net/manual/en/function.get-defined-functions.php
     * @link https://stackoverflow.com/questions/13421317/finding-the-php-file-at-run-time-where-a-method-of-an-object-was-defined
     * @link https://stackoverflow.com/questions/2222142/how-to-find-out-where-a-function-is-defined
     * @param string $str
     * @return string
     * @see DL_Log_Parser::get_data()
     * @since 1.0.0
     * @todo Rename to `highlight_text_line`!
     */
    public function make_source_links( $str ) {
        if ( strpos( $str, ABSPATH ) === false ) {
            return $str;
        }

        /** @var string $ret Holds HTML to return. */
        $ret = $str;
        // Variables based on user settings
        $short_src_links = (bool) $this->get_options( 'short_src_links', DL_Log_Table::DEFAULT_SHORT_SRC_LINKS );
        $src_win_width = (int) $this->get_options( 'src_win_width', DL_Log_Table::DEFAULT_SRC_WIN_WIDTH );
        $src_win_height = (int) $this->get_options( 'src_win_height', DL_Log_Table::DEFAULT_SRC_WIN_HEIGHT );
        // Other variables
        $abspath = str_replace( '/', '\/', ABSPATH );
        $regexp  = '/((' . $abspath . '[a-zA-Z0-9.\-\_\/]*))/';

        // Replace some entities that occur in log
        $ret = str_replace( '&#8217;', '’', $ret );

        // 1) Search for file links
        $file_links = [];
        $matches = preg_split( $regexp, $str, -1, PREG_SPLIT_DELIM_CAPTURE );

        foreach( $matches as $match ) {
            if( strpos( $match, ABSPATH ) === 0 ) {
                // Array item contains file link
                if( ! in_array( $match, $file_links ) ) {
                    $file_links[] = $match;
                }
            }
            else {
                // Other text
                // Highlight numbers
                $ret = DL_Log_Highlighter::highlight_numbers( $match, $ret );
                // Highlight function names
                $ret = DL_Log_Highlighter::highlight_functions( $match, $ret );
                // Highlight strings in brackets
                $ret = DL_Log_Highlighter::highlight_strings( $match, $ret );
                // Highlight "undefined variable: *"
                $ret = DL_Log_Highlighter::highlight_undefined_variable( $match, $ret );
                // Highlight others
                $ret = DL_Log_Highlighter::highlight_others( $match, $ret );
            }
        }

        // 2) Update string with HTML anchors for file links
        foreach( $file_links as $file_link ) {
            $file_name = $file_link;

            // Make link shorter if user wants it
            if( $short_src_links === true ) {
                $file_name = str_replace( WP_PLUGIN_DIR . '/' . DL_NAME, '&hellip;/' . DL_NAME, $file_name );
                $file_name = str_replace( WP_PLUGIN_DIR, '&hellip;/plugins', $file_name );
                $file_name = str_replace( ABSPATH, '&hellip;/', $file_name );
            }

            // Create link (all HTML)
            $url = add_query_arg( 'file', $file_link, plugins_url( 'odwpdl-show_url.php', DL_FILE ) ) .
                    "&amp;TB_iframe=true&amp;height={$src_win_height}&amp;width={$src_win_width}";
            $ret = str_replace(
                    $file_link,
                    '<a class="thickbox" href="' . $url . '" title="' . $file_link . '" target="blank">' .
                        '<code>' . $file_name . '</code>' .
                    '</a>',
                    $ret
            );
        }

        // 3) Process "on line 11" or ":11" ...
        // TODO Process file line.

        return $ret;
    }

    /**
     * @internal Sorting method for the table data.
     * @param DL_Log_Record $a The first row.
     * @param DL_Log_Record $b The second row.
     * @return integer
     * @since 1.0.0
     */
    protected function usort_reorder( DL_Log_Record $a, DL_Log_Record $b ) {
        $order = $this->get_sort_order();
        $orderby = $this->get_sort_orderby();
        $val1 = null;
        $val2 = null;

        switch( $orderby ) {
            case 'id':
                $val1 = $a->get_id();
                $val2 = $b->get_id();
                break;

            case 'time':
                $val1 = $a->get_time();
                $val2 = $b->get_time();
                break;

            case 'text':
                $val1 = $a->get_message();
                $val2 = $b->get_message();
                break;

            case 'type':
                $val1 = $a->get_type();
                $val2 = $b->get_type();
                break;
        }

        $result = strcmp( $val1, $val2 );

        return ( $order === 'asc' ) ? $result : -$result;
    }

    /**
     * Deletes record at given row (but does not save the file).
     * @param integer $line
     * @return boolean
     * @since 1.0.0
     * @todo This is wrong! It should consider <em>stack trace</em>!
     */
    public function delete_record( $line ) {
        // It should be, but just for case...
        if( $this->is_parsed !== false ) {
            return false;
        }

        // Check if given line number is
        $record = $this->log[$line];
        if( array_key_exists( $line, $this->log_raw ) ) {
            unset( $this->log_raw[$row] );
            $this->saved = false;
            return true;
        }

        return false;
    }

    /**
     * Đeletes records at given rows (but does not save the file).
     * @param integer $lines
     * @return mixed Returns FALSE if something went wrong otherwise count of deleted lines.
     * @since 1.0.0
     */
    public function delete_records( $lines ) {
        $ret = true;
        odwpdl_write_log( $lines ); // XXX Remove this!
        sort( $lines, SORT_NUMERIC );
        odwpdl_write_log( $lines ); // XXX Remove this!
        $lines = array_reverse( $lines );
        odwpdl_write_log( $lines ); // XXX Remove this!

        foreach( $lines as $line ) {
            $ret = $this->delete_record( $line );
        }

        return $ret;
    }

    /**
     * Saves {@see DL_Log_Parser::$log_raw} into the {@see DL_Log_Parser::$file}.
     * @return boolean
     * @since 1.0.0
     */
    public function save() {
        //if( file_put_contents( DL_LOG, implode( PHP_EOL, $this->log_raw ) ) === false ) {
        if( file_put_contents( DL_LOG, $this->log_raw ) === false ) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if log file is saved (or if there are unsaved changes).
     * @return boolean
     * @since 1.0.0
     */
    public function is_saved() {
       return $this->saved;
    }
}

endif;
