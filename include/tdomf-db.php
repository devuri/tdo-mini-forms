<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('TDOMF: You are not allowed to call this page directly.'); }

/* TODO: Look at AJAX again
 * TODO: Reset widgets for a specific form */

function tdomf_db_create_tables() {
  global $wpdb,$wp_roles;
  $table_form_name = $wpdb->prefix . TDOMF_DB_TABLE_FORMS;
  $table_widget_name = $wpdb->prefix . TDOMF_DB_TABLE_WIDGETS;

  if($wpdb->get_var("show tables like '$table_form_name'") != $table_form_name) {
    
     tdomf_log_message("$table_form_name does not exist. Will create it now...");
    
     $sql = "CREATE TABLE " . $table_form_name . " (
               form_id      bigint(20)   NOT NULL auto_increment,
               form_name    varchar(255) default NULL,
               form_options longtext,
               PRIMARY KEY  (form_id)
             );";
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
      
      // Now double check the table is created!
      //
      if($wpdb->get_var("show tables like '$table_form_name'") == $table_form_name) {
        
        if(get_option(TDOMF_VERSION_CURRENT) != false) {
          // we are importing...
          tdomf_log_message("$table_form_name created successfully. Importing default form now...",TDOMF_LOG_GOOD);
          
          // New form options
          //
          $form_name = $wpdb->escape(__('Default Form','tdomf'));
          //
          $form_options = array( TDOMF_OPTION_DESCRIPTION => __('Imported from default form','tdomf'),
                                 TDOMF_OPTION_CREATEDPAGES => false,
                                 TDOMF_OPTION_INCLUDED_YOUR_SUBMISSIONS => true,
                                 TDOMF_OPTION_WIDGET_INSTANCES => 10);
          //
          // Import from existing options
          //
          $form_options[TDOMF_ACCESS_ROLES] = get_option(TDOMF_ACCESS_ROLES);
          $form_options[TDOMF_NOTIFY_ROLES] = get_option(TDOMF_NOTIFY_ROLES);
          $form_options[TDOMF_DEFAULT_CATEGORY] = get_option(TDOMF_DEFAULT_CATEGORY);
          $form_options[TDOMF_OPTION_MODERATION] = get_option(TDOMF_OPTION_MODERATION);
          $form_options[TDOMF_OPTION_ALLOW_EVERYONE] = get_option(TDOMF_OPTION_ALLOW_EVERYONE);
          $form_options[TDOMF_OPTION_PREVIEW] = get_option(TDOMF_OPTION_PREVIEW);
          $form_options[TDOMF_OPTION_FROM_EMAIL] = get_option(TDOMF_OPTION_FROM_EMAIL);
          $form_options[TDOMF_OPTION_FORM_ORDER] = get_option(TDOMF_OPTION_FORM_ORDER);
          
          // Prepare for SQL 
          $form_options = maybe_serialize($form_options);
          
          // Now insert default form into table!
          $sql = "INSERT INTO $table_form_name" .
                "(form_name, form_options) " .
                "VALUES ('$form_name','".$wpdb->escape($form_options)."')";
          if($wpdb->query( $sql )) {
            
            tdomf_log_message("default form imported successfully into db table $table_form_name!",TDOMF_LOG_GOOD);
            
            //
            // Everything went well so we can get rid of the old options now!
            //
            delete_option(TDOMF_ACCESS_ROLES);
            delete_option(TDOMF_NOTIFY_ROLES);
            delete_option(TDOMF_DEFAULT_CATEGORY);
            delete_option(TDOMF_OPTION_MODERATION);
            delete_option(TDOMF_OPTION_ALLOW_EVERYONE);
            delete_option(TDOMF_OPTION_PREVIEW);
            delete_option(TDOMF_OPTION_FROM_EMAIL);
            delete_option(TDOMF_OPTION_FORM_ORDER);
  
            // Update capablities!
            //
            
            tdomf_log_message("Attempting to update '".TDOMF_CAPABILITY_CAN_SEE_FORM."' user capability to '".TDOMF_CAPABILITY_CAN_SEE_FORM."_1' ...",TDOMF_LOG_GOOD);
            
            if(!isset($wp_roles)) {
               $wp_roles = new WP_Roles();
            }
            $roles = $wp_roles->role_objects;
            foreach($roles as $role) {
              if(isset($role->capabilities[TDOMF_CAPABILITY_CAN_SEE_FORM])){
                 $role->remove_cap(TDOMF_CAPABILITY_CAN_SEE_FORM);
                 $role->add_cap(TDOMF_CAPABILITY_CAN_SEE_FORM.'_1');
              }
            }
            
            // We could attempt to update posts... but we're not going to.
            
          } else {
            tdomf_log_message("Failed to import default form into $table_form_name!",TDOMF_LOG_ERROR);
          }
        } else {
          tdomf_log_message("$table_form_name created successfully. Creating default form now...",TDOMF_LOG_GOOD);
          tdomf_create_form('Default Form');
        }
      } else {
         tdomf_log_message("Can't find db table $table_form_name! Table not created.",TDOMF_LOG_ERROR);
      }
  }
  
  
  if($wpdb->get_var("show tables like '$table_widget_name'") != $table_widget_name) {
    
    tdomf_log_message("$table_widget_name does not exist. Will create it now...");
    
     $sql = "CREATE TABLE " . $table_widget_name . " (
               id             bigint(20)   NOT NULL auto_increment,
               form_id        bigint(20)   NOT NULL default '0',
               widget_key     varchar(255) default NULL,
               widget_value   longtext,
               PRIMARY KEY    (id),
               KEY form_id    (form_id),
               KEY widget_key (widget_key)
             );";
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
      
      if($wpdb->get_var("show tables like '$table_widget_name'") == $table_widget_name) {
        
        tdomf_log_message("$table_widget_name created successfully. Importing widget settings for default form...");

        // default form id
        $form_id = 1;
        
        // scan for widget options
        $alloptions = wp_load_alloptions();
        foreach($alloptions as $id => $val) {
          if(preg_match('#^tdomf_.+#',$id)) {
            
            $widget_key = $wpdb->escape($id);
            $widget_value = $wpdb->escape(maybe_serialize(get_option($id)));
            
            // TODO: need to avoid copying up real options to the table!
            
            // Now insert into widget table
            $sql = "INSERT INTO $table_widget_name" .
                   "(form_id, widget_key, widget_value) " .
                   "VALUES ('$form_id','$widget_key','$widget_value')";
            if($wpdb->query( $sql )) {
               tdomf_log_message("Imported widget option $id into $table_widget_name!",TDOMF_LOG_GOOD);
               delete_option($id);
            } else {
               tdomf_log_message("Failed to import widget option $id into db table $table_widget_name!",TDOMF_LOG_ERROR);
            }
          }
        }
    } else {
      tdomf_log_message("Can't find db table $table_widget_name! Table not created.",TDOMF_LOG_ERROR);
    }
  }
  return true;
}

