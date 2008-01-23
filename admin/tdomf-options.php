<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('TDOMF: You are not allowed to call this page directly.'); }

///////////////////////////////
// Code for the options menu //
///////////////////////////////

function tdomf_show_general_options() {
  ?> 
  <div class="wrap">
    
    <h2><?php _e('General Options for TDOMF', 'tdomf') ?></h2>

    <p><?php _e("Global options for this plugin and applies to all forms.","tdomf"); ?></p>
    
    <form method="post" action="admin.php?page=tdomf_show_options_menu">

    <?php if(function_exists('wp_nonce_field')){ wp_nonce_field('tdomf-options-save'); } ?>

  <h3><?php _e("Default Author","tdomf"); ?></h3>

	<p><?php _e("You <b>must</b> pick a default user to be used as the \"author\" of the post. This user cannot be able to publish or edit posts.","tdomf"); ?>
	  <br/><br/>

    <?php // update created users list (in case a user has been deleted)
      $created_users = get_option(TDOMF_OPTION_CREATEDUSERS);
      if($created_users != false) {
        $updated_created_users = array();
        foreach($created_users as $created_user) {
          if(get_userdata($created_user)){
            $updated_created_users[] = $created_user;
          }
        }
        update_option(TDOMF_OPTION_CREATEDUSERS,$updated_created_users);
      } ?>
    
	  <?php $def_aut = get_option(TDOMF_DEFAULT_AUTHOR);
           $def_aut_bad = false; ?>

	 <b><?php _e("Default Author","tdomf"); ?></b>
    <select id="tdomf_def_user" name="tdomf_def_user">
    <?php $users = tdomf_get_all_users();
          $cnt_users = 0;
          foreach($users as $user) {
            $status = get_usermeta($user->ID,TDOMF_KEY_STATUS);
            $user_obj = new WP_User($user->ID);
            if($user->ID == $def_aut || (!$user_obj->has_cap("publish_posts"))) {
               $cnt_users++;
               ?>
              <option value="<?php echo $user->ID; ?>" <?php if($user->ID == $def_aut) { ?> selected <?php } ?> ><?php if($user_obj->has_cap("publish_posts")) {?><font color="red"><?php }?><?php echo $user->user_login; ?><?php if(!empty($status) && $status == TDOMF_USER_STATUS_BANNED) { ?> (Banned User) <?php } ?><?php if($user_obj->has_cap("publish_posts")) { $def_aut_bad = true; ?> (Error) </font><?php }?></option>
          <?php } } ?>
    </select>

    <br/><br/>

    <?php if($def_aut_bad || $cnt_users <= 0) { ?>

    <?php $create_user_link = "admin.php?page=tdomf_show_options_menu&action=create_dummy_user";
	      if(function_exists('wp_nonce_url')){
	          $create_user_link = wp_nonce_url($create_user_link, 'tdomf-create-dummy-user');
          } ?>

    <a href="<?php echo $create_user_link; ?>">Create a dummy user &raquo;</a>
    <?php } ?>

    </p>

    <h3><?php _e("Author and Submitter fix","tdomf"); ?></h3>

	<p>
	<?php _e("If an entry is submitted by a subscriber and is published using the normal wordpress interface, the author can be changed to the person who published it, not submitted. Select this option if you want this to be automatically corrected. This problem only occurs on blogs that have more than one user who can publish.","tdomf"); ?>
	<br/><br/>

	<?php $fix_aut = get_option(TDOMF_AUTO_FIX_AUTHOR); ?>

	<b><?php _e("Auto-correct Author","tdomf"); ?></b>
	<input type="checkbox" name="tdomf_autocorrect_author" id="tdomf_autocorrect_author"  	<?php if($fix_aut) echo "checked"; ?> >
	</p>

	<h3><?php _e('Auto Trust Submitter Count',"tdomf"); ?></h3>

	<p>
	<?php _e('This only counts for submitters who register with your blog and submit using a user account. You can have the user automatically changed to "trusted" after a configurable number of approved submissions. Setting it the value to 0, means that a registered user is automatically trusted. Setting it to -1, disables the feature. A trusted user can still be banned.',"tdomf"); ?> <?php printf(__('You can change a users status (to/from trusted or banned) using the <a href="%s">Manage</a> menu',"tdomf"),"admin.php?page=tdomf_show_manage_menu"); ?>
	</p>

	<p>
	<b><?php _e("Auto Trust Submitter Count","tdomf"); ?></b>
	<input type="text" name="tdomf_trust_count" id="tdomf_trust_count" size="3" value="<?php echo htmlentities(get_option(TDOMF_OPTION_TRUST_COUNT),ENT_QUOTES,get_bloginfo('charset')); ?>" />
	</p>

    <h3><?php _e('Change author to submitter automatically',"tdomf"); ?> </h3>

	<p>
	<?php _e('If your theme displays the author of a post, you can automatically have it display the submitter info instead, if avaliable. It is recommended to use the "Who Am I" widget to get the full benefit of this option. The default and classic themes in Wordpress do not display the author of a post.',"tdomf"); ?>
    </p>

    <?php $on_author_theme_hack = get_option(TDOMF_OPTION_AUTHOR_THEME_HACK); ?>

	</p>
	<b><?php _e("Use submitter info for author in your theme","tdomf"); ?></b>
	<input type="checkbox" name="tdomf_author_theme_hack" id="tdomf_author_theme_hack"  <?php if($on_author_theme_hack) echo "checked"; ?> >
	</p>

    <h3><?php _e('Add submitter link automatically to post',"tdomf"); ?> </h3>

	<p>
	<?php _e('You can automatically add submitter info to the end of a post. This works on all themes.',"tdomf"); ?>
    </p>

    <?php $on_add_submitter = get_option(TDOMF_OPTION_ADD_SUBMITTER); ?>

	</p>
	<b><?php _e("Add submitter to end of post","tdomf"); ?></b>
	<input type="checkbox" name="tdomf_add_submitter" id="tdomf_add_submitter"  <?php if($on_add_submitter) echo "checked"; ?> >
	</p>

  <h3><?php _e('Disable Error Messages','tdomf'); ?></h3>
  
  <p>
  <?php _e('You can disable the display of errors to the user when they use this form. This does not stop errors being reported to the log or enable forms to be submitted with "Bad Data"','tdomf'); ?>
  </p>
  
  <?php $disable_errors = get_option(TDOMF_OPTION_DISABLE_ERROR_MESSAGES); ?>

	</p>
	<b><?php _e("Disable error messages being show to user","tdomf"); ?></b>
	<input type="checkbox" name="tdomf_disable_errors" id="tdomf_disable_errors"  <?php if($disable_errors) echo "checked"; ?> >
	</p>
  
  <h3><?php _e('Extra Log Messages','tdomf'); ?></h3>
  
  <p>
  <?php _e('You can enable extra log messages to aid in debugging problems','tdomf'); ?>
  </p>
  
  <?php $extra_log = get_option(TDOMF_OPTION_EXTRA_LOG_MESSAGES); ?>

	</p>
	<b><?php _e("Enable extra log messages ","tdomf"); ?></b>
	<input type="checkbox" name="tdomf_extra_log" id="tdomf_extra_log"  <?php if($extra_log) echo "checked"; ?> >
	</p>
  
  
  <h3><?php _e('"Your Submissions" Page','tdomf'); ?></h3>
  
  <p>
  <?php _e('When a user logs into Wordpress, they can access a "Your Submissions" page which contains a copy of the form. You can disable this page by disabling this option.','tdomf'); ?>
  </p>
  
  <?php $your_submissions = get_option(TDOMF_OPTION_YOUR_SUBMISSIONS); ?>

	</p>
	<b><?php _e("Enable 'Your Submissions' page ","tdomf"); ?></b>
	<input type="checkbox" name="tdomf_your_submissions" id="tdomf_your_submissions"  <?php if($your_submissions) echo "checked"; ?> >
	</p>
  
    <br/><br/>

    <table border="0"><tr>

    <td>
    <input type="hidden" name="save_settings" value="0" />
    <input type="submit" name="tdomf_save_button" id="tdomf_save_button" value="<?php _e("Save","tdomf"); ?> &raquo;" />
	</form>
    </td>

    <td>
    <form method="post" action="admin.php?page=tdomf_show_options_menu">
    <input type="submit" name="refresh" value="Refresh" />
    </form>
    </td>

    </tr></table>

   </div> 
   <?php
}

