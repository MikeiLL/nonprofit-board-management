<?php
/*
Plugin Name: Nonprofit Board Management
Plugin URI: WIRED IMPACT URL GOES HERE
Description: Manage your board of directors or young friends board directly from WordPress.
Version: 0.1
Author: Wired Impact
Author URI: http://wiredimpact.com/
License: GPLv2
*/

/*
GPLv2 - read it - http://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses
*/


/**
 * WI_Board_Management is used to set up the board management plugin by adding the needed roles
 * and caps, along with providing most of the necessary css and js.
 *
 * @package Nonprofit Board Management
 *
 * @version 0.1
 * @author Wired Impact
 */
class WI_Board_Management {
  
   /*
    * All of the board members' user objects.
    * 
    * @var array
    */
    public $board_members;
    
  
    public function __construct(){
        //Load translations
        load_plugin_textdomain( 'nonprofit-board-management', false, basename( dirname( __FILE__ ) ) . '/languages' );
      
        //Put all the user objects for every board member in a variable.
        $this->board_members = $this->get_users_who_serve();
      
        register_activation_hook( __FILE__, array( $this, 'add_board_roles' ) );
        register_deactivation_hook( __FILE__, array( $this, 'remove_board_roles' ) );
        
        //Setup top level menu
        add_action( 'admin_menu', array( $this, 'create_menu' ), 10 ); 
        
        //Load CSS and JS
        add_action( 'admin_enqueue_scripts', array( $this, 'insert_css') );
        add_action( 'admin_enqueue_scripts', array( $this, 'insert_js') );
        
        //Add our board members dashboard widget
        add_action('wp_dashboard_setup', array( $this, 'add_board_members_dashboard_widget' ) );
        
        //Add notice to admin who can't serve on board in case they want to.
        add_action( 'admin_notices', array( $this, 'show_admins_notices' ) );

        //Allow admin to click a button and start serving on the board.
        add_action( 'wp_ajax_allow_user_to_serve', array( $this, 'allow_user_to_serve' ) );
    }
    
    
    /*
     * Add the board roles when the plugin is first activated.
     * 
     * The only capability that can be added later is the ability to track
     * event attendance.  This capability can only be given by an admin. The
     * checkbox to allow this capability shows on the bottom of the profile page.
     */
    public function add_board_roles(){   
      add_role( 
              'board_member',
              'Board Member', 
              array( 
                  'read' => true,
                  'view_board_content' => true,
                  'edit_board_content' => true,
                  'serve_on_board' => true,
                  
                  //Board event caps
                  'rsvp_board_events' => true,
                  'edit_board_events' => true,
                  'edit_others_board_events' => true,
                  'publish_board_events' => true,
                  'read_private_board_events' => true,
                  'delete_board_events' => true,
                  'delete_private_board_events' => true,
                  'delete_published_board_events' => true,
                  'delete_others_board_events' => true,
                  'edit_private_board_events' => true,
                  'edit_published_board_events' => true,
                  
                  //Board committee caps
                  'join_board_committee' => true,
                  'edit_board_committees' => true,
                  'edit_others_board_committees' => true,
                  'publish_board_committees' => true,
                  'read_private_board_committees' => true,
                  'delete_board_committees' => true,
                  'delete_private_board_committees' => true,
                  'delete_published_board_committees' => true,
                  'delete_others_board_committees' => true,
                  'edit_private_board_committees' => true,
                  'edit_published_board_committees' => true
                  )
              );
      
      //Give admin access to view and edit all board content.
      //Initially they can't serve on the board, but can add that cap
      //through the UI.
      $role =& get_role( 'administrator' );
      if ( !empty( $role ) ){
        $role->add_cap( 'view_board_content' );
        $role->add_cap( 'edit_board_content' );
        
        //Board event caps
        $role->add_cap( 'edit_board_events' );
        $role->add_cap( 'edit_others_board_events' );
        $role->add_cap( 'publish_board_events' );
        $role->add_cap( 'read_private_board_events' );
        $role->add_cap( 'delete_board_events' );
        $role->add_cap( 'delete_private_board_events' );
        $role->add_cap( 'delete_published_board_events' );
        $role->add_cap( 'delete_others_board_events' );
        $role->add_cap( 'edit_private_board_events' );
        $role->add_cap( 'edit_published_board_events' ); 
        
        //Board committee caps
        $role->add_cap( 'edit_board_committees' );
        $role->add_cap( 'edit_others_board_committees' );
        $role->add_cap( 'publish_board_committees' );
        $role->add_cap( 'read_private_board_committees' );
        $role->add_cap( 'delete_board_committees' );
        $role->add_cap( 'delete_private_board_committees' );
        $role->add_cap( 'delete_published_board_committees' );
        $role->add_cap( 'delete_others_board_committees' );
        $role->add_cap( 'edit_private_board_committees' );
        $role->add_cap( 'edit_published_board_committees' );
      }
    }

    
    /*
     * Remove the board member role when the plugin is deactivated.
     * 
     * We remove the board member role when the plugin is deactivated along with
     * the caps we gave to admins except for serve_on_board since that is an
     * opt-in capability that will be needed if the plugin is added again.
     */
    public function remove_board_roles(){
      $member_users = get_users( array( 'role' => 'board_member', 'number' => 1 ) );
      if( empty( $member_users ) ){
        remove_role( 'board_member' );
      }
           
      //Remove all the admin caps aside from serve_on_board.
      $role =& get_role( 'administrator' );
      if ( !empty( $role ) ){
        $role->remove_cap( 'view_board_content' );
        $role->remove_cap( 'edit_board_content' );
        
        //Board event caps
        $role->remove_cap( 'edit_board_events' );
        $role->remove_cap( 'edit_others_board_events' );
        $role->remove_cap( 'publish_board_events' );
        $role->remove_cap( 'read_private_board_events' );
        $role->remove_cap( 'delete_board_events' );
        $role->remove_cap( 'delete_private_board_events' );
        $role->remove_cap( 'delete_published_board_events' );
        $role->remove_cap( 'delete_others_board_events' );
        $role->remove_cap( 'edit_private_board_events' );
        $role->remove_cap( 'edit_published_board_events' ); 
        
        //Board committee caps
        $role->remove_cap( 'edit_board_committees' );
        $role->remove_cap( 'edit_others_board_committees' );
        $role->remove_cap( 'publish_board_committees' );
        $role->remove_cap( 'read_private_board_committees' );
        $role->remove_cap( 'delete_board_committees' );
        $role->remove_cap( 'delete_private_board_committees' );
        $role->remove_cap( 'delete_published_board_committees' );
        $role->remove_cap( 'delete_others_board_committees' );
        $role->remove_cap( 'edit_private_board_committees' );
        $role->remove_cap( 'edit_published_board_committees' );
      }
    }
    
    
    /*
     * Enqueue the necessary CSS.
     */
    public function insert_css(){ 
      wp_enqueue_style( 'board-mgmt', BOARD_MANAGEMENT_PLUGINFULLURL . 'css/custom.css' );
    }

    
    /*
     * Enqueue the necessary JS.
     */
    public function insert_js(){      
      wp_enqueue_script( 'board-mgmt', BOARD_MANAGEMENT_PLUGINFULLURL . 'js/custom.js', 'jquery' );
      
      //Send whether the current screen should expand the board mgmt menu.
      $screen = get_current_screen();
      $screen->expand_board_menu = false;
      if( $screen->id == 'board_events' ||
          $screen->id == 'edit-board_events' ||
          $screen->id == 'board_committees' ||
          $screen->id == 'admin_page_nonprofit-board/attendance/member' ||
          $screen->id == 'admin_page_nonprofit-board/resources/edit' ){
        
        $screen->expand_board_menu = true;
      }
      
      //wp_localize_script allows us to send PHP info to JS
      wp_localize_script( 'board-mgmt', 'wi_board_mgmt', array(
        'allow_serve_nonce' => wp_create_nonce( 'allow_serve_nonce' ),
        'error_allow_serve' => __( 'Woops. We weren\'t able to allow you to RSVP.  Please try again.', 'nonprofit-board-management' ),
        'get_description_nonce' => wp_create_nonce( 'get_description_nonce' ),
        'error_get_description' => __( 'Woops. We weren\'t able to show you the description.  Please contact support.', 'nonprofit-board-management' ),
        'see_attendees_nonce' => wp_create_nonce( 'see_attendees_nonce' ),
        'load_spinner_html' => '<span class="waiting spinner" style="display: none;"></span>',
        'error_see_attendees' => __( 'Woops. We weren\'t able to show you the attendees.  Please contact support.', 'nonprofit-board-management' ),
        'expand_board_menu' => $screen->expand_board_menu //Send whether we should expand the board mgmt menu
        )
       );
    }
    
    
    /*
     * Create each of the menu items we need for board management.
     */
    public function create_menu(){
      //Create top level menu item
      add_menu_page( __( 'Nonprofit Board Management', 'nonprofit-board-management' ), __( 'Board Mgmt', 'nonprofit-board-management' ), 'view_board_content', 'nonprofit-board', '', BOARD_MANAGEMENT_PLUGINFULLURL . 'css/images/nonprofit-board-gavel-menu.png'  );
      
      //Create Board Members page
      add_submenu_page( 'nonprofit-board', __( 'Board Members', 'nonprofit-board-management' ), __( 'Board Members', 'nonprofit-board-management' ), 'view_board_content', 'nonprofit-board', array( $this, 'display_members_page' ) );
      
      //Add Board Events page
      add_submenu_page( 'nonprofit-board', __( 'Board Events', 'nonprofit-board-management' ), __( 'Board Events', 'nonprofit-board-management' ), 'edit_board_events' , 'edit.php?post_type=board_events' );
      
      //Add Event Attendance pages
      global $wi_board_attendance;
      add_submenu_page( 'nonprofit-board', __( 'Board Event Attendance', 'nonprofit-board-management' ), __( 'Event Attendance', 'nonprofit-board-management' ), 'view_board_content', 'nonprofit-board/attendance', array( $wi_board_attendance, 'display_board_attendance_page' ) );
      add_submenu_page( 'options.php', 'Board Member Attendance', 'Board Member Attendance', 'view_board_content', 'nonprofit-board/attendance/member', array( $wi_board_attendance, 'display_member_attendance_page' ) );
      
      //Add Board Committees page
      add_submenu_page( 'nonprofit-board', __( 'Board Committees', 'nonprofit-board-management' ), __( 'Board Committees', 'nonprofit-board-management' ), 'edit_board_committees' , 'edit.php?post_type=board_committees' ); 
      
      //Add new board event and board committee pages
      add_submenu_page( 'nonprofit-board', __( 'Add Board Event', 'nonprofit-board-management' ), __( 'Add Board Event', 'nonprofit-board-management' ), 'edit_board_events' , 'post-new.php?post_type=board_events' ); 
      add_submenu_page( 'nonprofit-board', __( 'Add Board Committee', 'nonprofit-board-management' ), __( 'Add Board Committee', 'nonprofit-board-management' ), 'edit_board_committees' , 'post-new.php?post_type=board_committees' ); 
      
      //Add Resources and Support pages
      add_submenu_page( 'nonprofit-board', __( 'Board Resources', 'nonprofit-board-management' ), __( 'Board Resources', 'nonprofit-board-management' ), 'view_board_content', 'nonprofit-board/resources', array( $this, 'display_resources_page' ) );
      //Use options.php as the parent page so it doesn't show in any menu.
      add_submenu_page( 'options.php', __( 'Edit Your Board Resources', 'nonprofit-board-management' ), __( 'Edit Your Board Resources', 'nonprofit-board-management' ), 'edit_board_content', 'nonprofit-board/resources/edit', array( $this, 'edit_resources_page' ) );
      add_submenu_page( 'nonprofit-board', __( 'Support', 'nonprofit-board-management' ), __( 'Support', 'nonprofit-board-management' ), 'view_board_content', 'nonprofit-board/support', array( $this, 'display_support_page' ) );
    }
    
    
    /*
     * Display the list of board members with their contact info and current committees.
     */
    public function display_members_page(){ ?>
      <div class="wrap">
        <?php screen_icon( 'board-mgmt' ); ?>
        <h2>
          <?php _e( 'Board Members ', 'nonprofit-board-management' ); ?>
          <?php if( current_user_can( 'create_users' ) ){ ?>
            <a href="user-new.php" class="add-new-h2"><?php _e( '&#43; Add New User', 'nonprofit-board-management' ); ?></a>
          <?php } ?>
        </h2>
        <table class="wp-list-table widefat fixed posts" id="board-members-table" cellspacing="0">
          <thead>
            <tr>
              <th scope="col" id="name" class="manage-column column-name"><?php _e( 'Name', 'nonprofit-board-management' ); ?></th>
              <th scope="col" id="phone" class="manage-column column-phone"><?php _e( 'Phone', 'nonprofit-board-management' ); ?></th>
              <th scope="col" id="email" class="manage-column column-email"><?php _e( 'Email', 'nonprofit-board-management' ); ?></th>
              <th scope="col" id="job" class="manage-column column-job"><?php _e( 'Job', 'nonprofit-board-management' ); ?></th>
              <th scope="col" id="committees" class="manage-column column-committees"><?php _e( 'Committees', 'nonprofit-board-management' ); ?></th>
            </tr>
          </thead>
          <tfoot>
            <tr>
              <th scope="col" class="manage-column column-name"><?php _e( 'Name' ); ?></th>
              <th scope="col" class="manage-column column-phone"><?php _e( 'Phone' ); ?></th>
              <th scope="col" class="manage-column column-email"><?php _e( 'Email' ); ?></th>
              <th scope="col" class="manage-column column-job"><?php _e( 'Job' ); ?></th>
              <th scope="col" class="manage-column column-committees"><?php _e( 'Committees' ); ?></th>
            </tr>
          </tfoot>
          <tbody>
        
        <?php
        $board_members = $this->board_members;
        
        //If no board members were found then give them a message.
        if( empty( $board_members ) ){ ?>
            <tr class="no-items">
              <td class="colspanchange" colspan="5">
                <?php _e( 'There aren\'t currently any members on your board (which could definitely limit its effectiveness).  
                  Why don\'t you <a href="user-new.php">add one now</a>? <br />Oh, and make sure to set the new user\'s role to "Board Member".', 'nonprofit-board-management' ); ?>
              </td>
            </tr>
        <?php
        }
        
        $alternate = 'alternate';
        foreach( $board_members as $board_member ){
         $board_member_meta = $this->get_board_member_meta( $board_member->ID );
         $job = $board_member_meta->job_title;
         if( $board_member_meta->current_employer != '' and $board_member_meta->job_title != ''){
           $job .= __( ' at ', 'nonprofit-board-management' );
         }
         $job .= $board_member_meta->current_employer;
         ?>
          <tr class="<?php echo $alternate; ?>">
            <td class="name column-username">
              <?php echo get_avatar( $board_member->ID, '44' ); echo '<strong>' . $board_member->display_name . '</strong>'; ?>
              <?php if( current_user_can( 'edit_user', $board_member->ID ) ){ ?>
              <div class="row-actions">
                <span class="edit">
                  <a href="<?php echo admin_url( 'user-edit.php?user_id=' . $board_member->ID ); ?>"><?php _e( 'Edit', 'nonprofit-board-management' ); ?></a>
                </span>
              </div>
              <?php }//if edit user ?>
            </td>
            <td class="phone column-phone"><?php echo esc_html( $board_member_meta->phone ); ?></td>
            <td class="email column-email"><?php echo esc_html( $board_member->user_email ); ?></td>
            <td class="job column-job"><?php echo esc_html( $job ); ?></td>
            <td class="committees column-committees"><?php echo WI_Board_Committees::get_user_committees( $board_member->ID ); ?></td>
          </tr>
        <?php
        $alternate = ( $alternate == 'alternate' ) ? '' : 'alternate';
        }        
        ?>
        
          </tbody>
        </table>
        <p><?php 
          _e( '<strong>Your Photo:</strong> You can set your photo by creating a <a href="http://en.gravatar.com/" target="_blank">Gravatar account</a>
            using the same email address you used here.<br />', 'nonprofit-board-management' );
          _e( '<strong>Your Name:</strong> You can adjust your name by changing the "Display name publicly as" dropdown in <a href="profile.php">your profile</a>.', 'nonprofit-board-management' );
          ?>
        </p>
      </div>
    <?php }//display_members_page()

    
    /*
     * Get all the meta data for a board member.
     * 
     * @param int $board_member_id User ID of the board member.
     * @return object Meta data for the provided board member.
     */
    private function get_board_member_meta( $board_member_id ){
      $board_member_meta = new stdClass();
      $board_member_meta->phone = get_user_meta( $board_member_id, 'phone', true );
      $board_member_meta->current_employer = get_user_meta( $board_member_id, 'current_employer', true );
      $board_member_meta->job_title = get_user_meta( $board_member_id, 'job_title', true );
      
      return $board_member_meta;
    }
   
    
    /*
     * Display the content for the board resources page.
     */
    public function display_resources_page(){
      ?>
      <div class="wrap board-resources">
        <?php screen_icon( 'board-mgmt' ); ?>
        <h2><?php _e( 'Board Resources', 'nonprofit-board-management' ); ?></h2>
        <p><?php _e( 'We\'ve provided two resource sections.  
          One for you to include your own resources and one for some resources we think are helpful.', 'nonprofit-board-management' ); ?></p>
        <h3>
          <?php _e( 'Your Board Resources', 'nonprofit-board-management' ); ?>
          <a class="button secondary-button" href="<?php echo admin_url( 'admin.php?page=nonprofit-board/resources/edit' ); ?>">
            <?php _e( 'Edit your board resources', 'nonprofit-board-management' ); ?>
          </a>
        </h3>
        <div class="custom-board-resources">
          <?php echo apply_filters( 'the_content', stripslashes( get_option( 'board_resources_content', 'You haven\'t added any resources yet.  Use the edit button above to add some.' ) ) ); ?>
        </div>
        
        <h3><?php _e( 'Some Other Helpful Resources', 'nonprofit-board-management' ); ?></h3>
        <div class="fixed-board-resources">
          <p><a href="http://asana.com/" target="_blank">Asana</a> – <?php _e( 'A shared task list for your board.', 'nonprofit-board-management' ); ?></p>
          <p><a href="http://www.boardsource.org/" target="_blank">BoardSource</a> – <?php _e( 'A collection of articles and tools on running a board.', 'nonprofit-board-management' ); ?></p>
          <p><a href="http://www.bridgespan.org/Publications-and-Tools/Nonprofit-Boards.aspx" target="_blank">The Bridgespan Group: Nonprofit Boards</a> – <?php _e( 'Featured content dedicated to nonprofit boards.', 'nonprofit-board-management' ); ?></p>
          <p><a href="http://doodle.com/" target="_blank">Doodle</a> – <?php _e( 'An easy way to find a good time to meet.', 'nonprofit-board-management' ); ?></p>
          <p><a href="https://www.dropbox.com/" target="_blank">Dropbox</a> – <?php _e( 'A great way to share files.', 'nonprofit-board-management' ); ?></p>
          <p><a href="https://drive.google.com/" target="_blank">Google Drive</a> – <?php _e( 'A good way to share and collaborate on documents.', 'nonprofit-board-management' ); ?></p>
          <p><a href="http://nonprofits.linkedin.com/" target="_blank">LinkedIn Board Member Connect</a> – <?php _e( 'A tool to find great talent to join your board.', 'nonprofit-board-management' ); ?></p>
          <p><a href="http://wiredimpact.com/" target="_blank">Wired Impact</a> – <?php _e( 'Library articles and blog posts on how nonprofits can use the web to do more good.', 'nonprofit-board-management' ); ?></p>
        </div>
      </div><!-- /wrap -->
      <?php
    }
    
    
    /*
     * Screen for editing the organization's board resources content.
     */
    public function edit_resources_page(){
      if( isset( $_POST['board_resources'] ) ){
        //Save our content if the user clicked update.
        $this->save_board_resources();
        
        //We show a message to the user if we updated our content.
        ?>
        <div class="updated">
          <p>
            <?php _e( 'Your board resources have been updated.', 'nonprofit-board-management' ); ?>
            <a href="<?php echo admin_url( 'admin.php?page=nonprofit-board/resources' ); ?>">
              <?php _e( 'View your board resources.', 'nonprofit-board-management' ); ?>
            </a>
          </p>
        </div>
        <?php
      }
      
      ?>
      <div class="wrap edit-board-resources">
        <?php screen_icon( 'board-mgmt' ); ?>
        <h2><?php _e( 'Edit Your Board Resources', 'nonprofit-board-management' ); ?></h2>
        <p><?php _e( 'Edit the content in your board resources section.', 'nonprofit-board-management' ); ?></p>
        <form method="post" action="">
        <div id="poststuff">
          <div class="postbox">
            <h3 class="hndle">
              <span><?php _e( 'Save Your Resources' ); ?></span>
            </h3>
            <div class="inside">
              <input type="submit" class="button button-primary button-large" value="Update" />
              <a class="button secondary-button button-large" href="<?php echo admin_url( 'admin.php?page=nonprofit-board/resources' ); ?>">
                <?php _e( 'Back to Resources', 'nonprofit-board-management' ); ?>
              </a>
            </div>
          </div><!-- /postbox -->
        </div><!-- /poststuff -->
        <div id="edit-resources-editor">
          <?php wp_editor( stripslashes( get_option( 'board_resources_content' ) ), 'board_resources', array( 'teeny' => true ) ); ?>
        </div><!-- /edit-resources-editor -->
        <?php $board_resources_nonce = wp_create_nonce( 'board_resources_nonce' ); ?>
        <input type="hidden" id="board_resources_nonce" name="board_resources_nonce" value="<?php echo $board_resources_nonce; ?>" />
        </form>
      </div><!-- /wrap -->
      <?php
    }
    
    
    /*
     * Save the organization's board resources.
     * 
     * @return bool True if the resources were updated, false otherwise.
     */
    private function save_board_resources(){
      if( !current_user_can( 'edit_board_content' ) ){
        return false;
      }
      if ( !isset( $_POST['board_resources_nonce'] ) || !wp_verify_nonce( $_POST['board_resources_nonce'], 'board_resources_nonce' ) ){
        return false;
      }
      
      //Sanitize the board resources content, then save or delete it.
      $clean_content = wp_kses_post( $_POST['board_resources'] );
      if( $clean_content != '' ){
        $result = update_option( 'board_resources_content', $clean_content );
      }
      else{
        $result = delete_option( 'board_resources_content' );
      }
      
      return $result;
    }
    
    
    /*
     * Display the content for our support page.
     * TODO Replace these videos with our support videos.
     */
    public function display_support_page(){
      ?>
      <div class="wrap">
        <?php screen_icon( 'board-mgmt' ); ?>
        <h2><?php _e( 'Support', 'nonprofit-board-management' ); ?></h2>
        <p><?php _e( 'In case you need help here are some videos to help you navigate the board management plugin.', 'nonprofit-board-management' ); ?></p>
        
        <h3><a class="support-heading" href="#"><span>+ </span><?php _e( 'Getting Started with Nonprofit Board Management', 'nonprofit-board-management' ); ?></a></h3>
        <div class="support-content hide">
          <iframe width="640" height="360" src="https://www.youtube.com/embed/j1EHA5T4rQA" frameborder="0" allowfullscreen></iframe>
        </div>
        
        <h3><a class="support-heading" href="#"><span>+ </span><?php _e( 'How to Add a Board Member', 'nonprofit-board-management' ); ?></a></h3>
        <div class="support-content hide">
          <iframe width="640" height="360" src="https://www.youtube.com/embed/kCwsqWrwkaA" frameborder="0" allowfullscreen></iframe>
        </div>
        
        <h3><a class="support-heading" href="#"><span>+ </span><?php _e( 'How to Change Your Personal Information', 'nonprofit-board-management' ); ?></a></h3>
        <div class="support-content hide">
          <iframe width="640" height="360" src="https://www.youtube.com/embed/GPwL7A-3d-M" frameborder="0" allowfullscreen></iframe>
        </div>
        
        <h3><a class="support-heading" href="#"><span>+ </span><?php _e( 'How to Serve on the Board as a WordPress Admin', 'nonprofit-board-management' ); ?></a></h3>
        <div class="support-content hide">
          <iframe width="640" height="360" src="https://www.youtube.com/embed/ZYYaIFYtG88" frameborder="0" allowfullscreen></iframe>
        </div>
        
        <h3><a class="support-heading" href="#"><span>+ </span><?php _e( 'How to Add a Board Event', 'nonprofit-board-management' ); ?></a></h3>
        <div class="support-content hide">
          <iframe width="640" height="360" src="https://www.youtube.com/embed/TfQIeeIVyt8" frameborder="0" allowfullscreen></iframe>
        </div>
        
        <h3><a class="support-heading" href="#"><span>+ </span><?php _e( 'How to RSVP to an Upcoming Event', 'nonprofit-board-management' ); ?></a></h3>
        <div class="support-content hide">
          <iframe width="640" height="360" src="https://www.youtube.com/embed/Nk6blZ3Zopc" frameborder="0" allowfullscreen></iframe>
        </div>
        
        <h3><a class="support-heading" href="#"><span>+ </span><?php _e( 'How to Track Attendance for an Event', 'nonprofit-board-management' ); ?></a></h3>
        <div class="support-content hide">
          <iframe width="640" height="360" src="https://www.youtube.com/embed/WLz6axkCW1Y" frameborder="0" allowfullscreen></iframe>
        </div>
        
        <h3><a class="support-heading" href="#"><span>+ </span><?php _e( 'How to Create a Committee and Add Committee Members', 'nonprofit-board-management' ); ?></a></h3>
        <div class="support-content hide">
          <iframe width="640" height="360" src="https://www.youtube.com/embed/yInKtr36Y5s" frameborder="0" allowfullscreen></iframe>
        </div>
        
        <h3><a class="support-heading" href="#"><span>+ </span><?php _e( 'How to Edit Your Board Resources', 'nonprofit-board-management' ); ?></a></h3>
        <div class="support-content hide">
          <iframe width="640" height="360" src="https://www.youtube.com/embed/XsXXEHAs9TU" frameborder="0" allowfullscreen></iframe>
        </div>
      <?php     
    }
    
    
    /*
     * Add our members dashboard widget to the list of widgets.
     */
    public function add_board_members_dashboard_widget(){
      if( current_user_can( 'view_board_content' ) ){
        wp_add_dashboard_widget('board_members_db_widget', __( 'Board Members', 'nonprofit-board-management' ), array( $this, 'display_board_members_dashboard_widget' ) );
      }
    }
    
    
    /*
     * Display a dashboard widget for all of the board members.
     * 
     * @see add_board_members_dashboard_widget()
     */
    public function display_board_members_dashboard_widget(){
      $board_members = $this->board_members;
      
      //If we don't have any board members then the user needs a message.
      if( empty( $board_members ) ){
        _e( 'You don\'t have any board members.  You should create some users and set their role to "Board Member".', 'nonprofit-board-management' );
        
        return;
      }
      
      ?>
        <table class="widefat">
          <thead>
            <th scope="col" class="column-name"><?php _e( 'Name', 'nonprofit-board-management' ); ?></th>
            <th scope="col" class="column-phone"><?php _e( 'Phone', 'nonprofit-board-management' ); ?></th>
            <th scope="col" class="column-email"><?php _e( 'Email', 'nonprofit-board-management' ); ?></th>
          </thead>
          <tbody>
      <?php
      $alternate = 'alternate';
      
      foreach( $board_members as $board_member ){
        $board_member_meta = $this->get_board_member_meta( $board_member->ID );
        ?>
        <tr class="<?php echo $alternate; ?>">
          <td><?php echo esc_html( $board_member->display_name ); ?></td>
          <td><?php echo esc_html( $board_member_meta->phone ); ?></td>
          <td><?php echo esc_html( $board_member->user_email ); ?></td>
        </tr>
        <?php
      $alternate = ( $alternate == 'alternate' ) ? '' : 'alternate';  
      }
      
      ?>
      </tbody></table>
        <p class="note"><a href="<?php echo admin_url( 'admin.php?page=nonprofit-board' ); ?>"><?php _e( 'View more board member details', 'nonprofit-board-management' ); ?></a></p>
      <?php
    }

    
    /*
     * Get the users who serve on the board.
     * 
     * The users who serve on the board includes all users
     * with the board member role and any admins who added the
     * serve_on_board capability.
     * 
     * @return array User objects for users who can serve on the board.
     */
    private function get_users_who_serve(){
      $board_members = get_users( array( 'role' => 'board_member' ) );
      $admins = get_users( array( 'role' => 'administrator' ) );

      //Check if admins can rsvp and if not, remove them from the array.
      $admins_count = count( $admins );
      for( $i = 0; $i < $admins_count; $i++ ){
        if( !isset( $admins[$i]->allcaps['serve_on_board'] ) || $admins[$i]->allcaps['serve_on_board'] != true ) {
          unset( $admins[$i] );
        }
      }

      //Combine board members with admins opted to rsvp
      $users_serving = array_merge( $board_members, $admins );
      //Since we added the admins at the end, we need to sort again by display_name
      usort( $users_serving, array( $this, "sort_users" ) );

      return $users_serving;
    }
    
    
    /*
     * Sort users based on display name.
     * 
     * @return int Info on which name should be ordered first.
     */
    private function sort_users( $user_one, $user_two ){
      
      return strcmp( $user_one->display_name, $user_two->display_name );
    }
    
    
    /*
    * Show notice to admins allowing them to start serving on the board if they'd like.
    * 
    * Show notice to admins that allows them to start serving on the board.  Handling
    * of the button click is done through ajax.  With this cap they're able to
    * RSVP to events, join committees and show in the board members list.
    * 
    * @see allow_user_to_serve()
    */
   public function show_admins_notices(){
     $screen = get_current_screen();
     
     //If the admin already has the serve capability then don't show the message.
     if( current_user_can( 'serve_on_board' ) ) return;
     
     //If the admin is on the members, events, or committees list then show them message.
     if( $screen->id == 'edit-board_events' || $screen->id == 'toplevel_page_nonprofit-board' || $screen->id == 'edit-board_committees' ){
     ?>
     <div class="updated">
       <p><?php _e( 'You don\'t currently have the board member role, so you can\'t RSVP to board events,
         join committees, or show up in the board member list.', 'nonprofit-board-management' ); ?>
         <input id="allow-board-serve" type="submit" class="button secondary-button" value="Add Me to the Board" />
       </p>
     </div>
     <?php
     }//End if
   }


   /*
    * Allow the current user to serve on the board.
    * 
    * Via ajax allow the current user to serve on the board 
    * by giving them the capability.  Only admins have
    * the ability to use this method since the button used to activate
    * this method is only shown to that role.
    * 
    * @see show_admin_notices()
    * @return string Echos '1' to show that the method has run.
    */
   public function allow_user_to_serve(){
     check_ajax_referer( 'allow_serve_nonce', 'security' );

     $current_user = wp_get_current_user();
     $current_user->add_cap( 'serve_on_board' );

     echo '1';

     die();
   }  
} //WI_Board_Management

if( is_admin() ){
  //Setup some constants for us to more easily work with files
  define( "BOARD_MANAGEMENT_BASENAME", plugin_basename(__FILE__) );
  define( "BOARD_MANAGEMENT_PLUGINPATH", "/" . plugin_basename(dirname(__FILE__)) . "/" );
  define( "BOARD_MANAGEMENT_PLUGINFULLPATH", WP_PLUGIN_DIR . BOARD_MANAGEMENT_PLUGINPATH );
  define( "BOARD_MANAGEMENT_PLUGINFULLURL", WP_PLUGIN_URL . BOARD_MANAGEMENT_PLUGINPATH );
  define( "BOARD_MANAGEMENT_FILEFULLPATH", BOARD_MANAGEMENT_PLUGINFULLPATH . 'nonprofit-board-management.php' );

  //Add board events and committees classes
  require_once BOARD_MANAGEMENT_PLUGINFULLPATH . 'includes/class-board-events.php';
  require_once BOARD_MANAGEMENT_PLUGINFULLPATH . 'includes/class-board-attendance.php';
  require_once BOARD_MANAGEMENT_PLUGINFULLPATH . 'includes/class-board-committees.php';

  //Instantiate each of our classes.
  $wi_board_mgmt = new WI_Board_Management();
  $wi_board_events = new WI_Board_Events();
  $wi_board_attendance = new WI_Board_Attendance();
  $wi_board_committees = new WI_Board_Committees();
}