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

if( ! class_exists( 'DL_Log_Table' ) ) {
    require_once( DL_PATH . 'src/DL_Log_Table.php' );
}

if( ! class_exists( 'DL_Log_Screen' ) ) :

/**
 * Administration screen for the log viewer.
 * @since 1.0.0
 */
class DL_Log_Screen extends DL_Screen_Prototype {
    /**
     * @var string
     * @since 1.0.0
     */
    const SLUG = DL_SLUG . '-log';

    /**
     * Constructor.
     * @param WP_Screen $screen Optional.
     * @return void
     * @since 1.0.0
     */
    public function __construct( \WP_Screen $screen = null ) {
        // Main properties
        $this->slug = self::SLUG;
        $this->menu_title = __( 'Ladící informace', DL_SLUG );
        $this->page_title = __( 'Prohlížeč ladících informací', DL_SLUG );

        // Specify help tabs
        $this->help_tabs[] = [
            'id' => self::SLUG . '-info-helptab',
            'title' => __( 'Obecné', DL_SLUG ),
            'content' => sprintf(
                    __(
                            //'<h3 class="help-title">Obecné informace</h3>' .
                            '<p>Tabulka obsahuje seznam <strong>chybových zpráv</strong>, které vznikly během běhu vaší instalace systému <a href="$s" target="blank">WordPress</a>. Tyto chyby jsou uloženy v souboru <code>debug.log</code>. Tuto tabulku si můžete přidat ve formě widgetu i na <a href="$s">nástěnku</a>.</p>' .
                            '<h4 class="help-title">Sloupce tabulky</h4>' .
                            '<ul class="help-list">' .
                                '<li><strong>Pořadové číslo</strong>&nbsp;&ndash;&nbsp;Pořadové číslo chyby</li>' .
                                '<li><strong>Datum a čas</strong>&nbsp;&ndash;&nbsp;Datum a čas ve kterém se chyba vyskytla</li>' .
                                '<li><strong>Typ chyby</strong>&nbsp;&ndash;&nbsp;Typ chyby (viz. přehled níže)</li>' .
                                '<li><strong>Záznam</strong>&nbsp;&ndash;&nbsp;Text chyby (včetně <em>stack trace</em>)</li>' .
                            '</ul>' .
                            '<h4 class="help-title">Typy chyb</h4>' .
                            '<ul class="help-list">' .
                                '<li><span class="dashicons dashicons-warning"></span><em>' . DL_Log_Record::TYPE_ERROR . '</em>&nbsp;&ndash;&nbsp;fatální chyba</li>' .
                                '<li><span class="dashicons dashicons-format-status"></span><em>' . DL_Log_Record::TYPE_NOTICE . '</em>&nbsp;&ndash;&nbsp;chybová připomínka</li>' .
                                '<li><span class="dashicons dashicons-editor-help"></span><em>' . DL_Log_Record::TYPE_OTHER . '</em>&nbsp;&ndash;&nbsp;jiné chyby</li>' .
                                '<li><span class="dashicons dashicons-thumbs-down"></span><em>' . DL_Log_Record::TYPE_PARSER . '</em>&nbsp;&ndash;&nbsp;chyba PHP parseru</li>' .
                                '<li><span class="dashicons dashicons-flag"></span><em>' . DL_Log_Record::TYPE_WARNING . '</em>&nbsp;&ndash;&nbsp;varování na konstrukci v PHP kódu</li>' .
                                '<li><span class="dashicons dashicons-no"></span><em>' . DL_Log_Record::TYPE_ODWPDL . '</em>&nbsp;&ndash;&nbsp;Chyba log parseru rozšíření <em>odwp-debug_log</em></li>' .
                            '</ul>',
                            DL_SLUG
                    ),
                    'https://wordpress.org/',
                    get_admin_url()
            ),
        ];
        $this->help_tabs[] = [
            'id' => self::SLUG . '-options-helptab',
            'title' => __( 'Nastavení', DL_SLUG ),
            'content' => sprintf(
                    __(
                            '<p>Zobrazení tabulky se záznamem logu může být ovlivněna nastavením.</p>' .
                            '<p><img src="%s" alt="Nastavení pluginu" title="Nastavení pluginu" class="help_tab_img"></p>',
                            DL_SLUG
                    ),
                    plugins_url( 'screenshot-02.png', DL_FILE )
            ),
        ];
        $this->help_tabs[] = [
            'id' => self::SLUG . '-todo_tab',
            'title' => 'TODO',
            'content' =>
                '<div class="todo-list--cont">' .
                '    <ul class="todo-list">' .
                '        <li class="done">přidat sloupec s typem chyby a hlavní text chyby o tento typ zkrátit (bude zkrácen jen, když je sloupec zobrazen)</li>' .
                '        <li>přidat filtrování - zobrazit jen dnešní chyby, chyby za poslední hodinu, zobrazit chyby určitého typu (<em>PHP Parse Error</em>, <em>PHP Warning</em>, <em>PHP Fatal Error</em>)</li>' .
                '        <li>dokončit hromadné úpravy (smazat vybrané záznamy)</li>' .
                '        <li>opravit/dokončit řazení dat</li>' .
                '        <li>umožnit proklik na zdrojové kódy ve kterých se chyba vyskytne</li>' .
                '        <li class="done">přidat základní stránkování</li>' .
                '        <li>zobrazit <em>stack trace</em> pokud je definována</li>' .
                '        <li>' .
                '            <b>Uživatelské nastavení:</b>' .
                '            <ul>' .
                '                <li>udělat to tak, aby nebylo zapotřebí <em>custom</em> šablony pro vygenerování formuláře s nastavením obrazovky (<code>DL_Screen_Prototype::screen_options()</code>)</li>' .
                '                <li class="done">počet položek na stránce</li>' .
                '                <li>které sloupce se mají zobrazit</li>' .
                '                <li class="done">jak zobrazit sloupec s typem záznamu - jestli jako text či ikonu</li>' .
                '                <li>uživatelské nastavení pro defaultní řazení (sloupec a směr řazení)</li>' .
                '            </ul>' .
                '        </li>' .
                '        <li>vyřešit všechny problémy, které se mohou vyskytnout při použití na nástěnce (<em>dashboard widget</em>)</li>' .
                '    </ul>' .
                '</div>',
        ];

        // Specify help sidebars
        $this->help_sidebars[] = sprintf(
                '<b>%s</b>' .
                '<p><a href="%s" target="blank">%s</a></p>' .
                '<p><a href="%s" target="blank">%s</a></p>',
                __( 'Užitečné odkazy', DL_LOG ),
                'https://github.com/ondrejd/odwp-debug_log',
                __( 'GitHub - zdrojové kódy', DL_LOG ),
                'https://github.com/ondrejd/odwp-debug_log/issues',
                __( 'GitHub - ohlášení chyb', DL_LOG )
        );

        // Specify screen options
        $this->options[self::SLUG . '-hidden_cols'] = [
            'label'   => __( 'Zobrazené sloupce', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_HIDDEN_COLS,
            'option'  => self::SLUG . '-hidden_cols',
        ];
        $this->options[self::SLUG . '-per_page'] = [
            'label'   => __( 'Počet záznamů na stránku', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_PER_PAGE,
            'option'  => self::SLUG . '-per_page',
        ];
        $this->options[self::SLUG . '-show_icons'] = [
            'label'   => __( 'Zobrazit typ záznamu jako ikonu?', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SHOW_ICONS,
            'option'  => self::SLUG . '-show_icons',
        ];
        $this->options[self::SLUG . '-show_links'] = [
            'label'   => __( 'Zobrazit odkazy na zdrojové soubory?', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SHOW_LINKS,
            'option'  => self::SLUG . '-show_links',
        ];
        $this->options[self::SLUG . '-sort_col'] = [
            'label'   => __( 'Defaultní sloupec k řazení', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SORT_COL,
            'option'  => self::SLUG . '-sort_col',
        ];
        $this->options[self::SLUG . '-sort_dir'] = [
            'label'   => __( 'Defaultní směr řazení', DL_SLUG ),
            'default' => DL_Log_Table::DEFAULT_SORT_DIR,
            'option'  => self::SLUG . '-sort_dir',
        ];
        $this->enable_screen_options = true;

        // Finish screen constuction
        parent::__construct( $screen );
    }

    /**
     * Action for `admin_menu` hook.
     * @return void
     * @since 1.0.0
     */
    public function admin_menu() {
        $this->hookname = add_management_page(
                $this->page_title,
                $this->menu_title,
                'manage_options',
                self::SLUG,
                [$this, 'render']
        );

        add_action( 'load-' . $this->hookname, [$this, 'screen_load'] );
    }

    /**
     * Returns current screen options.
     * @return array
     * @see DL_Screen_Prototype::get_screen_options()
     * @see DL_Log_Table::get_options();
     * @since 1.0.0
     */
    public function get_screen_options() {
        if( $this->enable_screen_options !== true ) {
            return [];
        }

        return DL_Log_Table::get_options();
    }

    /**
     * Save screen options.
     * @return void
     * @see DL_Screen_Prototype::get_screen_options()
     * @since 1.0.0
     * @todo It should be done automatically by using {@see DL_Screen_Prototype::$options} without need of writing own code.
     */
    public function save_screen_options() {
        if( $this->enable_screen_options !== true ) {
            return;
        }

        $user = get_current_user_id();

        if(
                filter_input( INPUT_POST, self::SLUG . '-submit' ) &&
                (bool) wp_verify_nonce( filter_input( INPUT_POST, self::SLUG . '-nonce' ) ) === true
        ) {
            // TODO Hidden columns
//$show_cols   = filter_input( INPUT_POST, self::SLUG . '-show_cols', FILTER_DEFAULT , FILTER_REQUIRE_ARRAY );
//var_dump( $show_cols );
// 1) mame sloupce, ktere chce uzivatel zobrazit
// 2) vyloucime ty, ktere nemohou byt skryty
// 3) ted vyhodime ostatni zaskrtnute
// 4) ty co zbydou ulozime jako retezec do $hidden_cols
//exit();
//$hidden_cols = filter_input( INPUT_POST, self::SLUG . '-hidden_cols' );
//update_user_meta( $user, self::SLUG . '-hidden_cols', $hidden_cols );
            // Rows per page
            $per_page = filter_input( INPUT_POST, self::SLUG . '-per_page' );
            update_user_meta( $user, self::SLUG . '-per_page', ( int ) $per_page );
            // Show icons
            $show_icons = filter_input( INPUT_POST, self::SLUG . '-show_icons' );
            update_user_meta( $user, self::SLUG . '-show_icons', ( strtolower( $show_icons ) == 'on' ) ? 1 : 0 );
            // Show links
            $show_links = filter_input( INPUT_POST, self::SLUG . '-show_links' );
            update_user_meta( $user, self::SLUG . '-show_links', ( strtolower( $show_links ) == 'on' ) ? 1 : 0 );
            // Sorting column
            $sort_col = filter_input( INPUT_POST, self::SLUG . '-sort_col' );
            update_user_meta( $user, self::SLUG . '-sort_col', $sort_col );
            // Sorting direction
            $sort_dir = filter_input( INPUT_POST, self::SLUG . '-sort_dir' );
            update_user_meta( $user, self::SLUG . '-sort_dir', $sort_dir );
        }
    }
}

endif;