function tdomf_show_form_options($form_id) {
  if(!tdomf_form_exists($form_id)) { ?>
    <div class="wrap"><font color="red"><?php printf(__("Form id %d does not exist!","tdomf"),$form_id); ?></font></div>
  <?php } else { ?>
    
    <?php $pages = tdomf_get_option_form(TDOMF_OPTION_CREATEDPAGES,$form_id);
          $updated_pages = false;
          if($pages != false) {
            $updated_pages = array();
            foreach($pages as $page_id) {
              if(get_permalink($page_id) != false) {
                $updated_pages[] = $page_id; 
              }
            }
            if(count($updated_pages) == 0) { $updated_pages = false; }
            tdomf_set_option_form(TDOMF_OPTION_CREATEDPAGES,$updated_pages,$form_id);
          } ?>
    
    <div class="wrap">
    <?php if(function_exists('wp_nonce_url')) { ?>
       <a href="<?php echo wp_nonce_url("admin.php?page=tdomf_show_options_menu&delete=$form_id", 'tdomf-delete-form-'.$form_id); ?>">
          <?php _e("Delete","tdomf"); ?></a> |
       <a href="<?php echo wp_nonce_url("admin.php?page=tdomf_show_options_menu&copy=$form_id&form=$form_id", 'tdomf-copy-form-'.$form_id); ?>">
          <?php _e("Copy","tdomf"); ?></a> | 
    <?php } else { ?>
       <a href="admin.php?page=tdomf_show_options_menu&delete=<?php echo $form_id; ?>"><?php _e("Delete","tdomf"); ?></a> |
       <a href="admin.php?page=tdomf_show_options_menu&copy=<?php echo $form_id; ?>"><?php _e("Copy","tdomf"); ?></a> | 
    <?php } ?>
    <?php if($updated_pages != false) { ?>
      <a href="<?php echo get_permalink($updated_pages[0]); ?>" title="<?php _e("Live on your blog!","tdomf"); ?>" ><?php _e("View &raquo;","tdomf"); ?></a> |
    <?php } ?>
    <?php if(tdomf_get_option_form(TDOMF_OPTION_INCLUDED_YOUR_SUBMISSIONS,$form_id) && get_option(TDOMF_OPTION_YOUR_SUBMISSIONS)) { ?>
      <a href="users.php?page=tdomf_your_submissions#tdomf_form<?php echo $form_id; ?>" title="<?php _e("Included on the 'Your Submissions' page!",'tdomf'); ?>" >
      <?php _e("View &raquo;","tdomf"); ?></a>
    <?php } ?>
    </div>
    
    <div class="wrap">
    
    <h2><?php printf(__("Form %d Options","tdomf"),$form_id); ?></h2>
    
    
    
          <?php if($updated_pages == false) { ?>
          
             <?php $create_form_link = "admin.php?page=tdomf_show_options_menu&action=create_form_page&form=$form_id";
          if(function_exists('wp_nonce_url')){
          	$create_form_link = wp_nonce_url($create_form_link, 'tdomf-create-form-page');
          } ?>
    <p><a href="<?php echo $create_form_link; ?>"><?php _e("Create a page with this form automatically &raquo;","tdomf"); ?></a></p>

          <?php } ?>
    
    <p><a href="admin.php?page=tdomf_show_form_menu&form=<?php echo $form_id; ?>"><?php printf(__("Widgets for Form %d &raquo;","tdomf"),$form_id); ?></a></p>
    
    <form method="post" action="admin.php?page=tdomf_show_options_menu&form=<?php echo $form_id; ?>">

    <h3><?php _e('Form Name',"tdomf"); ?> </h3>
    
    <p>
    <?php _e('You can give this form a name to make it easier to identify. The name will also be used on the "Your Submissions" page if the form is included. HTML tags will be stripped.','tdomf'); ?>
    </p>
    
     <?php $form_name = tdomf_get_option_form(TDOMF_OPTION_NAME,$form_id); ?>
	</p>
	<b><?php _e("Form Name","tdomf"); ?></b>
	<input type="text" name="tdomf_form_name" id="tdomf_form_name" value="<?php if($form_name) { echo htmlentities(stripslashes($form_name),ENT_QUOTES,get_bloginfo('charset')); } ?>" />
	</p>
  
  <h3><?php _e('Form Description',"tdomf"); ?> </h3>

  <p>
    <?php _e('You can give a description of this form. The description will also be used on the "Your Submissions" page if the form is included. HTML can be used.','tdomf'); ?>
    </p>
  
     <?php $form_descp = tdomf_get_option_form(TDOMF_OPTION_DESCRIPTION,$form_id); ?>
	</p>
  <textarea cols="80" rows="3" name="tdomf_form_descp" id="tdomf_form_descp"><?php if($form_descp) { echo htmlentities(stripslashes($form_descp),ENT_NOQUOTES,get_bloginfo('charset')); } ?></textarea>
	</p>
  
     <h3><?php _e('Include this form in the "Your Submissions" Page',"tdomf"); ?> </h3>

	<p>
	<?php _e('You can optionally include the form in the "Your Submission" page will registered users can access',"tdomf"); ?>
    </p>

    <?php $inc_sub = tdomf_get_option_form(TDOMF_OPTION_INCLUDED_YOUR_SUBMISSIONS,$form_id); ?>

	</p>
	<b><?php _e("Include on 'Your Submissions' page","tdomf"); ?></b>
	<input type="checkbox" name="tdomf_include_sub" id="tdomf_include_sub" <?php if($inc_sub) echo "checked"; ?> >
	</p>
    
     <input type="hidden" id="tdomf_form_id" name="tdomf_form_id" value="<?php echo $form_id; ?>" />
     <?php if(function_exists('wp_nonce_field')){ wp_nonce_field('tdomf-options-save'); } ?>
          
<h3><?php _e("Who can access the form?","tdomf"); ?></h3>

	<p><?php _e("You can control access to the form based on user users roles. You can chose \"Unregistered Users\" if you want anyone to be able to access the form. If a user can publish their own posts, when they use the form, the post will be automatically published. (Only roles that cannot publish are listed here).","tdomf"); ?>

   <br/><br/>

	<?php if (!isset($wp_roles)) { $wp_roles = new WP_Roles(); }
	       $roles = $wp_roles->role_objects;
          $access_roles = array();
          foreach($roles as $role) {
             if(!isset($role->capabilities['publish_posts'])) {
                if($role->name != get_option('default_role')) {
                   array_push($access_roles,$role->name);
                } else {
                   $def_role = $role->name;
                }
             }
          } ?>

          <script type="text/javascript">
         //<![CDATA[
          function tdomf_unreg_user() {
            var flag = document.getElementById("tdomf_special_access_anyone").checked;
            if(flag) {
            <?php if(isset($def_role)) {?>
               document.getElementById("tdomf_access_<?php echo $def_role; ?>").checked = !flag;
            <?php } ?>
            <?php foreach($access_roles as $role) { ?>
               document.getElementById("tdomf_access_<?php echo $role; ?>").checked = !flag;
            <?php } ?>
            }
            <?php if(isset($def_role)) {?>
            document.getElementById("tdomf_access_<?php echo $def_role; ?>").disabled = flag;
            <?php } ?>
            <?php foreach($access_roles as $role) { ?>
            document.getElementById("tdomf_access_<?php echo $role; ?>").disabled = flag;
            <?php } ?>
           }
           <?php if(isset($def_role)) { ?>
           function tdomf_def_role() {
              var flag = document.getElementById("tdomf_access_<?php echo $def_role; ?>").checked;
              if(flag) {
              <?php foreach($access_roles as $role) { ?>
               //document.getElementById("tdomf_access_<?php echo $role; ?>").disabled = flag;
               document.getElementById("tdomf_access_<?php echo $role; ?>").checked = flag;
              <?php } ?>
              } else {
              <?php foreach($access_roles as $role) { ?>
               //document.getElementById("tdomf_access_<?php echo $role; ?>").disabled = flag;
              <?php } ?>
              }
           }
           <?php } ?>
           //-->
           </script>

          <label for="tdomf_special_access_anyone">
   <input value="tdomf_special_access_anyone" type="checkbox" name="tdomf_special_access_anyone" id="tdomf_special_access_anyone" <?php if(tdomf_get_option_form(TDOMF_OPTION_ALLOW_EVERYONE,$form_id) != false) { ?>checked<?php } ?> onClick="tdomf_unreg_user();" />
   <?php _e("Unregistered Users"); ?>
           </label><br/>

   <?php if(isset($def_role)) { ?>
             <label for="tdomf_access_<?php echo ($def_role); ?>">
             <input value="tdomf_access_<?php echo ($def_role); ?>" type="checkbox" name="tdomf_access_<?php echo ($def_role); ?>" id="tdomf_access_<?php echo ($def_role); ?>"  <?php if(isset($wp_roles->role_objects[$def_role]->capabilities[TDOMF_CAPABILITY_CAN_SEE_FORM.'_'.$form_id])) { ?> checked <?php } ?> onClick="tdomf_def_role()" <?php if(tdomf_get_option_form(TDOMF_OPTION_ALLOW_EVERYONE,$form_id) != false) { ?> disabled <?php } ?> />
             <?php echo $wp_roles->role_names[$def_role]." ".__("(default role for new users)"); ?>
             </label><br/>
          <?php } ?>

          <?php foreach($access_roles as $role) { ?>
             <label for="tdomf_access_<?php echo ($role); ?>">
             <input value="tdomf_access_<?php echo ($role); ?>" type="checkbox" name="tdomf_access_<?php echo ($role); ?>" id="tdomf_access_<?php echo ($role); ?>" <?php if(isset($wp_roles->role_objects[$role]->capabilities[TDOMF_CAPABILITY_CAN_SEE_FORM.'_'.$form_id])) { ?> checked <?php } ?> <?php if(tdomf_get_option_form(TDOMF_OPTION_ALLOW_EVERYONE,$form_id) != false) { ?> disabled <?php } ?> />
             <?php echo $wp_roles->role_names[$role]; ?>
             </label><br/>
          <?php } ?>
	 </p>

        <h3><?php _e("Who gets notified?","tdomf"); ?></h3>

	<p><?php _e("When a form is submitted by someone who can't automatically publish their entry, someone who can approve or publish the posts will be notified by email. You can chose which roles will be notified. If you select no role, no-one will be notified.","tdomf"); ?>
     <br/><br/>

	 <?php $notify_roles = tdomf_get_option_form(TDOMF_NOTIFY_ROLES,$form_id);
	       if($notify_roles != false) { $notify_roles = explode(';', $notify_roles); }  ?>

	 <?php foreach($roles as $role) {
           if(isset($role->capabilities['edit_others_posts'])
	           && isset($role->capabilities['publish_posts'])) { ?>
		     <label for="tdomf_notify_<?php echo ($role->name); ?>">
		     <input value="tdomf_notify_<?php echo ($role->name); ?>" type="checkbox" name="tdomf_notify_<?php echo ($role->name); ?>" id="tdomf_notify_<?php echo ($role->name); ?>" <?php if($notify_roles != false && in_array($role->name,$notify_roles)) { ?>checked<?php } ?> />
		      <?php echo $wp_roles->role_names[$role->name]; ?> <br/>
		     </label>
		     <?php
		  }
	       } ?>
         <br/>

	 </p>

	<h3><?php _e("Default Category","tdomf"); ?></h3>

       <p><?php _e("You can select a default category that the entry will be added to by default. You can change always edit the entry before publishing.","tdomf"); ?>
	   <br/><br/>

	         <?php $def_cat = tdomf_get_option_form(TDOMF_DEFAULT_CATEGORY,$form_id); ?>

	   <b><?php _e("Default Category","tdomf"); ?></b>

	   <SELECT NAME="tdomf_def_cat" id="tdomf_def_cat">
	   <?php $cats = get_categories("get=all");
        if(!empty($cats)) {
           foreach($cats as $c) {
             if($c->term_id == $def_cat ) {
               echo "<OPTION VALUE=\"$c->term_id\" selected>$c->category_nicename\n";
             } else {
               echo "<OPTION VALUE=\"$c->term_id\">$c->category_nicename\n";
             }
          }
        }?>
	</select>
	</p>
  
  <h3><?php _e('Turn On/Off Moderation',"tdomf"); ?> </h3>

	<p>
	<?php _e('<b>It is not recommended to turn off moderation.</b> Someone should always approve submissions from anonoymous users otherwise your webpage becomes a source for spammers and bots. However this feature has been requested too many times to not include. I recommend you use the "Auto Trust Submitter Count" instead if you want to enable automatic posting from users. Turning off moderation does not prevent you from banning specific users and IP address or deleting or setting to draft submitted posts.',"tdomf"); ?>
    </p>

    <?php $on_mod = tdomf_get_option_form(TDOMF_OPTION_MODERATION,$form_id); ?>

	</p>
	<b><?php _e("Enable Moderation","tdomf"); ?></b>
	<input type="checkbox" name="tdomf_moderation" id="tdomf_moderation"  	<?php if($on_mod) echo "checked"; ?> >
	</p>

    <h3><?php _e('Preview',"tdomf"); ?> </h3>

	<p>
	<?php _e('If your chosen widgets support preview, you can allow users to preview their post before submission',"tdomf"); ?>
    </p>

    <?php $on_preview = tdomf_get_option_form(TDOMF_OPTION_PREVIEW,$form_id); ?>

	</p>
	<b><?php _e("Enable Preview","tdomf"); ?></b>
	<input type="checkbox" name="tdomf_preview" id="tdomf_preview"  <?php if($on_preview) echo "checked"; ?> >
	</p>

  	<h3><?php _e('From Email Address for Notifications',"tdomf"); ?> </h3>

	<p>
	<?php _e('You can set a different email address for notifications here. If you leave this field blank, the default for your blog will be used.',"tdomf"); ?>
    </p>

    <?php $from_email = tdomf_get_option_form(TDOMF_OPTION_FROM_EMAIL,$form_id); ?>

	</p>
	<b><?php _e("From Email Address","tdomf"); ?></b>
	<input type="text" name="tdomf_from_email" id="tdomf_from_email" value="<?php if($from_email) { echo htmlentities($from_email,ENT_QUOTES,get_bloginfo('charset')); } ?>" >
	</p>
  
  <h3><?php _e('Maximum number of Widget instances',"tdomf"); ?></h3>

	<p>
	<?php _e('You can increase or decrease the number of instances of Widgets that support multiple copies. The minimum is at least 1.','tdomf'); ?>
	</p>

	<p>
	<b><?php _e("Widget Instances","tdomf"); ?></b>
  <?php $widget_count = tdomf_get_option_form(TDOMF_OPTION_WIDGET_INSTANCES,$form_id);
  if($widget_count == false) { $widget_count = 9; } ?>
	<input type="text" name="tdomf_widget_count" id="tdomf_widget_count" size="3" value="<?php echo htmlentities(strval($widget_count),ENT_QUOTES,get_bloginfo('charset')); ?>" />
	</p>
  

  <table border="0"><tr>

    <td>
    <input type="hidden" name="save_settings" value="0" />
    <input type="submit" name="tdomf_save_button" id="tdomf_save_button" value="<?php _e("Save","tdomf"); ?> &raquo;" />
	</form>
    </td>

    <td>
    <form method="post" action="admin.php?page=tdomf_show_options_menu&form=<?php echo $form_id; ?>">
    <input type="submit" name="refresh" value="Refresh" />
    </form>
    </td>

    </tr></table>
  
  </div>
  
  <?php }
}

