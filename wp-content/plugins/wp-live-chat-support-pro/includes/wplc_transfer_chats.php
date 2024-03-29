<?php 
/*
 * Handles the transferring of a chat
*/

add_filter("wplc_admin_chat_area_before_end_chat_button", "wplc_pro_admin_transfer_tools", 15, 1);
/**
 * Outputs (echo) the 'Transfer' button
 *
 * @return void
*/
function wplc_pro_admin_transfer_tools($chat_data){
	echo wplc_create_modal_trigger_button_open("transfer", __("Transfer", "wplivechat"));
}


add_filter("wplc_hook_admin_below_chat_box", "wplc_pro_admin_transfer_tools_modals", 15, 1);
/**
 * Outputs (echo) the 'Transfer' modal
 *
 * @return void
*/
function wplc_pro_admin_transfer_tools_modals($chat_data){
	if(function_exists('wplc_create_modal_window')){
		echo wplc_create_modal_window("transfer", __("Transfer Chat", "wplivechat"), wplc_pro_admin_transfer_tools_modal_content($chat_data));
		
		wplc_pro_admin_transfer_tools_css();
		wplc_pro_admin_transfer_tools_js();
	}
}

/**
 * Returns content for the transfer Modal
 *
 * @return string (html)
*/
function wplc_pro_admin_transfer_tools_modal_content($chat_data){
	$content =  "<div id='wplc_transfer_modal_step_one_container'>";
	$content .= 	"<span>" . __("Would you like to transfer this chat to", "wplivechat") . "</span><br><br>";
	$content .= 	"<a href='javascript:void(0);' class='button' id='wplc_pro_tranfer_to_agent_btn'>" . __("Agent", "wplivechat") . "</a> ";
	$content .= 	"<a href='javascript:void(0);' class='button button-primary' id='wplc_pro_tranfer_to_department_btn'>" . __("Department", "wplivechat") . "</a> ";
	$content .= "</div>";
	
	$content .=  "<div id='wplc_transfer_modal_step_two_container'>";
	$content .=  	"<div id='wplc_transfer_modal_step_two_agent'>";
	$content .= 		"<span>" . __("Please select an agent to transfer to", "wplivechat") . "</span><br><br>";
	$content .= 		wplc_pro_admin_transfer_agent_selection() . "<br><br>";

	$content .= 		"<span id='wplc_transfer_to_agent_check'></span>";

	$content .= 	"</div>";
	$content .=  	"<div id='wplc_transfer_modal_step_two_department'>";
	$content .= 		"<span>" . __("Please select a department to transfer to", "wplivechat") . "</span><br><br>";
	$content .= 		wplc_pro_admin_transfer_department_selection() . "<br><br>";

	$content .= 		"<span id='wplc_transfer_to_department_check'></span>";

	$content .= 	"</div>";
	$content .= "</div>";

	return $content;
}

/**
 * Returns dropdown (html) of all online agent
 *
 * @return string (html)
*/
function wplc_pro_admin_transfer_agent_selection(){
	$content = "<select id='wplc_transfer_modal_agent_selection'>";
	$content .= "<option value=''>" . __("No Agent", "wplivechat") . "</option>";
	$my_id = get_current_user_id();
	$user_array =  get_users(array(
        'meta_key'=> 'wplc_chat_agent_online',
    ));
    foreach($user_array as $user){
        $content .= "<option value='" . $user->ID . "'>" . $user->display_name . ($user->ID == $my_id ? "(" . __("You", "wplivechat") . ")" : "") . "</option>";
    }

	$content .= "<select>";
    return $content;
}

/**
 * Returns dropdown (html) of all departments agent
 *
 * @return string (html)
*/
function wplc_pro_admin_transfer_department_selection(){
	if(function_exists("wplc_get_all_deparments")){
		$content = "<select id='wplc_transfer_modal_department_selection'>";
		$content .= "<option value='-1'>" . __("No Department", "wplivechat") . "</option>";
		$departments = wplc_get_all_deparments();
		if($departments){
			foreach($departments as $dep){
				$content .= "<option value=" . $dep->id . ">" . $dep->name . "</option>";
			}
		}
		$content .= "<select>";
	} else {
		$content = "Version error";
	}
    return $content;
}


