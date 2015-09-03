<?php

/**
 * Single redirection class for RedirectMan.
 *
 * @package RedirectMan
 * @subpackage Redirection
 * @author Armando Lüscher
 * @since 1.0.0
 */
if ( ! class_exists('RedirectMan_Redirection' ) ) {
  class RedirectMan_Redirection {

    /**
     * An array of all redirections.
     *
     * @var array
     */
    private static $_all_redirections = null;

    /**
     * Name of the database table.
     *
     * @var string
     */
    private static $_database_table = 'redirectman';

    /**
     * The database ID of the redirection.
     *
     * @var int
     */
    private $_redirection_id;

    /**
     * Redirect from here.
     *
     * @var string
     */
    private $_redirect_from;

    /**
     * Redirect to here.
     *
     * @var string
     */
    private $_redirect_to;

    /**
     * HTTP status code for redirect. (301, 302, 303 or 307)
     *
     * @var int
     */
    private $_status_code;

    /**
     * Current redirection is a duplicate.
     *
     * @var boolean
     */
    private $_is_duplicate = false;

    /**
     * Current redirection is involved in a circular redirection.
     *
     * @var boolean
     */
    private $_is_circular  = false;


    /**
     * Load all the redirections from the database.
     */
    public static function load_all_redirections() {
      global $wpdb;
      $query = "SELECT redirection_id, redirect_from, redirect_to, status_code FROM " . $wpdb->prefix . self::$_database_table . " ORDER BY redirect_from DESC";
      $all_redirections = $wpdb->get_results( $query, ARRAY_A );
      foreach ( $all_redirections as $redirection ) {
        extract( $redirection );
        new self( $redirection_id, $redirect_from, $redirect_to, $status_code );
      }
    }

    /**
     * Make an array of all duplicate redirections.
     *
     * @return array An array of all redirections flagged as duplicates.
     */
    public static function get_duplicates() {
      if ( is_null( self::$_all_redirections ) ) {
        self::load_all_redirections();
      }
      $duplicates = array();
      foreach ( self::$_all_redirections as $redirection ) {
        if ( $redirection->is_duplicate() ) {
          $duplicates[ $redirection->get_redirection_id() ] = $redirection;
        }
      }
      return $duplicates;
    }

    /**
     * Make an array of all circular redirections.
     *
     * @return array An array of all redirections flagged as circular redirections.
     */
    public static function get_circulars() {
      if ( is_null( self::$_all_redirections ) ) {
        self::load_all_redirections();
      }
      $circulars = array();
      foreach ( self::$_all_redirections as $redirection ) {
        if ( $redirection->is_circular() ) {
          $circulars[ $redirection->get_redirection_id() ] = $redirection;
        }
      }
      return $circulars;
    }

    /**
     * Create a new redirection object and add it to the $_all_redirections array.
     *
     * @param int $redirection_id   The database ID of the redirection.
     * @param string $redirect_from Redirect from here.
     * @param string $redirect_to   Redirect to here.
     * @param int $status_code      HTTP status code for redirect. (301, 302, 303 or 307)
     */
    public function __construct( $redirection_id, $redirect_from, $redirect_to, $status_code ) {
      $this->_redirection_id = $redirection_id;
      $this->set_redirect_from( $redirect_from );
      $this->set_redirect_to( $redirect_to );
      $this->set_status_code( $status_code );

      self::$_all_redirections[ $redirection_id ] = $this;
    }

    /**
     * Return the redirection id.
     * @return int The redirection id.
     */
    public function get_redirection_id() {
      return $this->_redirection_id;
    }

    /**
     * Return the "redirect_from" field.
     *
     * @return string
     */
    public function get_redirect_from() {
      return $this->_redirect_from;
    }

    /**
     * Clean up and set the "redirect_from" field.
     *
     * @param string $redirect_from
     */
    public function set_redirect_from( $redirect_from ) {
      $redirect_from = am_str_replace_recursive( '//', '/', $redirect_from );
      if ( '/' !== $redirect_from ) {
        $redirect_from = rtrim( $redirect_from, '/' );
      }

      $this->_redirect_from = $redirect_from;
    }

    /**
     * Return the "redirect_to" field.
     *
     * @return string
     */
    public function get_redirect_to() {
      return $this->_redirect_to;
    }

    /**
     * Clean up and set the "redirect_to" field.
     *
     * @param string $redirect_to
     */
    public function set_redirect_to( $redirect_to ) {
      $redirect_to = am_str_replace_recursive( '//', '/', $redirect_to );
      if ( '/' !== $redirect_to ) {
        $redirect_to = rtrim( $redirect_to, '/' );
      }

      $this->_redirect_to = $redirect_to;
    }

    /**
     * Return the HTTP status code. (301, 302, 303 or 307)
     *
     * @return int
     */
    public function get_status_code() {
      return $this->_status_code;
    }

    /**
     * Set the HTTP status code. (301, 302, 303 or 307)
     *
     * @param int $status_code
     */
    public function set_status_code( $status_code ) {
      $this->_status_code = $status_code;
    }

    /**
     * Get or set the "is_duplicate" flag.
     *
     * @param  bool|null $is_duplicate null returns value, bool sets value.
     * @return boolean The state of the "is_duplicate" flag.
     */
    public function is_duplicate( $is_duplicate = null ) {
      if ( ! is_null( $is_duplicate ) ) {
        $this->_is_duplicate = (bool) $is_duplicate;
      }
      return $this->_is_duplicate;
    }

    /**
     * Get or set the "is_circular" flag.
     *
     * @param  bool|null $is_circular null returns value, bool sets value.
     * @return boolean The state of the "is_circular" flag.
     */
    public function is_circular( $is_circular = null ) {
      if ( ! is_null( $is_circular ) ) {
        $this->_is_circular = (bool) $is_circular;
      }
      return $this->_is_circular;
    }





    public function get_next_redirection() {

    }

    public function get_final_redirection() {

    }

    public function find_duplicates() {
      $unique_froms = array();
      $duplicate_ids = array();

      foreach ( self::$_all_redirections as $redirection ) {
        extract( $redirection );

        if ( $key = array_search( $redirection_from, $unique_froms ) ) {
          $duplicate_ids[ $redirection_id ] = true;
          $duplicate_ids[ $key ] = true;
        }

        foreach ( self::$_all_redirections as $j_redirection ) {
          extract( $i_redirection, EXTR_PREFIX_ALL, 'j' );

          // Skip comparisons with itself.
          if ( $i_redirection_id === $j_redirection_id ) {
            continue;
          }

          if ( $i_redirect_from === $j_redirect_from ) {
            // Simple comparison.
            $duplicate_ids[] = $i_redirection_id;
            continue;
          }

          $i_is_wildcard = ( false !== strpos( $i_redirect_from, '*' ) );
          $j_is_wildcard = ( false !== strpos( $j_redirect_from, '*' ) );

          // If they both have a wildcard but aren't the same, they aren't duplicates.
          if ( $i_is_wildcard && $j_is_wildcard ) {
            continue;
          }

        if ( $i_is_wildcard ) {
          // Wildcard comparison.
          $i_redirect_from = str_replace( '*', '(.*)', $i_redirect_from );
          $pattern = '/^' . str_replace( '/', '\/', $redirect_from ) . '$/';

          if ( preg_match( $pattern, $redirect_to_new ) ) {
            // Pattern matched.
            return $redirection_id;
          }
        } elseif ( false !== strpos( $j_redirect_from, '*' ) ) {
          // Wildcard comparison.

        }


      }
      }
    }

    public function find_circulars() {

    }
  }
}

?>