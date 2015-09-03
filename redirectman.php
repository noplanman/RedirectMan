<?php
/*
Plugin Name: RedirectMan
Plugin URI: http://www.armyman.ch/redirectman-plugin-for-wordpress/
Description: Create a list of URIs that you would like to redirect to another URI. For developers, this plugin can be used to integrate redirection metaboxes for any post type.
Version: 1.0
Author: Armando Lüscher
Author URI: http://www.armyman.ch/
*/

/*  Copyright 2014 Armando Lüscher (email : armando@armyman.ch)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


// Make sure the plugin can't be called directly.
if ( ! defined( 'ABSPATH' ) ) {
  die( -1 );
}

//return;

require_once( plugin_dir_path( __FILE__ ) . 'redirectman-redirection.php' );
require_once( plugin_dir_path( __FILE__ ) . 'redirectman-table.php' );
require_once( plugin_dir_path( __FILE__ ) . 'redirectman-help.php' );


/**
 * Contextual help class for RedirectMan.
 * 
 * @package RedirectMan
 * @author Armando Lüscher
 * @since 1.0.0
 */
if ( ! class_exists( 'RedirectMan' ) ) {
  
  class RedirectMan {

  private static $status_codes = array(
    '301' => '301 Moved Permanently',
    '302' => '302 Found',
    '303' => '303 See Other',
    '307' => '307 Temporary Redirect'
  );

  private $redirectman_table;
  private static $database_table;

  public static function get_status_codes( $status_code = null ) {
    if ( isset( $status_code ) && isset( self::$status_codes[ $status_code ] ) ) {
      return self::$status_codes[ $status_code ];
    }
    return self::$status_codes;
  }

  public static function get_database_table() {
    return self::$database_table;
  }

  public function __construct() {
    global $wpdb;
    self::$database_table = $wpdb->prefix . 'redirectman';
  }






    /**
     * options_page function
     * Generate the options page in the wordpress admin.
     * @access public
     * @return void
     */
    public function options_page() {
      $table = $this->redirectman_table;
    ?>
    <div class="wrap redirectman">
        
      <h2><?php _e( 'RedirectMan', 'redirectman' ); ?><span id="add-new-redirection" class="add-new-h2"><?php _e( 'Add New Redirection', 'redirectman' ); ?></span></h2>

      <form id="new-redirection-form" method="post">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />

        <?php $table->display_new(); ?>
      </form>

      <form id="redirections-form" method="post">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />

          <?php

          $status_message = $table->get_status_message();
          $operation = $table->get_operation();
          $class = '';
          switch ( $operation ) {
            case 'error':   $class .= ' error message-error'; break;
            case 'added':   $class .= ' updated message-added'; break;
            case 'updated': $class .= ' updated message-updated'; break;
            case 'deleted': $class .= ' updated message-deleted'; break;
          }
          if ( ! empty( $status_message ) ) { ?>
            <div id="status-message" class="<?php echo $class; ?>"><p><?php echo $status_message; ?></p></div>
          <?php }

          echo '<div class="above-tablenav">';

          $duplicates = $table->get_duplicates();
          $circulars  = $table->get_circulars();
          if ( ! empty( $duplicates ) || ! empty( $circulars ) ) {

            echo '<div id="inconsistencies" class="is-error">';

            if ( ! empty( $duplicates ) ) {
              submit_button( sprintf( _n( '%d Duplicate', '%d Duplicates', count( $duplicates ), 'redirectman' ), count( $duplicates ) ), 'primary', 'show-duplicates', false );
            }
            if ( ! empty( $circulars ) ) {
              submit_button( sprintf( _n( '%d Circular', '%d Circulars', count( $circulars ), 'redirectman' ), count( $circulars ) ), 'primary', 'show-circulars', false );
            }

//            echo '<div id="inconsistencies" class="alignleft">' . __( 'Inconsistencies have been found!', 'redirectman' ) . '</div>';
            echo '<span>' . __( 'Inconsistencies have been found!', 'redirectman' ) . '</span>';
            echo '<span title="' . __( 'What does this mean?', 'redirectman' ) . '" class="show-help show-help-inconsistencies"></span>';

            echo '</div>';
          }

          $this->redirectman_table->search_box( __( 'Search', 'redirectman' ), $this->redirectman_table->get_name());

          echo '</div>';
          ?>
        <!-- Now we can render the completed list table -->
        <?php $this->redirectman_table->display(); ?>
      </form>
    </div>
    <?php
    } // end of function options_page







    function get_next_redirection( $circulars_clean, $redirect_to_new ) {
      foreach ( $circulars_clean as $redirection_id => $circular ) {
        // If the current redirection has already been handled, continue.
//        if ( array_key_exists( $redirection_id, $this->_parsed_redirections ) || in_array( $redirection_id, $this->_circular_ids ) ) {
//          continue;
//        }


        extract( $circular );

if ( $redirect_to == $redirect_to_new ) {
  //return false;
}

        $root = true;

        // If the redirect_to is not at root level (/xyz), check only last part of redirect_from.
/*        if ( 0 !== strpos( $redirect_to_new, '/' ) ) {
          // Deeper level.
          $root = false;
          $redirect_from = substr( strrchr( $redirect_from, '/' ), 1 );
        }*/
        if ( 0 !== strpos( $redirect_to_new, '/' ) ) {
          // Deeper level.
          $root = false;
          $a = substr( $redirect_from, strrpos( $redirect_from, '/' ) );
          fu($redirect_to_new);
          $redirect_from = substr( strrchr( $redirect_from, '/' ), 1 );
        }

        if ( false !== strpos( $redirect_from, '*' ) ) {
          // WILDCARD redirection.
          $redirect_from = str_replace( '*', '(.*)', $redirect_from );
          $pattern = '/^' . str_replace( '/', '\/', $redirect_from ) . '$/';

/*echo "<pre>";
var_dump("pattern: ".$pattern);
var_dump("from   : ".$redirect_from);
var_dump("to_new : ".$redirect_to_new);
var_dump("matched: ".preg_match( $pattern, $redirect_to_new ));
echo "</pre>";*/

          if ( preg_match( $pattern, $redirect_to_new ) ) {
            // Pattern matched.
            return $redirection_id;
          }
        } elseif ( $redirect_from === $redirect_to_new ) {
          // SIMPLE redirection.
          return $redirection_id;
        }
      }
      return false;
    }

    private $_duplicate_ids;
    private $_circular_ids;

    /**
     * Load and return all ids of redirections that are involved in circular redirection.
     * 
     * @since 1.0.0
     * @access public
     *
     * @return array Ids of all circular redirections.
     */
    public function get_circular_redirections( $force_reload = false ) {
      if ( isset( $this->_circular_ids ) && ! $force_reload ) {
        return $this->_circular_ids;
      }

      global $wpdb;
      $query = "SELECT redirection_id, redirect_from, redirect_to, status_code FROM " . self::get_database_table() . " ORDER BY redirect_from DESC";
      $circulars = $wpdb->get_results( $query, ARRAY_A );
      
      // Clean up results and make a readable array.
      $circulars_clean = array();
      foreach ( $circulars as $circular ) {

        extract( $circular );

        $redirect_from = '/' . trim( am_str_replace_recursive( '//', '/', $redirect_from ), '/' );
        $redirect_to = am_str_replace_recursive( '//', '/', $redirect_to );
        if ( '/' !== $redirect_to ) {
          $redirect_to = rtrim( $redirect_to, '/' );
        }

        $circulars_clean[ $redirection_id ] = compact( 'redirect_from', 'redirect_to', 'status_code' );
      }

      $this->_circular_ids = array();
//fu($circulars_clean);
      $circular_redirection_ids = array();
      foreach ( $circulars_clean as $redirection_id => $circular ) {
        // If the current redirection has already been handled, continue.
        if ( array_key_exists( $redirection_id, $this->_parsed_redirections ) || in_array( $redirection_id, $this->_circular_ids ) ) {
          continue;
        }

        $is_circular = false;
        extract( $circular );
        $current_circular_redirection_ids = array( $redirection_id );
//fu($this->get_next_redirection( $circulars_clean, $redirect_to ) );
        while ( $redirection_id_new = $this->get_next_redirection( $circulars_clean, $redirect_to ) ) {
          if ( $redirection_id == $redirection_id_new || in_array( $redirection_id_new, $current_circular_redirection_ids ) ) {
            $is_circular = true;
            $this->_circular_ids = array_unique( array_merge( $this->_circular_ids, $current_circular_redirection_ids ) );
            break;
          }

          $current_circular_redirection_ids[] = $redirection_id_new;
          $redirect_to = $circulars_clean[ $redirection_id_new ]['redirect_to'];
        }
//fu($redirect_to);
        if ( ! $is_circular ) {
          
//          $this->_parsed_redirections[ $redirection_id ] = compact( 'redirect_from', 'redirect_to', 'status_code' );
          // Set all chain redirections as parsed.
          if ( ! empty( $current_circular_redirection_ids ) ) {
            foreach ( $current_circular_redirection_ids as $redirection_id ) {
              $this->_parsed_redirections[ $redirection_id ] = $circulars_clean[ $redirection_id ];
              $this->_parsed_redirections[ $redirection_id ]['redirect_to'] = $redirect_to;
            }
          }
//fu($this->_parsed_redirections);
        }
      }
      return $this->_circular_ids;
    }


    /**
     * Load and return all duplicate redirection ids.
     * 
     * @since 1.0.0
     * @access public
     *
     * @return array Ids of all duplicate redirections.
     */
    public function get_duplicate_redirections( $force_reload = false ) {
      if ( isset( $this->_duplicate_ids ) && ! $force_reload ) {
        return $this->_duplicate_ids;
      }

      global $wpdb;
      $query = "SELECT r.redirection_id FROM " .
               "(SELECT redirect_from, count(*) duplicates FROM " . self::get_database_table() . " GROUP BY redirect_from) ss " .
               "RIGHT JOIN " . self::get_database_table() . " r USING(redirect_from) " .
               "WHERE ss.duplicates > 1";

      $duplicates = $wpdb->get_results( $query, ARRAY_A );
      $this->_duplicate_ids = array();
      if ( ! empty( $duplicates ) ) {
        foreach ($duplicates as $duplicate) {
          $this->_duplicate_ids[] = $duplicate['redirection_id'];
        }
      }
      return $this->_duplicate_ids;
    }


    private $_parsed_redirections = array();

    public function get_parsed_redirections() {
      return $this->_parsed_redirections;
    }






    /**
     * redirect function
     * Read the list of redirects and if the current page 
     * is found in the list, send the visitor on her way
     * @access public
     * @return void
     */
    public function redirect() {
//return;
//fu($this->_parsed_redirections);
      if ( ! empty( $this->_parsed_redirections ) ) {


        // This is what the user asked for (strip out home portion, case insensitive).
        $full_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

        $user_request = rtrim( str_ireplace( get_option( 'home' ), '', $full_url ), '/' );

//fu($user_request);
//  wp_die(get_home_url());

        // Don't allow people to accidentally lock themselves out of admin.
        if ( 0 !== strpos( $user_request, '/wp-login' ) && 0 !== strpos( $user_request, '/wp-admin' ) ) {
        
          // Find a matching redirection, if it exists.
          foreach ( $this->_parsed_redirections as $redirection_id => $redirection ) {

            $redirect_found = false;

            extract( $redirection );

            // Check if we should use wildcard method.
            if ( false !== strpos( $redirect_from, '*' ) ) {
              // WILDCARD redirection.
              $redirect_from = str_replace( '*', '(.*)', $redirect_from );
              $pattern = '/^' . str_replace( '/', '\/', $redirect_from ) . '$/';
              if ( preg_match( $pattern, $user_request ) ) {
                // Pattern matched.
                $redirect_found = true;
              }
            } elseif ( $redirect_from === $user_request ) {
              // SIMPLE redirection.
              $redirect_found = true;
            }



/*
            if ( strpos( $redirect_from, '*' ) !== false) {
              // WILDCARD redirection.
              // Make sure it gets all the proper decoding and rtrim action
              $redirect_from = str_replace( '*', '(.*)', $redirect_from);
              //$redirect_to = str_replace('*','$1',$redirect_to);
//              $pattern = '/^' . str_replace( '/', '\/', rtrim( $redirect_from, '/' ) ) . '/';
              $pattern = '/^' . str_replace( '/', '\/', $redirect_from ) . '$/';
              $output = preg_replace( $pattern, $redirect_to, $user_request );
              if ( $output !== $user_request ) {
                // pattern matched, perform redirect
                $do_redirect_to = $output;
              }
            } elseif( trim( urldecode( $user_request ), '/' ) == trim( $redirect_from, '/' ) ) {
              // SIMPLE redirection.
              $do_redirect_to = $redirect_to;
            }
*/
          
            // Do the redirection.
            if ( $redirect_found ) { //&& substr( $user_request, - strlen( $do_redirect_to ) ) !== $do_redirect_to ) {
              // Check if destination needs the home url prepended.
              if ( 0 === strpos( $redirect_to, '/' ) ){
                $redirect_to = home_url() . $redirect_to;
              }
              wp_redirect( $redirect_to, $status_code );
              exit();
            }
          }
        }
      }
    } // end funcion redirect


  /**
  * create_menu function
  * generate the link to the options page under settings
  *
  * @access public
  * @return void
  */
  public function create_menu() {

//    $this->redirectman_table = new RedirectMan_Table();
//    $this->redirectman_table->prepare_items();

    
    $hook = add_options_page( 'RedirectMan', 'RedirectMan', 'manage_options', 'redirectman', array( $this, 'options_page' ) );

    // Setup the contextual help menu when loaded.
    $redirectman_help = new RedirectMan_Help();
    add_action('load-' . $hook, array( $redirectman_help, 'init' ) );

//    $this->redirectman_table = new RedirectMan_Table();



//    add_action( "load-$hook", array(&$this, 'add_options') );

  }


  public function initialize() {
    $this->redirectman_table = new RedirectMan_Table();
    $this->redirectman_table->set_duplicates( $this->get_duplicate_redirections() );
    $this->redirectman_table->set_circulars( $this->get_circular_redirections() );
    $this->redirectman_table->prepare_items();

    wp_register_script( 'redirectman-js', WP_PLUGIN_URL . '/redirectman/script.js', array('jquery') );
    wp_localize_script( 'redirectman-js', 'redirection_str', array(
      'missing_input'    => __( 'Some information is missing.', 'redirectman' ),
      'same_source_dest' => __( 'Redirect From / To must be different.', 'redirectman' ),
      'are_you_sure'     => __( 'Are you sure?', 'redirectman' )
    ) );

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'redirectman-js' );
    
    wp_enqueue_style( 'redirectman-css', WP_PLUGIN_URL . '/redirectman/style.css' );
  }


 
  } // end class Simple301Redirects
  
} // end check for existance of class