/**
 * Outputs inline JavaScript for Transfer Modal
 *
 * @return void
*/
function wplc_pro_admin_transfer_tools_js(){
	?>
	<script>
		var wplc_to_agent = null;
		var wplc_online_check_complete = false;

		var wplc_transfer_checking_agent_string = "<?php _e('Checking if agent is online', 'wplivechat'); ?>";
		var wplc_transfer_error_agent_string = "<?php _e('Agent is not online, transfer cannot be made', 'wplivechat'); ?>";
		
		var wplc_transfer_checking_department_string = "<?php _e('Checking if agents in department are online', 'wplivechat'); ?>";
		var wplc_transfer_error_department_string = "<?php _e('No agent within the department are available to accept the transfer, transfer cannot be made', 'wplivechat'); ?>";
		
		var wplc_transfer_success_string = "<?php _e('Agent(s) available, safe to transfer', 'wplivechat'); ?>";

		var wplc_transfer_complete_string = "<?php _e('Transfer Complete. Closing Window...', 'wplivechat'); ?>";
		var wplc_transfer_fail_string = "<?php _e('Transfer Failed. Please try again later...', 'wplivechat'); ?>";

		jQuery(function(){
				if(typeof wplc_modal_initialize !== "undefined" && typeof wplc_modal_initialize === "function"){
					wplc_modal_initialize(wplc_modal_transfer_open_modal, wplc_modal_transfer_confirm, wplc_modal_transfer_cancel, false);
				}	

				jQuery("body").on("click", "#wplc_pro_tranfer_to_agent_btn", function(){
					jQuery("#wplc_transfer_modal_step_one_container").hide();
					jQuery("#wplc_transfer_modal_step_two_container").show();
					jQuery("#wplc_transfer_modal_step_two_agent").show();
					jQuery("#wplc_modal_inner_actions_transfer").show();
					wplc_to_agent = true;
				});

				jQuery("body").on("click", "#wplc_pro_tranfer_to_department_btn", function(){
					jQuery("#wplc_transfer_modal_step_one_container").hide();
					jQuery("#wplc_transfer_modal_step_two_container").show();
					jQuery("#wplc_modal_inner_actions_transfer").show();
					jQuery("#wplc_transfer_modal_step_two_department").show();
					wplc_to_agent = false;
				});

				jQuery("body").on("change", "#wplc_transfer_modal_agent_selection", function(){
					wplc_online_check_complete = false;
					jQuery("#wplc_modal_confirm_transfer").removeClass("button-primary");
					jQuery("#wplc_modal_confirm_transfer").addClass("button-disabled");
					jQuery("#wplc_transfer_to_agent_check").text(wplc_transfer_checking_agent_string);
					wplc_modal_transfer_check_online_agent(function(){
						wplc_online_check_complete = true;
						jQuery("#wplc_modal_confirm_transfer").removeClass("button-disabled");
						jQuery("#wplc_modal_confirm_transfer").addClass("button-primary");
						jQuery("#wplc_transfer_to_agent_check").text(wplc_transfer_success_string);
					}, function(){
						jQuery("#wplc_transfer_to_agent_check").text(wplc_transfer_error_agent_string);
					});

				});

				jQuery("body").on("change", "#wplc_transfer_modal_department_selection", function(){
					wplc_online_check_complete = false;
					jQuery("#wplc_modal_confirm_transfer").removeClass("button-primary");
					jQuery("#wplc_modal_confirm_transfer").addClass("button-disabled");
					jQuery("#wplc_transfer_to_department_check").text(wplc_transfer_checking_department_string);
					wplc_modal_transfer_check_online_department(function(){
						wplc_online_check_complete = true;
						jQuery("#wplc_modal_confirm_transfer").removeClass("button-disabled");
						jQuery("#wplc_modal_confirm_transfer").addClass("button-primary");
						jQuery("#wplc_transfer_to_department_check").text(wplc_transfer_success_string);
					}, function(){
						jQuery("#wplc_transfer_to_department_check").text(wplc_transfer_error_department_string);
					});
				});
		});	

		function wplc_modal_transfer_open_modal(){
			jQuery("#wplc_transfer_modal_step_one_container").show();
			jQuery("#wplc_transfer_modal_step_two_container").hide();
			jQuery("#wplc_modal_inner_actions_transfer").hide();
			jQuery("#wplc_transfer_modal_step_two_agent").hide();
			jQuery("#wplc_transfer_modal_step_two_department").hide();
			jQuery("#wplc_modal_confirm_transfer").removeClass("button-primary");
			jQuery("#wplc_modal_confirm_transfer").addClass("button-disabled");

			jQuery("#wplc_transfer_to_agent_check").text("");
			jQuery("#wplc_transfer_to_department_check").text("");

			wplc_to_agent = null;
			wplc_online_check_complete = false;
		}

		function wplc_modal_transfer_check_online_agent(check_success_callback, check_fail_callback){
			var aid = jQuery("#wplc_transfer_modal_agent_selection").val();

			var data ={
				action : "wplc_admin_transfer_check_agent_online",
				agent_id : parseInt(aid)
			};

			wplc_modal_transfer_ajax(data, function(return_data){
				if(return_data == "true"){
					if(typeof check_success_callback === "function"){
						check_success_callback();	
					}
				} else {
					if(typeof check_fail_callback === "function"){
						check_fail_callback();	
					}
				}
			}, function(){
				if(typeof check_fail_callback === "function"){
					check_fail_callback();	
				}
			});

			
		}

		function wplc_modal_transfer_check_online_department(check_success_callback, check_fail_callback){
			var depid = jQuery("#wplc_transfer_modal_department_selection").val();

			var data ={
				action : "wplc_admin_transfer_check_department_online",
				dep_id : parseInt(depid)
			};
			
			wplc_modal_transfer_ajax(data, function(return_data){
				if(return_data == "true"){
					if(typeof check_success_callback === "function"){
						check_success_callback();	
					} 
				} else {
					if(typeof check_fail_callback === "function"){
						check_fail_callback();	
					}
				}
			}, function(){
				if(typeof check_fail_callback === "function"){
					check_fail_callback();	
				}
			});
		}

		function wplc_modal_transfer_ajax(req_data, on_succcess, on_error){
			jQuery.ajax({
             url : "<?php echo admin_url('admin-ajax.php'); ?>",
             type : 'POST',
             data : req_data,
             success : function(return_data) {    
                if(typeof on_succcess === "function"){
					on_succcess(return_data);	
				}
             },
             error : function (){
                if(typeof on_error === "function"){
					on_error();	
				}
             }
         	});
		}

		function wplc_modal_transfer_confirm(){
			if(wplc_online_check_complete){
				if(wplc_to_agent !== null){
					if(wplc_to_agent == true){
						//Send to agent
						var aid = jQuery("#wplc_transfer_modal_agent_selection").val();
						var data ={
							action : "wplc_admin_transfer_to_agent",
							cid    : parseInt("<?php echo $_GET['cid']; ?>"),
							agent_id : parseInt(aid)
						};
						
						wplc_modal_transfer_ajax(data, function(return_data){
							if(return_data == "true"){
								jQuery("#wplc_transfer_to_agent_check").text(wplc_transfer_complete_string);

								setTimeout(function(){
									 window.close();
								}, 300);
							} else {
								jQuery("#wplc_transfer_to_agent_check").text(wplc_transfer_fail_string);
							}
						}, function(){
							jQuery("#wplc_transfer_to_agent_check").text(wplc_transfer_fail_string);
						});
					} else {
						//Send to department
						var depid = jQuery("#wplc_transfer_modal_department_selection").val();
						var data ={
							action : "wplc_admin_transfer_to_department",
							cid    : parseInt("<?php echo $_GET['cid']; ?>"),
							dep_id : parseInt(depid)
						};
						
						wplc_modal_transfer_ajax(data, function(return_data){
							if(return_data == "true"){
								jQuery("#wplc_transfer_to_department_check").text(wplc_transfer_complete_string);

								setTimeout(function(){
									 window.close();
								}, 300);
							} else {
								jQuery("#wplc_transfer_to_department_check").text(wplc_transfer_fail_string);
							}
						}, function(){
							jQuery("#wplc_transfer_to_department_check").text(wplc_transfer_fail_string);
						});
					}

				} else {
					/* console.log("No Mode Selected"); */
				}
			}
		}

		function wplc_modal_transfer_cancel(){
			wplc_to_agent = null;
			wplc_online_check_complete = false;
		}
	</script>
	<?php
}

