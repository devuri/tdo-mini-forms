<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('TDOMF: You are not allowed to call this page directly.'); }

//////////////////////////////
// Code for Form generation //
//////////////////////////////

// TODO: Clear and/or reset button

// Checks if current user/ip has permissions to post!
//
function tdomf_check_permissions_form($form_id = 1) {
   global $current_user, $wpdb;

   get_currentuserinfo();

   // User Banned
   //
   if(is_user_logged_in()) {
       $user_status = get_usermeta($current_user->ID,TDOMF_KEY_STATUS);
       if($user_status == TDOMF_USER_STATUS_BANNED) {
          tdomf_log_message("Banned user $current_user->user_name tried to submit a post!",TDOMF_LOG_ERROR);
          return tdomf_get_message_instance(TDOMF_OPTION_MSG_PERM_BANNED_USER,$form_id); 
       }
   }

  // IP banned
  //
  $ip =  $_SERVER['REMOTE_ADDR'];
  $banned_ips = get_option(TDOMF_BANNED_IPS);
  if($banned_ips != false) {
  	$banned_ips = split(";",$banned_ips);
  	foreach($banned_ips as $banned_ip) {
		if($banned_ip == $ip) {
           tdomf_log_message("Banned ip $ip tried to submit a post!",TDOMF_LOG_ERROR);
           return tdomf_get_message_instance(TDOMF_OPTION_MSG_PERM_BANNED_IP,$form_id);
		}
	 }
  }

  // Throttling Rules
  //
  $rules = tdomf_get_option_form(TDOMF_OPTION_THROTTLE_RULES,$form_id);
  if(is_array($rules) && !empty($rules)) {
      foreach($rules as $rule_id => $rule) {
          $query = "SELECT ID, post_status, post_date ";
          $query .= "FROM $wpdb->posts ";
          $query .= "LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";
          if($rule['type'] == 'ip') {
              $query .= "WHERE meta_key = '".TDOMF_KEY_IP."' ";
              $query .= "AND meta_value = '$ip' ";
          } else if($rule['type'] == 'user') {
              $query .= "WHERE meta_key = '".TDOMF_KEY_USER_ID."' ";
              $query .= "AND meta_value = '".$current_user->ID."' ";
          }
          if($rule['sub_type'] == 'unapproved') {
              $query .= "AND post_status = 'draft' ";
          }
          if($rule['opt1']) {
              $timestamp = tdomf_timestamp_wp_sql(time() - $rule['time']);
              $query .= "AND post_date > '$timestamp' ";
          }
          $query .= "ORDER BY post_date ASC ";
          $query .= "LIMIT " . ($rule['count'] + 1);
          $results = $wpdb->get_results( $query );
          #var_dump($results);
          if(count($results) >= $rule['count']) {
              tdomf_log_message("IP $ip blocked by Throttle Rule $rule_id",TDOMF_LOG_BAD);
              return tdomf_get_message_instance(TDOMF_OPTION_MSG_PERM_THROTTLE,$form_id);
          }
      }
  }
  
  // Users who can access form
  //
  if(tdomf_get_option_form(TDOMF_OPTION_ALLOW_EVERYONE,$form_id) == false) {
  	if(!current_user_can("publish_posts")  && !current_user_can(TDOMF_CAPABILITY_CAN_SEE_FORM.'_'.$form_id)) {
      tdomf_log_message("User with the incorrect privilages attempted to submit a post!",TDOMF_LOG_ERROR);
      if(is_user_logged_in()) {
        return tdomf_get_message_instance(TDOMF_OPTION_MSG_PERM_INVALID_USER,$form_id);
      } else {
        return tdomf_get_message_instance(TDOMF_OPTION_MSG_PERM_INVALID_NOUSER,$form_id);
      }
  	}
  }

  return NULL;
}