// Display the menu to configure options for this plugin
//
function tdomf_show_options_menu() {
  global $wpdb, $wp_roles;

  $new_form_id = tdomf_handle_options_actions();
  $selected_form_id = intval($_REQUEST['form']);

  ?>

  <div class="wrap">

  <?php if(isset($_REQUEST['form']) || $new_form_id != false) { ?>
    <a href="admin.php?page=tdomf_show_options_menu"><?php _e("General Options"); ?></a> |
  <?php } else { ?> 
    <b><?php _e("General Options"); ?></b> | 
  <?php } ?>
    
    <?php $form_ids = tdomf_get_form_ids();
          if(!empty($form_ids)) {
            foreach($form_ids as $form_id) { ?>
              <?php if($form_id->form_id == $new_form_id) { ?>
                <b>
              <?php } else if($form_id->form_id == $selected_form_id && $new_form_id == false) { ?>
                <b>
              <?php } else { ?>
                <a href="admin.php?page=tdomf_show_options_menu&form=<?php echo $form_id->form_id; ?>">
              <?php } ?>
              <?php printf(__("Form %d","tdomf"),$form_id->form_id); ?><?php if($form_id->form_id == $new_form_id) { ?></b><?php } else if($form_id->form_id == $selected_form_id && $new_form_id == false) { ?></b><?php } else {?></a><?php } ?>
                 |
            <?php }
          }
    ?>
    <?php if(function_exists('wp_nonce_url')) { ?>
   <a href="<?php echo wp_nonce_url("admin.php?page=tdomf_show_options_menu&new", 'tdomf-new-form'); ?>">
          <?php _e("New Form &raquo;","tdomf"); ?></a>
    <?php } else { ?>
      <a href="admin.php?page=tdomf_show_options_menu&new"><?php _e("New Form &raquo;","tdomf"); ?></a>
    <?php } ?>
  </div>
  
  <?php 
  
  if($new_form_id!= false) {
    tdomf_show_form_options(intval($new_form_id));
  } else if(isset($_REQUEST['form'])) {
    tdomf_show_form_options($selected_form_id);
  } else {
    tdomf_show_general_options();
  }
}

