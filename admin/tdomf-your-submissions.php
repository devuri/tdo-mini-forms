<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('TDOMF: You are not allowed to call this page directly.'); }

/////////////////////////////////
// User "Your Submission" page //
/////////////////////////////////

// Grab a list of published submitted posts for user
//
function tdomf_get_user_published_posts($user_id = 0, $offset = 0, $limit = 0) {
  global $wpdb;
	$query = "SELECT ID, post_title, meta_value, post_status, post_modified_gmt, post_modified ";
	$query .= "FROM $wpdb->posts ";
	$query .= "LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";
   $query .= "WHERE meta_key = '".TDOMF_KEY_USER_ID."' ";
   $query .= "AND post_status = 'publish' ";
   $query .= "AND meta_value = '$user_id' ";
   	$query .= "ORDER BY ID DESC ";
   if($limit > 0) {
         $query .= "LIMIT $limit ";
      }
      if($offset > 0) {
         $query .= "OFFSET $offset ";
   }
	return $wpdb->get_results( $query );
}

// Grab a list of unmoderated submitted posts for user
//
function tdomf_get_user_draft_posts($user_id = 0, $offset = 0, $limit = 0) {
  global $wpdb;
	$query = "SELECT ID, post_title, meta_value, post_status  ";
	$query .= "FROM $wpdb->posts ";
	$query .= "LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";
   $query .= "WHERE meta_key = '".TDOMF_KEY_USER_ID."' ";
   $query .= "AND post_status = 'draft' ";
   $query .= "AND meta_value = '$user_id' ";
   	$query .= "ORDER BY ID DESC ";
   if($limit > 0) {
         $query .= "LIMIT $limit ";
      }
      if($offset > 0) {
         $query .= "OFFSET $offset ";
   }
	return $wpdb->get_results( $query );
}

// Show the page!
//
function tdomf_show_your_submissions_menu() {
  global $current_user;

  get_currentuserinfo();
  
  $tdomf_flag = get_usermeta($current_user->ID,TDOMF_KEY_FLAG);
  $sub_total = tdomf_get_users_submitted_posts_count($current_user->ID);
  $app_total = tdomf_get_users_published_posts_count($current_user->ID);
  $user_status = get_usermeta($current_user->ID,TDOMF_KEY_STATUS);
  $app_posts = tdomf_get_user_published_posts($current_user->ID,0,5);
  $mod_posts = tdomf_get_user_draft_posts($current_user->ID);
 
  ?>

  <div class="wrap">
    <h2><?php _e('Your Submissions', 'tdomf') ?></h2>
    
    <?php if(in_array($_REQUEST['REMOTE_ADDR'],tdomf_get_ips_banned())) { ?>
      <?php printf(__("You are logged on from the banned IP %s. If this is in error please contact the <a href='mailto:%s'>admins</a>.","tdomf"),$_REQUEST['REMOTE_ADDR'],get_bloginfo('admin_email')); ?>
    <?php } else if($user_status == TDOMF_USER_STATUS_BANNED) { ?>
      <?php printf(__("You are banned from using this functionality on this site. If this is in error please contact the <a href='mailto:%s'>admins</a>.","tdomf"),get_bloginfo('admin_email')); ?>
    <?php } else { ?>

      <p>
      <?php if($user_status == TDOMF_USER_STATUS_TRUSTED) { ?>
        <?php printf(__("Good to see you again <b>%s</b>! ","tdomf"),$current_user->display_name); ?>
      <?php } else if($tdomf_flag) { ?>
        <?php printf(__("Welcome back <b>%s</b>!","tdomf"),$current_user->display_name); ?>
      <?php } else { ?>
        <?php printf(__("Welcome <b>%s</b>.","tdomf"),$current_user->display_name); ?>
      <?php } ?>
      </p>
      
      <p><?php printf(__("From here you can submit posts to the %s using the form below and check on the status of your submissions.","tdomf"),get_bloginfo()); ?></p>
      
      <?php if(current_user_can('edit_others_posts') || current_user_can('manage_options')) { ?>
      <ul>
      <?php if(current_user_can('manage_options')) { ?>
      <li><a href="admin.php?page=tdomf_show_options_menu"><?php _e("Configure Options","tdomf"); ?></a></li>
      <li><a href="admin.php?page=tdomf_show_form_menu"><?php _e("Modify Form","tdomf"); ?></a></li>
      <?php } ?>
      <li><a href="admin.php?page=tdomf_show_mod_posts_menu"><?php _e("Moderate Submissions","tdomf"); ?></a></li>
      </ul>
      <?php } ?>

    <?php if($tdomf_flag && ($sub_total > 0 || $app_total > 0)) { ?>
       <?php if($app_total > 0) { ?>
         <h3><?php printf(__('Your Last %d Approved Submissions','tdomf'),5); ?></h3>
         <ul>
         <?php foreach($app_posts as $p) { ?>
          <li><a href="<?php echo get_permalink($p->ID); ?>">"<?php echo $p->post_title; ?>"</a> approved on <?php echo mysql2date("jS F, g:iA", $p->post_modified); ?></li>
         <?php } ?>
    	  </ul>
       <?php } ?>
       <?php if(($sub_total - $app_total)> 0) { ?>
         <h3><?php _e('Your Sumissions waiting Moderation','tdomf'); ?></h3>
         <ul>
         <?php foreach($mod_posts as $p) { ?>
          <li>"<?php echo $p->post_title; ?>"</li>
         <?php } ?>
    	  </ul>
       <?php } ?>
    <?php } ?>      
      
     </div>
      
     <!-- Form formatting -->     
     <style>
     .tdomf_form {
     }
     .tdomf_form fieldset legend {
       #border-bottom: 1px dotted black;
       font-weight: bold;
       padding: 0px;
       margin: 0px;
       padding-bottom: 10px;
     }
     .tdomf_form_preview {
       border: 1px dotted black;
       padding: 5px;
       margin: 5px;
       margin-bottom: 20px;
     }
     .tdomf_form_preview p {
       margin-left: 15px;
     }
     .tdomf_form .required {
       color: red;
     }
     .tdomf_form fieldset {
       margin-bottom: 10px;
     }
     </style>
      
         
    <div class="wrap">
    <h2><?php _e("Make a submission!","tdomf"); ?></h2>
    <?php echo tdomf_generate_form(); ?>
    <br/><br/>
    
    <?php } ?>
    
  </div>

  <p><center><?php _e('Powered by the <a href="http://thedeadone.net/software/tdo-mini-forms-wordpress-plugin/">TDO Mini Forms Plugin.','tdomf'); ?></a></center></p>
  
<?php
}
?>
