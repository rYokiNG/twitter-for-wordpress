<?php
/*
Plugin Name: WP Twitter widget
Plugin URI: http://ryokingz.blogspot.com/
Description: Create twitter widget easy fix twitter api 1.1 
Version: 1.0
Author: rYokiNG
Author URI: http://ryokingz.blogspot.com/
Tags: twitter, twitter widget, twitter feeds, twitter timeline, twitter api, twitter 1.1
License: GPL2
*/

include_once('libs/twitteroauth.php');
include_once('libs/OAuth.php');

/**
 * Start acction lists 
 */
add_action( 'admin_menu', 'wp_widget_plugin_menu', 1);
add_action( 'widgets_init', 'twitter_widget_init', 1);

/**
 * Support metabox 
 */
add_action( 'load-settings_page_wptw_options', 'wptw_add_screen_meta_boxes');
/**
 * Support Dragoption 
 */
add_action( 'admin_footer-settings_page_wptw_options','wptw_print_script_in_footer');

add_action( 'add_meta_boxes', 'wptw_meta_box_add' , 10); 

add_action( 'admin_notices', 'check_twitter_settings');
/**
 * Support ajax cb
 */
add_action('wp_ajax_wptw_twitter_auth', 'wptw_auth_redirect');
add_action('wp_ajax_wptw_twitter_callback', 'wptw_auth_callback');
add_action('wp_ajax_nopriv_wptw_twitter_callback', 'wptw_auth_callback_redirect');

add_action( 'wp_enqueue_scripts', 'wptw_display' );

/** End action lists */

/**
 * Start Main Widget
 * WP_Twitter_Widget
 *
 * @since 3.2.8
 */
class WP_Twitter_Widget extends WP_Widget {
	public $ck, $cs, $ot, $os, $sn;
	
	function __construct() {
		$widget_ops = array('classname' => 'wp_twitter_widget', 'description' => __('Display sidebar twitter widget drag and drop into sldebar'));
		$control_ops = array('width' => 400, 'height' => 350);
		parent::__construct('wptw', __('WP Twitter widget'), $widget_ops, $control_ops);
		
		$this->ck = get_option('wptp_consumer_key');  
		$this->cs = get_option('wptp_consumer_secret'); 
		$this->ot = get_option('wptp_oauth_token'); 
		$this->os = get_option('wptp_oauth_token_secret'); 
		$this->sn = get_option('wptp_screen_name'); 
		
	}

	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$screenname = $instance['screenname'];
		if(empty($screenname)) { $screenname = $this->sn; } 
		
		$count = absint($instance['count']);
		
		$showheader = $instance['showheader'] ? 1 : 0;
		$timeago = $instance['timeago'] ? 1 : 0; 
		
		echo $before_widget; 
		
