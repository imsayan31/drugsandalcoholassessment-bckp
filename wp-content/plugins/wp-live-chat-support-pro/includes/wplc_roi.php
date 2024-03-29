<?php 
/*
 * Handles WPLC roi functionality
*/
global $wplc_tblname_chat_roi_goals;
global $wplc_tblname_chat_roi_conversions;

$wplc_tblname_chat_roi_goals = $wpdb->prefix . "wplc_roi_goals";
$wplc_tblname_chat_roi_conversions = $wpdb->prefix . "wplc_roi_conversions";


/*
 * Hooks into 'wp_print_footer_scripts' to check if the a goal is present
*/
add_action("wp_print_footer_scripts", "wplc_pro_roi_goals_page_check", 10);
function wplc_pro_roi_goals_page_check(){
	$post_id = url_to_postid($_SERVER['REQUEST_URI']);
	$goal_id = wplc_pro_roi_check_page_id($post_id);
	if($goal_id){
		//This page matches a goal
		if(isset($_COOKIE['wplc_had_chat']) && ( $_COOKIE['wplc_had_chat'] === 'true' || $_COOKIE['wplc_had_chat'] === true )){
			if(isset($_COOKIE['wplc_cid'])){
				//There is a CID - Try log the conversion
				$cid = intval($_COOKIE['wplc_cid']);
				$goal_id = intval($goal_id);
				if(wplc_pro_roi_conversion($cid, $goal_id)){
					//Added
				}
			}
		}
	}
}