/**
 * Outputs inline css for Transfer Modal
 *
 * @return void
*/
function wplc_pro_admin_transfer_tools_css(){
	?>
	<style>
		#wplc_transfer_modal_step_one_container, #wplc_transfer_modal_step_two_container{
			text-align: center;
		}
	</style>
	<?php
}

add_filter("wplc_accept_chat_button_filter", "wplc_pro_admin_accept_chat_button_text", 10, 2);
/**
 * Changes 'Accept Chat' to 'Accept Transfer' 
 *
 * @return string
*/
function wplc_pro_admin_accept_chat_button_text($content, $cid){
	$chat_data = wplc_get_chat_data($cid, __LINE__);
	$max_wait_time = 60; //60 Seconds
    if (isset($chat_data->other)) {
        $other_data = maybe_unserialize( $chat_data->other );
        if( (isset($other_data['transfer']) && $other_data['transfer'] == true)){
        	if(isset($chat_data->agent_id) && intval($chat_data->agent_id) !== 0){
        		//For a specific agent
        		if(isset($other_data['agent_transfer_time'])){
        			//Check the time difference
        			$seconds = time() - $other_data['agent_transfer_time'];
        			if(intval($seconds) > $max_wait_time){
        				do_action("wplc_pro_admin_transfer_agent_exceeded_accept_time", $cid, $chat_data->agent_id);
        			}
        		}

        		$agent_info = get_userdata(intval($chat_data->agent_id));

        		return __("Transfer for", "wplivechat") . " " . $agent_info->display_name;
        	} else {
        		//Department 
        		return __("Accept Transfer", "wplivechat");
        	}
        } else if ( (isset($other_data['unanswered']) && $other_data['unanswered'] == true) ){
        	// Initial chat request -> Should still check timer
        	if(isset($other_data['agent_transfer_time'])){
    			//Check the time difference
    			$seconds = time() - $other_data['agent_transfer_time'];
    			if(intval($seconds) > $max_wait_time){
    				do_action("wplc_pro_admin_transfer_agent_exceeded_accept_time", $cid, -1); //Transfer to the next agent
    			}
    		}
        	return $content;
        }
    }

	return $content;
}