// Generate a preview based on form arguments
//
function tdomf_preview_form($args,$mode=false) {
   global $tdomf_form_widgets_preview,$tdomf_form_widgets_preview_hack;

   $form_id = intval($args['tdomf_form_id']);
   
   // Set mode of form
   $hack = false;
   if($mode === false) {
      if(tdomf_get_option_form(TDOMF_OPTION_SUBMIT_PAGE,$form_id)) {
         $mode = "new-page";
      } else {
         $mode = "new-post";
      }
   } else {
      if(strpos($mode,'-hack') !== false) {
         $hack = true;
      }
   }
      
   do_action('tdomf_preview_form_start',$form_id,$mode);
   
   // handle hacked forms
   //
   if(!$hack) {
      // see if there is a "hacked" preview already! 
      $hacked_message = tdomf_get_option_form(TDOMF_OPTION_FORM_PREVIEW_HACK,$form_id);
      if($hacked_message != false) {
          $widgets = tdomf_filter_widgets($mode, $tdomf_form_widgets_preview);
          $message = tdomf_prepare_string($hacked_message, $form_id, $mode, false, "", $args);
          
          // basics
          $unused_patterns = array();
          $patterns     = array ();
          $replacements = array ();
       
          // widgets
          $widget_args = array_merge( array( "before_widget"=>"<p>\n",
                                      "after_widget"=>"\n</p>\n",
                                      "before_title"=>"<b>",
                                      "after_title"=>"</b><br/>",
                                      "mode"=>$mode ),
                                      $args);
          $widget_order = tdomf_get_widget_order($form_id);
          foreach($widget_order as $w) {
              if(isset($widgets[$w])) {
                  $patterns[]     = '/'.TDOMF_MACRO_WIDGET_START.$w.TDOMF_MACRO_END.'/';
                  // all widgets need to be excuted even if not displayed
                  $replacements[] = $widgets[$w]['cb']($widget_args,$widgets[$w]['params']);
              } else {
                   $unused_patterns[] = '/'.TDOMF_MACRO_WIDGET_START.$w.TDOMF_MACRO_END.'/';
              }
          }
          
          // create message
          $message = preg_replace($patterns,$replacements,$message);
          $message = preg_replace($unused_patterns,"",$message);
          return $message;
      }
   } 
      
   $message = "";
   if(!$hack) {
       $widgets = tdomf_filter_widgets($mode, $tdomf_form_widgets_preview);
       $widget_args = array_merge( array( "before_widget"=>"<p>\n",
                                          "after_widget"=>"\n</p>\n",
                                          "before_title"=>"<b>",
                                          "after_title"=>"</b><br/>",
                                          "mode"=>$mode, 
                                          "tdomf_form_id"=>$form_id),
                                          $args);
       $widget_order = tdomf_get_widget_order($form_id);
       foreach($widget_order as $w) {
          if(isset($widgets[$w])) {
            tdomf_log_message_extra("Looking at preview widget $w");
            $message .= $widgets[$w]['cb']($widget_args,$widgets[$w]['params']);
          }
       }
   } else {
      $widgets_o = tdomf_filter_widgets($mode, $tdomf_form_widgets_preview);
      $widgets_h = tdomf_filter_widgets($mode, $tdomf_form_widgets_preview_hack);
      $widget_args = array( "before_widget"=>"<p>\n",
                             "after_widget"=>"\n</p>\n",
                             "before_title"=>"<b>",
                             "after_title"=>"</b>\n\t<br/>\n",
                             "mode"=>$mode,
                             "tdomf_form_id"=>$form_id);
      $widget_order = tdomf_get_widget_order($form_id);
      $message .= "\n<!-- widgets start -->\n";
      foreach($widget_order as $w) {
          if(!isset($widgets_h[$w]) && isset($widgets_o[$w])) {
              $message .= "%%WIDGET:$w%%\n";
          } else if(isset($widgets_h[$w])) {
              $message .= "<!-- $w start -->\n";
              $message .= $widgets_h[$w]['cb']($widget_args,$widgets_h[$w]['params']);
              $message .= "<!-- $w end -->\n";
          }
      }
      $message .= "<!-- widgets end -->\n";
   }
   
   if($message == "") {
      tdomf_log_message("Couldn't generate preview!",TDOMF_LOG_ERROR);
	  return __("Error! Could not generate a preview!","tdomf");
   }
   return sprintf(__("This is a preview of your submission:%s\n","tdomf"),$message);
}

// Validate input using widgets
//
function tdomf_validate_form($args,$preview = false) {
   global $tdomf_form_widgets_validate;

   $form_id = intval($args['tdomf_form_id']);
   
   // Set mode of page
   if(tdomf_get_option_form(TDOMF_OPTION_SUBMIT_PAGE,$form_id)) {
     $mode = "new-page";
   } else {
     $mode = "new-post";
   }
   do_action('tdomf_validate_form_start',$form_id,$mode);
   $widgets = tdomf_filter_widgets($mode, $tdomf_form_widgets_validate);

   $message = "";
   $widget_args = array_merge( array( "before_widget"=>"",
                                      "after_widget"=>"<br/>\n",
                                      "before_title"=>"<b>",
                                      "after_title"=>"</b><br/>",
                                      "mode"=>$mode),
							   $args);
   $widget_order = tdomf_get_widget_order($form_id,$preview);
   foreach($widget_order as $w) {
	  if(isset($widgets[$w])) {
		$temp_message = $widgets[$w]['cb']($widget_args,$preview,$widgets[$w]['params']);
		if($temp_message != NULL && trim($temp_message) != ""){
		   $message .= $temp_message;
		}
	   }
   }
   // Oh dear! Something didn't validate!
   if(trim($message) != "") {
	  tdomf_log_message("Their submission didn't validate.");
	  return "<font color='red'>$message</font>\n";
   }
   return NULL;
}

function tdomf_timestamp_wp_sql( $timestamp, $gmt = false ) {
   return ( $gmt ) ? gmdate( 'Y-m-d H:i:s', $timestamp ) : gmdate( 'Y-m-d H:i:s', ( $timestamp + ( get_option( 'gmt_offset' ) * 3600 ) ) );
}