/*
 * Updates/Creates the required tables in order to use roi in WPLC
*/
add_action("wplc_pro_update_db_hook", "wplc_pro_update_db_roi", 10);
function wplc_pro_update_db_roi(){
	global $wplc_tblname_chat_roi_goals;
	global $wplc_tblname_chat_roi_conversions;

	$wplc_roi_goal_sql = "
        CREATE TABLE " . $wplc_tblname_chat_roi_goals . " (
          id int(11) NOT NULL AUTO_INCREMENT,
          name varchar(700) NOT NULL,
          pages int(11) NOT NULL,
          overview varchar(700) NULL,
          amount float NOT NULL,
          PRIMARY KEY  (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
    ";

    $wplc_roi_conversion_sql = "
        CREATE TABLE " . $wplc_tblname_chat_roi_conversions . " (
          id int(11) NOT NULL AUTO_INCREMENT,
          goal_id int(11) NOT NULL,
          chat_id int(11) NOT NULL,
          timestamp datetime NOT NULL,
          PRIMARY KEY  (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
    ";

    dbDelta($wplc_roi_goal_sql);
    dbDelta($wplc_roi_conversion_sql);
}

/*
 * Adds a menu item to WPLC for the roi Goal Area
*/
add_action("wplc_hook_menu_mid","wplc_pro_roi_menu",10,1);
function wplc_pro_roi_menu($cap){
	add_submenu_page('wplivechat-menu', __('ROI Goals', 'wplivechat'), __('ROI Goals', 'edit_posts'), $cap[0], 'wplc-pro-roi-goals', 'wplc_pro_roi_goals_page');
}

/*
 * Handles creation of the roi Goals Area
*/
function wplc_pro_roi_goals_page(){

	wplc_enqueue_admin_styles();
	wplc_pro_roi_version_notice();
	
	$wplc_add_goal_btn = isset($_GET['wplc_action']) ? "" : "<a href='?page=wplc-pro-roi-goals&wplc_action=add_goal' class='wplc_add_new_btn'>". __("Add New", 'wp-livechat') ."</a>";
	$wplc_view_reports_btn = isset($_GET['wplc_action']) ? "" : "<a href='?page=wplc-pro-reporting#rio_reports' class='wplc_add_new_btn'>". __("View Reports", 'wp-livechat') ."</a>";

	$content = "<div class='wrap wplc_wrap'>";
    $content .= "<h2>".__('WP Live Chat Support ROI Goals', 'wp-livechat')." (beta) " . $wplc_add_goal_btn . " " . $wplc_view_reports_btn . "</h2>";
   	
   	if(isset($_GET['wplc_action']) && ($_GET['wplc_action'] == "add_goal" || $_GET['wplc_action'] == "edit_goal")){
		$content .= wplc_pro_get_add_goal_content();
    } else if(isset($_GET['wplc_action']) && ($_GET['wplc_action'] == "delete_goal")){
    	$content .= wplc_pro_delete_goal_content();
    } else {
    	$content .= wplc_pro_get_roi_goals_table(); 	
    }

    $content .= "</div>"; //Close Wrap
    

    echo $content;
}

/*
 * Version monitor
*/
function wplc_pro_roi_version_notice(){
	global $wplc_version;
    if (intval(str_replace(".","",$wplc_version)) < 6207) {
    	echo "<div class='update-nag' style='padding-top:0px;margin-top:5px;margin-bottom:10px;'>";
  			echo "<p>In order to use ROI Goals, please ensure you are using the latest basic version (version 6.2.06 or newer).</p>";
			echo "<a title='Update Now' href='./update-core.php' class='button button-primary'>".__("Update now" ,"wp-livechat")."</a>";
		echo "</div>";
    }
}

/*
 * Returns the roi Goals table
*/
function wplc_pro_get_roi_goals_table(){
	$content = "";

  	$results = wplc_get_all_goals();


	$content .= "<table class=\"wp-list-table wplc_list_table widefat fixed \" cellspacing=\"0\" style='width:98%'>";
	$content .= 	"<thead>";
  	$content .= 		"<tr>";
    $content .= 			"<th scope='col'><span>" . __("ID", "wplivechat") . "</span></th>";
    $content .= 			"<th scope='col'><span>" . __("Name", "wplivechat") . "</span></th>";
    $content .= 			"<th scope='col'>" . __("Overview", "wplivechat") . "</th>";
    $content .= 			"<th scope='col'>" . __("Page", "wplivechat") . "</th>";
    $content .= 			"<th scope='col'>" . __("Value", "wplivechat") . "</th>";
    $content .= 			"<th scope='col'><span>" . __("Action", "wplivechat") . "</span></th>";
    $content .= 		"</tr>";
  	$content .= 	"</thead>";

  	
  	if($results){
  		foreach ($results as $result) {
  			$roi_goal_actions = "<a class='button' href='?page=wplc-pro-roi-goals&wplc_action=edit_goal&goal_id=".$result->id."'>".__("Edit", "wp-livechat")."</a> ";
  			$roi_goal_actions .= "<a class='button' href='?page=wplc-pro-roi-goals&wplc_action=delete_goal&goal_id=".$result->id."'>".__("Delete", "wp-livechat")."</a> ";

  			$content .= "<tr>";
  			$content .= 	"<td>".$result->id."</td>";
  			$content .= 	"<td>".$result->name."</td>";
  			$content .= 	"<td>".trim(substr(strip_tags($result->overview), 0, 120))."</td>";
  			$content .= 	"<td>".(strip_tags($result->pages) == "" ? __("None", "wp-livechat") : strip_tags($result->pages))."</td>";
  			$content .= 	"<td>".$result->amount."</td>";
  			$content .= 	"<td>".$roi_goal_actions."</td>";
  			$content .= "</tr>";
  			
  		}
  	} else {
  		$content .= "<tr><td>".__("No ROI Goals Found...", "wp-livechat")."</td><td></td><td></td><td></td><td></td></tr>";
  	}

  	$content .= 	"</table>";
	
	return $content;
}

/*
 * Return all goals from database
*/
function wplc_get_all_goals(){
	global $wpdb;
    global $wplc_tblname_chat_roi_goals;
    
    $sql = "SELECT * FROM $wplc_tblname_chat_roi_goals"; 

    $results =  $wpdb->get_results($sql);
    if($wpdb->num_rows){
    	return $results;
    } else {
    	return false;
    } 
}

/*
 * Create the 'Add new' or 'Edit' Goal page
*/

function wplc_pro_get_add_goal_content(){

	
	$content = "";

	//Content Vars
	$goal_name = "";
	$goal_overview = "";
	$goal_page = "";
	$goal_value = "";

	$header_array = wplc_pro_goal_admin_head();
	
	if($header_array){
		if(isset($header_array['wplc_goal_name'])){ $goal_name = $header_array['wplc_goal_name']; }
		if(isset($header_array['wplc_goal_overview'])){ $goal_overview = $header_array['wplc_goal_overview']; }
		if(isset($header_array['wplc_goal_page'])){ $goal_page = intval($header_array['wplc_goal_page']); }
		if(isset($header_array['wplc_goal_value'])){ $goal_value = floatval($header_array['wplc_goal_value']); }
	}


	$pages_on_site = wplc_pro_goal_dropdown_selector("wplc_goal_page", intval($goal_page));

	$wplc_submit_label = (isset($_GET['wplc_action']) && $_GET['wplc_action'] !== "edit_goal" ? "Create Goal" : "Edit Goal"); //Default

	$content .= "<form method='POST'>";
	$content .= "<table class=\"wp-list-table wplc_list_table widefat fixed form-table\" cellspacing=\"0\" style='width:50%'>";

  	$content .= 	"<tr>";
    $content .= 		"<td>".__("Goal Name", "wp-livechat").":</td>";
    $content .= 		"<td><input type='text' name='wplc_goal_name' value='$goal_name'></td>";
    $content .= 	"</tr>";

    $content .= 	"<tr>";
    $content .= 		"<td>".__("Goal Overview", "wp-livechat").":</td>";
    $content .= 		"<td><input type='text' name='wplc_goal_overview' value='$goal_overview'></td>";
    $content .= 	"</tr>";

    $content .= 	"<tr>";
    $content .= 		"<td>".__("Goal Page", "wp-livechat").":</td>";
    $content .= 		"<td>";

    $content .= $pages_on_site;

    $content .= 		"</td>";
    $content .= 	"</tr>";

    $content .= 	"<tr>";
    $content .= 		"<td>".__("Goal Value", "wp-livechat").":</td>";
    $content .= 		"<td><input type='text' name='wplc_goal_value' value='$goal_value'></td>";
    $content .= 	"</tr>";

    $content .= 	"<tr>";
    $content .= 		"<td></td>";
    $content .= 		"<td><input class='button button-primary' type='submit' name='wplc_goal_submit' value='".__($wplc_submit_label, "wp-livechat")."'> <a href='".admin_url()."admin.php?page=wplc-pro-roi-goals"."' class='button'>".__("Close", "wp-livechat")."</a></td>";
    $content .= 	"</tr>";

	$content .= "</table>";
	$content .= "</form>";

	if($header_array){
		if(count($header_array["errors"]) >= 1){
			$content .= "<div class='update-nag'>";
			$content .= "<strong>".__("Please review your submission", "wp-livechat").":</strong>";
			$content .= 	"<ul style='list-style:initial;'>";
			for($i = 0; $i < count($header_array["errors"]); $i++){
				$content .= 	"<li style='margin-left: 25px;'>".__($header_array["errors"][$i], "wp-livechat")."</li>";
			}
			$content .= 	"</ul>";
			$content .= "</div>";
		}

		if(isset($header_array["success"])){
			$content .= "<div class='update-nag' style='border-color:#67d552;'>";
			$content .= "<strong>".__($header_array["success"], "wp-livechat")."</strong>";
			$content .= "</div>";
		}
	}
	

	return $content;
}

/*
 * Generates custom dropdown for Posts and Pages
*/
function wplc_pro_goal_dropdown_selector($name, $selected_value){
    $r = array(
        'depth' 	=> 0, 
        'child_of' 	=> 0,
        'selected' 	=> $selected_value, 
        'echo' 		=> false,
        'name' 		=> $name, 
        'id' 		=> '',
        'class' 	=> '',
        'show_option_none' 		=> '', 
        'show_option_no_change' => '',
        'option_none_value' 	=> '',
        'value_field' 			=> 'ID',
    );
 	
 	$pages = get_pages($r);
 	$posts = get_posts(array('posts_per_page' => -1));

 	$posts_pages = array_merge($pages,$posts);

    
    $output = '';
    if ( empty( $r['id'] ) ) {
        $r['id'] = $r['name'];
    }
 
    if ( ! empty( $posts_pages ) ) {
        $class = '';
        if ( ! empty( $r['class'] ) ) {
            $class = " class='" . esc_attr( $r['class'] ) . "'";
        }
 
        $output = "<select name='" . esc_attr( $r['name'] ) . "'" . $class . " id='" . esc_attr( $r['id'] ) . "' value=".intval($selected_value).">\n";
        
        foreach ($posts_pages as $key => $value) {
        	$output .= "\t<option value='".$value->ID."' ".(intval($value->ID) === intval($selected_value) ? "selected" : "").">" . $value->ID . " - " . $value->post_title . "</option>\n";
        }
           
        $output .= "</select>\n";
    }

    $html = $output;
 
    if ( $r['echo'] ) {
        echo $html;
    }
    return $html;
}


/*
 * Handles all the head stuff
*/
function wplc_pro_goal_admin_head(){
	if(isset($_GET['wplc_action'])){
		$return_array = array();
		$form_valid = true;
		if(isset($_POST['wplc_goal_submit'])){
			$return_array["errors"] = array();

			if(isset($_POST['wplc_goal_name']) && $_POST['wplc_goal_name'] !== ""){
				$return_array["wplc_goal_name"] = $_POST['wplc_goal_name'];
			} else {
				$return_array["errors"][count($return_array["errors"]) >= 1 ? count($return_array["errors"]) : 0] = "Name cannot be empty";
				$form_valid = false; //No Longer Valid
			}

			if(isset($_POST['wplc_goal_overview'])){
				$return_array["wplc_goal_overview"] = $_POST['wplc_goal_overview'];
			} else {
				$return_array["wplc_goal_overview"] = "";
			}

			if(isset($_POST['wplc_goal_page'])){
				$return_array["wplc_goal_page"] = $_POST['wplc_goal_page'];
			} else {
				$return_array["wplc_goal_page"] = "";
			}

			if(isset($_POST['wplc_goal_value'])){
				$return_array["wplc_goal_value"] = $_POST['wplc_goal_value'];
			} else {
				$return_array["wplc_goal_value"] = "";
			}
		}

		if($_GET['wplc_action'] == "add_goal"){
				if($form_valid && isset($_POST['wplc_goal_submit'])){
					//All good continue
					if(wplc_add_goal($return_array)){
						//Redirect here
						echo "<script> window.location = '".admin_url()."admin.php?page=wplc-pro-roi-goals"."';</script>";
					}
				} else {
					return $return_array; //Return Posted Data
				}
		} else if ($_GET['wplc_action'] == "edit_goal"){
			//Editing now
			$edit_array = array();
			$edit_array["errors"] = array();
			if (isset($return_array["errors"])) { $edit_array["errors"] = $return_array["errors"];  }

			//Submit data first
			if($form_valid && isset($_POST['wplc_goal_submit'])){
				//All good continue
				if(isset($_GET['goal_id'])){
					if(wplc_edit_goal($return_array, intval($_GET['goal_id']))){
						//Show edit message
						$edit_array['success'] = "<div>".__("Goal has been edited.", "wp-livechat")."</div>";
					}
				} else {
					$edit_array["errors"][count($edit_array["errors"]) >= 1 ? count($edit_array["errors"]) : 0] = "Goal ID not found";
				}
			}

			$goal_data = wplc_get_goal(intval($_GET['goal_id']));
			if($goal_data){
				if($goal_data !== false && is_array($goal_data)){					
					//Got the data
					if(isset($goal_data[0]->name) && $goal_data[0]->name !== ""){ $edit_array["wplc_goal_name"] = $goal_data[0]->name; }
					if(isset($goal_data[0]->overview) && $goal_data[0]->overview !== ""){ $edit_array["wplc_goal_overview"] = $goal_data[0]->overview; }
					if(isset($goal_data[0]->pages) && $goal_data[0]->pages !== ""){ $edit_array['wplc_goal_page'] = $goal_data[0]->pages ; }
					if(isset($goal_data[0]->amount)){ $edit_array["wplc_goal_value"] = $goal_data[0]->amount; }
				}
			} else{
				$edit_array["errors"][count($edit_array["errors"]) >= 1 ? count($edit_array["errors"]) : 0] = "Goal ID not found";
			}

			return $edit_array; //Return Server Data
		}else if($_GET['wplc_action'] == "delete_goal"){
			$delete_array = array();
			if(isset($_GET['goal_id'])){
				$goal_data = wplc_get_goal(intval($_GET['goal_id']));
				if($goal_data){
					$delete_array["name"] = $goal_data[0]->name;
				}

				if(isset($_POST['delete_confirm'])){
					//Delete now
					if(wplc_delete_goal(intval($_GET['goal_id']))){
						//Success
					}
					echo "<script> window.location = '".admin_url()."admin.php?page=wplc-pro-roi-goals"."';</script>";
				}
			}
			return $delete_array;
		}else {
			return false;
		}
	}else{
		return false;
	}
}

/*
 * Adds a new Goal
*/
function wplc_add_goal($goal_data){
	global $wpdb;
    global $wplc_tblname_chat_roi_goals;
	if($goal_data){
		$goal_name;
		$goal_overview;
		$goal_page;
		$goal_value;

		//Validation - 1
		if($goal_data['wplc_goal_name'] != ""){ $goal_name = $goal_data['wplc_goal_name']; } else { return false; }
		if($goal_data['wplc_goal_overview'] != ""){ $goal_overview = $goal_data['wplc_goal_overview']; } else { $goal_overview = ""; }
		if($goal_data['wplc_goal_page'] != ""){ $goal_page = intval($goal_data['wplc_goal_page']); } else { return false; }
		if($goal_data['wplc_goal_value'] != ""){ $goal_value = $goal_data['wplc_goal_value']; } else { return false; }
		
		//Validation - 2 
		$goal_name = esc_attr($goal_name);
		$goal_overview = esc_attr($goal_overview);

		$sql = "INSERT INTO $wplc_tblname_chat_roi_goals SET `name` = '".$goal_name."', `pages` = '".$goal_page."', `overview` = '".$goal_overview."', `amount` = '".$goal_value."' ";
       	$wpdb->query($sql);
        if ($wpdb->last_error) { 
            return false;  
        } else {
            return true;
        } 
	}
}

/*
 * Edit a Goal
*/
function wplc_edit_goal($goal_data, $goal_id){
	global $wpdb;
    global $wplc_tblname_chat_roi_goals;
	if($goal_data){
		$goal_name;
		$goal_overview;
		$goal_page;
		$goal_value;

		//Validation - 1
		if($goal_data['wplc_goal_name'] != ""){ $goal_name = $goal_data['wplc_goal_name']; } else { return false; }
		if($goal_data['wplc_goal_overview'] != ""){ $goal_overview = $goal_data['wplc_goal_overview']; } else { $goal_overview = ""; }
		if($goal_data['wplc_goal_page'] != ""){ $goal_page = intval($goal_data['wplc_goal_page']); } else { return false; }
		if($goal_data['wplc_goal_value'] != ""){ $goal_value = floatval($goal_data['wplc_goal_value']); } else { return false; }
		
		//Validation - 2 
		$goal_name = esc_attr($goal_name);
		$goal_overview = esc_attr($goal_overview);

		$goal_id = intval($goal_id);
		$sql = "UPDATE $wplc_tblname_chat_roi_goals SET `name` = '".$goal_name."', `pages` = '".$goal_page."', `overview` = '".$goal_overview."', `amount` = '".$goal_value."' WHERE `id` = '".$goal_id."' ";
       	$wpdb->query($sql);
        if ($wpdb->last_error) { 
            return false;  
        } else {
            return true;
        } 
	}
}

/*
 * Removes a Goal
*/
function wplc_delete_goal($goal_id){
	global $wpdb;
	global $wplc_tblname_chat_roi_goals;
	$goal_id = intval($goal_id);
	$sql = "DELETE FROM $wplc_tblname_chat_roi_goals WHERE `id` = '".$goal_id."' ";
   	$wpdb->query($sql);
    if ($wpdb->last_error) { 
        return false;  
    } else {
        return true;
    } 
}

/*
 * Retrieved one goal
*/
function wplc_get_goal($goal_id){
	global $wpdb;
    global $wplc_tblname_chat_roi_goals;
    
    $goal_id = intval($goal_id);

    $sql = "SELECT * FROM $wplc_tblname_chat_roi_goals WHERE `id` = '$goal_id'"; 

    $results =  $wpdb->get_results($sql);
    if($wpdb->num_rows){
    	return $results;
    } else {
    	return false;
    }
}

/*
 * Handles confirmation prior to deleteing a goal
*/
function wplc_pro_delete_goal_content(){
	$header_array = wplc_pro_goal_admin_head();
	$goal_name = "";
	if($header_array){
		if(isset($header_array["wplc_goal_name"])){ $goal_name = $header_array["wplc_goal_name"];}
	}

	$content = "";
	if( (isset($_GET['wplc_action']) & isset($_GET['goal_id']))&& ($_GET['wplc_action'] == "delete_goal" && $_GET['goal_id'] != "")){
		
		$content .= "<form method='POST'>";
		$content .= 	"<table class=\"wp-list-table wplc_list_table widefat fixed form-table\" cellspacing=\"0\" style='width:50%'>";
		$content .= 		"<tr>";
		$content .= 			"<td>";
		$content .= 				__("Are you sure you would like to delete goal") . ": <strong>" . $goal_name . "</strong>";
		$content .= 			"</td>";
		$content .= 		"</tr>";
		$content .= 		"<tr>";
		$content .= 			"<td>";
		$content .= 				"<input type='submit' class='button' name='delete_confirm' value='".__("Delete", "wp-livechat")."'>";
		$content .= 				" <a href='".admin_url()."admin.php?page=wplc-pro-roi-goals' class='button'>".__("Cancel", "wp-livechat")."</a>";
		$content .= 			"</td>";
		$content .= 		"</tr>";
	  	$content .= 	"</table>";
	  	$content .= "</form>";
	}
    
    return $content;
}

/*
 * Checks to see if a page id matches on of our ROI Goals
*/
function wplc_pro_roi_check_page_id($pid){
	$pid = intval($pid);
	$goals = wplc_get_all_goals();
	$matched = false;
	if($goals){
  		foreach ($goals as $goal) {
  			if(intval($goal->pages) == $pid){
  				return $goal->id;
  			}
  		}
  	}

  	return $matched;
}

/*
 * Handles adding a conversion to the table
*/
function wplc_pro_roi_conversion($cid, $goal_id){
	global $wpdb;
	global $wplc_tblname_chat_roi_conversions;

	if(isset($cid) && isset($goal_id)){
		if(wplc_pro_roi_safe_to_add($cid)){
			//We can add it now
			$cid = intval($cid);
			$goal_id = intval($goal_id);		

			$sql = "INSERT INTO $wplc_tblname_chat_roi_conversions SET `goal_id` = '".$goal_id."', `chat_id` = '".$cid."', `timestamp` = '".date("Y-m-d H:i:s")."' ";
		   	$wpdb->query($sql);
		    if ($wpdb->last_error) { 
		        return false;  
		    } else {
		        return true;
		    } 
		}
	}
}

/*
 * Checks if a conversion of this chat has been made before
*/
function wplc_pro_roi_safe_to_add($cid){
	global $wpdb;
    global $wplc_tblname_chat_roi_conversions;
    
    $cid = intval($cid);

    $sql = "SELECT * FROM $wplc_tblname_chat_roi_conversions WHERE `chat_id` = '$cid'"; 

    $results =  $wpdb->get_results($sql);
    if($wpdb->num_rows){
    	return false; //Already converted - dont add or update
    } else {
    	return true;
    }
}

/*
 * Adds Reporting Tab for ROI
*/
add_filter('wplc_reporting_tabs', 'wplc_reporting_tabs_roi_reporting_tab', 10, 1);
function wplc_reporting_tabs_roi_reporting_tab($tabs_array){
	$tabs_array['rio_reports'] = __("ROI Reporting", "wp-livechat");
	return $tabs_array;
}

/*
 * Adds Reporting Content for ROI
*/
add_filter('wplc_reporting_tab_content', 'wplc_reporting_tabs_roi_reporting_content', 10, 1);
function wplc_reporting_tabs_roi_reporting_content($tabs_array){
	wplc_enqueue_admin_styles();
	
	wp_register_script('wplc_roi_reporting_js', plugins_url('js/wplc_admin_roi_reporting.js', __FILE__), array('jquery'));
    wp_enqueue_script('wplc_roi_reporting_js');

	$content = "<h3>".__("Goal Statistics", "wp-livechat")."</h3>";

	$content .= "<div class='wplc_roi_report_list'>";

	$goals = wplc_get_all_goals();
	if($goals){
		$count = 0;
		foreach ($goals as $goal) {
			$content .= "<div class='wplc_roi_report_list_item' id='wplc_roi_report_list_item_".$count."' goal='".$goal->id."'>".$goal->name."</div>";
			$count ++;
		}
	} else{
		$content .= __("No Goals Found", "wplivechat");
		$content .= " <small><a href='?page=wplc-pro-roi-goals'>Add Goal</a></small>";
	}

	$content .= "</div>";

	$content .= "<div class='wplc_roi_report_content'>";

	$content .= "<select name='wplc_roi_report_date_selector' id='wplc_roi_report_date_selector'>";
	$content .= 	"<option value='0'>". __("All", "wplivechat") ."</option>";
	$content .= 	"<option value='1'>". __("Last 30 Days", "wplivechat") ."</option>";
	$content .= 	"<option value='2'>". __("Last 15 Days", "wplivechat") ."</option>";
	$content .= 	"<option value='3'>". __("Last 7 Days", "wplivechat") ."</option>";
	$content .= 	"<option value='4'>". __("Last 24 Hours", "wplivechat") ."</option>";
	$content .= "</select>";

	$content .= 	"<div class='wplc_roi_report_content_inner'>";

	$content .= 	"</div>";
	$content .= "</div>";


	$tabs_array['rio_reports'] = $content;
	return $tabs_array;
}

/*
 * Handles Ajax data request
*/
add_action('wp_ajax_get_goal_data', 'wplc_reporting_roi_ajax');
function wplc_reporting_roi_ajax(){
 	if($_POST['action'] == "get_goal_data") {
 		if(isset($_POST['goal_id']) && isset($_POST['term'])){
 			$goal_id = intval($_POST['goal_id']);
 			$term = intval($_POST['term']);

 			$stats_content = wplc_get_goal_stats($goal_id, $term);
 			
 			echo json_encode($stats_content);

 			die();
 		}
 	}
}

/*
 * Creates a stats html page for ajax call 
*/
function wplc_get_goal_stats($goal_id, $term){
	$goal_id = intval($goal_id);
	$goal_info = wplc_get_goal($goal_id);

	$content = "";
	if($goal_info){
		$content .= "<h3>" . $goal_info[0]->name . "</h3>";

		$content .= "<hr>";

		$conversion_info = wplc_get_conversions_for_goal($goal_id, $term);
		if($conversion_info){
			$count_total = count($conversion_info);
			$amount_total = $count_total * floatval($goal_info[0]->amount);
			
			$content .= "<div style='width:100%'>";
			$content .= 	"<div style='width: 33%; display: inline-block; vertical-align: top; text-align: center;'>";
			$content .= 		"<strong>" . __("Value Per Conversion", "wplivechat") . "</strong>";
			$content .= 		"<p>" . $goal_info[0]->amount . "</p>";
			$content .= 	"</div>";


			$content .= 	"<div style='width: 33%; display: inline-block; vertical-align: top; text-align: center;'>";
			$content .= 		"<strong>" .  __("Total Value", "wplivechat") . "</strong>";
			$content .= 		"<p>" . $amount_total . "</p>";
			$content .= 	"</div>";

			$content .= 	"<div style='width: 33%; display: inline-block; vertical-align: top; text-align: center;'>";
			$content .= 		"<strong>" .  __("Total Conversions", "wplivechat") . "</strong>";
			$content .= 		"<p>" . $count_total . "</p>";
			$content .= 	"</div>";
			$content .= "</div><br><br>";

			$agent_count_array = array();
			$date_array = array();

			foreach ($conversion_info as $conversion) {
				$chat_data = wplc_get_chat_data($conversion->chat_id);
				if(!array_key_exists($chat_data->agent_id, $agent_count_array)){
					$user = get_user_by('ID', $chat_data->agent_id)->display_name;

					$agent_count_array[$chat_data->agent_id] = array();
					$agent_count_array[$chat_data->agent_id]['count'] = 0;
					$agent_count_array[$chat_data->agent_id]['name'] = $user;
					//$agent_count_array[$chat_data->agent_id]['chats'] = array();
				}
				
				$agent_count_array[$chat_data->agent_id]['count'] += 1;
				$agent_count_array[$chat_data->agent_id]['value'] = $agent_count_array[$chat_data->agent_id]['count'] * floatval($goal_info[0]->amount);
				//$agent_count_array[$chat_data->agent_id]['chats']['$conversion->chat_id'] = '';

				$day = substr($conversion->timestamp, 0, strpos($conversion->timestamp, " "));
				if(!array_key_exists($day, $date_array)){
					$date_array[$day] = array();
					$date_array[$day]['count'] = 0;
					$date_array[$day]['date'] = $day;
				}

				$date_array[$day]['count'] += 1;
				$date_array[$day]['value'] = $date_array[$day]['count'] * floatval($goal_info[0]->amount);
			}

			$content .= "<strong>" . __("Value By Date", "wplivechat") . ":</strong><hr>";
			$content .= "<div class='wplc_roi_grid' id='wplc_roi_grid_chart'> </div><br><br>";
			
			$content .= "<strong>" . __("Value By Agent", "wplivechat") . ":</strong><hr>";
			$content .= "<div class='wplc_roi_grid' id='wplc_roi_agent_chart'> </div>";


			$return_array = array('html' => $content, 'date_array' => $date_array, 'agent_array' => $agent_count_array);
		} else {
			$content .= "<p>" . __("No data available yet...", 'wplivechat') . "</p>";

			$return_array = array('html' => $content, 'date_array' => array(), 'agent_array' => array());
		}
	}


	return $return_array;
}

/*
 * Returns conversions
*/
function wplc_get_conversions_for_goal($goal_id,  $term){
	global $wpdb;
    global $wplc_tblname_chat_roi_conversions;
    
    $goal_id = intval($goal_id);
    $term = intval($term);

    $sql_date = "";

    switch($term){
    	case 0:
    		$sql_date = "";
    		break;
    	case 1:
    		$sql_date = "AND `timestamp` >  DATE_SUB(NOW(), INTERVAL 30 DAY)";
    		break;
    	case 2:
    		$sql_date = "AND `timestamp` >  DATE_SUB(NOW(), INTERVAL 15 DAY)";
    		break;
    	case 3:
    		$sql_date = "AND `timestamp` >  DATE_SUB(NOW(), INTERVAL 7 DAY)";
    		break;
    	case 4:
    		$sql_date = "AND `timestamp` >  DATE_SUB(NOW(), INTERVAL 1 DAY)";
    		break;
    }

    $sql = "SELECT * FROM $wplc_tblname_chat_roi_conversions WHERE `goal_id` = '$goal_id' " . $sql_date; 

    $results =  $wpdb->get_results($sql);
    if($wpdb->num_rows){
    	return $results;
    } else {
    	return false;
    }
}