function tdomf_db_delete_tables() {
  global $wpdb;
  
  $table_form_name = $wpdb->prefix . TDOMF_DB_TABLE_FORMS;
  $table_widget_name = $wpdb->prefix . TDOMF_DB_TABLE_WIDGETS;

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  
  if($wpdb->get_var("show tables like '$table_form_name'") == $table_form_name) {
      tdomf_log_message("Deleting db table $table_form_name...");
      $sql = "DROP TABLE IF EXISTS " . $table_form_name . ";";
      if($wpdb->query($sql)) {
        tdomf_log_message("Db table $table_form_name deleted!");
      }
  }
  if($wpdb->get_var("show tables like '$table_widget_name'") == $table_widget_name) {
      tdomf_log_message("Deleting db table $table_widget_name...");
      $sql = "DROP TABLE IF EXISTS " . $table_widget_name . ";";
      if($wpdb->query($sql)) {
        tdomf_log_message("Db table $table_widget_name deleted!");
      }
  }   
  return false;
}

function tdomf_set_option_widget($key,$value,$form_id = 1) {
  global $wpdb;

  // check if option exists!
  //
  $table_name = $wpdb->prefix . TDOMF_DB_TABLE_WIDGETS;
  $query = "SELECT widget_value 
            FROM $table_name 
            WHERE widget_key = '" .$wpdb->escape($key)."'
                  AND form_id = '".$wpdb->escape($form_id)."'";
  $option = $wpdb->get_row( $query );

  // if option doesn't exist - add
  //
  if($option == NULL) {
    $value = maybe_serialize($value);    
    $query = "INSERT INTO $table_name" .
             "(form_id, widget_key, widget_value) " .
              "VALUES ('".$wpdb->escape($form_id)."',
                       '" .$wpdb->escape($key)."',
                       '".$wpdb->escape($value)."')";
    return $wpdb->query($query);
  } else {
    // if option does exist - check if it has changed
    //
    $current_value = maybe_unserialize($option->widget_value);
    if($current_value != $value) {
      $value = maybe_serialize($value);
      // it's changed! So update
      //
      $query = "UPDATE $table_name 
                SET widget_value = '".$wpdb->escape($value)."' 
                 WHERE widget_key = '" .$wpdb->escape($key)."'
                       AND form_id = '".$wpdb->escape($form_id)."'";
      return $wpdb->query($query);
    }
  }
  return false;
}