////////////////////
// Manage options //
////////////////////

// Generate a dummy user
//
function tdomf_create_dummy_user() {
   $rand_username = "tdomf_".tdomf_random_string(5);
   $rand_password = tdomf_random_string(8);
   tdomf_log_message("Attempting to create dummy user $rand_username");
   $user_id = wp_create_user($rand_username,$rand_password);
   $user = new WP_User($user_id);
   if($user->has_cap("publish_posts")) {
      $user->remove_cap("publish_posts");
   }

   $users = get_option(TDOMF_OPTION_CREATEDUSERS);
   if($users == false) {
     $users = array( $user_id );
     add_option(TDOMF_OPTION_CREATEDUSERS,$users);
   } else {
     $users = array_merge( $users, array( $user_id ) );
     update_option(TDOMF_OPTION_CREATEDUSERS,$users);
   }
   
   update_option(TDOMF_DEFAULT_AUTHOR,$user_id);
   tdomf_log_message("Dummy user created for default author, user id = $user_id");
   return $user_id;
}

// Create a random string!
// Taken from http://www.tutorialized.com/view/tutorial/PHP-Random-String-Generator/13903
//
function tdomf_random_string($length)
{
    // Generate random 32 charecter string
    $string = md5(time());

    // Position Limiting
    $highest_startpoint = 32-$length;

    // Take a random starting point in the randomly
    // Generated String, not going any higher then $highest_startpoint
    $tdomf_random_string = substr($string,rand(0,$highest_startpoint),$length);

    return $tdomf_random_string;

}