function tdomf_queue_date($form_id,$current_ts)  {
    tdomf_log_message("Current ts is $current_ts (" . tdomf_timestamp_wp_sql($current_ts) . ")" );
    $queue_period = intval(tdomf_get_option_form(TDOMF_OPTION_QUEUE_PERIOD,$form_id));
    if($queue_period > 0) {
        tdomf_log_message("Queue period is $queue_period seconds");
        global $wpdb;
        $query = "SELECT DISTINCT(ID), post_date
          FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
          WHERE $wpdb->postmeta.meta_key='".TDOMF_KEY_FORM_ID."'
                AND $wpdb->postmeta.meta_value='".$form_id."'
                AND ($wpdb->posts.post_status='future' OR $wpdb->posts.post_status='publish')
          ORDER BY post_date DESC 
          LIMIT 1 ";
          $results = $wpdb->get_results($query);
          if(count($results) > 0) {
              #$last_ts = strtotime($results[0]->post_date);
              $last_ts = mysql2date('U',$results[0]->post_date);
              tdomf_log_message("Got latest ts of $last_ts (" . tdomf_timestamp_wp_sql($last_ts) . ")");
              $next_ts = $last_ts + $queue_period;
              if($next_ts > $current_ts) {
                  tdomf_log_message("Sticking post in queue with ts of $next_ts (" . tdomf_timestamp_wp_sql($next_ts) . ")");
                  return $next_ts;
              }
          }
    }
    return $current_ts;
}