		if(!empty($this->ck) && !empty($this->cs) && !empty($this->ot) && !empty($this->os)) { 
				$connection = new TwitterOAuth($this->ck, $this->cs, $this->ot, $this->os);
		
				if(is_object($connection)) {
						$content = $connection->get('statuses/user_timeline', array('screen_name' => $screenname, 'count' => $count));
				} 
		}
		
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; }  ?>   
			<div class="wp_twitter_widget">
            <?php 
			if(count($content) > 0) {
			?>
            	<?php if($showheader) { ?>
                <div class="wptw_header">
                    <div class="status_display">
                    	<span class="profile_image"><a href="https://twitter.com/<?php echo $screenname ?>" target="_blank"><img src="<?php echo $content[0]->user->profile_image_url_https ?>" style="vertical-align:top;"></a></span>
                        
                        <?php //if optional ?>
                        <div class="right">
                        <a href="https://twitter.com/<?php echo $screenname ?>" class="twitter-follow-button right" data-show-count="false" data-lang="en" data-show-screen-name="false" data-align="right">Follow</a> 
                        
                        </div>
						<?php //end if optional ?>
                        
                    	<span class="status_name"><a href="https://twitter.com/<?php echo $screenname ?>" target="_blank"><?php echo $content[0]->user->name; ?></a></span><br />
                    	<span class="status_screenname">@<?php echo $content[0]->user->screen_name; ?></span>
                        
                        
                    </div>
                    <div class="wptp_description"><?php echo preg_replace(
					'@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@',
		 			'<a target="_blank" class="statuslink" href="$1">$1</a>', $content[0]->user->description); ?></div>
				</div>
				<?php } // display header ?> 
			 
                <ul class="widget_twitter">
                    <?php 
                        foreach($content as $key => $entry) {
							
							$staus = preg_replace('/@(\w+)/','<span class="twitter_user">@$1</span>', $entry->text);
							
							if($timeago) {
								$meta = "<span class='twitter_meta'>".$this->timeago(strtotime($entry->created_at))."</span>";
							} else {
								$meta = "<span class='twitter_meta'>".date('h:iA - j M Y', strtotime($entry->created_at))."</span>";
							}
							// next version develop timming
							if(!empty($entry->in_reply_to_screen_name)) {
							}
							if(isset($entry->retweeted_status) && is_object($entry->retweeted_status)) {
							}
							
							// need add class for support reply retweeted url media video user_memtions
							// if() $entry->entities->urls 
							// if() $entry->entities->user_mentions
							// if() $entry->entities->media 
							
							 
                            echo '<li class="text_'.$entry->id_str.'">';
                            echo preg_replace("/(http|https):\/\/(.*?)\/[^ ]*/", '<a class="contentlink" target="_blank" href="\\0">\\0</a>', $staus). " ". $meta;
                            echo '</li>';				
                        }
                    ?>
                </ul> 
            	<div class="wptw_footer">
                    <?php 
					// version 1.1
					/* <a href="https://twitter.com/$screenname" class="twitter-follow-button" data-show-count="true" data-lang="en" data-show-screen-name="true">Follow</a> */
                    ?>   
                     <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
                        
                </div>
            <?php 
            } else {
                // version 1.1
            }  
			
			?>
            
             
            </div> 
		<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['screenname'] = strip_tags($new_instance['screenname']);
		$instance['count'] = absint( $new_instance['count'] );
		$instance['showheader'] = $new_instance['showheader'] ? 1 : 0;
		$instance['timeago'] = $new_instance['timeago'] ? 1 : 0;
		
		return $instance;
	}

	function timeago($time) {
		$timediff = time() - $time;
		if ($timediff < 60) {
			return 'a minute ago';
		} else if ($timediff < 120) {
			return 'about a minute ago';
		} else if ($timediff < 3600) {
			return floor($timediff / 60) . ' minutes ago';
		} else if ($timediff < (3600 * 2)) {
			return 'about an hour ago';
		} else if ($timediff < (24 * 3600)) {
			return 'about ' . floor($timediff / 3600) . ' hours ago';
		} else if ($timediff < (48 * 3600)) {
			return '1 day ago.';
		} else {
			return date('h:iA - j M Y', $time);
		}
	}


	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, 
						array( 'title'      => '', 
							   'screenname' => $this->sn, 
							   'count' => 10, 
							   'showheader' => 1,
							   'timeago' => 1
						) );
		$title = strip_tags($instance['title']);
		$screenname = strip_tags($instance['screenname']); 
		$count = isset($instance['count']) ? absint($instance['count']) : 5;
		$showheader = $instance['showheader'] ? 'checked="checked"' : '';
		$timeago = $instance['timeago'] ? 'checked="checked"' : ''; 
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

        <p>
        <label for="<?php echo $this->get_field_id('screenname'); ?>"><?php _e('Twitter username:'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('screenname'); ?>" name="<?php echo $this->get_field_name('screenname'); ?>" type="text" value="<?php echo esc_attr($screenname); ?>" />
        </p>
            
        <p>
        <label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Number of tweets:'); ?></label>
		<input id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" size="3" />
        </p>   
        
        <p>     
        <input class="checkbox" type="checkbox" <?php echo $showheader; ?> id="<?php echo $this->get_field_id('showheader'); ?>" name="<?php echo $this->get_field_name('showheader'); ?>" value="1"/> 
        <label for="<?php echo $this->get_field_id('showheader'); ?>"><?php _e('Show Header'); ?></label>
        
		<br/>   
        <input class="checkbox" type="checkbox" <?php echo $timeago; ?> id="<?php echo $this->get_field_id('timeago'); ?>" name="<?php echo $this->get_field_name('timeago'); ?>" value="1"/> 
        <label for="<?php echo $this->get_field_id('timeago'); ?>"><?php _e('Human readable time format'); ?></label> 
		</p> 
        <?php 
		/* 
		day ago      
        -----------
        twitter button option
        top or button
        display screen name
        width
        align
        show count
		
		version 1.1 date format setting 
		
		*/ 
		
		?> 
         
<?php
	}
}
/** End widget*/ 

