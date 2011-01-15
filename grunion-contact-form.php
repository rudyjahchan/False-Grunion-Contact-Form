<?php

/*
Plugin Name: False Grunion Contact Form
Description: Add a contact form to any post, page or text widget.  Emails will be sent to the post's author by default, or any email address you choose.  As seen on WordPress.com.
Plugin URI: http://automattic.com/#
Author: Automattic, Inc.
Author URI: http://automattic.com/
Version: 1.0
*/

define( 'GRUNION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GRUNION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( is_admin() )
	require_once GRUNION_PLUGIN_DIR . '/admin.php';

// take the content of a contact-form shortcode and parse it into a list of field types
function contact_form_parse( $content ) {
	
	// first parse all the contact-field shortcodes into an array
	global $contact_form_fields, $grunion_form;
	$contact_form_fields = array();
	
	$out = do_shortcode( $content );
	
	if ( empty($contact_form_fields) || !is_array($contact_form_fields) ) {
		// default form: same as the original Grunion form
		$default_form = '
		[contact-field label="'.__('Name').'" type="name" required="true" /]
		[contact-field label="'.__('Email').'" type="email" required="true" /]
		[contact-field label="'.__('Website').'" type="url" /]';
		if ( 'yes' == strtolower($grunion_form->show_subject) )
			$default_form .= '
			[contact-field label="'.__('Subject').'" type="subject" /]';
		$default_form .= '
		[contact-field label="'.__('Message').'" type="textarea" /]';

		$out = do_shortcode( $default_form );
	}

	return $out;
}

function contact_form_render_field( $field ) {
	global $contact_form_last_id, $contact_form_errors, $contact_form_fields, $current_user, $user_identity;
	
	$r = '';
	
	$field_id = $field['id'];
	if ( isset($_POST[ $field_id ]) ) {
		$field_value = stripslashes( $_POST[ $field_id ] );
	} elseif ( is_user_logged_in() ) {
		// Special defaults for logged-in users
		if ( $field['type'] == 'email' )
			$field_value = $current_user->data->user_email;
		elseif ( $field['type'] == 'name' )
			$field_value = $user_identity;
		elseif ( $field['type'] == 'url' )
			$field_value = $current_user->data->user_url;
		else
			$field_value = $field['default'];
	} else {
		$field_value = $field['default'];
	}

	if ( $field['type'] == 'email' ) {
		$r .= "\n<div>\n";
		$r .= "\t\t<label for='".esc_attr($field_id)."' class='grunion-field-label ".esc_attr($field['type']) . ( contact_form_is_error($field_id) ? ' form-error' : '' ) . "'>" . htmlspecialchars( $field['label'] ) . ( $field['required'] ? '<span>'. __("(required)") . '</span>' : '' ) . "</label>\n";
		$r .= "\t\t<input type='text' name='".esc_attr($field_id)."' id='".esc_attr($field_id)."' value='".esc_attr($field_value)."' class='".esc_attr($field['type'])."'/>\n";
		$r .= "\t</div>\n";
	} elseif ( $field['type'] == 'textarea' ) {
		$r .= "\n<div>\n";
		$r .= "\t\t<label for='".esc_attr($field_id)."' class='".esc_attr($field['type']) . ( contact_form_is_error($field_id) ? ' form-error' : '' ) . "'>" . htmlspecialchars( $field['label'] ) . ( $field['required'] ? '<span>'. __("(required)") . '</span>' : '' ) . "</label>\n";
		$r .= "\t\t<textarea name='".esc_attr($field_id)."' id='contact-form-comment-".esc_attr($field_id)."' rows='20'>".htmlspecialchars($field_value)."</textarea>\n";
		$r .= "\t</div>\n";
	} elseif ( $field['type'] == 'radio' ) {
		$r .= "\t<div><label class='".esc_attr($field['type']) . ( contact_form_is_error($field_id) ? ' form-error' : '' ) . "'>" . htmlspecialchars( $field['label'] ) . ( $field['required'] ? '<span>'. __("(required)") . '</span>' : '' ) . "</label>\n";
		foreach ( $field['options'] as $option ) {
			$r .= "\t\t<input type='radio' name='".esc_attr($field_id)."' value='".esc_attr($option)."' class='".esc_attr($field['type'])."' ".( $option == $field_value ? "checked='checked' " : "")." />\n";
 			$r .= "\t\t<label class='".esc_attr($field['type']) . ( contact_form_is_error($field_id) ? ' form-error' : '' ) . "'>". htmlspecialchars( $option ) . "</label>\n";
			$r .= "\t\t<div></div>\n";
		}
		$r .= "\t\t</div>\n";
	} elseif ( $field['type'] == 'checkbox' ) {
		$r .= "\t<div>\n";
		$r .= "\t\t<input type='checkbox' name='".esc_attr($field_id)."' value='".__('Yes')."' class='".esc_attr($field['type'])."' ".( $field_value ? "checked='checked' " : "")." />\n";
		$r .= "\t\t<label class='".esc_attr($field['type']) . ( contact_form_is_error($field_id) ? ' form-error' : '' ) . "'>\n";
		$r .= "\t\t". htmlspecialchars( $field['label'] ) . ( $field['required'] ? '<span>'. __("(required)") . '</span>' : '' ) . "</label>\n";
		$r .= "\t</div>\n";
	} elseif ( $field['type'] == 'select' ) {
		$r .= "\n<div>\n";
		$r .= "\t\t<label for='".esc_attr($field_id)."' class='".esc_attr($field['type']) . ( contact_form_is_error($field_id) ? ' form-error' : '' ) . "'>" . htmlspecialchars( $field['label'] ) . ( $field['required'] ? '<span>'. __("(required)") . '</span>' : '' ) . "</label>\n";
		$r .= "\t<select name='".esc_attr($field_id)."' id='".esc_attr($field_id)."' value='".esc_attr($field_value)."' class='".esc_attr($field['type'])."'/>\n";
		foreach ( $field['options'] as $option ) {
			$r .= "\t\t<option".( $option == $field_value ? " selected='selected'" : "").">". htmlspecialchars($option)."</option>\n";
		}
		$r .= "\t</select>\n";
		$r .= "\t</div>\n";
	} else {
		// default: text field
		// note that any unknown types will produce a text input, so we can use arbitrary type names to handle
		// input fields like name, email, url that require special validation or handling at POST
		$r .= "\n<div>\n";
		$r .= "\t\t<label for='".esc_attr($field_id)."' class='".esc_attr($field['type']) . ( contact_form_is_error($field_id) ? ' form-error' : '' ) . "'>" . htmlspecialchars( $field['label'] ) . ( $field['required'] ? '<span>'. __("(required)") . '</span>' : '' ) . "</label>\n";
		$r .= "\t\t<input type='text' name='".esc_attr($field_id)."' id='".esc_attr($field_id)."' value='".esc_attr($field_value)."' class='".esc_attr($field['type'])."'/>\n";
		$r .= "\t</div>\n";
	}
	
	return $r;
}

function contact_form_validate_field( $field ) {
	global $contact_form_last_id, $contact_form_errors, $contact_form_values;

	$field_id = $field['id'];
	$field_value = isset($_POST[ $field_id ]) ? stripslashes($_POST[ $field_id ]) : '';

	if ( $field['required'] && !trim($field_value) ) {
		if ( !is_wp_error($contact_form_errors) )
			$contact_form_errors = new WP_Error();
		$contact_form_errors->add( $field_id, sprintf( __('%s is required'), $field['label'] ) );
	}
	
	$contact_form_values[ $field_id ] = $field_value;
}

function contact_form_is_error( $field_id ) {
	global $contact_form_errors;
	
	return ( is_wp_error( $contact_form_errors ) && $contact_form_errors->get_error_message( $field_id ) );
}

// generic shortcode that handles all of the major input types
// this parses the field attributes into an array that is used by other functions for rendering, validation etc
function contact_form_field( $atts, $content, $tag ) {
	global $contact_form_fields, $contact_form_last_id, $grunion_form;
	
	$field = shortcode_atts( array(
		'label' => null,
		'type' => 'text',
		'required' => false,
		'options' => array(),
		'id' => null,
		'default' => null,
	), $atts);
	
	// special default for subject field
	if ( $field['type'] == 'subject' && is_null($field['default']) )
		$field['default'] = $grunion_form->subject;
	
	// allow required=1 or required=true
	if ( $field['required'] == '1' || strtolower($field['required']) == 'true' )
		$field['required'] = true;
	else
		$field['required'] = false;
		
	// parse out comma-separated options list
	if ( !empty($field['options']) && is_string($field['options']) )
		$field['options'] = array_map('trim', explode(',', $field['options']));

	// make a unique field ID based on the label, with an incrementing number if needed to avoid clashes
	$id = $field['id'];
	if ( empty($id) ) {
		$id = sanitize_title_with_dashes( $contact_form_last_id . '-' . $field['label'] );
		$i = 0;
		while ( isset( $contact_form_fields[ $id ] ) ) {
			$i++;
			$id = sanitize_title_with_dashes( $contact_form_last_id . '-' . $field['label'] . '-' . $i );
		}
		$field['id'] = $id;
	}
	
	$contact_form_fields[ $id ] = $field;
	
	if ( $_POST )
		contact_form_validate_field( $field );
	
	return contact_form_render_field( $field );
}

add_shortcode('contact-field', 'contact_form_field');


function contact_form_shortcode( $atts, $content ) {
	global $post;

	$default_to = get_option( 'admin_email' );
	$default_subject = "[" . get_option( 'blogname' ) . "]";

	if ( !empty( $atts['widget'] ) && $atts['widget'] ) {
		$default_subject .=  " Sidebar";
	} elseif ( $post->ID ) {
		$default_subject .= " ". wp_kses( $post->post_title, array() );
		$post_author = get_userdata( $post->post_author );
		$default_to = $post_author->user_email;
	}

	extract( shortcode_atts( array(
		'to' => $default_to,
		'subject' => $default_subject,
		'show_subject' => 'no', // only used in back-compat mode
		'widget' => 0 //This is not exposed to the user. Works with contact_form_widget_atts
	), $atts ) );

	if ( ( function_exists( 'faux_faux' ) && faux_faux() ) || is_feed() )
		return '[contact-form]';

	global $wp_query, $grunion_form, $contact_form_errors, $contact_form_values, $user_identity, $contact_form_last_id;
	
	// used to store attributes, configuration etc for access by contact-field shortcodes
	$grunion_form = new stdClass();
	$grunion_form->to = $to;
	$grunion_form->subject = $subject;
	$grunion_form->show_subject = $show_subject;

	if ( $widget )
		$id = 'widget-' . $widget;
	elseif ( is_singular() )
		$id = $wp_query->get_queried_object_id();
	else
		$id = $GLOBALS['post']->ID;
	if ( !$id ) // something terrible has happened
		return '[contact-form]';

	if ( $id == $contact_form_last_id )
		return;
	else
		$contact_form_last_id = $id;

	ob_start();
		wp_nonce_field( 'contact-form_' . $id );
		$nonce = ob_get_contents();
	ob_end_clean();


	$body = contact_form_parse( $content );

	$r = "<div id='contact-form-$id'>\n";
	
	$errors = array();
	if ( is_wp_error( $contact_form_errors ) && $errors = (array) $contact_form_errors->get_error_codes() ) {
		$r .= "<div class='form-error'>\n<h3>" . __( 'Error!' ) . "</h3>\n<ul class='form-errors'>\n";
		foreach ( $contact_form_errors->get_error_messages() as $message )
			$r .= "\t<li class='form-error-message'>$message</li>\n";
		$r .= "</ul>\n</div>\n\n";
	}
	
	$r .= "<form action='#contact-form-$id' method='post' class='contact-form commentsblock'>\n";
	$r .= '<div>';
	$r .= $body;
	$r .= "\t<p class='contact-submit'>\n";
	$r .= "\t\t<input type='submit' value='" . __( "Submit &#187;" ) . "' class='pushbutton-wide'/>\n";
	$r .= "\t\t$nonce\n";
	$r .= "\t\t<input type='hidden' name='contact-form-id' value='$id' />\n";
	$r .= "\t</p>\n";
	$r .= '</div>';
	$r .= "</form>\n</div>";
	
	// form wasn't submitted, just a GET
	if ( empty($_POST) )
		return $r;


	if ( is_wp_error($contact_form_errors) )
		return $r;

	
	$emails = str_replace( ' ', '', $to );
	$emails = explode( ',', $emails );
	foreach ( (array) $emails as $email ) {
		if ( is_email( $email ) && ( !function_exists( 'is_email_address_unsafe' ) || !is_email_address_unsafe( $email ) ) )
			$valid_emails[] = $email;
	}

	$to = ( $valid_emails ) ? $valid_emails : $default_to;

	$message_sent = contact_form_send_message( $to, $subject, $widget );

	if ( is_array( $contact_form_values ) )
		extract( $contact_form_values );

	if ( !isset( $comment_content ) )
		$comment_content = '';
	else
		$comment_content = wp_specialchars( $comment_content );


	$r = "<div id='contact-form-$id'>\n";

	$errors = array();
	if ( is_wp_error( $contact_form_errors ) && $errors = (array) $contact_form_errors->get_error_codes() ) :
		$r .= "<div class='form-error'>\n<h3>" . __( 'Error!' ) . "</h3>\n<p>\n";
		foreach ( $contact_form_errors->get_error_messages() as $message )
			$r .= "\t$message\n";
		$r .= "</p>\n</div>\n\n";
	else :
		$r .= "<h3>" . __( 'Message Sent' ) . "</h3>\n\n";
		$r .= wpautop( $comment_content ) . "</div>";
		
		// Reset for multiple contact forms. Hacky
		$contact_form_values['comment_content'] = '';

		return $r;
	endif;

	return $r;
}
add_shortcode( 'contact-form', 'contact_form_shortcode' );

function contact_form_send_message( $to, $subject, $widget ) {
	global $post;
	
 	if ( !isset( $_POST['contact-form-id'] ) )
		return;
		
	if ( ( $widget && 'widget-' . $widget != $_POST['contact-form-id'] ) || ( !$widget && $post->ID != $_POST['contact-form-id'] ) )
		return;

	if ( $widget )
		check_admin_referer( 'contact-form_widget-' . $widget );
	else
		check_admin_referer( 'contact-form_' . $post->ID );

	global $contact_form_values, $contact_form_errors, $current_user, $user_identity;
	global $contact_form_fields;
	
	// compact the fields and values into an array of Label => Value pairs
	// also find values for comment_author_email and other significant fields
	$all_values = $extra_values = array();
	foreach ( $contact_form_fields as $id => $field ) {
		if ( $field['type'] == 'email' && empty( $comment_author_email ) )
			$comment_author_email = $contact_form_values[ $id ];
		elseif ( $field['type'] == 'name' && empty( $comment_author ) )
			$comment_author = $contact_form_values[ $id ];
		elseif ( $field['type'] == 'url' && empty( $comment_author_url ) )
			$comment_author_url = $contact_form_values[ $id ];
		elseif ( $field['type'] == 'textarea' && empty( $comment_content ) )
			$comment_content = $contact_form_values[ $id ];
		else
			$extra_values[ $field['label'] ] = $contact_form_values[ $id ];
		$all_values[ $field['label'] ] = $contact_form_values[ $id ];
	}

/*
	$contact_form_values = array();
	$contact_form_errors = new WP_Error();

	list($comment_author, $comment_author_email, $comment_author_url) = is_user_logged_in() ?
		add_magic_quotes( array( $user_identity, $current_user->data->user_email, $current_user->data->user_url ) ) :
		array( $_POST['comment_author'], $_POST['comment_author_email'], $_POST['comment_author_url'] );
*/

	$comment_author = stripslashes( apply_filters( 'pre_comment_author_name', $comment_author ) );

	$comment_author_email = stripslashes( apply_filters( 'pre_comment_author_email', $comment_author_email ) );

	$comment_author_url = stripslashes( apply_filters( 'pre_comment_author_url', $comment_author_url ) );
	if ( 'http://' == $comment_author_url )
		$comment_author_url = '';

	$comment_content = stripslashes( $comment_content );
	$comment_content = trim( wp_kses( $comment_content, array() ) );

	if ( empty( $contact_form_subject ) )
		$contact_form_subject = $subject;
	else
		$contact_form_subject = trim( wp_kses( $contact_form_subject, array() ) );
		
	$comment_author_IP = $_SERVER['REMOTE_ADDR'];

	$vars = array( 'comment_author', 'comment_author_email', 'comment_author_url', 'contact_form_subject', 'comment_author_IP' );
	foreach ( $vars as $var )
		$$var = str_replace( array("\n", "\r" ), '', $$var ); // I don't know if it's possible to inject this
	$vars[] = 'comment_content';

	$contact_form_values = compact( $vars );

	$spam = '';
	$akismet_values = contact_form_prepare_for_akismet( $contact_form_values );
	$is_spam = contact_form_is_spam_akismet( $akismet_values );
	if ( is_wp_error( $is_spam ) )
		return; // abort
	else if ( $is_spam )
		$spam = '***SPAM*** ';

	if ( !$comment_author )
		$comment_author = $comment_author_email;
		
	$headers =	"From: $comment_author <$comment_author_email>\n" .
			"Reply-To: $comment_author_email\n" .
			"Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

	$subject = apply_filters( 'contact_form_subject', $spam . $contact_form_subject );

	$time = date_i18n( __('l F j, Y \a\t g:i a'), current_time( 'timestamp' ) );
	
	$extra_content = '';
	
	foreach ( $extra_values as $label => $value )
		$extra_content = $label . ': ' . trim($value) . "\n";

	$message = __( "Name:" ) . " " . $comment_author . "
" . __( "Email:" ) . " " . $comment_author_email . "
" . __( "Website:" ) . " " . $comment_author_url . "

$comment_content
$extra_content

" . __( "Time:" ) . " " . $time . "
" . __( "IP Address:" ) . " " . $comment_author_IP . "

";

	if ( is_user_logged_in() ) {
		$message .= sprintf(
			__( "Sent by a verified %s user." ),
			isset( $GLOBALS['current_site']->site_name ) && $GLOBALS['current_site']->site_name ? $GLOBALS['current_site']->site_name : '"' . get_option( 'blogname' ) . '"'
		);
	} else {
		$message .= __( "Sent by an unverified visitor to your site." );
	}

	$message = apply_filters( 'contact_form_message', $message );

	$to = apply_filters( 'contact_form_to', $to );

	// keep a copy of the feedback as a custom post type
	$feedback_mysql_time = current_time( 'mysql' );
	$feedback_title = "{$comment_author} - {$feedback_mysql_time}";
	$feedback_status = 'publish';
	if ( $is_spam )
		$feedback_status = 'spam';

	$post_id = wp_insert_post( array(
		'post_date'		=> $feedback_mysql_time,
		'post_type'		=> 'feedback',
		'post_status'	=> $feedback_status,
		'post_parent'	=> $post->ID,
		'post_title'	=> $feedback_title,
		'post_content'	=> $comment_content . "\n<!--more-->\n" . "AUTHOR: {$comment_author}\nAUTHOR EMAIL: {$comment_author_email}\nAUTHOR URL: {$comment_author_url}\nSUBJECT: {$contact_form_subject}\nIP: {$comment_author_IP}\n" . print_r( $all_values, TRUE ), // so that search will pick up this data
		'post_name'		=> md5( $feedback_title )
	) );
	update_post_meta( $post_id, '_feedback_author', $comment_author );
	update_post_meta( $post_id, '_feedback_author_email', $comment_author_email 
);
	update_post_meta( $post_id, '_feedback_author_url', $comment_author_url );
	update_post_meta( $post_id, '_feedback_subject', $contact_form_subject );
	update_post_meta( $post_id, '_feedback_ip', $comment_author_IP );
	update_post_meta( $post_id, '_feedback_all_fields', $all_values );
	update_post_meta( $post_id, '_feedback_extra_fields', $extra_values );
	update_post_meta( $post_id, '_feedback_akismet_values', $akismet_values );
	update_post_meta( $post_id, '_feedback_email', array( 'to' => $to, 'subject' => $subject, 'message' => $message, 'headers' => $headers ) );

	// Only send the email if it's not spam
	if ( !$is_spam )
		return wp_mail( $to, $subject, $message, $headers );
	return true;
}

// populate an array with all values necessary to submit a NEW comment to Akismet
// note that this includes the current user_ip etc, so this should only be called when accepting a new item via $_POST
function contact_form_prepare_for_akismet( $form ) {

	$form['comment_type'] = 'contact_form';
	$form['user_ip']      = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
	$form['user_agent']   = $_SERVER['HTTP_USER_AGENT'];
	$form['referrer']     = $_SERVER['HTTP_REFERER'];
	$form['blog']         = get_option( 'home' );

	$ignore = array( 'HTTP_COOKIE' );

	foreach ( $_SERVER as $k => $value )
		if ( !in_array( $k, $ignore ) && is_string( $value ) )
			$form["$k"] = $value;
			
	return $form;
}

// submit an array to Akismet. If you're accepting a new item via $_POST, run it through contact_form_prepare_for_akismet() first
function contact_form_is_spam_akismet( $form ) {
	global $akismet_api_host, $akismet_api_port;

	$query_string = '';
	foreach ( array_keys( $form ) as $k )
		$query_string .= $k . '=' . urlencode( $form[$k] ) . '&';

	$response = akismet_http_post( $query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port );
	$result = false;
	if ( 'true' == trim( $response[1] ) ) // 'true' is spam
		$result = true;
	return apply_filters( 'contact_form_is_spam_akismet', $result, $form );
}

// submit a comment as either spam or ham
// $as should be a string (either 'spam' or 'ham'), $form should be the comment array
function contact_form_akismet_submit( $as, $form ) {
	global $akismet_api_host, $akismet_api_port;
	
	if ( !in_array( $as, array( 'ham', 'spam' ) ) )
		return false;

	$query_string = '';
	foreach ( array_keys( $form ) as $k )
		$query_string .= $k . '=' . urlencode( $form[$k] ) . '&';

	$response = akismet_http_post( $query_string, $akismet_api_host, '/1.1/submit-'.$as, $akismet_api_port );
	return trim( $response[1] );
}

function contact_form_widget_atts( $text ) {
	static $widget = 0;
	
	$widget++;

	return str_replace( '[contact-form', '[contact-form widget="' . $widget . '"', $text );
}
add_filter( 'widget_text', 'contact_form_widget_atts', 0 );

function contact_form_widget_shortcode_hack( $text ) {
	$old = $GLOBALS['shortcode_tags'];
	remove_all_shortcodes();
	add_shortcode( 'contact-form', 'contact_form_shortcode' );
	$text = do_shortcode( $text );
	$GLOBALS['shortcode_tags'] = $old;
	return $text;
}

function contact_form_init() {
	if ( function_exists( 'akismet_http_post' ) )
		add_filter( 'contact_form_is_spam', 'contact_form_is_spam_akismet', 10, 2 );
	if ( !has_filter( 'widget_text', 'do_shortcode' ) )
		add_filter( 'widget_text', 'contact_form_widget_shortcode_hack', 5 );

	// custom post type we'll use to keep copies of the feedback items
	register_post_type( 'feedback', array(
		'labels'	=> array(
			'name'			=> __( 'Feedbacks' ),
			'singular_name'	=> __( 'Feedback' ),
			'search_items'	=> __( 'Search Feedback' ),
			'not_found'		=> __( 'No feedback found' ),
			'not_found_in_trash'	=> __( 'No feedback found' )
		),
		'menu_icon'		=> GRUNION_PLUGIN_URL . '/images/grunion-menu.png',
		'show_ui'		=> TRUE,
		'public'		=> FALSE,
		'rewrite'		=> FALSE,
		'query_var'		=> FALSE,
		'capability_type'	=> 'page'
	) );

	register_post_status( 'spam', array(
		'label'			=> 'Spam',
		'public'		=> FALSE,
		'exclude_from_search'	=> TRUE,
		'show_in_admin_all_list'=> FALSE,
		'label_count' => _n_noop( 'Spam <span class="count">(%s)</span>', 'Spam <span class="count">(%s)</span>' ),
		'protected'		=> TRUE,
		'_builtin'		=> FALSE
	) );
}
add_action( 'init', 'contact_form_init' );

/**
 * Add a contact form button to the post composition screen
 */
add_action( 'media_buttons', 'grunion_media_button', 999 );
function grunion_media_button( ) {
	global $post_ID, $temp_ID;
	$iframe_post_id = (int) (0 == $post_ID ? $temp_ID : $post_ID);
	$title = esc_attr( __( 'Add a custom form' ) );
	$plugin_url = esc_url( GRUNION_PLUGIN_URL );
	$site_url = site_url( "/?post_id=$iframe_post_id&amp;grunion=form-builder&amp;TB_iframe=true&amp;width=768" );

	echo '<a href="' . $site_url . '&id="add_form" class="thickbox" title="' . $title . '"><img src="' . $plugin_url . '/images/grunion-form.png" alt="' . $title . '" width="13" height="12" /></a>';
}

add_action( 'parse_request', 'parse_wp_request' );

function parse_wp_request( $wp ) {
	if ( !empty( $_GET['grunion'] ) && $_GET['grunion'] == 'form-builder' ) {
		display_form_view( );
		exit;
	}
}

function display_form_view( ) {
	require_once GRUNION_PLUGIN_DIR . 'grunion-form-view.php';
}

function menu_alter() {
    echo '
	<style>
	#menu-posts-feedback .wp-menu-image img { display: none; }
	#adminmenu .menu-icon-feedback:hover div.wp-menu-image, #adminmenu .menu-icon-feedback.wp-has-current-submenu div.wp-menu-image, #adminmenu .menu-icon-feedback.current div.wp-menu-image { background: url("' .GRUNION_PLUGIN_URL . '/images/grunion-menu-hover.png") no-repeat 6px 7px !important; }
	#adminmenu .menu-icon-feedback div.wp-menu-image, #adminmenu .menu-icon-feedback div.wp-menu-image, #adminmenu .menu-icon-feedback div.wp-menu-image { background: url("' . GRUNION_PLUGIN_URL . '/images/grunion-menu.png") no-repeat 6px 7px !important; }
	</style>';
}

add_action('admin_head', 'menu_alter');