// Creates a post using args
//
function tdomf_create_post($args) {
   global $wp_rewrite, $tdomf_form_widgets_post, $current_user;

   $form_id = intval($args['tdomf_form_id']);
   
   // Set mode of page
   if(tdomf_get_option_form(TDOMF_OPTION_SUBMIT_PAGE,$form_id)) {
     $mode = "new-page";
   } else {
     $mode = "new-post";
   }
   
   do_action('tdomf_create_post_start',$form_id,$mode);
   
   tdomf_log_message("Attempting to create a post based on submission");

   // Default submitter
   $user_id = get_option(TDOMF_DEFAULT_AUTHOR);
   if(is_user_logged_in()) {
      $user_id = $current_user->ID;
   }

   // Default category
   //
   $post_cats = array(tdomf_get_option_form(TDOMF_DEFAULT_CATEGORY,$form_id));

   // Default title (should this be an option?)
   //
   $def_title = tdomf_get_log_timestamp();

   // Build post and post it as draft
   //
   $post = array (
	   "post_content"   => "",
#	   "post_excerpt"   => "",
	   "post_title"     => $def_title,
	   "post_category"  => $post_cats,
	   "post_author"    => $user_id,
	   "post_status"    => 'draft',
#	   "post_name"      => "",
#	   "post_date"      => $post_date,
#    "post_date_gmt"  => $post_date_gmt,
#	   "comment_status" => get_option('default_comment_status'),
#	   "ping_status"    => get_option('default_ping_status').
   );
   //
   // submit a page instead of a post
   //   
   if(tdomf_get_option_form(TDOMF_OPTION_SUBMIT_PAGE,$form_id)) {
     $post['post_type'] = 'page';
   }
   //
   $post_ID = wp_insert_post($post);
   if($post_ID == 0)
   {
       tdomf_log_message("Failed to create post! \$post_ID == 0",TDOMF_LOG_ERROR);
       return __("TDOMF ERROR: Failed to create post! \$post_ID == 0","tdomf");
   }

   tdomf_log_message("Post with id $post_ID (and default title $def_title) created as draft.");

   // Flag this post as TDOMF!
   add_post_meta($post_ID, TDOMF_KEY_FLAG, true, true);

   // Submitter info
   if($user_id != get_option(TDOMF_DEFAULT_AUTHOR)){
     tdomf_log_message("Logging default submitter info (user $user_id) for this post $post_ID");
     add_post_meta($post_ID, TDOMF_KEY_USER_ID, $user_id, true);
     add_post_meta($post_ID, TDOMF_KEY_USER_NAME, $current_user->user_login, true);
     update_usermeta($user_id, TDOMF_KEY_FLAG, true);
   }

   // IP info
   if(isset($args['ip'])){
        $ip = $args['ip'];
        tdomf_log_message("Logging default ip $ip for this post $post_ID");
        add_post_meta($post_ID, TDOMF_KEY_IP, $ip, true);
   }

   // Form Id
   //
   add_post_meta($post_ID, TDOMF_KEY_FORM_ID, $form_id, true);

   
   tdomf_log_message("Let the widgets do their work on newly created $post_ID");

   // Disable kses protection! It seems to get over-protective of non-registered
   // posts! If the post is going to be moderated, then we don't have an issue
   // as an admin will verify it... I think. Hope to god this is not a
   // security risk!
   if(tdomf_get_option_form(TDOMF_OPTION_MODERATION,$form_id)){
     kses_remove_filters();
   }
   
   // Widgets:post
   //
   $message = "";
   $widget_args = array_merge( array( "post_ID"=>$post_ID,
                                      "before_widget"=>"",
                                      "after_widget"=>"<br/>\n",
                                      "before_title"=>"<b>",
                                      "after_title"=>"</b><br/>",
                                      "mode"=>$mode),
                                      $args);
   $widget_order = tdomf_get_widget_order($form_id);
   $widgets = tdomf_filter_widgets($mode, $tdomf_form_widgets_post);
   foreach($widget_order as $w) {
    if(isset($widgets[$w])) {
      $temp_message = $widgets[$w]['cb']($widget_args,$widgets[$w]['params']);
      if($temp_message != NULL && trim($temp_message) != ""){
        $message .= $temp_message;
      }
	  }
   }
   // Oh dear! Errors after submission!
   if(trim($message) != "") {
     tdomf_log_message("Post widgets report error! Attempting to delete $post_ID post...");
     wp_delete_post($post_ID);
     return "<font color='red'>$message</font>\n";
   }
   

   $send_moderator_email = true;
   
   // Spam check
   //
   add_post_meta($post_ID, TDOMF_KEY_USER_AGENT, $_SERVER['HTTP_USER_AGENT'], true);
   add_post_meta($post_ID, TDOMF_KEY_REFERRER, $_SERVER['HTTP_REFERER'], true);
   if(tdomf_check_submissions_spam($post_ID)) {
     
       // Submitted post count!
       //
       $submitted_count = get_option(TDOMF_STAT_SUBMITTED);
       if($submitted_count == false) {
          $submitted_count = 0;
       }
       $submitted_count++;
       update_option(TDOMF_STAT_SUBMITTED,$submitted_count);
       tdomf_log_message("post $post_ID is number $submitted_count submission!");
       
     // publish (maybe)
     //
     if(!tdomf_get_option_form(TDOMF_OPTION_MODERATION,$form_id)){
        tdomf_log_message("Moderation is disabled. Publishing $post_ID!");
        // Use update post instead of publish post because in WP2.3, 
        // update_post doesn't seem to add the date correctly!
        // Also when it updates a post, if comments aren't set, sets them to
        // empty! (Not so in WP2.2!)
        
        // Schedule date
        //
        $current_ts = time();
        $ts = tdomf_queue_date($form_id,$current_ts);
        if($current_ts == $ts) {
            $post = array (
              "ID"             => $post_ID,
              "post_status"    => 'publish',
              "comment_status" => get_option('default_comment_status'),
              );
        } else {
            $post_date = tdomf_timestamp_wp_sql($ts);
            $post_date_gmt = get_gmt_from_date($post_date);
            #$post_date_gmt = tdomf_timestamp_wp_sql($ts,1);
            tdomf_log_message("Future Post Date = $post_date!");
            $post = array (
              "ID"             => $post_ID,
              "post_status"    => 'future',
              "comment_status" => get_option('default_comment_status'),
              "post_date"      => $post_date,
              "post_date_gmt"  => $post_date_gmt,
              );
        }
        
        wp_update_post($post);
        $send_moderator_email = tdomf_get_option_form(TDOMF_OPTION_MOD_EMAIL_ON_PUB,$form_id);
     } else if($user_id != get_option(TDOMF_DEFAULT_AUTHOR)) {
          $testuser = new WP_User($user_id,$user->user_login);
          $user_status = get_usermeta($user_id,TDOMF_KEY_STATUS);
          if(current_user_can('publish_posts') || $user_status == TDOMF_USER_STATUS_TRUSTED) {
             tdomf_log_message("Publishing post $post_ID!");
             // Use update post instead of publish post because in WP2.3, 
             // update_post doesn't seem to add the date correctly!
             // Also when it updates a post, if comments aren't set, sets them to
             // empty! (Not so in WP2.2!)
             
            // Schedule date
            //
            $current_ts = time();
            $ts = tdomf_queue_date($form_id,$current_ts);
            if($current_ts == $ts) {
                $post = array (
                  "ID"             => $post_ID,
                  "post_status"    => 'publish',
                  "comment_status" => get_option('default_comment_status'),
                  );
            } else {
                $post_date = tdomf_timestamp_wp_sql($ts);
                $post_date_gmt = get_gmt_from_date($post_date);
                #$post_date_gmt = tdomf_timestamp_wp_sql($ts,1);
                tdomf_log_message("Future Post Date = $post_date!");
                $post = array (
                  "ID"             => $post_ID,
                  "post_status"    => 'future',
                  "comment_status" => get_option('default_comment_status'),
                  "post_date"      => $post_date,
                  "post_date_gmt"  => $post_date_gmt,
                  );
            }
             wp_update_post($post);
             #wp_publish_post($post_ID);
             $send_moderator_email = tdomf_get_option_form(TDOMF_OPTION_MOD_EMAIL_ON_PUB,$form_id);
          }
     }
   } else {
     // it's spam :(
     
     if(get_option(TDOMF_OPTION_SPAM_NOTIFY) == 'none') {
       $send_moderator_email = false;
     }
   }
   
   // Notify admins
   //
   if($send_moderator_email){
      tdomf_notify_admins($post_ID,$form_id);
   }

   // Re-enable filters so we dont' break anything else!
   //
   if(tdomf_get_option_form(TDOMF_OPTION_MODERATION,$form_id) && current_user_can('unfiltered_html') == false){
     kses_init_filters();
   }
   
   do_action('tdomf_create_post_end',$post_ID,$form_id,$mode);
   
   return intval($post_ID);
}

