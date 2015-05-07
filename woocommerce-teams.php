<?php
/**
 * Plugin Name: WooCommerce Teams
 * Plugin URI: http://rob.mayfirst.org/woocommerce-teams/
 * Description: This plugin enables the creation of fundraising teams within WooCommerce.
 * Version: 1.1
 * Author: Rob Korobkin
 * Author URI: http://URI_Of_The_Plugin_Author
 * License: GPL2
 
  Copyright 2014 Rob Korobkin (email: rob.korobkin@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

	--
	
	Plugin Resources:  http://codex.wordpress.org/Writing_a_Plugin
	ReadMe.txt generator: http://generatewp.com/plugin-readme/
	

 */
	
	defined('ABSPATH') or die("No script kiddies please!");
	
	global $wooTeamsFields;
	$wooTeamsFields = array(
		'wcft-teamList', 
		'wcft-allowUserSubmittedTeams', 
		'wcft-barGraphColor', 
		'wcft-barGraphScale'
	);
	
	
	
	// admin stuff
	if ( is_admin() ){ // admin actions
		add_action( 'admin_menu', 'woo_teams_admin_menu' );
		add_action( 'admin_init', 'register_wtsettings' );
	}
	
	
	// Register style sheet.
	add_action( 'wp_enqueue_scripts', 'register_wcft_styles' );
	function register_wcft_styles() {
		wp_register_style( 'woocommerce-teams', plugins_url( 'woocommerce-teams/styles.css' ) );
		wp_enqueue_style( 'woocommerce-teams' );
	}




	// create custom admin menu item
	function woo_teams_admin_menu(){

		//create new top-level menu
		add_submenu_page('woocommerce', 'Woo Teams', 'Woo Teams', 'administrator', __FILE__, 'woo_teams_admin_page');

		//call register settings function
		add_action( 'admin_init', 'register_wtSettings' );
		
	}
	
	//register our settings
	function register_wtSettings(){
		global $wooTeamsFields;
		foreach($wooTeamsFields as $f){
			register_setting( 'wooteams-settings-group', $f );
		}
	}
	
	
	
	function woo_teams_admin_page(){
	
		global $wooTeamsFields;
	
		foreach($wooTeamsFields as $f){
			$pluginState[$f] = esc_attr( get_option($f) );
		}
		
	
		echo 	'	<style type="text/css">
						#wooteams-adminform 			{ }
						#wooteams-adminform td 			{ padding-bottom: 10px; vertical-align: top; padding-right: 10px; }
						#wooteams-adminform .tInput 	{ width: 300px; }
						#wooteams-adminform textarea 	{ height: 100px; width: 300px; }
					</style>
		
					<form style="padding: 30px;" id="wooteams-adminform" method="post" action="options.php">';
					
		settings_fields( 'wooteams-settings-group' );
		do_settings_sections( 'wooteams-settings-group' );					
		
		$checked = ($pluginState['wcft-allowUserSubmittedTeams'] == "on") ? "checked" : "";
					
		echo			'<h2>Woocommerce Team Settings</h2>
						<table>
							<tr>
								<td><label for="allowUserSubmitted">Allow User Submitted Teams</label></td>
								<td><input type="checkbox" name="wcft-allowUserSubmittedTeams" id="allowUserSubmitted" ' . $checked  . ' /></td>
							</tr>
							<tr>
								<td><label for="barGraphColor">Bar Graph Color (hex)</label></td>						
								<td><input type="text" name="wcft-barGraphColor" id="barGraphColor" class="tInput" 
											value="' . $pluginState['wcft-barGraphColor'] . '"
											placeholder="#00ff00" /></td>
							</tr>
							<tr>
								<td><label for="barGraphScale">Bar Graph Scale ($/px)</label></td>						
								<td><input type="text" name="wcft-barGraphScale" id="barGraphScale" class="tInput" 
											value="' . $pluginState['wcft-barGraphScale'] . '"
											placeholder="10"/></td>
							</tr>
							<tr>
								<td><label for="teamList">Teams (team 1, team 2 etc.)</label></td>
								<td><textarea name="wcft-teamList" id="teamList">' . 
										$pluginState['wcft-teamList'] . 
									'</textarea></td>
							</tr>
						</table>';
						submit_button();
		echo 		'</form>';
	
	}
	
	


