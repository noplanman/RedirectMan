<?php


/**
 * Base WP_List_Table class from WP 3.8.1.
 * 
 * @package RedirectMan
 * @subpackage RedirectMan_Table
 * @since 1.0.0
 */
if ( ! class_exists('RedirectMan_Table' ) ) {
  class RedirectMan_Table {

    /**
     * The current list of items.
     *
     * @since 1.0.0
     * @access protected
     * @var array
     */
    protected $items;

    /**
     * Various information about the current table.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_args;

    /**
     * Various information needed for displaying the pagination.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_pagination_args = array();

    /**
     * The current screen.
     *
     * @since 1.0.0
     * @access protected
     * @var object
     */
    protected $screen;

    /**
     * Cached bulk actions.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_actions;

    /**
     * Cached pagination output.
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $_pagination;

    /**
     * Status message to display when loading the page.
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $_status_message = '';

    /**
     * A list of all ids that have been affected during the last operation.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_affected_ids = array();

    /**
     * Last executed operation (insert, update, delete).
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $_operation = '';

    /**
     * A list of all ids that have duplicate entries.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_duplicate_redirections = array();

    /**
     * A list of all ids that have circular redirections.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_circular_redirections = array();

    /**
     * Show only duplicates?
     *
     * @since 1.0.0
     * @access private
     * @var bool
     */
    private $_show_duplicates = false;

    /**
     * Show only circular redirections?
     *
     * @since 1.0.0
     * @access private
     * @var bool
     */
    private $_show_circulars = false;


    /**
     * Constructor.
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
      $this->_args = array(
        'singular' => 'redirectman',
        'plural'   => 'redirectman',
        'screen'   => 'settings_page_redirectman'
      );

      $this->screen = convert_to_screen( $this->_args['screen'] );

      //add_filter( "manage_{$this->screen->id}_columns", array( $this, 'get_columns' ), 0 );
    }

    /**
     * Set the array of ids of duplicate redirections.
     * 
     * @param array $duplicate_ids Array of ids of duplicate redirections.
     */
    public function set_duplicates( $duplicate_ids ) {
      $this->_duplicate_redirections = $duplicate_ids;
    }

    /**
     * Set the array of ids of circular redirections.
     * 
     * @param array $circular_ids Array of ids of circular redirections.
     */
    public function set_circulars( $circular_ids ) {
      $this->_circular_redirections = $circular_ids;
    }

    /**
     * Get the status message to display.
     *
     * @since 1.0.0
     * @access public
     */
    public function get_status_message() {
      return $this->_status_message;
    }

    /**
     * Get the current executed operation.
     *
     * @since 1.0.0
     * @access public
     */
    public function get_operation() {
      return $this->_operation;
    }

    /**
     * Get the ids of duplicate redirections.
     *
     * @since 1.0.0
     * @access public
     */
    public function get_duplicates() {
      return (array) $this->_duplicate_redirections;
    }

    /**
     * Get the ids of circular redirections.
     *
     * @since 1.0.0
     * @access public
     */
    public function get_circulars() {
      return (array) $this->_circular_redirections;
    }

    /**
     * Get the singular name of the table.
     *
     * @since 1.0.0
     * @access public
     */
    public function get_name() {
      return $this->_args['singular'];
    }

    /**
     * Prepares the list of items for displaying.
     *
     * @since 1.0.0
     * @access public
     */
    public function prepare_items() {
      global $wpdb;

      // Process bulk actions before preparing the items.
      $this->process_bulk_action();

      // Check if there are any duplicate redirects from the same source.
//      $duplicate_redirection_ids = $this->_duplicate_redirections;

      // Check if there are any circular redirects, redirecting in circles.
//      $circular_redirection_ids = $this->_circular_redirections;

      $orderby     = am_strornull( $_REQUEST['orderby'], true, 'redirection_id' );
      $order       = am_strornull( $_REQUEST['order'], true, 'desc' );
      $search      = am_strornull( $_REQUEST['s'] );
      $status_code = (int) am_strornull( $_REQUEST['status_code'] );


      // Inconsistencies.
      $duplicates = ( '1' === am_strornull( $_REQUEST['duplicates'] ) ) ? true : null;
      $circulars  = ( '1' === am_strornull( $_REQUEST['circulars'] ) )  ? true : null;

      if ( $this->_show_duplicates ) {
        $duplicates = true;

        $this->_show_circulars = false;
        $circulars = null;
      } elseif ( $this->_show_circulars ) {
        $circulars = true;

        $this->_show_duplicates = false;
        $duplicates = null;
      }

      // If the form has been sent, redirect to a new URL.
      if ( ! empty( $_POST ) ) {
        if ( 'redirection_id' === $orderby ) {
          $orderby = $order = null;
        }

        $operation    = am_strornull( $this->_operation );
        $affected_ids = (array) $this->_affected_ids;
        $affected_ids = ( ! empty( $this->_affected_ids ) ) ? implode( ',', $affected_ids ) : null;
        $message      = am_strornull( urlencode( $this->_status_message ) );
        $status_code  = ( -1 !== $status_code ) ? $status_code : null;

        $redirect_location = add_query_arg( array(
          'page'        => $_REQUEST['page'],
          'orderby'     => $orderby,
          'order'       => $order,
          'status_code' => $status_code,
          's'           => $search,
          'op'          => $operation,
          'ids'         => $affected_ids,
          'message'     => $message,
          'duplicates'  => $duplicates,
          'circulars'   => $circulars
        ), 'options-general.php' );
        wp_redirect( $redirect_location );
        exit;
      }

      $this->_affected_ids   = ( ! empty( $_REQUEST['ids'] ) ) ? explode( ',', $_REQUEST['ids'] ) : null;
      $this->_operation      = am_strornull( $_REQUEST['op'] );
      $this->_status_message = am_strornull( urldecode( $_REQUEST['message'] ) );

      // Inconsistencies.
      $this->_show_duplicates = ! is_null( $duplicates );
      $this->_show_circulars  = ! is_null( $circulars );


      $_SERVER['REQUEST_URI'] = remove_query_arg( array( 'ids', 'op', 'message' ), $_SERVER['REQUEST_URI'] );
      

      // Array of columns to be displayed (slugs & titles), a list of columns to keep hidden, and a list of columns that are sortable.
      $columns  = $this->get_columns();
      $hidden   = array();
      $sortable = $this->get_sortable_columns();
      $this->_column_headers = array( $columns, $hidden, $sortable );


      // Set up the query.
      $query = "SELECT redirection_id, redirect_from, redirect_to, status_code FROM " . RedirectMan::get_database_table();

      // Take search into account.
      $where = array();
      if ( isset( $search ) ) {
        $where[] = "(" .
          "redirect_from LIKE '%$search%' OR " .
          "redirect_to LIKE '%$search%'" .
        ")";
      }

      // Take status code filter into account.
      if ( isset( $status_code ) && ! empty( $status_code ) && -1 != $status_code ) {
        $where[] = "status_code = '$status_code'";
      }

      // Figure out which inconsistencies to show.
      if ( $this->_show_duplicates && isset( $this->_duplicate_redirections ) && ! empty( $this->_duplicate_redirections )
        && $this->_show_circulars  && isset( $this->_circular_redirections )  && ! empty( $this->_circular_redirections ) ) {
        $where[] = "redirection_id IN (" . implode( ',', array_unique( array_merge( $this->_duplicate_redirections, $this->_circular_redirections ) ) ) . ")";
      } else {
        if ( $this->_show_duplicates && isset( $this->_duplicate_redirections ) && ! empty( $this->_duplicate_redirections ) ) {
          $where[] = "redirection_id IN (" . implode( ',', $this->_duplicate_redirections ) . ")";
        }
        if ( $this->_show_circulars && isset( $this->_circular_redirections ) && ! empty( $this->_circular_redirections ) ) {
          $where[] = "redirection_id IN (" . implode( ',', $this->_circular_redirections ) . ")";
        }
      }

      $query .= ( ! empty( $where ) ) ? " WHERE " . implode( ' AND ', $where ) : '';
      
      // Set orderby fields.
      if ( ! empty( $orderby ) && ! empty( $order ) ) {
        $query .= " ORDER BY $orderby $order";
      }


      // Set up pagination.
      // Total number of items.
      $total_items = $wpdb->query( $query );

      // Current page number.
      $current_page = $this->get_pagenum();
      
      // Items to display per page.
      $per_page = 20;

      // Take pagination into account.
      if ( ! empty( $current_page ) && ! empty( $per_page ) ) {
        $offset = ( $current_page - 1 ) * $per_page;
        $query .= " LIMIT $offset, $per_page";
      }

      // Set up the pagination.
      $this->set_pagination_args( array(
        'per_page'    => $per_page,
        'total_items' => $total_items,
        'total_pages' => ceil( $total_items / $per_page )
      ) );

      // Fetch all the redirections.
      $this->items = $wpdb->get_results( $query, ARRAY_A );
    }

    /**
     * Set all the necessary pagination arguments.
     *
     * @since 1.0.0
     * @access protected
     *
     * @param array $args An associative array with information about the pagination.
     */
    protected function set_pagination_args( $args ) {
      $args = wp_parse_args( $args, array(
        'total_items' => 0,
        'total_pages' => 0,
        'per_page'    => 0
      ) );

      if ( ! $args['total_pages'] && $args['per_page'] > 0 ) {
        $args['total_pages'] = ceil( $args['total_items'] / $args['per_page'] );
      }

      // redirect if page number is invalid and headers are not already sent
      if ( ! headers_sent() && $args['total_pages'] > 0 && $this->get_pagenum() > $args['total_pages'] ) {
        wp_redirect( add_query_arg( 'paged', $args['total_pages'] ) );
        exit;
      }

      $this->_pagination_args = $args;
    }

    /**
     * Access the pagination args.
     *
     * @since 1.0.0
     * @access protected
     *
     * @param string $key Specified pagination arg.
     * @return int|string
     */
    protected function get_pagination_arg( $key ) {
      if ( 'page' == $key ) {
        return $this->get_pagenum();
      }

      if ( isset( $this->_pagination_args[ $key ] ) ) {
        return $this->_pagination_args[ $key ];
      }
    }

    /**
     * Whether the table has items to display or not.
     *
     * @since 1.0.0
     * @access public
     *
     * @return bool
     */
    public function has_items() {
      return ! empty( $this->items );
    }

    /**
     * Message to be displayed when there are no items
     *
     * @since 1.0.0
     * @access protected
     */
    protected function no_items() {
      _e( 'No redirections found.', 'redirectman' );
    }

    /**
     * Display the search box.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $text The search button text.
     * @param string $input_id The search input id.
     */
    public function search_box( $text, $input_id ) {
      $input_id .= '-search-input';

      /*if ( ! empty( $_REQUEST['orderby'] ) )
        echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
      if ( ! empty( $_REQUEST['order'] ) )
        echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
      if ( ! empty( $_REQUEST['post_mime_type'] ) )
        echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
      if ( ! empty( $_REQUEST['detached'] ) )
        echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';*/
      ?>
      <p class="search-box">
        <label class="screen-reader-text" for="<?php echo $input_id; ?>"><?php echo $text; ?>:</label>
        <input type="search" id="<?php echo $input_id; ?>" name="s" value="<?php _admin_search_query(); ?>" />
        <?php submit_button( $text, 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
      </p>
      <?php
    }


    /**
     * Get an associative array ( option_name => option_title ) with the list
     * of bulk actions available on this table.
     *
     * @since 1.0.0
     * @access protected
     *
     * @return array $actions, a list of all available Bulk Actions.
     */
    protected function get_bulk_actions() {
      $actions = array(
        'delete' => __( 'Delete', 'redirectman' ),
      ) + RedirectMan::get_status_codes();
      return $actions;
    }

    /**
     * Display the bulk actions dropdown.
     *
     * @since 1.0.0
     * @access public
     */
    public function bulk_actions() {
      if ( is_null( $this->_actions ) ) {
        $no_new_actions = $this->_actions = $this->get_bulk_actions();
        /**
         * Filter the list table Bulk Actions drop-down.
         *
         * The dynamic portion of the hook name, $this->screen->id, refers
         * to the ID of the current screen, usually a string.
         *
         * This filter can currently only be used to remove bulk actions.
         *
         * @since 3.5.0
         *
         * @param array $actions An array of the available bulk actions.
         */
        $this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
        $this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
        $two = '';
      } else {
        $two = '2';
      }

      if ( empty( $this->_actions ) ) {
        return;
      }

      echo '<select name="action' . $two . '">';
      echo '<option value="-1" selected="selected">' . __( 'Bulk Actions' ) . '</option>';

      foreach ( $this->_actions as $name => $title ) {
        $class = ( 'edit' == $name ) ? ' class="hide-if-no-js"' : '';

        echo '<option value="' . $name . '"' . $class . '>' . $title . '</option>';
      }

      echo '</select>';

      submit_button( __( 'Apply' ), 'action', false, false, array( 'id' => 'doaction' . $two ) );
    }

    /**
     * Get the current action selected from the bulk actions dropdown.
     *
     * @since 1.0.0
     * @access protected
     *
     * @return string|bool The action name or False if no action was selected.
     */
    protected function current_action() {

      // Save a new redirection.
      if ( isset( $_REQUEST['save-new-redirection'] ) ) {
        return 'save_new';
      }

      // Save all redirections.
      if ( isset( $_REQUEST['save-redirections'] ) ) {
        return 'save';
      }

      // Show only duplicate redirections.
      if ( isset( $_REQUEST['show-duplicates'] ) ) {
        return 'duplicates';
      }

      // Show only circular redirections.
      if ( isset( $_REQUEST['show-circulars'] ) ) {
        return 'circulars';
      }

      // Bulk Actions.
      if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
        return $_REQUEST['action'];
      }
      if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
        return $_REQUEST['action2'];
      }

      return false;
    }

    /**
     * Generate row actions div.
     *
     * @since 1.0.0
     * @access protected
     *
     * @param array $actions The list of actions.
     * @param bool $always_visible Whether the actions should be always visible.
     * @return string
     */
    protected function row_actions( $actions, $always_visible = false ) {
      $action_count = count( $actions );
      $i = 0;

      if ( ! $action_count ) {
        return '';
      }

      $out = '<div class="row-actions' . ( ( $always_visible ) ? ' visible' : '' ) . '">';
      foreach ( $actions as $action => $link ) {
        ++$i;
        $sep = ( $i == $action_count ) ? '' : ' | ';
        $out .= '<span class="' . $action . '">' . $link . $sep . '</span>';
      }
      $out .= '</div>';

      return $out;
    }

    /**
     * Get the current page number.
     *
     * @since 1.0.0
     * @access protected
     *
     * @return int The current page number.
     */
    protected function get_pagenum() {
      $pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;

      if( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] ) {
        $pagenum = $this->_pagination_args['total_pages'];
      }

      return max( 1, $pagenum );
    }

    /**
     * Get number of items to display on a single page.
     *
     * @since 1.0.0
     * @access protected
     *
     * @return int
     */
    protected function get_items_per_page( $option, $default = 20 ) {
      $per_page = (int) get_user_option( $option );
      if ( empty( $per_page ) || 1 > $per_page ) {
        $per_page = $default;
      }

      /**
       * Filter the number of items to be displayed on each page of the list table.
       *
       * The dynamic hook name, $option, refers to the per page option depending
       * on the type of list table in use. Possible values may include:
       * 'edit_comments_per_page', 'sites_network_per_page', 'site_themes_network_per_page',
       * 'themes_netework_per_page', 'users_network_per_page', 'edit_{$post_type}', etc.
       *
       * @since 2.9.0
       *
       * @param int $per_page Number of items to be displayed. Default 20.
       */
      return (int) apply_filters( $option, $per_page );
    }

    /**
     * Display the pagination.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function pagination( $which ) {
      if ( empty( $this->_pagination_args ) ) {
        return;
      }

      extract( $this->_pagination_args, EXTR_SKIP );

      $output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

      $current = $this->get_pagenum();

      $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

      $current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

      $page_links = array();

      $disable_first = ( 1 == $current ) ? ' disabled' : '';
      $disable_last  = ( $total_pages == $current ) ? ' disabled' : '';

      $page_links[] = sprintf( '<a class="%s" title="%s" href="%s">%s</a>',
        'first-page' . $disable_first,
        esc_attr__( 'Go to the first page' ),
        esc_url( remove_query_arg( 'paged', $current_url ) ),
        '&laquo;'
      );

      $page_links[] = sprintf( '<a class="%s" title="%s" href="%s">%s</a>',
        'prev-page' . $disable_first,
        esc_attr__( 'Go to the previous page' ),
        esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
        '&lsaquo;'
      );

      if ( 'bottom' == $which ) {
        $html_current_page = $current;
      } else {
        $html_current_page = sprintf( '<input class="current-page" title="%s" type="text" name="paged" value="%s" size="%d" />',
          esc_attr__( 'Current page' ),
          $current,
          strlen( $total_pages )
        );
      }

      $html_total_pages = sprintf( '<span class="total-pages">%s</span>', number_format_i18n( $total_pages ) );
      $page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

      $page_links[] = sprintf( '<a class="%s" title="%s" href="%s">%s</a>',
        'next-page' . $disable_last,
        esc_attr__( 'Go to the next page' ),
        esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
        '&rsaquo;'
      );

      $page_links[] = sprintf( '<a class="%s" title="%s" href="%s">%s</a>',
        'last-page' . $disable_last,
        esc_attr__( 'Go to the last page' ),
        esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
        '&raquo;'
      );

      $pagination_links_class = 'pagination-links';
      if ( ! empty( $infinite_scroll ) ) {
        $pagination_links_class = ' hide-if-js';
      }
      $output .= '<span class="' . $pagination_links_class . '">' . join( '', $page_links ) . '</span>';

      if ( $total_pages ) {
        $page_class = ( 2 > $total_pages ) ? ' one-page' : '';
      } else {
        $page_class = ' no-pages';
      }

      $this->_pagination = '<div class="tablenav-pages' . $page_class . '">' . $output . '</div>';

      echo $this->_pagination;
    }

    /**
     * Define the columns that are going to be used in the table.
     *
     * @since 1.0.0
     * @access protected
     * @return array $columns, the array of columns to use with the table.
     */
    protected function get_columns() {
      $columns = array(
        'cb'            => '<input type="checkbox" />', // Render a checkbox instead of text.
        'redirect_from' => __( 'Redirect From', 'redirectman' ),
        'redirect_to'   => __( 'Redirect To', 'redirectman' ),
        'status_code'   => __( 'Status Code', 'redirectman' )
      );
      return $columns;
    }

    /**
     * Decide which columns to activate the sorting functionality on.
     *
     * @since 1.0.0
     * @access protected
     * @return array $sortable, the array of columns that can be sorted by the user.
     */
    protected function get_sortable_columns() {
      $sortable = array(
        'redirect_from' => array( 'redirect_from', false),
        'redirect_to'   => array( 'redirect_to', false),
        'status_code'   => array( 'status_code', false)
      );
      return $sortable;
    }

    /**
     * Get a list of all, hidden and sortable columns, with filter applied.
     *
     * @since 1.0.0
     * @access protected
     *
     * @return array
     */
    protected function get_column_info() {
      if ( isset( $this->_column_headers ) ) {
        return $this->_column_headers;
      }

      $columns = get_column_headers( $this->screen );
      $hidden = get_hidden_columns( $this->screen );

      $sortable_columns = $this->get_sortable_columns();
      /**
       * Filter the list table sortable columns for a specific screen.
       *
       * The dynamic portion of the hook name, $this->screen->id, refers
       * to the ID of the current screen, usually a string.
       *
       * @since 3.5.0
       *
       * @param array $sortable_columns An array of sortable columns.
       */
      $_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $sortable_columns );

      $sortable = array();
      foreach ( $_sortable as $id => $data ) {
        if ( empty( $data ) ) {
          continue;
        }

        $data = (array) $data;
        if ( ! isset( $data[1] ) ) {
          $data[1] = false;
        }

        $sortable[ $id ] = $data;
      }

      $this->_column_headers = array( $columns, $hidden, $sortable );

      return $this->_column_headers;
    }

    /**
     * Return number of visible columns.
     *
     * @since 1.0.0
     * @access public
     *
     * @return int
     */
    public function get_column_count() {
      list ( $columns, $hidden ) = $this->get_column_info();
      $hidden = array_intersect( array_keys( $columns ), array_filter( $hidden ) );
      return count( $columns ) - count( $hidden );
    }

    /**
     * Print column headers, accounting for hidden and sortable columns.
     *
     * @since 1.0.0
     * @access protected
     *
     * @param bool $with_id Whether to set the id attribute or not.
     */
    protected function print_column_headers( $with_id = true ) {
      list( $columns, $hidden, $sortable ) = $this->get_column_info();

      $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
      $current_url = remove_query_arg( 'paged', $current_url );

      $current_orderby = ( isset( $_GET['orderby'] ) ) ? $_GET['orderby'] : '';
      $current_order   = ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] ) ? 'desc' : 'asc';

      if ( ! empty( $columns['cb'] ) ) {
        static $cb_counter = 1;
        $columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
          . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
        $cb_counter++;
      }

      foreach ( $columns as $column_key => $column_display_name ) {
        $class = array( 'manage-column', 'column-' . $column_key );

        $style = '';
        if ( in_array( $column_key, $hidden ) ) {
          $style = ' style="display:none;"';
        }

        if ( 'cb' == $column_key ) {
          $class[] = 'check-column';
        } elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) ) {
          $class[] = 'num';
        }

        if ( isset( $sortable[ $column_key ] ) ) {
          list( $orderby, $desc_first ) = $sortable[ $column_key ];

          if ( $current_orderby == $orderby ) {
            $order   = ( 'asc' == $current_order ) ? 'desc' : 'asc';
            $class[] = 'sorted';
            $class[] = $current_order;
          } else {
            $order   = ( $desc_first ) ? 'desc' : 'asc';
            $class[] = 'sortable';
            $class[] = ( $desc_first ) ? 'asc' : 'desc';
          }

          $column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
        }

        $id = ( $with_id ) ? ' id="' . $column_key . '"' : '';

        if ( ! empty( $class ) ) {
          $class = ' class="' . join( ' ', $class ) . '"';
        }

        echo '<th scope="col"' . $id . $class . $style . '>' . $column_display_name . '</th>';
      }
    }

    /**
     * Display the table.
     *
     * @since 1.0.0
     * @access public
     */
    public function display() {
      extract( $this->_args );

      $this->display_tablenav( 'top' );
      ?>
      <table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
        <thead>
        <tr>
          <?php $this->print_column_headers(); ?>
        </tr>
        </thead>

        <tfoot>
        <tr>
          <?php $this->print_column_headers( false ); ?>
        </tr>
        </tfoot>

        <tbody id="the-list"<?php if ( $singular ) echo ' data-wp-lists="list:' . $singular . '"'; ?>>
          <?php $this->display_rows_or_placeholder(); ?>
        </tbody>
      </table>
      <?php
      $this->display_tablenav( 'bottom' );
    }

    /**
     * Display the table for adding a new redirection.
     *
     * @since 1.0.0
     * @access public
     */
    public function display_new() {
      ?>
      <table class="wp-list-table-new <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
        <thead>
        <tr>
          <?php
            foreach ( $this->get_columns() as $column_key => $column_display_name ) {
              echo '<th scope="col" class="manage-column column-' . $column_key . '"">' . $column_display_name . '</th>';
            }
          ?>
        </tr>
        </thead>

        <tbody>
          <?php $this->single_row_columns( array( 'redirection_id' => -1 ) ); ?>
        </tbody>
      </table>
      <div id="new-redirection-buttons" class="actions">
        <?php submit_button( __( 'Save New Redirection', 'redirectman' ), 'primary large', 'save-new-redirection', false ); ?>          
        <?php submit_button( __( 'Cancel', 'redirectman' ), '', 'cancel-new-redirection', false ); ?>
        <span id="new-redirection-message"></span>
      </div>
      <?php
    }

    /**
     * Get a list of CSS classes for the <table> tag.
     *
     * @since 1.0.0
     * @access protected
     *
     * @return array
     */
    protected function get_table_classes() {
      return array( 'widefat', 'fixed', $this->_args['plural'] );
    }

    /**
     * Generate the table navigation above or below the table.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function display_tablenav( $which ) {
      if ( 'top' == $which ) {
        wp_nonce_field( 'bulk-' . $this->_args['plural'] );
      }
      ?>
      <div class="tablenav <?php echo esc_attr( $which ); ?>">
        <div class="alignleft actions bulkactions">
          <?php $this->bulk_actions(); ?>
        </div>
      <?php
        $this->extra_tablenav( $which );
        $this->pagination( $which );
      ?>
        <br class="clear" />
      </div>
      <?php
    }

    /**
     * Add extra markup in the toolbars before or after the list.
     * 
     * @since 1.0.0
     * @access protected
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list.
     */
    protected function extra_tablenav( $which ) {
      ?>
      <div class="alignleft actions">
      <?php
      switch ( $which ) {
        case 'bottom':
          submit_button( __( 'Save All', 'redirectman' ), 'primary large', 'save-redirections', false );
          break;
        case 'top':
          $selected_status_code = ( isset( $_REQUEST['status_code'] ) ) ? $_REQUEST['status_code'] : null;
          echo $this->status_code_dropdown( 'status_code', $selected_status_code, true );
          submit_button( __( 'Filter', 'redirectman' ), 'button', 'filter', false );
          echo '<a id="show-all" class="button" href="options-general.php?page='. $_REQUEST['page'] . '">' . __( 'Show All', 'redirectman' ) . '</a>';
          break;
      }
      ?>
      </div>
      <?php
    }

    /**
     * Generate the <tbody> part of the table.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function display_rows_or_placeholder() {
      if ( $this->has_items() ) {
        $this->display_rows();
      } else {
        echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
        $this->no_items();
        echo '</td></tr>';
      }
    }

    /**
     * Generate the table rows
     *
     * @since 1.0.0
     * @access protected
     */
    protected function display_rows() {
      foreach ( $this->items as $item ) {
        $this->single_row( $item );
      }
    }

    /**
     * Generates content for a single row of the table.
     *
     * @since 1.0.0
     * @access protected
     *
     * @param object $item The current item.
     */
    protected function single_row( $item ) {
      static $row_class = '';
      $row_class = ( '' == $row_class ) ? ' class="alternate"' : '';

      echo '<tr' . $row_class . '>';
      $this->single_row_columns( $item );
      echo '</tr>';
    }

    /**
     * Generates the columns for a single row of the table.
     *
     * @since 1.0.0
     * @access protected
     *
     * @param object $item The current item.
     */
    protected function single_row_columns( $item ) {
      list( $columns, $hidden ) = $this->get_column_info();

      foreach ( $columns as $column_name => $column_display_name ) {
        $class = ' class="' . $column_name . ' column-' . $column_name . '"';

        $style = ( in_array( $column_name, $hidden ) ) ? ' style="display:none;"' : '';

        $attributes = $class . $style;

        if ( 'cb' == $column_name ) {
          echo '<th scope="row" class="check-column">';
          echo $this->column_cb( $item );
          echo '</th>';
        } elseif ( method_exists( $this, 'column_' . $column_name ) ) {
          echo '<td ' . $attributes . '>';
          echo call_user_func( array( $this, 'column_' . $column_name ), $item );
          echo '</td>';
        } else {
          echo '<td ' . $attributes . '>';
          echo $this->column_default( $item, $column_name );
          echo '</td>';
        }
      }
    }



