function tdomf_set_option_form($key,$value,$form_id = 1) {
  if($key == TDOMF_OPTION_NAME) {
    global $wpdb;
    $table_name = $wpdb->prefix . TDOMF_DB_TABLE_FORMS;
    $query = "UPDATE $table_name
              SET form_name = '".$wpdb->escape($value)."'
              WHERE form_id = '".$wpdb->escape($form_id)."'";
    return $wpdb->query($query);
  } else {
    $options = array( $key => $value);
    return tdomf_set_options_form($options,$form_id);
  }
}

function tdomf_delete_widgets($form_id) {
  if(tdomf_form_exists($form_id))
  {
    global $wpdb;
    $table_name = $wpdb->prefix . TDOMF_DB_TABLE_WIDGETS;
    $query = "DELETE FROM $table_name
              WHERE form_id = '".$wpdb->escape($form_id)."'";
    $wpdb->query($query);
  }
}

function tdomf_delete_form($form_id) {
  if(tdomf_form_exists($form_id))
  {
    global $wpdb;

    // Delete pages created with this form
    //
    $pages = tdomf_get_option_form(TDOMF_OPTION_CREATEDPAGES,$form_id);
    if($pages != false) {
       foreach($pages as $page_id) {
          if(get_permalink($page_id) != false) {
                wp_delete_post($page_id);
          }
        }
    }
    
    // Delete form options
    //
    $table_name = $wpdb->prefix . TDOMF_DB_TABLE_FORMS;
    $query = "DELETE FROM $table_name
              WHERE form_id = '".$wpdb->escape($form_id)."'";
    $wpdb->query($query);
    
    // Delete widget options
    //
    $table_name = $wpdb->prefix . TDOMF_DB_TABLE_WIDGETS;
    $query = "DELETE FROM $table_name
              WHERE form_id = '".$wpdb->escape($form_id)."'";
    $wpdb->query($query);
    
    // TODO: Remove capablities
    
    return true;
  }
  return false;
}

function tdomf_create_form($form_name = '',$options = array()) {
  global $wpdb;
  $defaults = array( TDOMF_OPTION_DESCRIPTION => '',
                     TDOMF_OPTION_CREATEDPAGES => false,
                     TDOMF_OPTION_INCLUDED_YOUR_SUBMISSIONS => true,
                     TDOMF_ACCESS_ROLES => false,
                     TDOMF_NOTIFY_ROLES => false,
                     TDOMF_DEFAULT_CATEGORY => 0,
                     TDOMF_OPTION_MODERATION => true,
                     TDOMF_OPTION_ALLOW_EVERYONE => true,
                     TDOMF_OPTION_PREVIEW => true,
                     TDOMF_OPTION_FROM_EMAIL => '',
                     TDOMF_OPTION_FORM_ORDER => false,
                     TDOMF_OPTION_WIDGET_INSTANCES => 10);
  $options = wp_parse_args($options,$defaults);
  $options = maybe_serialize($options);
  $table_name = $wpdb->prefix . TDOMF_DB_TABLE_FORMS;
  $sql = "INSERT INTO $table_name " .
         "(form_name, form_options) " .
         "VALUES ('$form_name','".$wpdb->escape($options)."')";
  $result = $wpdb->query( $sql );
  return $wpdb->insert_id;
}

function tdomf_copy_form($form_id) {
  global $wp_roles, $wpdb;

  $table_name = $wpdb->prefix . TDOMF_DB_TABLE_WIDGETS;
  
  // Copy form options
  //
  $form_name = sprintf(__("Copy of %s","tdomf"),tdomf_get_option_form(TDOMF_OPTION_NAME,$form_id));
  $form_to_copy_options = tdomf_get_options_form($form_id);
  if(empty($form_to_copy_options)) {
    return 0;
  }
  $options = wp_parse_args($options,$form_to_copy_options);
  $copied_form_id = tdomf_create_form($form_name,$options);

  // Reset the "created pages" option
  //
  tdomf_set_option_form(TDOMF_OPTION_CREATEDPAGES,false,$copied_form_id);
  
  //Copy widget options
  //
  $query = "SELECT * 
            FROM $table_name 
            WHERE form_id = '".$wpdb->escape($form_id)."'";
  $widgets = $wpdb->get_results( $query );
  foreach($widgets as $widget) {
    tdomf_set_option_widget($widget->widget_key,maybe_unserialize($widget->widget_value),$copied_form_id);
  }

  // Copy capablities
  //
  if($copied_form_id != 0) {
    if(!isset($wp_roles)) {
       $wp_roles = new WP_Roles();
    }
    $roles = $wp_roles->role_objects;
    foreach($roles as $role) {
       if(isset($role->capabilities[TDOMF_CAPABILITY_CAN_SEE_FORM.'_'.$form_id])){
          $role->add_cap(TDOMF_CAPABILITY_CAN_SEE_FORM.'_'.$copied_form_id);
       }
    }
  }
  return $copied_form_id;
}