function check_twitter_settings() {
	/* admin notice */

	if (isset( $_POST['wptw_safefrm'] ) || 
	wp_verify_nonce( $_POST['wptw_safefrm'], plugin_basename( __FILE__ ) ) )  {
		
		if(isset($_POST['wptp_consumer_key'])) update_option( 'wptp_consumer_key', trim($_POST['wptp_consumer_key']) );
		if(isset($_POST['wptp_consumer_secret'])) update_option( 'wptp_consumer_secret', trim($_POST['wptp_consumer_secret']) ); 
	}
	
	$ck = get_option('wptp_consumer_key');  
	$cs = get_option('wptp_consumer_secret'); 
	
	if(!isset($ck) || empty($ck)) { 
		echo '<div class="error"><p><a href="'.admin_url( 'options-general.php?page=wptw_options').'">Please click here to add Twitter Consumer key and read the introductions.</a></p></div>';
	}
	 
	if(!isset($cs) || empty($cs)) {
		echo '<div class="error"><p><a href="'.admin_url( 'options-general.php?page=wptw_options').'">Please click here to add Twitter Consumer secret and read the introductions.</a></p></div>';
	}
	
}

function wptw_auth_redirect() {
	session_start();
	$ck = get_option('wptp_consumer_key');  
	$cs = get_option('wptp_consumer_secret'); 
	
	if(isset($ck) || !empty($ck) || isset($cs) || !empty($cs)) { 
		$connection = new TwitterOAuth($ck, $cs);			
		$cb = admin_url( 'admin-ajax.php?action=wptw_twitter_callback' );
		$request_token = $connection->getRequestToken($cb);
	 
		$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret']; 
		
		switch ($connection->http_code) {
		  case 200: 
			$redirect = $connection->getAuthorizeURL($token);
			wp_redirect( $redirect );
	 
			exit;
			break;
		  default:
			/* Show notification if something went wrong. */
	
			//wp_redirect( admin_url( 'options-general.php?page=wptw_options&connect=false') );
			exit;
		}


		exit;
	} else {
		wp_redirect( admin_url( 'options-general.php?page=wptw_options&error=1') );
	}
	
}

function wptw_auth_callback_redirect () {
	wp_redirect( admin_url( 'options-general.php?page=wptw_options&callback=false') );
}