// Generate Form Key and place it in Session for Post forms
//
function tdomf_generate_form_key($form_id) {
 
  $tdomf_verify = get_option(TDOMF_OPTION_VERIFICATION_METHOD);
  if($tdomf_verify == 'wordpress_nonce' && function_exists('wp_create_nonce')) {
    $nonce_string = wp_create_nonce( 'tdomf-form-'.$form_id );
    return "<input type='hidden' id='tdomf_key_$form_id' name='tdomf_key_$form_id' value='$nonce_string' />";
  } else if($tdomf_verify == 'none') {
    // do nothing! Bad :(
    return "";
  }
  
  // default
  $form_data = tdomf_get_form_data($form_id);  
  $random_string = tdomf_random_string(100);
  $form_data["tdomf_key_$form_id"] = $random_string;
  tdomf_log_message_extra('Placing key '.$random_string.' in form_data: <pre>'.var_export($form_data,true)."</pre>");
  tdomf_save_form_data($form_id,$form_data);
  return "<input type='hidden' id='tdomf_key_$form_id' name='tdomf_key_$form_id' value='$random_string' />";
}

// Create the form!
//
function tdomf_generate_form($form_id = 1,$mode = false) {
  global $tdomf_form_widgets,$tdomf_form_widgets_hack;

  if(!tdomf_form_exists($form_id)) {
    return sprintf(__("Form %d does not exist.",'tdomf'),$form_id); 
  }

  // Set mode of form
  $hack = false;
  if($mode === false) {
      if(tdomf_get_option_form(TDOMF_OPTION_SUBMIT_PAGE,$form_id)) {
         $mode = "new-page";
      } else {
         $mode = "new-post";
      }
  } else {
      if(strpos($mode,'-hack') !== false) {
         $hack = true;
      }
  }
  
  
  $use_ajax = tdomf_widget_is_ajax_avaliable($form_id);
  if($use_ajax) {
      $mode .= "-ajax";
  }

  $form = tdomf_check_permissions_form($form_id);
  if($form != NULL) {
    return $form;
  }

  do_action('tdomf_generate_form_start',$form_id,$mode);

  // initilise some variables
  //
  if($hack) {
      $widgets = tdomf_filter_widgets($mode, $tdomf_form_widgets_hack);
  } else {
      $widgets = tdomf_filter_widgets($mode, $tdomf_form_widgets);
  }
  $form = "";
  $form_data = tdomf_get_form_data($form_id);
  
  // handle hacked forms
  //
  if(!$hack) {
      $hacked_form = tdomf_get_option_form(TDOMF_OPTION_FORM_HACK,$form_id);
      if($hacked_form != false) {
          
          // grab form message and post args (if exists)
          //
          $post_args = array();
          $message = false;
          if(isset($form_data['tdomf_form_post_'.$form_id])) {
              // grab post args
              $post_args = $form_data['tdomf_form_post_'.$form_id];
              unset($form_data['tdomf_form_post_'.$form_id]);
              tdomf_save_form_data($form_id,$form_data);
              if(isset($post_args['tdomf_post_message_'.$form_id])) {
                  // grab message (preview/validation)
                  $message = $post_args['tdomf_post_message_'.$form_id];
                  unset($form_data['tdomf_post_message_'.$form_id]);
                  tdomf_save_form_data($form_id,$form_data);
              }
              // form has been turned off! just return message
              if(isset($post_args['tdomf_no_form_'.$form_id])) {
                  unset($post_args['tdomf_no_form_'.$form_id]);
                  tdomf_save_form_data($form_id,$form_data);
                  return $message;
              }
          }
          
          $form = tdomf_prepare_string($hacked_form, $form_id, $mode, false, "", $post_args);
          
          // basics
          $unused_patterns = array();
          $patterns     = array ( '/'.TDOMF_MACRO_FORMKEY.'/' );
          $replacements = array ( tdomf_generate_form_key($form_id) );

          // message
          if($use_ajax && $message == false) {
              $patterns[]     = '/'.TDOMF_MACRO_FORMMESSAGE.'/';
              $replacements[] = "<div id='tdomf_form${form_id}_message' id='tdomf_form${form_id}_message' class='hidden'></div>";
          } else {
              $patterns[]     = '/'.TDOMF_MACRO_FORMMESSAGE.'/';
              $replacements[] = $message;
          }
          
          // widgets
          $widget_args = array_merge( array( "before_widget"=>"<fieldset>\n",
                                           "after_widget"=>"\n</fieldset>\n",
                                           "before_title"=>"<legend>",
                                           "after_title"=>"</legend>",
                                           "tdomf_form_id"=>$form_id,
                                           "mode"=>$mode),
                                           $post_args);
          $widget_order = tdomf_get_widget_order($form_id);
          foreach($widget_order as $w) {
              if(isset($widgets[$w])) {
                  $patterns[]     = '/'.TDOMF_MACRO_WIDGET_START.$w.TDOMF_MACRO_END.'/';
                  // all widgets need to be excuted even if not displayed
                  $replacements[] = $widgets[$w]['cb']($widget_args,$widgets[$w]['params']);
              } else {
                   $unused_patterns[] = '/'.TDOMF_MACRO_WIDGET_START.$w.TDOMF_MACRO_END.'/';
              }
          }
          
          // create form
          $form = preg_replace($patterns,$replacements,$form);
          $form = preg_replace($unused_patterns,"",$form);
          return $form;
      }
  }
  
  $form_name = 'tdomf_form'.$form_id;
  
  if($hack) {
     $form .= "\n<!-- Form $form_id start -->\n";
  }
  
  if($use_ajax) {
      $ajax_script = TDOMF_URLPATH.'tdomf-form-ajax.php';
      if($hack) {
          $form .= "<!-- AJAX js start -->\n";
      }
      $jquery_url = get_bloginfo('wpurl').'/wp-includes/js/jquery/jquery.js';
      $form .= "<script type='text/javascript' src='$jquery_url'></script>\n";
      $sack_url = get_bloginfo('wpurl').'/wp-includes/js/tw-sack.js';
      $ajax_error = __("TDOMF: ERROR with AJAX request.","tdomf");
      $form .= "<script type='text/javascript' src='$sack_url'></script>\n";
      $form .= <<<EOT
<script type="text/javascript">
	//<!-- [CDATA[
	function ajaxProgressStart$form_id() {
		var w = jQuery('#ajaxProgress$form_id').width();
		var h = jQuery('#ajaxProgress$form_id').height();
		var offset = jQuery('#$form_name').offset();
		var x = offset.left + ((jQuery('#$form_name').width() - w) / 2);
		var y = offset.top + ((jQuery('#$form_name').height() - h) / 2);
		jQuery('#ajaxProgress$form_id').css({display: 'block', height: h + 'px', width: w + 'px', position: 'absolute', left: x + 'px', top: y + 'px', zIndex: '1000' });
		jQuery('#ajaxProgress$form_id').attr('class','progress');
		ajaxShadow$form_id();
	}
	function ajaxShadow$form_id() {
		var offset = jQuery('#$form_name').offset();
		var w = jQuery('#$form_name').width();
		var h = jQuery('#$form_name').height();
		jQuery('#shadow$form_id').css({ width: w + 'px', height: h + 'px', position: 'absolute', left: offset.left + 'px', top: offset.top + 'px' });
		jQuery('#shadow$form_id').css({zIndex: '999', display: 'block'});
		jQuery('#shadow$form_id').fadeTo('fast', 0.2);
	}
	function ajaxUnshadow$form_id() {
		jQuery('#shadow$form_id').fadeOut('fast', function() {jQuery('#shadow').hide()});
	}
	function ajaxProgressStop$form_id() {
		jQuery('#ajaxProgress$form_id').attr('class','hidden');
		jQuery('#ajaxProgress$form_id').hide();
		ajaxUnshadow$form_id();
	}
	function tdomfSubmit$form_id(action) {
		ajaxProgressStart$form_id();
		var mysack = new sack("$ajax_script" );
		mysack.execute = 1;
		mysack.method = 'POST';
		mysack.setVar( "tdomf_action", action );
		for(i=0; i<document.$form_name.elements.length; i++) {
			if(document.$form_name.elements[i].type == "checkbox") {
				if(document.$form_name.elements[i].checked == 1) {
					mysack.setVar(document.$form_name.elements[i].name,document.$form_name.elements[i].value);
				}
			} else {
				mysack.setVar(document.$form_name.elements[i].name,document.$form_name.elements[i].value);
			}
		}
		mysack.onError = function() { alert('$ajax_error' )};
		mysack.runAJAX();
		return true;
	}
	function tdomfDisplayMessage$form_id(message, mode) {
		if(mode == "full") {
			jQuery('#tdomf_form${form_id}_message').attr('class','hidden');
			document.getElementById('tdomf_form${form_id}_message').innerHTML = "";
			document.$form_name.innerHTML = message;
		} else if(mode == "preview") {
			jQuery('#tdomf_form${form_id}_message').attr('class','tdomf_form_preview');
			document.getElementById('tdomf_form${form_id}_message').innerHTML = message;
		} else {
			jQuery('#tdomf_form${form_id}_message').attr('class','tdomf_form_message');
			document.getElementById('tdomf_form${form_id}_message').innerHTML = message;
		}
		ajaxProgressStop$form_id();
	}
	function tdomfRedirect$form_id(url) {
		ajaxProgressStop$form_id();
		window.location = url;
	}
	//]] -->
</script>
EOT;
    if($hack) {
        $form .= "\n<!-- AJAX js end -->\n<!-- shadow required for disabling form during AJAX submit -->\n";
    }
    $form .= "<div id='shadow$form_id' class='shadow'></div>\n";
    if($hack) {
        $form .= "<!-- ajaxProgress holds the HTML to show during AJAX busy -->\n";
    }
    $form .= "<div id='ajaxProgress$form_id' class='hidden'>".__('Please wait a moment while your submission is processed...','tdomf')."</div>\n";
    if(!$hack) {
        $form .= "<div id='tdomf_form${form_id}_message' id='tdomf_form${form_id}_message' class='hidden'></div>";
    }
  } 
  
  $post_args = array();
  if(!$hack) {
     if(isset($form_data['tdomf_form_post_'.$form_id])) {
        $post_args = $form_data['tdomf_form_post_'.$form_id];
        unset($form_data['tdomf_form_post_'.$form_id]);
        tdomf_save_form_data($form_id,$form_data);
        if(isset($post_args['tdomf_post_message_'.$form_id])) {
           $form = $post_args['tdomf_post_message_'.$form_id];
           unset($form_data['tdomf_post_message_'.$form_id]);
           tdomf_save_form_data($form_id,$form_data);
        }
        if(isset($post_args['tdomf_no_form_'.$form_id])) {
           unset($post_args['tdomf_no_form_'.$form_id]);
           tdomf_save_form_data($form_id,$form_data);
           return $form;
        }
     }
  } else {
      $form .= TDOMF_MACRO_FORMMESSAGE."\n";
  }
  
  if($hack) {
        $form .= "<!-- form start -->\n";
  }
     
  $form .= "<form method=\"post\" action=\"".TDOMF_URLPATH."tdomf-form-post.php\" id='$form_name' name='$form_name' class='tdomf_form' >\n";
   
  // generate key
  //
  if($hack) {
      $form .= "\t".TDOMF_MACRO_FORMKEY."\n";
  } else {
      $form .= tdomf_generate_form_key($form_id);
  } 
  
  // Form id
  //
  $form .= "\t<input type='hidden' id='tdomf_form_id' name='tdomf_form_id' value='$form_id' />\n";

  if($hack) {
      $redirect_url = TDOMF_MACRO_FORMURL;
  } else {
      # use message id as re-direct because we *know* where this will appear on a non-hacked form
      #$redirect_url = $_SERVER['REQUEST_URI'].'#tdomf_form'.$form_id;
      $redirect_url = $_SERVER['REQUEST_URI']."#tdomf_form${form_id}_message";
  }
  $form .= "\t<input type='hidden' id='redirect' name='redirect' value='$redirect_url' />\n";
  
  // Process widgets
  //
  
  if($hack) {
      $widget_args = array( "before_widget"=>"\t<fieldset>\n",
                            "after_widget"=>"\n\t</fieldset>\n",
                             "before_title"=>"\t\t<legend>",
                             "after_title"=>"</legend>\n",
                             "tdomf_form_id"=>$form_id,
                             "mode"=>$mode);
      $form .= "\t<!-- widgets start -->\n";
      $widget_order = tdomf_get_widget_order($form_id);
      foreach($widget_order as $w) {
          if(!isset($widgets[$w])) {
              $form .= "\t%%WIDGET:$w%%\n";
          } else {
              $form .= "\t<!-- $w start -->\n";
              $form .= $widgets[$w]['cb']($widget_args,$widgets[$w]['params']);
              $form .= "\t<!-- $w end -->\n";
          }
      }
      $form .= "\t<!-- widgets end -->\n";
  } else {
      $widget_args = array_merge( array( "before_widget"=>"<fieldset>\n",
                                           "after_widget"=>"\n</fieldset>\n",
                                           "before_title"=>"<legend>",
                                           "after_title"=>"</legend>",
                                           "tdomf_form_id"=>$form_id,
                                           "mode"=>$mode),
                                    $post_args);
      $widget_order = tdomf_get_widget_order($form_id);
      foreach($widget_order as $w) {
          if(isset($widgets[$w])) {
              $form .= $widgets[$w]['cb']($widget_args,$widgets[$w]['params']);
          }
      }
  }
  
  // Form buttons
  //
  if($hack) {
        $form .= "\t<!-- form buttons start -->\n";
  }  
  $form .= "\t<table border='0' align='left'><tr>\n";
  if(tdomf_widget_is_preview_avaliable($form_id)) {
      $form .= "\t\t".'<td width="10px"><input type="submit" value="'.__("Preview","tdomf").'" name="tdomf_form'.$form_id.'_preview" id="tdomf_form'.$form_id.'_preview" onclick="tdomfSubmit'.$form_id."('preview'); return false;\" /></td>\n";
  }
  $form .= "\t\t".'<td width="10px"><input type="submit" value="'.__("Send","tdomf").'" name="tdomf_form'.$form_id.'_send" id="tdomf_form'.$form_id.'_send" onclick="tdomfSubmit'.$form_id."('post'); return false;\" /></td>\n";
  $form .= "\t</tr></table>\n";
  if($hack) {
        $form .= "\t<!-- form buttons end -->\n";
  }

  $form .= "</form>\n";

  if($hack) {
      $form .= "<!-- form end -->\n<!-- Form $form_id end -->\n";
  }
  
  return $form;
}

