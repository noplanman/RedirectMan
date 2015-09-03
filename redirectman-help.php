<?php

/**
 * Contextual help class for RedirectMan.
 * 
 * @package RedirectMan
 * @subpackage Help
 * @author Armando Lüscher
 * @since 1.0.0
 */
if ( ! class_exists('RedirectMan_Help' ) ) {
  class RedirectMan_Help {
    private $_tabs = array();

    public function init() {
      $this->_add_tabs();
//      $this->_set_sidebar();
    }

    public function __construct() {
      $this->_tabs = array(
        'overview' => array(
          'title'   => __( 'Overview', 'redirectman' ),
          'content' => __( 
            '<p><strong>Overview</strong></p>
            <p>RedirectMan offers you the ability to redirect URIs to other URIs.</p>
            <ul>
            <li>All defined redirections are listed in the table below, where they can be freely modified.</li>
            <li>Make use of the "Bulk Actions" to modify multiple entries at the same time.</li>
            <li>Adding a new redirection is easy as pie, just click the "Add New Redirection" button and define your new redirection.</li>
            <li>To delete specific redirections, simply check them and choose "Delete" from the "Bulk Actions" menu.</li>
            </ul>',
            'redirectman' )
        ),
        'status_codes' => array(
          'title'   => __( 'Status Codes', 'redirectman' ),
          'content' => __(
            '<p><strong>What are HTTP status codes?</strong><br />
            <a target="_blank" href="http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3">HTTP/1.1: Status Code Definitions</a></p>
            <ul>
            <li><strong>301 Moved Permanently</strong>: The requested resource has been assigned a new permanent URI.</li>
            <li><strong>302 Found</strong>: The requested resource resides temporarily under a different URI.</li>
            <li><strong>303 See Other</strong>: The response to the request can be found under a different URI.</li>
            <li><strong>307 Temporary Redirect</strong>: The requested resource resides temporarily under a different URI.</li>
            </ul>',
            'redirectman'
          )
        ),
        'inconsistencies' => array(
          'title'   => __( 'Inconsistencies', 'redirectman' ),
          'content' => __(
            '<p><strong>What are inconsistencies?</strong></p>
            <p>Inconsistencies are errors within the redirections, meaning they are not functioning as they should be. Clicking the respective button will display all redirections affected.</p>
            <p>There are two types of inconsistencies:</p>
            <ul>
            <li><span class="help-duplicate">Duplicates</span>Multiple Redirections have the same "Redirect From" address. Bearing this in mind, while redirecting, the first occurance will be used to redirect, ignoring all further instances.</li>
            <li><span class="help-circular">Circulars</span>Some Redirections are redirecting infinitely in circles. These redirects are simply ignored, because they would otherwise cause the browser to fail loading and display an error message.</li>
            </ul>',
            'redirectman'
          )
        ),
        'credits' => array(
          'title'   => __( 'Credits', 'redirectman' ),
          'content' => __(
            '<p><strong>Special thanks to:</strong></p>
            <ul>
            <li><strong class="heart">My beautiful lady who supports me in all imaginable ways!</strong></li>
            <li>Inspired by Simple 301 Redirects Plugin by <a href="http://www.scottnelle.com/" target="_blank">Scott Nellé</a></li>
            <li>Silk Icons by <a href="http://www.famfamfam.com/about/" target="_blank">Mark James</a></li>
            <li>WP_List_Table insight thanks to Custom List Table Example Plugin by <a href="http://www.mattvanandel.com/" target="_blank">Matt Van Andel</a></li>
            <li>Tons of other open and helpful people out there, from whom I was able to learn so much! </li>
            </ul>',
            'redirectman'
          )
        )
      );
    }

    /**
     * Add help tabs.
     *
     * @since 1.0.0
     */
    private function _add_tabs() {
      foreach ( $this->_tabs as $id => $data ) {
        get_current_screen()->add_help_tab( array(
          'id'       => $id,
          'title'    => $data['title'],
          // Use the content only if you want to add something
          // static on every help tab. Example: Another title inside the tab
          'content'  => '',
          'callback' => array( $this, 'prepare' )
        ) );
      }
    }

/*    private function _set_sidebar() {
      get_current_screen()->set_help_sidebar( sprintf( '<p>%s</p>', 
        __( 'SIDEBAR' ,'redirectman' )
      ) );
    }*/

    public function prepare( $screen, $tab ) {
      printf( '<p>%s</p>', $tab['callback'][0]->_tabs[ $tab['id'] ]['content'] );
    }
  }
}

?>