// Create a page with the form embedded
//
function tdomf_create_form_page($form_id = 1) {
   global $current_user;

   $post = array (
	   "post_content"   => "[tdomf_form$form_id]",
	   "post_title"     => __("Submit A Post","tdomf"),
	   "post_author"    => $current_user->ID,
	   "post_status"    => 'publish',
	   "post_type"      => "page"
   );
   $post_ID = wp_insert_post($post);

   $pages = tdomf_get_option_form(TDOMF_OPTION_CREATEDPAGES,$form_id);
   if($pages == false) {
     $pages = array( $post_ID );
   } else {
     $pages = array_merge( $pages, array( $post_ID ) );
   }
   tdomf_set_option_form(TDOMF_OPTION_CREATEDPAGES,$pages,$form_id);
   
   return $post_ID;
}

// Handle actions for this form
//
function tdomf_handle_options_actions() {
   global $wpdb, $wp_roles;

   $message = "";
   $retValue = false;
   
  if(!isset($wp_roles)) {
  	$wp_roles = new WP_Roles();
  }
  $roles = $wp_roles->role_objects;

  if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'create_dummy_user') {
     check_admin_referer('tdomf-create-dummy-user');
     tdomf_create_dummy_user();
     $message = "Dummy user created for Default Author!<br/>";
  } else if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'create_form_page') {
     check_admin_referer('tdomf-create-form-page');
     $form_id = intval($_REQUEST['form']);
     $page_id = tdomf_create_form_page($form_id);
     $message = sprintf(__("A page with the form has been created. <a href='%s'>View page &raquo;</a><br/>","tdomf"),get_permalink($page_id));
  } else if(isset($_REQUEST['save_settings']) && !isset($_REQUEST['tdomf_form_id'])) {

      check_admin_referer('tdomf-options-save');

      // Default Author

      $def_aut = $_POST['tdomf_def_user'];
      update_option(TDOMF_DEFAULT_AUTHOR,$def_aut);

      // Author and Submitter fix

      $fix_aut = false;
      if(isset($_POST['tdomf_autocorrect_author'])) { $fix_aut = true; }
      update_option(TDOMF_AUTO_FIX_AUTHOR,$fix_aut);

      //Auto Trust Submitter Count

      $cnt = -1;
      if(isset($_POST['tdomf_trust_count']) 
       && !empty($_POST['tdomf_trust_count']) 
       && is_numeric($_POST['tdomf_trust_count'])){ 
         $cnt = intval($_POST['tdomf_trust_count']);
      }
      update_option(TDOMF_OPTION_TRUST_COUNT,$cnt);

      //Author theme hack

      $author_theme_hack = false;
      if(isset($_POST['tdomf_author_theme_hack'])) { $author_theme_hack = true; }
      update_option(TDOMF_OPTION_AUTHOR_THEME_HACK,$author_theme_hack);

      //Add submitter info

      $add_submitter = false;
      if(isset($_POST['tdomf_add_submitter'])) { $add_submitter = true; }
      update_option(TDOMF_OPTION_ADD_SUBMITTER,$add_submitter);

      //disable errors
      
      $disable_errors = false;
      if(isset($_POST['tdomf_disable_errors'])) { $disable_errors = true; }
      update_option(TDOMF_OPTION_DISABLE_ERROR_MESSAGES,$disable_errors);
      
      // extra log messages
      
      $extra_log = false;
      if(isset($_POST['tdomf_extra_log'])) { $extra_log = true; }
      update_option(TDOMF_OPTION_EXTRA_LOG_MESSAGES,$extra_log);
      
      // your submissions
      
      $your_submissions = false;
      if(isset($_POST['tdomf_your_submissions'])) { $your_submissions = true; }
      update_option(TDOMF_OPTION_YOUR_SUBMISSIONS,$your_submissions);
      
      $message .= "Options Saved!<br/>";
      tdomf_log_message("Options Saved");

  } else if(isset($_REQUEST['save_settings']) && isset($_REQUEST['tdomf_form_id'])) {
    
      check_admin_referer('tdomf-options-save');
    
      $form_id = intval($_REQUEST['tdomf_form_id']);
     
      // Who can access the form?

      if(isset($_REQUEST['tdomf_special_access_anyone']) && tdomf_get_option_form(TDOMF_OPTION_ALLOW_EVERYONE,$form_id) == false) {
         tdomf_set_option_form(TDOMF_OPTION_ALLOW_EVERYONE,true,$form_id);
     	foreach($roles as $role) {
     	    // remove cap as it's not needed
		    if(isset($role->capabilities[TDOMF_CAPABILITY_CAN_SEE_FORM.'_'.$form_id])){
   				$role->remove_cap(TDOMF_CAPABILITY_CAN_SEE_FORM.'_'.$form_id);
		    }
 	  	}
      } else if(!isset($_REQUEST['tdomf_special_access_anyone'])){
         tdomf_set_option_form(TDOMF_OPTION_ALLOW_EVERYONE,false,$form_id);
         // add cap to right roles
         foreach($roles as $role) {
		    if(isset($_REQUEST["tdomf_access_".$role->name])){
				$role->add_cap(TDOMF_CAPABILITY_CAN_SEE_FORM.'_'.$form_id);
		    } else if(isset($role->capabilities[TDOMF_CAPABILITY_CAN_SEE_FORM.'_'.$form_id])){
   				$role->remove_cap(TDOMF_CAPABILITY_CAN_SEE_FORM.'_'.$form_id);
		    }
 	  	}
      }

      // Who gets notified?

      $notify_roles = "";
	  foreach($roles as $role) {
		if(isset($_REQUEST["tdomf_notify_".$role->name])){
			$notify_roles .= $role->name.";";
	    }
      }
      if(!empty($notify_roles)) {
        tdomf_set_option_form(TDOMF_NOTIFY_ROLES,$notify_roles,$form_id);
      } else {
        tdomf_set_option_form(TDOMF_NOTIFY_ROLES,false,$form_id);
      }

      // Default Category

      $def_cat = $_POST['tdomf_def_cat'];
      tdomf_set_option_form(TDOMF_DEFAULT_CATEGORY,$def_cat,$form_id);

       //Turn On/Off Moderation

      $mod = false;
      if(isset($_POST['tdomf_moderation'])) { $mod = true; }
      tdomf_set_option_form(TDOMF_OPTION_MODERATION,$mod,$form_id);

      //Preview

      $preview = false;
      if(isset($_POST['tdomf_preview'])) { $preview = true; }
      tdomf_set_option_form(TDOMF_OPTION_PREVIEW,$preview,$form_id);

            //From email

      if(trim($_POST['tdomf_from_email']) == "") {
       	tdomf_set_option_form(TDOMF_OPTION_FROM_EMAIL,false);
       } else {
        tdomf_set_option_form(TDOMF_OPTION_FROM_EMAIL,$_POST['tdomf_from_email'],$form_id);
       }

       // Form name
       
       if(trim($_POST['tdomf_form_name']) == "") {
        tdomf_set_option_form(TDOMF_OPTION_NAME,"");
       } else {
        tdomf_set_option_form(TDOMF_OPTION_NAME,strip_tags($_POST['tdomf_form_name']),$form_id);
       }
       
       // Form description
       
       if(trim($_POST['tdomf_form_descp']) == "") {
       	tdomf_set_option_form(TDOMF_OPTION_DESCRIPTION,false);
       } else {
        tdomf_set_option_form(TDOMF_OPTION_DESCRIPTION,$_POST['tdomf_form_descp'],$form_id);
       }
       
       // Include on "your submissions" page
       //
       $include = false;
      if(isset($_POST['tdomf_include_sub'])) { $include = true; }
      tdomf_set_option_form(TDOMF_OPTION_INCLUDED_YOUR_SUBMISSIONS,$include,$form_id);
       
      if(get_option(TDOMF_OPTION_YOUR_SUBMISSIONS) && $include) {
        $message .= sprintf(__("Saved Options for Form %d. <a href='%s'>See your form &raquo</a>","tdomf"),$form_id,"users.php?page=tdomf_your_submissions#tdomf_form%d")."<br/>";
      } else {
        $message .= sprintf(__("Saved Options for Form %d.","tdomf"),$form_id)."<br/>";
      }
      
      // widget count
      //
      $widget_count = 10;
      if(isset($_POST['tdomf_widget_count'])) { $widget_count = intval($_POST['tdomf_widget_count']); }
      if($widget_count < 1){ $widget_count = 1; }
      tdomf_set_option_form(TDOMF_OPTION_WIDGET_INSTANCES,$widget_count,$form_id);
      
       tdomf_log_message("Options Saved for Form ID $form_id");
       
  } else if(isset($_REQUEST['delete'])) {
      
    $form_id = intval($_REQUEST['delete']);
    
    check_admin_referer('tdomf-delete-form-'.$form_id);
    
    if(tdomf_form_exists($form_id)) {
      $count_forms = count(tdomf_get_form_ids());
      if($count_forms > 1) {
        if(tdomf_delete_form($form_id)) {
           $message .= sprintf(__("Form %d deleted.<br/>","tdomf"),$form_id);
        } else {
          $message .= sprintf(__("Could not delete Form %d!<br/>","tdomf"),$form_id);
        }
      } else {
        $message .= sprintf(__("You cannot delete the last form! There must be at least one form in the system.<br/>","tdomf"),$form_id);
      }
    } else {
      $message .= sprintf(__("Form %d is not valid!<br/>","tdomf"),$form_id);
    }
  } else if(isset($_REQUEST['copy'])) {
    
    $form_id = intval($_REQUEST['copy']);
    
    check_admin_referer('tdomf-copy-form-'.$form_id);
    
    $copy_form_id = tdomf_copy_form($form_id);
   
    if($copy_form_id != 0) {
      $message .= sprintf(__("Form %d copied with id %d.<br/>","tdomf"),$form_id,$copy_form_id);
      $retValue = $copy_form_id;
    } else {
      $message .= sprintf(__("Failed to copy Form %d!<br/>","tdomf"),$form_id);
    }
        
  } else if(isset($_REQUEST['new'])) {
    
    check_admin_referer('tdomf-new-form');
    
    $form_id = tdomf_create_form(__('New Form','tdomf'),array());
   
    if($form_id != 0) {
      $message .= sprintf(__("New form created with %d.<br/>","tdomf"),$form_id);
      $retValue = $form_id;
    } else {
      $message .= __("Failed to create new Form!<br/>","tdomf");
    }
  }

   // Warnings

   $message .= tdomf_get_error_messages(false);

   if(!empty($message)) { ?>
   <div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
   <?php }
   
   return $retValue;
}

