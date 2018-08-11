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

if ( ! class_exists( 'DL_Log_Record' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Record.php' );
}

if ( ! class_exists( 'DL_Log_Highlighter' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Highlighter.php' );
}

if ( ! class_exists( 'DL_Log_Parser_Stats' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Parser_Stats.php' );
}

if ( ! class_exists( 'DL_Log_Parser' ) ) :

/**
 * Parser for WordPress debug.log files.
 *
 * Snippet below explains how to initialize the parser:
 *
 * <pre>
 * // Set up options:
 * $options =  [
 *     'per_page'   => 10,
 *     'show_links' => true,
 *     'sort_col'   => 'time',
 *     'sort_dir'   => DL_Log_Table::DEFAULT_SORT_DIR_DESC,
 * ];
 *
 * // Set up log source log file:
 * $source = '/usr/home/test/debug.log'; // When you have full path of log file
 * $source = null;                       // When you want use WP log file
 *
 * // Initialize the parser:
 * $parser = new DL_Log_Parser( $source, $options );
 * </pre>
 *
 * And here is snippet how to use initialized parser:
 *
 * <pre>
 * // If you need to prepare parser (total count of rows will be available):
 * $parser->prepare();
 *
 * // If you need also filtering you should parse whole log:
 * $parser->parse();
 *
 * // Now here are snippets how to get your data:
 *
 * // Get data by page:
 * $items = $parser->get_data( ['page' => 11] );
 *
 * // Get data by range:
 * $items = $parser->get_data( ['from' => 0, 'to' => 35] );
 * <pre>
 *
 * Here is an example how to filter data:
 *
 * <pre>
 * $this->parse();
 * $parser->filter( ['type' => DL_Log_Record::TYPE_ERROR] );
 * $items = $parser->get_data( ['page' => 3] );
 * </pre>
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @since 1.0.0
 */
class DL_Log_Parser {

    /**
     * @since 1.0.0
     * @var string
     */
    const FLAG_ERASED = 'ERASED' . PHP_EOL;

    /**
     * @since 1.0.0
     * @var array $options
     */
    protected $options;

    /**
     * @since 1.0.0
     * @var bool $is_prepared If is TRUE the log data are already prepared.
     */
    private $is_prepared = false;

    /**
     * @since 1.0.0
     * @var bool $is_parsed If is TRUE the log data are already parsed.
     */
    private $is_parsed = false;

    /**
     * @since 1.0.0
     * @var array $log_raw Prepared log.
     */
    protected $log_raw = [];

    /**
     * @since 1.0.0
     * @var array $log Parsed log.
     */
    protected $log;

    /**
     * @since 1.0.0
     * @var array $log_unfiltered Parsed log (copy for canceling filters).
     */
    private $log_unfiltered;

    /**
     * @since 1.0.0
     * @var \DL_Log_Record $_record Temporary variable when parsing data.
     */
    private $_record = null;

    /**
     * @since 1.0.0
     * @var string $_order "Order" sorting parameter.
     */
    private $_order;

    /**
     * @since 1.0.0
     * @var string $_orderby "Order by" sorting parameter.
     */
    private $_orderby;

    /**
     * @since 1.0.0
     * @var string $_view View name.
     */
    private $_view;

    /**
     * @since 1.0.0
     * @var bool $is_saved
     */
    private $is_saved = true;

    /**
     * @since 1.0.0
     * @var DL_Log_Parser_Stats $stats
     */
    private $stats;

    /**
     * Constructor.
     *
     * @param array $options
     * @return void
     * @since 1.0.0
     */
    public function __construct( $options = [] ) {

        // Set parser options
        $this->set_options( $options );

        // Prepare raw log
        $this->prepare();

        // Parse log
        $this->parse();
    }

    /**
     * Destructor.
     *
     * @return void
     * @since 1.0.0
     */
    public function __destruct() {

        // Ensure that log file is saved.
        if ( $this->is_saved() ) {
            $this->save();
        }
    }

    /**
     * Return parser options.
     * 
     * @param string $option
     * @param mixed $default
     * @return array
     * @since 1.0.0
     */
    public function get_options( $option = null, $default = null ) {
        if ( empty( $option ) || is_null( $option ) ) {
            return $this->options;
        }

        if ( array_key_exists( $option, $this->options ) ) {
            return $this->options[$option];
        }

        return $default;
    }

    /**
     * Return "Order by" parameter for sorting.
     * 
     * @return string
     * @since 1.0.0
     */
    public function get_sort_order() {
        if ( empty( $this->_order ) ) {
            $this->_order = filter_input( INPUT_GET, 'order' );

            if ( empty( $this->_order ) ) {
                $this->_order = self::get_options()['sort_dir'];
            }
        }

        return $this->_order;
    }

    /**
     * Return "Order by" parameter for sorting.
     * 
     * @return string
     * @since 1.0.0
     */
    public function get_sort_orderby() {
        if ( empty( $this->_orderby ) ) {
            $this->_orderby = filter_input( INPUT_GET, 'orderby' );

            if ( empty( $this->_orderby ) ) {
                $this->_orderby = self::get_options()['sort_col'];
            }
        }

        return $this->_orderby;
    }

    /**
     * Return total count of log items.
     *
     * @deprecated Use `$parser->get_log_stats()->get_total_count()` instead!
     * @return int
     * @since 1.0.0
     */
    public function get_total_count() {
        return $this->get_stats()->get_total_count();
    }

    /**
     * Return name of used view.
     * 
     * @return string
     * @since 1.0.0
     */
    public function get_view() {
        return $this->view;
    }

    /**
     * Set parser options.
     * 
     * @param array $options
     * @since 1.0.0
     */
    public function set_options( $options = [] ) {
        $this->options = $options;
    }

    /**
     * Prepare log parser.
     * 
     * @return void
     * @since 1.0.0
     */
    public function prepare() {

        // If log parser is already prepare do nothing
        if ( $this->is_prepared === true ) {
            return;
        }

        // Prepare log parser
        $this->stats          = new DL_Log_Parser_Stats();
        $this->log_raw        = file( DL_LOG, FILE_SKIP_EMPTY_LINES );
        $this->log            = [];
        $this->log_unfiltered = [];
        $this->stats->set_total_count( count( $this->log_raw ) );
        $this->is_prepared = true;
    }

    /**
     * Parse prepared log data.
     *
     * @return void
     * @since 1.0.0
     */
    public function parse() {

        // If log is already parsed do nothing
        if ( $this->is_parsed === true ) {
            return;
        }

        // If log parser is unprepared prepare it
        if ( $this->is_prepared !== true ) {
            $this->prepare();
            $this->log = [];
        }

        // Parse log line by line
        foreach ( $this->log_raw as $index => $log_line ) {

            // But skip already erased lines
            if ( $log_line !== self::FLAG_ERASED ) {
                $this->parse_line( $log_line, $index );
            }
        }

        // Sometimes record is not properly finished after parser is done so fix it
        if ( ( $this->_record instanceof DL_Log_Record ) ) {

            // update parser stats
            $this->update_stats();

            // and save and null the record
            $this->log[$this->_record->get_id()] = $this->_record;
            $this->_record = null;
        }

        // Update total count
        $this->stats->set_total_count( count( $this->log ) );

        // Set up some other parser properties
        $this->log_unfiltered = $this->log;
        $this->is_parsed = true;
    }

    /**
     * Parse single log line.
     *
     * @param string $line
     * @param int $line_num
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

        $parser_error_msg = 'DL_Log_Parser: Parser error (1) ["%s"|"%s"|"%s"]';

        // Line is empty or something is wrong with our regular expression
        if ( ! is_array( $matches ) ) {

            // TODO Don't forgot to comment this for release!
            if ( WP_DEBUG === true ) {
                odwpdl_write_log( sprintf( $parser_error_msg, $line_num, $line, json_encode($matches) ) );
            }

            return;
        }

        // This is normal log row (date and details)
        if ( count( $matches ) == 2 ) {

            // If there is initialized record update parser statistics and save record
            if ( ( $this->_record instanceof DL_Log_Record ) ) {
                $this->update_stats();
                $this->log[$this->_record->get_id()] = $this->_record;
            }

            $_msg = trim( $matches[1] );
            $type = $this->parse_type( $_msg );
            $msg  = str_replace( $type . ': ', '', $_msg );

            $this->_record = new DL_Log_Record( 0, '', '' );
            $this->_record->set_id( $line_num );
            $this->_record->set_time( strtotime( trim( $matches[0], '[]' ) ) );
            $this->_record->set_type( $type );
            $this->_record->set_message( $msg );
            $this->_record->set_start_line( $line_num );
        }

        // This is just continue of of previous line (debug details)
        elseif ( count( $matches ) == 1 && ( $this->_record instanceof DL_Log_Record ) ) {
            if ( strpos( $matches[0], '#' ) === 0 ) {
                $this->_record->add_trace( $matches[0] );
                $this->_record->set_end_line( $line_num );
            }
        }

        // Something is (maybe) wrong or just empty line
        else {

            if ( empty( trim( $matches[0] ) ) ) {
                return;
            }

            // TODO Don't forgot to comment this for release!
            if ( WP_DEBUG === true ) {
                odwpdl_write_log( sprintf( $parser_error_msg, $line_num, $line, json_encode( $matches ) ) );
            }
        }
    }

    /**
     * Update parser stats.
     *
     * @return void
     * @since 1.0.0
     */
    private function update_stats() {

        // If is not any record currently parsed do nothing
        if ( ! ( $this->_record instanceof DL_Log_Record ) ) {
            return;
        }

        // Note: We don't update total count here.

        // update count by type
        switch ( $this->_record->get_type() ) {
            case DL_Log_Record::TYPE_DLPARSER:
            case DL_Log_Record::TYPE_ERROR:
            case DL_Log_Record::TYPE_NOTICE:
            case DL_Log_Record::TYPE_PARSER:
            case DL_Log_Record::TYPE_OTHER:
            case DL_Log_Record::TYPE_WARNING:
                $this->stats->increment_count_by_type( $this->_record->get_type() );
                break;
        }

        // update count by period
        if ( $this->_record->was_today() ) {
            $this->stats->increment_count_by_period( DL_Log_Table::VIEW_TODAY );
        }
        elseif ( $this->_record->was_yesterday() ) {
            $this->stats->increment_count_by_period( DL_Log_Table::VIEW_YESTERDAY );
        }
        else {
            $this->stats->increment_count_by_period( DL_Log_Table::VIEW_EARLIER );
        }
    }

    /**
     * Filter parsed log data.
     *
     * @param Closure $filter_func
     * @return void
     * @since 1.0.0
     */
    public function filter( $filter_func ) {

        if ( $this->is_parsed !== true ) {
            $this->parse();
        }

        $temp = array_filter( $this->log, $filter_func );
        $this->log = $temp;
    }

    /**
     * Set view.
     *
     * @param string $view
     * @return void
     * @since 1.0.0
     */
    public function set_view( $view ) {

        if ( $this->_view === $view ) {
            return;
        }

        if ( $this->is_parsed !== true ) {
            $this->parse();
        }

        $log_by_view = [];

        foreach ( $this->log as $item ) {

            if ( $view === DL_Log_Table::VIEW_TODAY && $item->was_today() ) {
                $log_by_view[] = $item;
            } else if ( $view === DL_Log_Table::VIEW_YESTERDAY && $item->was_yesterday() ) {
                $log_by_view[] = $item;
            } else if ( $view === DL_Log_Table::VIEW_EARLIER && ( ! $item->was_today() && ! $item->was_yesterday() ) ) {
                $log_by_view[] = $item;
            } else if ( $view === DL_Log_Table::VIEW_ALL ) {
                $log_by_view[] = $item;
            }
        }

        $this->log = $log_by_view;
    }

    /**
     * Sort parsed (or parsed and filtered) data.
     * 
     * @param array $args Sorting arguments ('sort_col' and 'sort_dir').
     * @return void
     * @since 1.0.0
     */
    public function sort( $args = [] ) {
        if ( $this->is_parsed !== true ) {
            $this->parse();
        }

        if ( array_key_exists( 'sort_col', $args ) ) {
            $this->_orderby = $args['sort_col'];
        }

        if ( array_key_exists( 'sort_dir', $args ) ) {
            $this->_order = $args['sort_dir'];
        }

        usort( $this->log, [$this, 'usort_reorder'] );
    }

    /**
     * Reset used sorting or filters.
     * 
     * @return void
     * @since 1.0.0
     */
    public function reset() {
        $this->log = $this->log_unfiltered;
    }

    /**
     * Return data from the log.
     * 
     * @param array $options
     * @return DL_Log_Record|[DL_Log_Record]
     * @since 1.0.0
     */
    public function get_data( $options = ['page' => -1] ) {
        
        if ( $this->is_parsed !== true ) {
            $this->parse();
        }

        if ( array_key_exists( 'page', $options ) && $options['page'] == -1 ) {
            $data = $this->log;
        }
        elseif ( array_key_exists( 'record', $options ) ) {
            $data = $this->log[(int) $options['record']];
        }
        else {
            $per_page = $this->get_options( 'per_page', DL_Log_Table::DEFAULT_PER_PAGE );
            $current  = array_key_exists( 'page', $options ) ? $options['page'] : 1;
            $data     = array_slice( $this->log, ( ( $current - 1 ) * $per_page ), $per_page );
        }

        return $data;
    }

    /**
     * Recognizes and returns type of log record from the given string.
     *
     * @param string $str
     * @return string
     * @see DL_Log_Table::get_data()
     * @since 1.0.0
     */
    public function parse_type( $str ) {
        if ( strpos( $str, DL_Log_Record::TYPE_ERROR ) === 0 ) {
            return DL_Log_Record::TYPE_ERROR;
        }
        else if ( strpos( $str, DL_Log_Record::TYPE_NOTICE ) === 0 ) {
            return DL_Log_Record::TYPE_NOTICE;
        }
        else if ( strpos( $str, DL_Log_Record::TYPE_PARSER ) === 0 ) {
            return DL_Log_Record::TYPE_PARSER;
        }
        else if ( strpos( $str, DL_Log_Record::TYPE_WARNING ) === 0 ) {
            return DL_Log_Record::TYPE_WARNING;
        }
        else if ( strpos( $str, DL_Log_Record::TYPE_DLPARSER ) === 0 ) {
            return DL_Log_Record::TYPE_DLPARSER;
        }

        return DL_Log_Record::TYPE_OTHER;
    }

    /**
     * Return the same text as given but the mentioned PHP source files are made accessible as links.
     * 
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

        foreach ( $matches as $match ) {
            if ( strpos( $match, ABSPATH ) === 0 ) {
                
                // Array item contains file link
                if ( ! in_array( $match, $file_links ) ) {
                    $file_links[] = $match;
                }
            } else {
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
        foreach ( $file_links as $file_link ) {
            $file_name = $file_link;

            // Make link shorter if user wants it
            if ( $short_src_links === true ) {
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
     * Sorting method for the table data.
     * 
     * @param DL_Log_Record $a The first row.
     * @param DL_Log_Record $b The second row.
     * @return int
     * @since 1.0.0
     */
    protected function usort_reorder( DL_Log_Record $a, DL_Log_Record $b ) {
        $order = $this->get_sort_order();
        $orderby = $this->get_sort_orderby();
        $val1 = null;
        $val2 = null;

        switch ( $orderby ) {
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

        if ( is_numeric( $val1 ) && is_numeric( $val2 ) ) {
            $result = ( $val1 < $val2 ) ? -1 : ( ( $val1 > $val2 ) ? 1 : 0 );
        } else {
            $result = strcmp( $val1, $val2 );
        }

        return ( $order === 'asc' ) ? $result : -$result;
    }

    /**
     * Delete specified log record (but does not save the file!).
     *
     * @param DL_Log_Record $record
     * @param bool $save Optional. Default value is TRUE.
     * @return bool
     * @since 1.0.0
     */
    public function delete_record( DL_Log_Record $record, $save = true ) {

        // It should be, but just for case...
        if ( $this->is_parsed === false ) {
            return false;
        }

        // Check if given line number exists
        if ( ! array_key_exists( $record->get_id(), $this->log ) ) {
            return false;
        }

        // Starting/ending lines
        $start_index = $record->get_start_line();
        $end_index = $record->get_end_line();

        echo '<pre>START/END:'.$start_index.'/'.$end_index.'</pre>';

        // Delete item from raw log
        if ( $start_index === $end_index ) {
            array_splice( $this->log_raw, $start_index, 1, self::FLAG_ERASED );
        } else {
            array_splice( $this->log_raw, $start_index, $end_index - $start_index, self::FLAG_ERASED );
        }

        // Parse raw log again
        $this->is_prepared = true;
        $this->is_parsed = false;
        $this->parse();

        // Don't forget to set log unsaved
        $this->is_saved = false;

        // Save log file and return result
        return ( $save === true ) ? $this->save() : true;
    }

    /**
     * Delete records.
     * 
     * @param array $records IDs of records to delete.
     * @return array Return array with count of done/failed deletes and save result (`['done' => 0, 'failed' => 0, 'saved' => false]`).
     * @since 1.0.0
     */
    public function delete_records( array $records ) {
        $ret = ['done' => 0, 'failed' => 0, 'saved' => false];

        // Go through all record IDs and delete them one by one
        foreach ( $records as $record ) {
            $record_obj = $this->get_data( ['record' => $record] );

            // Check if everything is OK
            if ( ! ( $record_obj instanceof DL_Log_Record ) ) {
                continue;
            }

            // Delete record and update `$ret`
            if ( $this->delete_record( $record_obj, false ) === true ) {
                $ret['done']++;
            } else {
                $ret['failed']++;
            }
        }

        // If at least one delete was done save the log file
        if ( $ret['done'] > 0 ) {
            $ret['saved']   = $this->save();
            $this->is_saved = $ret->saved;
        }

        return $ret;
    }

    /**
     * Save {@see DL_Log_Parser::$log_raw} into the {@see DL_Log_Parser::$file}.
     * 
     * @return bool
     * @since 1.0.0
     */
    protected function save() {
        $this->is_saved = ( file_put_contents( DL_LOG, $this->log_raw ) !== false );

        return $this->is_saved;
    }

    /**
     * Return true if log file is saved (or if there are unsaved changes).
     * 
     * @return bool
     * @since 1.0.0
     */
    public function is_saved() {
       return $this->is_saved;
    }

    /**
     * Return statistics for the last parsing.
     *
     * @return DL_Log_Parser_Stats
     * @since 1.0.0
     */
    public function get_stats() {
        return $this->stats;
    }
}

endif;