/*  BACKUP WITHOUT WILDCARD 

    function circular_next_from( $circulars_clean, $to ) {

      foreach ( $circulars_clean as $redirection_id => $circular ) {
        if ( $circular['redirect_from'] == $to ) {
          return $redirection_id;
        }
      }
      return false;
    }
*/

/*    function circular_next_from( $circulars_clean, $to_new ) {
      $new = true;
      if ( $new ) {
        foreach ( $circulars_clean as $redirection_id => $circular ) {
          $from = $circular['redirect_from'];
          $to   = $circular['redirect_to'];
          $wildcard = ( false !== strpos( $from, '*' ) );
          if ( $wildcard ) {
            $to   = str_replace( '*', '$1', $to );
            $from = str_replace( '*', '(.*)', $from );
            $pattern = '/^' . str_replace( '/', '\/', rtrim( $from, '/' ) ) . '/';
            $from = preg_replace( $pattern, $to, $to_new );
            if ( $from !== $to_new ) {
              // Pattern matched.
              return $redirection_id;
            }
          } elseif ( $from === $to_new ) {
            return $redirection_id;
          }
        }
        return false;
      } else {
        foreach ( $circulars_clean as $redirection_id => $circular ) {
          if ( $circular['redirect_from'] == $to_new ) {
            return $redirection_id;
          }
        }
        return false;
      }
    }*/

    /**
     * Load and return all duplicate redirection ids.
     * 
     * @since 1.0.0
     * @access public
     *
     * @return array Ids of all duplicate redirections.
     */