add_filter("wplc_start_chat_hook_other_data_hook", "wplc_pro_admin_transfer_add_initial_transfer_time", 10, 1);
/**
 * Adds an initial 'agent transfer time' value to the other data of the chat - On Chat Request 
 *
 * @return array
*/
function wplc_pro_admin_transfer_add_initial_transfer_time($other_data){
	$other_data['agent_transfer_time'] = time();
	return $other_data;
}

add_action("wplc_pro_admin_transfer_agent_exceeded_accept_time", "wplc_pro_admin_transfer_to_next_available_agent", 10, 2);
/**
 * Transfers chat to the next available agetn
 *
 * @return void
*/
function wplc_pro_admin_transfer_to_next_available_agent($chat_id, $current_agent){
	$chat_id = intval($chat_id);
	$current_agent = intval($current_agent);

	//Find all online agents
	$user_array =  get_users(array(
        'meta_key'=> 'wplc_chat_agent_online',
    ));
	$identified_candidate = false;

	foreach ($user_array as $user) {
		if($identified_candidate === false){
			if($current_agent !== $user->ID){
				$identified_candidate = $user->ID;
			}
		}
	}

	if($identified_candidate !== false){
		wplc_pro_admin_transfer_to_agent($chat_id, $identified_candidate, true);
	}

}