// SHOW INPUTS
add_filter( 'woocommerce_checkout_fields' , 'add_wooteams_to_checkout' );
function add_wooteams_to_checkout( $fields ) {

	// get woocommerce teams
	$teamsRaw = explode(',', esc_attr( get_option('wcft-teamList') ));
	$teams[] = "Select your fundraising team...";
	foreach($teamsRaw as $t) $teams[] = trim($t);

	// display
	$team['team_id'] = array(
		'type' => "select",
		'label' => "Are you helping to raise money for a particular team?",	
		'options' => $teams
	);
	
	$allowWriteIn = esc_attr(get_option('wcft-allowUserSubmittedTeams'));
	if($allowWriteIn == "on"){
		$team['team_name'] = array(
			'type' => "text",
			'label' => "Write in other team name:"
		);
	}
	
	// add it to the top of the billing column
	$fields["billing"] = $team + $fields["billing"];
	
    return $fields;
}


/*
// this will put content above the form, good position, but not in form
add_action('woocommerce_before_checkout_form', 'wooteams_checkout_html');
function wooteams_checkout_html(){
	echo "monkey";
}
*/

// SAVE INFORMATION
add_action( 'woocommerce_checkout_update_order_meta', 'save_wooteam_on_checkout' );
function save_wooteam_on_checkout( $order_id ) {
	
	// get teams
	$teamsRaw = explode(',', esc_attr( get_option('wcft-teamList') ));
	$teams[] = "Select your fundraising team...";
	foreach($teamsRaw as $t) $teams[] = trim($t);
	
	// save selection
    if ( !empty($_POST['team_id']) && $_POST['team_id'] != "0" ) {
		$teamName = $teams[$_POST['team_id']];
		update_post_meta( $order_id, 'Team Name', $teamName);
	}
	
	// save user-submitted
	else if(!empty($_POST['team_name'])) {
		$teamName = $_POST['team_name'];
		update_post_meta( $order_id, 'Team Name', $teamName);
    }
    
    // save default
	else {
		$teamName = "No team selected";
		update_post_meta( $order_id, 'Team Name', $teamName);
	}
}

// show scoreboard
function wooteams_scoreboard_display( $atts ){
	
	global $wooTeamsFields;	
	foreach($wooTeamsFields as $f){
		$pluginState[$f] = esc_attr( get_option($f) );
	}
	

	$bargraphColor = $pluginState['wcft-barGraphColor'] != '' ? $pluginState['wcft-barGraphColor'] : 'green';
	$bargraphScale = $pluginState['wcft-barGraphScale'] != '' ? $pluginState['wcft-barGraphScale'] : 10;
	
	
	// ITERATE THROUGH ALL ORDERS AND TALLY UP TEAMS - THIS MAY TAKE A WHILE... COULD BE CACHED etc.
	$args = array(
		'post_type'			=> 'shop_order',
		'posts_per_page' 	=> '-1'
	);
	$posts_array = get_posts( $args );
	$scores = array();
	
	foreach($posts_array as $post){

		$status = $post -> post_status;

		$tmp = get_post_meta( $post -> ID, "_order_total");
		$total = $tmp[0];
	
		if($status != "wc-processing" && $status != "wc-completed") continue;	
	
		$tmp = get_post_meta( $post -> ID, "Team Name");
		$teamName = trim($tmp[0]);
		
		$noGood = array("Individual (not associated with a team)", "No team selected", "");
		if( !in_array($teamName, $noGood) ) {
			$score = array(
				"total" => $total,
				"id"	=> $post -> ID
			);
			$scores[$teamName]["orders"][] 	=  $score;
			$scores[$teamName]["total"] 	+= $score["total"];
			$scores[$teamName]["teamName"] 	=  $teamName;
		}
	}
	if(count($scores) == 0) return '<div class="wt-empty">Nothing to display<br /><br />There are no approved orders associated with a team.</div>';
	function sortTeams($a, $b){
		return $a['total'] < $b['total'];
	}
	usort($scores, sortTeams);



	
	$html = '<table id="scoreBoard">';
	foreach($scores as $teamName => $team){
		$html .= 	"<tr>" . 
						'<td class="label">' . $team["teamName"] . " (" . count($team["orders"]) . ")</td>" .
						'<td style="line-height: 40px;  font-size: 13px;">$' . $team['total'] . "</td>" .
						"<td>" . 
							'<div  class="bar" style="width: ' . ($team['total'] / $bargraphScale) . 'px;
														background: ' . $bargraphColor . '"></div>' .
						"</td>" .
					"</tr>";
	}
	$html .= '</table>';
	return $html;
}
add_shortcode( 'wooteams-scoreboard', 'wooteams_scoreboard_display' );