function tdomf_set_options_form($options,$form_id = 1) {
  global $wpdb;
  $defaults = tdomf_get_options_form($form_id);
  if(empty($defaults)) {
        $defaults = array( TDOMF_OPTION_DESCRIPTION => '',
                           TDOMF_OPTION_CREATEDPAGES => false,
                           TDOMF_OPTION_INCLUDED_YOUR_SUBMISSIONS => true,
                           TDOMF_ACCESS_ROLES => false,
                           TDOMF_NOTIFY_ROLES => false,
                           TDOMF_DEFAULT_CATEGORY => 0,
                           TDOMF_OPTION_MODERATION => true,
                           TDOMF_OPTION_ALLOW_EVERYONE => true,
                           TDOMF_OPTION_PREVIEW => true,
                           TDOMF_OPTION_FROM_EMAIL => '',
                           TDOMF_OPTION_FORM_ORDER => false);
  }
  $options = wp_parse_args($options,$defaults);
  $options = maybe_serialize($options);
  $table_name = $wpdb->prefix . TDOMF_DB_TABLE_FORMS;
  $query = "UPDATE $table_name 
            SET form_options = '".$wpdb->escape($options)."'
            WHERE form_id = '".$wpdb->escape($form_id)."'";
  return $wpdb->query($query);
}

function tdomf_get_option_widget($key,$form_id = 1) {
  global $wpdb;
  $table_name = $wpdb->prefix . TDOMF_DB_TABLE_WIDGETS;
  $query = "SELECT widget_value 
            FROM $table_name 
            WHERE widget_key = '" .$wpdb->escape($key)."'
                  AND form_id = '".$wpdb->escape($form_id)."'";
  $option = $wpdb->get_row( $query );
  if($option != NULL) {
    return maybe_unserialize($option->widget_value);
  } else {
    $option = tdomf_get_option_form($key,$form_id);
    if($option != false) {
      return $option;
    } else {
      return false;
    }
  }
}

function tdomf_get_widgets_form($form_id) {
  global $wpdb;
  $table_name = $wpdb->prefix . TDOMF_DB_TABLE_WIDGETS;
  $query = "SELECT * 
            FROM $table_name 
            WHERE form_id = '".$wpdb->escape($form_id)."'";
  return $wpdb->get_results( $query );
}

function tdomf_get_options_form($form_id = 1) {
  global $wpdb;
  $table_name = $wpdb->prefix . TDOMF_DB_TABLE_FORMS;
  $query = "SELECT form_options 
            FROM $table_name 
            WHERE form_id = '" .$wpdb->escape($form_id)."'";
  $options = $wpdb->get_row( $query );
  if($options == NULL) {
    return array();
  } else {
    return maybe_unserialize($options->form_options);
  }
  return false;
}

function tdomf_get_option_form($key,$form_id = 1) {
  global $wpdb;
  $table_name = $wpdb->prefix . TDOMF_DB_TABLE_FORMS;
  if($key == TDOMF_OPTION_NAME) {
    $query = "SELECT form_name 
              FROM $table_name 
              WHERE form_id = '" .$wpdb->escape($form_id)."'";
    return $wpdb->get_var( $query );
  } else {
    $options = tdomf_get_options_form($form_id);
    if(!empty($options) && isset($options[$key])) {
      return $options[$key];
    } else if(get_option($key) != false) {
      return get_option($key);
    }
  }
  return false;
}

function tdomf_get_first_form_id() {
  $form_ids = tdomf_get_form_ids();
  return $form_ids[0]->form_id;
}

function tdomf_get_form_ids(){
  global $wpdb;
  $table_name = $wpdb->prefix . TDOMF_DB_TABLE_FORMS;
  $query = "SELECT form_id 
            FROM $table_name 
            ORDER BY form_id ASC";
  return $wpdb->get_results($query);
}

function tdomf_form_exists($form_id) {
  global $wpdb;
  $table_name = $wpdb->prefix . TDOMF_DB_TABLE_FORMS;
  $query = "SELECT * 
            FROM $table_name 
            WHERE form_id = '" .$wpdb->escape($form_id)."'";
  $result = $wpdb->get_row( $query );
  if($result == NULL) {
    return false;
  }
  return true;
}

function tdomf_is_moderation_in_use(){
  $form_ids = tdomf_get_form_ids();
  $retValue = false;
  foreach($form_ids as $form_id) {
    if(tdomf_get_option_form(TDOMF_OPTION_MODERATION,$form_id->form_id)){
      $retValue = true;
      break;
    }
  }
  return $retValue;
}

?>