// Replaces <!--tdomf_formX--> or [tdomf_formX] with actual form
//
function tdomf_form_filter($content=''){
   if ('' == $content ||
       (preg_match('|<!--tdomf_form.*-->|', $content) <= 0 && preg_match('|\[tdomf_form.*\]|', $content) <= 0)) {
   	return $content;
   }

   $forms = array();
   if(preg_match_all('|<!--tdomf_form.*-->|', $content, $matches) > 0) {
     foreach($matches[0] as $match) {
       $match = str_replace('<!--tdomf_form','',trim($match));
       $match = intval(str_replace('-->','',$match));
       if(!isset($forms[$match])){
         $forms[$match] = tdomf_generate_form($match);
       }
     }
   }

   if(preg_match_all('|\[tdomf_form.*\]|', $content, $matches) > 0) {
     foreach($matches[0] as $match) {
       $match = str_replace('[tdomf_form','',trim($match));
       $match = intval(str_replace(']','',$match));
       if(!isset($forms[$match])){
         $forms[$match] = tdomf_generate_form($match);
       }
     }
   }

   foreach($forms as $id => $form ) {
     $content = preg_replace('|<!--tdomf_form$id-->|', '[tdomf_form$id]', $content);
     // prep form: the $ and \\ are special operators in preg_replace replacement string
     $form = str_replace('$','\\$',$form);
     $form = str_replace('\\\\','\\\\\\\\',$form);
     // make sure to swallow paragraph markers as well so the form is valid xhtml
     $content = preg_replace("|(<p>)*(\n)*\[tdomf_form$id\](\n)*(</p>)*|", $form, $content);
   }

   return $content;
}
add_filter('the_content', 'tdomf_form_filter');