add_action("wplc_hook_chat_notification","wplc_filter_control_chat_notification_department_transfer",10,3);
/**
 * Handles 'Transfer' system notification 
 *
 * @return string
*/
function wplc_filter_control_chat_notification_department_transfer($type,$cid,$data) {
	if ($type == "transfer") {
        global $wpdb;
        global $wplc_tblname_msgs;


        $user_info = get_userdata(intval($data['aid']));
        if( $user_info ){
        	$agent = $user_info->display_name;	
        } else {
        	$agent = "";
        }        

        if(isset($data["auto_transfer"]) && $data["auto_transfer"] == true){
        	error_log($data['aid']);
        	if(intval($data['aid']) === 0){
        		//Came from a department originally
        		$msg =  __("Department took too long to respond, we are transferring this chat to the next available agent.","wplivechat");
        	} else {
				$msg = $agent . " " . __("took too long to respond, we are transferring this chat to the next available agent.","wplivechat");
			}
        } else {
        	$msg = $agent . " ". __("has transferred the chat.","wplivechat");
        }

        $wpdb->insert( 
            $wplc_tblname_msgs, 
            array( 
                    'chat_sess_id' => $cid, 
                    'timestamp' => current_time('mysql'),
                    'msgfrom' => __('System notification',"wplivechat"),
                    'msg' => $msg,
                    'status' => 0,
                    'originates' => 0
            ), 
            array( 
                    '%s', 
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d'
            ) 
        );

        $msg = "<strong>" . __("User received this message", "wplivechat") . ":</strong> " . $msg;
        $wpdb->insert( 
            $wplc_tblname_msgs, 
            array( 
                    'chat_sess_id' => $cid, 
                    'timestamp' => current_time('mysql'),
                    'msgfrom' => __('System notification',"wplivechat"),
                    'msg' => $msg,
                    'status' => 0,
                    'originates' => 3
            ), 
            array( 
                    '%s', 
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d'
            ) 
        );
    }
    return;
}

add_action("wplc_hook_chat_notification","wplc_filter_control_chat_notification_auto_department_transfer",10,3);
/**
 * Handles 'Transfer' system notification 
 *
 * @return string
*/
function wplc_filter_control_chat_notification_auto_department_transfer($type,$cid,$data) {
	if ($type == "system_dep_transfer") {
        global $wpdb;
        global $wplc_tblname_msgs;

        $from_department = null; 
        $to_department = null;

        if(isset($data['to_dep_id']) && isset($data['from_dep_id'])){
        	if(function_exists("wplc_get_department")){
        		$from_department = wplc_get_department(intval($data['from_dep_id']));
        		$to_department = wplc_get_department(intval($data['to_dep_id']));
        	}
        }


        $msg = __("No agents available in","wplivechat") . " ";
        if($from_department === null){
        	$msg .= __("selected department", "wplivechat");
        } else {
        	$msg .= $from_department[0]->name;
        }
        $msg .= ", " . __("automatically transferring you to", "wplivechat") . " ";
        if($to_department === null){
        	$msg .= __("the next available department", "wplivechat");
        } else {
        	$msg .= $to_department[0]->name;
        }
        $msg .= ".";

        $wpdb->insert( 
            $wplc_tblname_msgs, 
            array( 
                    'chat_sess_id' => $cid, 
                    'timestamp' => current_time('mysql'),
                    'msgfrom' => __('System notification',"wplivechat"),
                    'msg' => $msg,
                    'status' => 0,
                    'originates' => 0
            ), 
            array( 
                    '%s', 
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d'
            ) 
        );

       	$msg = __("User has been transfered from ","wplivechat") . " ";
        if($from_department === null){
        	$msg .= __("department", "wplivechat");
        } else {
        	$msg .= $from_department[0]->name;
        }

        if($to_department !== null){
        	$msg .= __(" to ", "wplivechat") . " " . $to_department[0]->name;
        }
        $msg .= " " . __("as there were no agents online") .  ".";

        $wpdb->insert( 
            $wplc_tblname_msgs, 
            array( 
                    'chat_sess_id' => $cid, 
                    'timestamp' => current_time('mysql'),
                    'msgfrom' => __('System notification',"wplivechat"),
                    'msg' => $msg,
                    'status' => 0,
                    'originates' => 3
            ), 
            array( 
                    '%s', 
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d'
            ) 
        );
    }
    return;
}