function wptw_auth_callback() {
	session_start();
	if(isset($_REQUEST['oauth_verifier'])) {
		update_option( 'wptw_oauth_verifier', $_REQUEST['oauth_verifier'] );
	} 
	$ck = get_option('wptp_consumer_key');  
	$cs = get_option('wptp_consumer_secret'); 
	if(isset($ck) || !empty($ck) || isset($cs) || !empty($cs)) { 
	   
	$connection = new TwitterOAuth($ck, $cs, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']); 
	
	/* Request access tokens from twitter */
	$access_token = $connection->getAccessToken($_GET['oauth_verifier']); 
	
	if(is_array($access_token)) {
		update_option( 'wptp_oauth_token', $access_token['oauth_token'] );
		update_option( 'wptp_oauth_token_secret', $access_token['oauth_token_secret'] );
		update_option( 'wptp_screen_name', $access_token['screen_name'] );  
	} 
	
	/* Save the access tokens. Normally these would be saved in a database for future use. */
	$_SESSION['access_token'] = $access_token; 
	
	/* Remove no longer needed request tokens */ 
	
	/* If HTTP response is 200 continue otherwise send to connect page to retry */
	if (200 == $connection->http_code) {
	  /* The user has been verified and the access tokens can be saved for future use */
	  $_SESSION['status'] = 'verified'; 
	  wp_redirect( admin_url( 'options-general.php?page=wptw_options&connect=true') );  
	} else {
	  $_SESSION['status'] = 'verify false';
	  /* Save HTTP status for error dialog on connnect page.*/
	  wp_redirect( admin_url( 'options-general.php?page=wptw_options&connect=false') );
	}

		
		if(is_array($access_token)) {
			update_option( 'wptw_oauth_token', $access_token['oauth_token'] );
			update_option( 'wptw_oauth_token_secret', $access_token['oauth_token_secret'] );
		}  
	}
	exit;
}

/** Start settting plugin link*/
function wptw_plugin_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=wptw_options">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'wptw_plugin_settings_link' );

/** End settting plugin link*/


 
 
/*
 * Actions to be taken prior to page loading. This is after headers have been set.
 * @uses load-$hook
 */
function wptw_add_screen_meta_boxes() {
 	global $pagenow;
    /* Trigger the add_meta_boxes hooks to allow meta boxes to be added */
    do_action('add_meta_boxes_settings_page_wptw_options', null);
    do_action('add_meta_boxes', 'settings_page_wptw_options', null);
 
    /* Enqueue WordPress' script for handling the meta boxes */
    wp_enqueue_script('postbox');
 
    /* Add screen option: user can choose between 1 or 2 columns (default 2) */
    add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
}
 
/* Prints script in footer. This 'initialises' the meta boxes */
function wptw_print_script_in_footer() {
    
    echo '<script type="text/javascript">
		jQuery(\'.if-js-closed\').removeClass(\'if-js-closed\').addClass(\'closed\');
		jQuery(document).ready(function(){ ';
		global $wp_version;
		if(version_compare($wp_version,"2.7-alpha", "<")){
			echo "add_postbox_toggles(pagenow);";  
		}
		else{
			echo "postboxes.add_postbox_toggles(pagenow);";  
		}	 
	echo '	}); 
	</script>';
    
}


 
/** Start initial widget */
function twitter_widget_init() {
	if ( !is_blog_installed() )
		return; 

	register_widget('WP_Twitter_Widget'); 
	
}
/** End initial widget */


/** Start Admin menu */ 


function wp_widget_plugin_menu() {
	add_options_page( 'WP Widget Options', 'WP Twitter Widget Options', 'manage_options', 'wptw_options', 'wptw_create_menu' );
	
	add_action( 'admin_init', 'wptw_register_settings' );	
	
}


/** Step 3. */
function wptw_create_menu() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
 
	echo '<div class="wpbody">';
	
	/****
	**	Start donation button
	**/
	echo '
	<div style="float:right; margin-right:20px">
	 
	
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank"> 
	<input type="hidden" name="cmd" value="_donations">
	<input type="hidden" name="business" value="982D8YWYN8Q2Y">
	<input type="hidden" name="lc" value="US">
	<input type="hidden" name="item_name" value="Donation for Wordpress Twitter Widget">
	<input type="hidden" name="item_number" value="donation_for_wp_twitter_widget">
	<input type="hidden" name="amount" value="1.00">
	<input type="hidden" name="currency_code" value="USD">
	<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted">
	<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
	<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
	</form> 
	
	
	
	</div>';	
	/****
	**	End donation button
	**/
	
	screen_icon();
	echo '<h2 style="padding-top:12px;">Wordpress Twitter Widget Options</h2>';
	echo '<div class="wrap">';
	echo '<p>For settings Twitter API support display user timeline a collection recent Tweets posted by the user indicated by the screen_name or user_id parameters. Each user timeline protected, user will authenticated for use twitter api approved follower of the timeline</p>'; 
	
	?>
	<form name="saveform" method="post" action="<?php $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="action" value="wptw_options">
        <?php 
		wp_nonce_field( plugin_basename( __FILE__ ), 'wptw_safefrm' );
 
        /* Used to save closed meta boxes and their order */
        ?>
 
        <div id="poststuff">
 
            <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
 
                <div id="post-body-content">
                    <?php do_meta_boxes('','normal',null); ?>
                    <?php do_meta_boxes('','advanced',null); ?>
                </div>
 
                <div id="postbox-container-1" class="postbox-container">
                    <?php do_meta_boxes('','side',null); ?>
                </div>
 
                <div id="postbox-container-2" class="postbox-container">

                </div>
 				<div style="clear:both; height:0px;"></div>
                <?php submit_button(); ?>
            </div> <!-- #post-body -->
 
        </div> <!-- #poststuff -->
 	
    </form>
	<?php 
	echo ' 
		</div>
	</div>';
	  
	wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
	wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); 
	 
}
 
function wptw_meta_box_add() {  
    //add_meta_box( 'settings-twitter-app', 'Plugin support', 'wptw_meta_box_donation', 'settings_page_wptw_options', 'side');   
	add_meta_box( 'wptw-sta',          'Connect to your Application',             'wptw_meta_box_cb', 'settings_page_wptw_options');
	
	// verison 1.1
	// shortcode [optonal] 
	// add_meta_box( 'wptw-bc',          'Twitter Application Settings',             'wptw_meta_box_connect', 'settings_page_wptw_options');
}  

function wptw_meta_box_connect( $post ) {
	$ck = get_option('wptp_consumer_key');  
	$cs = get_option('wptp_consumer_secret');
	if(isset($ck) || !empty($ck) || isset($cs) || !empty($cs)) { 
	
	}
	
}

function wptw_meta_box_cb( $post ) {
  // Use nonce for verification
  // wp_nonce_field( plugin_basename( __FILE__ ), 'safefrm_twsettings' ); 
   
  $ck = get_option('wptp_consumer_key');
  $cs = get_option('wptp_consumer_secret');  
  $ot = get_option('wptp_oauth_token');  
  
  $error = false; 
   
  	
	echo '
		<table style="width:100%; font-size:13px;">
		<tbody>
			<tr valign="top">
				<th scope="row" style="width:10%; text-align:right; line-height:25px;">
					 
				</th>
				<td>';
				
	// introduct to settings up twitter
	echo '
			<h4>How to use wordpress twitter widget plugins.</h4>
			1. Create Twitter application by click this link <a href="https://dev.twitter.com/apps/new" target="_blank">https://dev.twitter.com/apps/new</a><br>
			2. In Application details Pages
			<ul style="list-style:circle inside none; padding-left:10px;">
				<li><strong>Name</strong> Your application name</li>
				<li><strong>Description</strong></li>
				<li>Your application description</li> 
				<li><strong>Website</strong> Your application url accessible home: <input type="text" value="'. home_url() .'" style="width:300px" onClick="this.select();"> </li>
				<li><strong>Callback URL</strong> copy here <input type="text" value="'.admin_url( 'admin-ajax.php?action=wptw_twitter_callback' ).'" style="width:500px" onClick="this.select();"> and paste into callback url.</li> 
				<li><strong>Developer Rules of the Road</strong> click agreement when read understand this agreement</li> 
				<li>Enter image you can see CAPTCHA and Create your Twitter application.</li>
			</ul>
			3. After create an application Twitter show application information, then look at OAuth settings session <strong>copy Consumer key and Consumer secret paste into input box</strong>.<br><br>
			
			'; 
					 
	echo '	</td>
			</tr>  
		</tbody>
		</table>
	';
	
  	echo '
		<table style="width:100%;';
	if($error) echo ' border:1px solid red;';	
	echo '">
		<tbody>
			<tr valign="top">
				<th scope="row" style="width:25%; text-align:right; line-height:25px;">
					 
				</th>
				<td>
					 
				</td>
			</tr> 	
			<tr valign="top">
				<th scope="row" style="width:25%; text-align:right; line-height:25px;">
					<label for="wptp_consumer_key">Consumer key</label>
				</th>
				<td>
					<input id="wptp_consumer_key" name="wptp_consumer_key" value="'.esc_attr($ck).'" size="60" type="text" style="padding:6px;">
				</td>
			</tr> 
			<tr valign="top">
				<th scope="row" style="width:25%; text-align:right; line-height:25px;">
					<label for="wptp_consumer_secret">Consumer secret</label>
				</th>
				<td>
					<input id="wptp_consumer_secret" name="wptp_consumer_secret" value="'.esc_attr($cs).'" size="60" type="text" style="padding:6px;">
				</td>
			</tr> ';  
			
			if($error) {
				echo '<tr valign="top">
				<th scope="row" style="width:25%; text-align:right; line-height:25px;">
					
				</th>
				<td>
					<span style="color:red">Invalid Consumer key or Consumer secret</span>
				</td>
			</tr> ';
			}
			
			if(isset($ck) && !empty($ck) && isset($cs) && !empty($cs) && !$error) { 		
			echo '	<tr valign="top">
						<th scope="row" style="width:25%; text-align:right; line-height:25px;">
							<label for="wptp_consumer_secret">Connnect to your application</label>
						</th>
						<td>'; 
			
			if(!isset($ot) || empty($ot)) {
				echo '<div style="border:1px solid red">';
			}			
						
				echo '				<a href="'.admin_url( 'admin-ajax.php?action=wptw_twitter_auth' ).'" title="Click here to connect to your twitter appliction"><img src="https://dev.twitter.com/sites/default/files/images_documentation/sign-in-with-twitter-gray.png" style="vertical-align:middle"></a>';
				
			if(!isset($ot) || empty($ot)) {
				echo '<strong style="color:red"> <== Click here for connect to your application </strong></div>';
			}	
				
			echo '		</td>
					</tr> 	';	
			}
			echo '	</tbody>
				</table><br>
			';



echo '
		<table style="width:100%; font-size:13px;">
		<tbody>
			<tr valign="top">
				<th scope="row" style="width:10%; text-align:right; line-height:25px;">
					 
				</th>
				<td>';
				
	// introduct to settings up twitter
	echo ' 
			4. Click Sign in with twitter.
			<br>';
			
	if(isset($ot) || !empty($ot)) {		
	echo '		
			5. Congratulation now you can drag and drop WP Twitter wiget into your widget slidbar <a href="'.admin_url('widgets.php').'">Goto widget page</a><br>  ';
			
			 
	}
	echo '	</td>
			</tr>  
		</tbody>
		</table>
	'; 

  
}
function wptw_meta_box_donation( $post ) {
	echo '';

}

// backwards compatible (before WP 3.0) 

function wptw_register_settings() {
	//register our settings
	register_setting( 'wptw_meta-settings', 'wptp_consumer_key' );
	register_setting( 'wptw_meta-settings', 'wptp_consumer_key' );
	register_setting( 'wptw_meta-settings', 'wptp_oauth_token' ); 
	register_setting( 'wptw_meta-settings', 'wptp_oauth_token_secret' );
	register_setting( 'wptw_meta-settings', 'wptp_screen_name' );  
}

function wptw_display() {
	wp_register_style( 'wptw-style', plugins_url('/css/wptw.css', __FILE__) );
  	wp_enqueue_style( 'wptw-style' );
} 