function tdomf_get_form_data($form_id) {
   $type = get_option(TDOMF_OPTION_FORM_DATA_METHOD);
   if($type == "session") {
      if(!isset($_SESSION)) { @session_start(); }
      if(!isset($_SESSION)) {
         headers_sent($filename,$linenum);
         tdomf_log_message( "session_start() has not been called before generating form! Form will not work.",TDOMF_LOG_ERROR);
         if(!get_option(TDOMF_OPTION_DISABLE_ERROR_MESSAGES)) { ?>
            <p><font color=\"red\"><b>
            <?php _e('ERROR: <a href="http://www.google.com/search?client=opera&rls=en&q=php+session_start&sourceid=opera&ie=utf-8&oe=utf-8">session_start()</a> has not been called yet!',"tdomf"); ?>
            </b> <?php _e('This may be due to...','tdomf'); ?>
            <ol> <?php
            if ( !defined('WP_USE_THEMES') || !constant('WP_USE_THEMES') ) { ?>
              <li>
              <?php printf(__('Your theme does not use the get_header template tag. You can confirm this by using the default or classic Wordpress theme and seeing if this error appears. If it does not use get_header, then you must call session_start at the beginning of %s.',"tdomf"),$filename); ?>
              </li> <?php
            } ?> 
            <li>
            <?php printf(__('Another Plugin conflicts with TDOMF. To confirm this, disable all your plugins and then renable only TDOMF. If this error disappears than another plugin is causing the problem.',"tdomf"),$filename); ?>
            </li>
            </li></ol></font></p> <?php
         }
     }
     if(ini_get('register_globals')  && !TDOMF_HIDE_REGISTER_GLOBAL_ERROR){
       if(!get_option(TDOMF_OPTION_DISABLE_ERROR_MESSAGES)) { ?>
         <p><font color="red"><b>
         <?php _e('ERROR: <a href="http://ie2.php.net/register_globals"><i>register_globals</i></a> is enabled in your PHP environment!',"tdomf"); ?>
         </font></p>
       <?php }
      tdomf_log_message('register_globals is enabled!',TDOMF_LOG_ERROR);
     }
     if(isset($_SESSION['tdomf_form_data_'.$form_id])) { 
        return $_SESSION['tdomf_form_data_'.$form_id];
     } else {
        return array();
     }
   } else if($type == "db") {
     $data = tdomf_session_get();
     if(!is_array($data)) { return array(); }
     return $data;
   }
   tdomf_log_message("Invalid option set for FORM DATA METHOD: $type",TDOMF_LOG_ERROR);
   return array();
}

function tdomf_save_form_data($form_id,$form_data) {
   $type = get_option(TDOMF_OPTION_FORM_DATA_METHOD);
   if($type == "session") {
      $_SESSION['tdomf_form_data_'.$form_id] = $form_data;
   } else if($type == "db") {
      tdomf_session_set(0,$form_data);
   }
}

?>