// instantiate
$redirectman = new RedirectMan();

if ( isset( $redirectman ) ) {

  RedirectMan_Redirection::load_all_redirections();


  $duplicate_ids = $redirectman->get_duplicate_redirections();
  $circular_ids  = $redirectman->get_circular_redirections();

  // add the redirect action, high priority
  add_action( 'init', array( $redirectman, 'redirect' ), 1 );



  // Make sure the CSS and JS is ONLY loaded on the plugin page, not for all admin pages.
  add_action( 'admin_print_scripts-settings_page_redirectman', array( $redirectman, 'initialize' ) );
//  add_action( 'admin_init', array( $redirectman, 'initialize' ) );

  // create the menu
  add_action( 'admin_menu', array( $redirectman, 'create_menu' ) );

//  add_action( 'admin_init', array( $redirectman, 'initialize2' ) );


  // if submitted, process the data
//fu($_REQUEST);
  if ( isset( $_REQUEST['redirectman']) ) {
//    add_action( 'admin_init', array( $redirectman, 'save_redirects' ) );
    //add_action( 'admin_init', array( $redirectman, 'init_process' ) );
  }
}

// Helper Functions.
function am_strornull( $str, $trim = true, $default = null ) {
  if ( isset( $str ) ) {
    $str = ( $trim ) ? trim( $str ) : $str;
    if ( '' !== $str ) {
      return $str;
    } else {
      return $default;
    }
  }
  return null;
}

// Recursive str_replace.
function am_str_replace_recursive( $search, $replace, $subject ) {
  $subject = str_replace( $search, $replace, $subject );
  if ( false !== stristr ( $subject, $search ) ) {
    return am_str_replace_recursive( $search, $replace, $subject );
  }
  return $subject;
}