/*    public function load_duplicate_redirections() {
      global $wpdb;
      $query = "SELECT r.redirection_id FROM " .
               "(SELECT redirect_from, count(*) duplicates FROM " . RedirectMan::get_database_table() . " GROUP BY redirect_from) ss " .
               "RIGHT JOIN " . RedirectMan::get_database_table() . " r USING(redirect_from) " .
               "WHERE ss.duplicates > 1";

      $duplicates = $wpdb->get_results( $query, ARRAY_A );
      $this->_duplicate_redirections = array();
      if ( ! empty( $duplicates ) ) {
        foreach ($duplicates as $duplicate) {
          $this->_duplicate_redirections[] = $duplicate['redirection_id'];
        }
      }
      return $this->_duplicate_redirections;
    }*/

    /**
     * Load and return all ids of redirections that are involved in circular redirection.
     * 
     * @since 1.0.0
     * @access public
     *
     * @return array Ids of all circular redirections.
     */
/*    public function load_circular_redirections() {
      global $wpdb;
      $query = "SELECT redirection_id, redirect_from, redirect_to FROM " . RedirectMan::get_database_table();
      $circulars = $wpdb->get_results( $query, ARRAY_A );
      
      // Clean up results and make a readable array.
      $circulars_clean = array();
      $this->_circular_redirections = array();
      foreach ( $circulars as $circular ) {
        $circulars_clean[ $circular['redirection_id'] ] = array(
          'redirect_from' => $circular['redirect_from'],
          'redirect_to'   => $circular['redirect_to']
        );
      }

      $circular_redirection_ids = array();
      foreach ( $circulars_clean as $redirection_id => $circular ) {
        $redirect_to = $circular['redirect_to'];
        $current_circular_redirection_ids = array( $redirection_id );

        while ( $redirection_id_new = $this->circular_next_from( $circulars_clean, $redirect_to ) ) {
          if ( $redirection_id == $redirection_id_new || in_array( $redirection_id_new, $current_circular_redirection_ids ) ) {
            $this->_circular_redirections = array_unique( array_merge( $this->_circular_redirections, $current_circular_redirection_ids ) );
            break;
          }

          $current_circular_redirection_ids[] = $redirection_id_new;
          $redirect_to = $circulars_clean[ $redirection_id_new ]['redirect_to'];
        }
        $this->_parsed_redirections[ $redirection_id ] = $circular;
      }
      return $this->_circular_redirections;
    }*/