// Check for error messages with options and return a message
//
function tdomf_get_error_messages($show_links=true) {
  global $wpdb, $wp_roles;
  if(!isset($wp_roles)) {
  	$wp_roles = new WP_Roles();
  }
  $roles = $wp_roles->role_objects;
  $message = "";
  
  if(ini_get('register_globals')){
    $message .= "<font color=\"red\"><strong>".__("ERROR: <em>register_globals</em> is enabled. This is a security risk and also prevents TDO Mini Forms from working.")."</strong></font>";
  }
  
  if(isset($_REQUEST['form'])) {
  
    $form_id = intval($_REQUEST['form']);
    
  if(tdomf_get_option_form(TDOMF_OPTION_ALLOW_EVERYONE,$form_id) == false) {
          $test_see_form = false;
          foreach($roles as $role) {
          if(!isset($role->capabilities['publish_posts']) && isset($role->capabilities[TDOMF_CAPABILITY_CAN_SEE_FORM.'_'.$form_id])){
            $test_see_form = true;
          }
          }
          if($test_see_form == false) {
            if($show_links) {
              $message .= "<font color=\"red\">".sprintf(__("<b>Warning</b>: Only users who can <i>already publish posts</i>, can see the form! <a href=\"%s\">Configure on Options Page &raquo;</a>"),get_bloginfo('wpurl')."/wp-admin/admin.php?page=tdomf_show_options_menu")."</font><br/>";
            } else {
              $message .= "<font color=\"red\">".__("<b>Warning</b>: Only users who can <i>already publish posts</i>, can seet this form!")."</font><br/>";
            }
            tdomf_log_message("Option Allow Everyone not set and no roles set to see the form",TDOMF_LOG_BAD);
          }
        }
  }
        
       $create_user_link = get_bloginfo('wpurl')."/wp-admin/admin.php?page=tdomf_show_options_menu&action=create_dummy_user";
	    if(function_exists('wp_nonce_url')){
	          $create_user_link = wp_nonce_url($create_user_link, 'tdomf-create-dummy-user');
    }
	  if(get_option(TDOMF_DEFAULT_AUTHOR) == false) {
	 	  $message .= "<font color=\"red\">".sprintf(__("<b>Error</b>: No default author set! <a href=\"%s\">Create dummy user for default author automatically &raquo;</a>","tdomf"),$create_user_link)."</font><br/>";
	 	  tdomf_log_message("Option Default Author not set!",TDOMF_LOG_BAD);
 	  } else {
 	  	$def_aut = new WP_User(get_option(TDOMF_DEFAULT_AUTHOR));
      if(empty($def_aut->data->ID)) {
        // User does not exist! Deleting option
        delete_option(TDOMF_DEFAULT_AUTHOR);
        $message .= "<font color=\"red\">".sprintf(__("<b>Error</b>: Current Default Author does not exist! <a href=\"%s\">Create dummy user for default author automatically &raquo;</a>","tdomf"),$create_user_link)."</font><br/>";
	 	    tdomf_log_message("Current Default Author does not exist! Deleting option.",TDOMF_LOG_BAD);
      }      
 	  	if($def_aut->has_cap("publish_posts")) {
	 	  $message .= "<font color=\"red\">".sprintf(__("<b>Error</b>: Default author can publish posts. Default author should not be able to publish posts! <a href=\"%s\">Create a dummy user for default author automatically &raquo;</a>","tdomf"),$create_user_link)."</font><br/>";
	 	  tdomf_log_message("Option Default Author is set to an author who can publish posts.",TDOMF_LOG_BAD);
 	  	}
    }
    return $message;
}

?>