add_action("wplc_hook_change_status_on_answer","wplc_pro_clear_transfer_request_if_set",10,2);
/**
 * Unsets the transfer request once it is answered
 *
 * @return int (response code)
*/
function wplc_pro_clear_transfer_request_if_set($get_data, $chat_data = false) {
	global $wpdb;
	global $wplc_tblname_chats;

	$chat_id = intval($get_data['cid']);
	if (!$chat_data) { $chat_data = wplc_get_chat_data($chat_id, __LINE__); }

    if (isset($chat_data->other)) {
        $other_data = maybe_unserialize( $chat_data->other );
        if(isset( $other_data['transfer'] ) && $other_data['transfer'] == true){
        	unset($other_data['transfer']);
        	unset($other_data['unanswered']);
        	unset($other_data['agent_transfer_time']);
			
			$new_chat_data = array('other' => maybe_serialize($other_data));

        	$wpdb->update( $wplc_tblname_chats, $new_chat_data, array('id' => $chat_id), array('%s'), array('%d'));
        }
    }
}

add_filter("wplc_pro_department_update_filter", "wplc_pro_admin_transfer_department_online_check", 10, 2);
/**
 * Unsets the transfer request once it is answered
 *
 * @return int (response code)
*/
function wplc_pro_admin_transfer_department_online_check($department_id, $cid) {
	$search_department = intval($department_id);
	$user_array =  get_users(array(
	    'meta_key'=> 'wplc_chat_agent_online',
	));

	$fallback_department = null; //First agent who is online will be responsible for fallback

	$check = false;
	foreach($user_array as $user){
	    $this_user_department = get_user_meta($user->ID, "wplc_user_department", true);
	    if(intval($this_user_department == -1) || $this_user_department === ""){
			$check = true;
	    } else if(intval($this_user_department) === $search_department){
	       	$check = true;
	    }

	    if($fallback_department === null){
	    	$fallback_department = $this_user_department;
	    }
	}

	if($check){
		return $department_id; //Someone is online in this department, or a global department
	} else {
		if($fallback_department !== null){
			wplc_record_chat_notification("system_dep_transfer",$cid, array("from_dep_id" => $department_id, "to_dep_id" => $fallback_department));
			return $fallback_department;
		} else {
			//Joh.... This is broken
			return $department_id; //Just return the department ID passed in originally, hope for the best
		}
	}
}

/**
 * Transfers a chat to a new agent
 *
 * @param int $chat_id Chat ID
 * @param int $agent_id Agent ID
 * @return boolean 
*/
function wplc_pro_admin_transfer_to_agent($chat_id, $agent_id, $auto_transfer){
	global $wpdb;
	global $wplc_tblname_chats;
	
	$chat_data = wplc_get_chat_data($chat_id, __LINE__);
    if (isset($chat_data->other)) {
        $other_data = maybe_unserialize( $chat_data->other );
        $other_data['unanswered'] = true;
        $other_data['transfer'] = true;
        $other_data['agent_transfer_time'] = time();

        $user_department = get_user_meta($agent_id, "wplc_user_department", true);
        if(!$user_department || $user_department === ""){
        	$user_department = 0;
        }

        $new_chat_data = array('status' => 2, 'agent_id' => $agent_id, 'department_id' => $user_department,'other' => maybe_serialize($other_data));

        if($wpdb->update( $wplc_tblname_chats, $new_chat_data, array('id' => $chat_id), array('%d', '%d', '%d','%s'), array('%d'))){
        	if(function_exists("wplc_record_chat_notification")){
        		wplc_record_chat_notification("transfer", $chat_id, array("aid" => $chat_data->agent_id, "auto_transfer" => $auto_transfer));
        	}
        	return true;
        } else {
        	return false;
        }
    }
    return false;
}