//    public $_parsed_redirections = array();







    /**
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @param array $item A singular item/row will all it's data.
     * @return string Text to be placed inside the checkbox column.
     **/
    protected function column_cb( $item ) {
      $classes = 'bulk_item_cb';
      $classes .= ( is_array( $this->_affected_ids ) && in_array( $item['redirection_id'], $this->_affected_ids ) ) ? ' redirection-changed redirection-is-' . $this->_operation : '';

      $duplicate_div = '';
      if ( isset( $this->_duplicate_redirections ) && in_array( $item['redirection_id'], $this->_duplicate_redirections ) ) {
        $duplicate_div = '<div title="' . __( 'Duplicate Redirection detected', 'redirectman' ) . '" class="is-duplicate"></div>';
      }
      $circular_div = '';
      if ( isset( $this->_circular_redirections ) && in_array( $item['redirection_id'], $this->_circular_redirections ) ) {
        $circular_div = '<div title="' . __( 'Circular Redirection detected', 'redirectman' ) . '" class="is-circular"></div>';
      }

      return sprintf(
        '<input class="%s" type="checkbox" name="%s[cb][]" value="%d" />%s%s',
        $classes,
        $this->get_name(),
        $item['redirection_id'],
        $duplicate_div,
        $circular_div
      );
    }

    function column_redirect_from( $item ) {
      return sprintf( '<input type="text" name="%s[%d][redirect_from]" value="%s" class="inline-edit-redirect_from-input" />',
        $this->get_name(),
        $item['redirection_id'],
        $item['redirect_from']
      );
    }

    function column_redirect_to( $item ) {
      return sprintf( '<input type="text" name="%s[%d][redirect_to]" value="%s" class="inline-edit-redirect_from-input" />',
        $this->get_name(),
        $item['redirection_id'],
        $item['redirect_to']
      );
    }

    function status_code_dropdown( $name, $selected_status_code = null, $show_title = false ) {
      $status_codes = RedirectMan::get_status_codes();
      if ( $show_title ) {
        $status_codes = array( '-1' => __( 'All Status Codes', 'redirectman' ) ) + $status_codes;
      }
      $return = sprintf( '<select name="%s">', $name );

      foreach ( $status_codes as $status_code => $status_code_text ) {
        $return .= '<option' . selected( $selected_status_code, $status_code, false ) . ' value="' . $status_code . '">' . $status_code_text . '</option>';
      }
      $return .= '</select>';
      return $return;
    }

    function column_status_code( $item ) {
      $name = sprintf( '%s[%s][status_code]',
        $this->get_name(),
        $item['redirection_id']
      );
      return $this->status_code_dropdown( $name, $item['status_code'] );
    }        

    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default( $item, $column_name ) {
      return print_r( $item, true ); //Show the whole array for troubleshooting purposes
    }






    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/

    function save_redirections( $data, $action = 'save' ) {

      if ( ! isset( $data ) || ! isset( $action ) ) {
        return null;
      }

      global $wpdb;

      if ( 'save' == $action ) {
        // Remove the "add-new-redirection" entry.
        if ( isset( $data[-1] ) ) {
          unset( $data[-1] );
        }
        $result = null;
        foreach ( $data as $redirection_id => $redirection ) {
          extract( $redirection );
          // Correct all slash issues.
          $redirect_from = '/' . trim( am_str_replace_recursive( '//', '/', $redirect_from ), '/' );
          $redirect_to = am_str_replace_recursive( '//', '/', $redirect_to );
          if ( '/' !== $redirect_to ) {
            $redirect_to = rtrim( $redirect_to, '/' );
          }

          $result = $wpdb->update(
            RedirectMan::get_database_table(),
            compact( 'redirect_from', 'redirect_to', 'status_code' ),
            array( 'redirection_id' => $redirection_id ),
            array( '%s', '%s', '%d' ),
            array( '%d' )
          );

          if ( false !== $result ) {
            if ( 0 < $result ) {
              $this->_affected_ids[] = $redirection_id;
            }
          } else {
            $this->_operation = 'error';
            $this->_status_message = _n( 'Error occured while saving Redirection!', 'Error occured while saving Redirections!', count( $data ), 'redirectman' );
            break;
          }
        }
        if ( false !== $result ) { //&& count( $this->_affected_ids ) > 0 ) {
          $this->_status_message = sprintf( _n( '%d Redirection updated!', '%d Redirections updated!', count( $this->_affected_ids ), 'redirectman' ), count( $this->_affected_ids ) );
          $this->_operation = 'updated';
        }

      } elseif ( 'save_new' == $action ) {
        if ( isset( $data[-1] ) && $redirection = $data[-1] ) {
          extract( $redirection );
          if ( am_strornull( $redirect_from ) ) {

            // Correct all slash issues.
            $redirect_from = '/' . trim( am_str_replace_recursive( '//', '/', $redirect_from ), '/' );
            $redirect_to = am_str_replace_recursive( '//', '/', $redirect_to );
            if ( '/' !== $redirect_to ) {
              $redirect_to = rtrim( $redirect_to, '/' );
            }


            $result = $wpdb->insert(
              RedirectMan::get_database_table(),
              compact( 'redirect_from', 'redirect_to', 'status_code' ),
              array( '%s', '%s', '%d' )
            );

            if ( $result ) {

              if ( isset( $wpdb->insert_id ) ) {
                $this->_affected_ids = array( $wpdb->insert_id );
              }

              $this->_operation = 'added';
              $this->_status_message = __( 'New Redirection Added!', 'redirectman' );
            } else {
              $this->_operation = 'error';
              $this->_status_message = __( 'Error occured while adding new Redirection!', 'redirectman' );
            }
          } 
        }
      }
    }

    function delete_redirections( $redirection_ids ) {
      global $wpdb;
      $query = "DELETE FROM " . RedirectMan::get_database_table() .
        " WHERE redirection_id IN (" . implode( ',', $redirection_ids) . ")";
      $result = $wpdb->query( $wpdb->prepare( $query ) );
      $this->_operation = 'deleted';
      $this->_status_message = sprintf( _n( '%d Redirection deleted!', '%d Redirections deleted!', $result, 'redirectman' ), $result );
    }

    function update_status_codes( $redirection_ids, $status_code = 301 ) {
      global $wpdb;
      $query = "UPDATE " . RedirectMan::get_database_table() .
        " SET status_code = %d" .
        " WHERE redirection_id IN (" . implode( ',', $redirection_ids) . ")";
      $wpdb->query( $wpdb->prepare( $query, $status_code ) );
      $this->_operation = 'updated';
      $this->_affected_ids = $redirection_ids;
      $this->_status_message = sprintf( _n( '%d Redirection updated!', '%d Redirections updated!', count( $redirection_ids ), 'redirectman' ), count( $redirection_ids ) );
    }

    function process_bulk_action() {

      // Check for inconsistencies query.
      $this->_show_duplicates = isset( $_POST['show-duplicates'] );
      $this->_show_circulars  = isset( $_POST['show-circulars'] );

      // Get the array of selected checkboxes.
      $redirection_data = ( isset( $_REQUEST[ $this->get_name() ] ) ) ? $_REQUEST[ $this->get_name() ] : null;

      if ( isset( $redirection_data ) ) {
        $redirection_ids = ( isset( $redirection_data['cb'] ) ) ? (array) $redirection_data['cb'] : null;

        $action = $this->current_action();

        if ( in_array( $action, array_keys( RedirectMan::get_status_codes() ) ) ) {
          // Bulk change status codes.
          $this->update_status_codes( $redirection_ids, $action );
        } else {
          switch ( $action ) {
            case 'save':
            case 'save_new':
              $this->save_redirections( $redirection_data, $action );
              break;
            case 'delete':
              $this->delete_redirections( $redirection_ids );
              break;
          }   
        }
      }
    }


  }
}


?>