/**
 * Transfers a chat to a new department
 *
 * @param int $chat_id Chat ID
 * @param int $dep_id Department ID
 * @return boolean 
*/
function wplc_pro_admin_transfer_to_department($chat_id, $dep_id){
	global $wpdb;
	global $wplc_tblname_chats;

	$chat_data = wplc_get_chat_data($chat_id, __LINE__);
    if (isset($chat_data->other)) {
        $other_data = maybe_unserialize( $chat_data->other );
        $other_data['unanswered'] = true;
        $other_data['transfer'] = true;

        $new_chat_data = array('status' => 2, 'agent_id' => 0, 'department_id' => $dep_id,'other' => maybe_serialize($other_data));

        if($wpdb->update( $wplc_tblname_chats, $new_chat_data, array('id' => $chat_id), array('%d', '%d', '%d','%s'), array('%d'))){
        	if(function_exists("wplc_record_chat_notification")){
        		wplc_record_chat_notification("transfer", $chat_id, array("aid" => $chat_data->agent_id));
        	}
        	return true;
        } else {
        	return false;
        }
    }

    return false;
}

add_action('wp_ajax_wplc_admin_transfer_check_agent_online', 'wplc_pro_admin_transfer_ajax');
add_action('wp_ajax_wplc_admin_transfer_check_department_online', 'wplc_pro_admin_transfer_ajax');
add_action('wp_ajax_wplc_admin_transfer_to_agent', 'wplc_pro_admin_transfer_ajax');
add_action('wp_ajax_wplc_admin_transfer_to_department', 'wplc_pro_admin_transfer_ajax');
/**
 * Handles the ajax calls for transfers
 *
 * @return string (response status)
*/
function wplc_pro_admin_transfer_ajax(){
	if(isset($_POST['action'])){
		if ($_POST['action'] == "wplc_admin_transfer_check_agent_online") {	
			if(isset($_POST['agent_id'])){
				$user_id = intval($_POST['agent_id']);
				$check = get_user_meta($user_id, "wplc_chat_agent_online", true);
				if($check && $check !== ""){
					echo "true";
					exit();
				} else {
					echo "false"; //Agent no longer online
					exit();
				}
			}
		}

		if ($_POST['action'] == "wplc_admin_transfer_check_department_online") {	
			if(isset($_POST['dep_id'])){
				$search_department = intval($_POST['dep_id']);
				$user_array =  get_users(array(
			        'meta_key'=> 'wplc_chat_agent_online',
			    ));

			    $check = false;

				foreach($user_array as $user){
			        $this_user_department = get_user_meta($user->ID, "wplc_user_department", true);
			        if(intval($this_user_department) === $search_department){
			        	$check = true;
			        }
			    }

				if($check){
					echo "true";
					exit();
				} else {
					echo "false"; //Agent no longer online
					exit();
				}
			}
		}

		if ($_POST['action'] == "wplc_admin_transfer_to_agent") {	
			if(isset($_POST['cid']) && isset($_POST['agent_id'])){
				$chat_id = intval($_POST['cid']);
				$agent_id = intval($_POST['agent_id']);				
	            if(wplc_pro_admin_transfer_to_agent($chat_id, $agent_id, false)){
	            	echo "true";
					exit();
	            } else {
	            	echo "false";
					exit();
	            }
	        }
		}

		if ($_POST['action'] == "wplc_admin_transfer_to_department") {	
			if(isset($_POST['cid']) && isset($_POST['dep_id'])){
				$chat_id = intval($_POST['cid']);
				$dep_id = intval($_POST['dep_id']);
	            if(wplc_pro_admin_transfer_to_department($chat_id, $dep_id)){
	            	echo "true";
					exit();
	            } else {
	            	echo "false";
					exit();
	            }
	        }  
		}
	}
}