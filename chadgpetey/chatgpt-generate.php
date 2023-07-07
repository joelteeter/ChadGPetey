<?php
/*
Plugin Name: Chad G Petey
Description: Use ChatGPT to generate content, mixing in creative commons images 
Version: 1.0
Author: Joel Teeter
*/
 
define( 'CHATGPT_GENERATE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
 
require_once( CHATGPT_GENERATE__PLUGIN_DIR . 'chatgpt-generate-js.php' );
require_once( CHATGPT_GENERATE__PLUGIN_DIR . 'chatgpt-generate-option.php' );
require_once( CHATGPT_GENERATE__PLUGIN_DIR . 'chatgpt-generate-settings.php' );

// Fires after WordPress has finished loading, but before any headers are sent.
add_action( 'init', 'script_enqueuer' );

function script_enqueuer() {
   
   // Register the JS file with a unique handle, file location, and an array of dependencies
   wp_register_script( "generate_script", plugin_dir_url(__FILE__).'/js/generate_script.js', array('jquery') );
   
   // localize the script to your domain name, so that you can reference the url to admin-ajax.php file easily
   wp_localize_script( 'generate_script', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
   
   // enqueue jQuery library and the script you registered above
   wp_enqueue_script( 'jquery' );
   wp_enqueue_script( 'generate_script' );

   // enqueue styles
   wp_enqueue_style( 'chad-styles', plugins_url( '/css/chadstyles.css', __FILE__ ) );
}

function enqueuing_admin_scripts(){
 
      // Register the JS file with a unique handle, file location, and an array of dependencies
   wp_register_script( "generate_script", plugin_dir_url(__FILE__).'/js/generate_script.js', array('jquery') );
   
   // localize the script to your domain name, so that you can reference the url to admin-ajax.php file easily
   wp_localize_script( 'generate_script', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
   
   // enqueue jQuery library and the script you registered above
   wp_enqueue_script( 'jquery' );
   wp_enqueue_script( 'generate_script' );

   // enqueue styles
   wp_enqueue_style( 'chad-styles', plugins_url( '/css/chadstyles.css', __FILE__ ) );

}

add_action( 'admin_enqueue_scripts', 'enqueuing_admin_scripts' );

/* Options page */
add_action('admin_menu', 'chat_gpt_generate_plugin_menu');
function chat_gpt_generate_plugin_menu() {
	add_menu_page('ChatGPT Generate Plugin Settings', 'ChatGPT Generate Plugin Settings', 'administrator', 'chatgpt-generate-plugin-settings', 'chat_gpt_generate_plugin_settings_page', 'dashicons-admin-generic');
}
function chat_gpt_generate_plugin_settings_page() {
   $nonce = wp_create_nonce("chad_post_nonce");
   ?>
   <div class="wrap">
      <h2>Staff Details</h2>
      
      <form method="post" action="options.php">
         <?php settings_fields( 'chat-gpt-generate-settings' ); ?>
         <?php do_settings_sections( 'chat-gpt-generate-settings' ); ?>
         <table class="form-table">
            <tr valign="top">
            <th scope="row">Secret Key</th>
            <td><input type="text" name="api_key" value="<?php echo esc_attr( get_option('api_key') ); ?>" /></td>
            </tr>
               
            <tr valign="top">
                  <th scope="row">API Model</th>
                  <td><input type="text" name="chat_model" value="<?php echo esc_attr( get_option('chat_model') ); ?>" /></td>
            </tr>
            <tr>
                  <td>see <a href="https://platform.openai.com/docs/models/gpt-3-5"> the models for 3.5 (use text-davinci-003)</a></td>
            </tr>
            <tr>
                  <td>if using 4 see <a href="https://platform.openai.com/docs/models/gpt-4"> the models for 4 (use gpt-4)</a></td>
            </tr>
            <tr>
                  <td>This parameter allows you to specify which language model you want the API to use to generate text</td>

            </tr>
            
            <tr valign="top">
            <th scope="row">Max Token</th>
            <td><input type="text" name="max_tokens" value="<?php echo esc_attr( get_option('max_tokens') ); ?>" /></td>
            </tr>
            <tr>
            <td>This parameter controls the maximum length of the generated text in terms of the number of tokens (words, phrases, etc.). You can set a specific number or use the default value of 2048.</td>
            </tr>

            <tr valign="top">
            <th scope="row">Temperature</th>
            <td><input type="text" name="temperature" value="<?php echo esc_attr( get_option('temperature') ); ?>" /></td>
            </tr>
            <tr>
            <td>This parameter controls the “creativity” of the generated text. A higher temperature value will result in more unpredictable and creative responses, while a lower temperature will result in more predictable and “safe” responses.</td>
            </tr>

            <tr valign="top">
            <th scope="row">Post Status</th>
            <td><input type="text" name="post_status" value="<?php echo esc_attr( get_option('post_status') ); ?>" /></td>
            </tr>
            <tr>
            <td>This parameter controls the status of the generated post.  publish, draft, etc.

            <tr valign="top">
            <th scope="row">Author ID</th>
            <td><input type="text" name="author_id" value="<?php echo esc_attr( get_option('author_id') ); ?>" /></td>
            </tr>
            <tr>
            <td>The ID of the author that will be creating the content (Chad G. Petey)</td>
            </tr>
         </table>
         
         <?php submit_button(); ?>
      
      </form>
      <div id="generate-content-form">
         <h2>Let Chad create some content for you!</h2>
         <label for="post_title">Post Title:</label>
         <input type="text" id="post-title" name="post_title">

         <label for="chad_prompt">Chad's Prompt ""Write a travel blog entry about... "":</label>
         <input id="chad-prompt" type="text" name="chad_prompt">
         <br />
         <label for="text">Category for the post</label>
         <?php 
         $args = array(
            'show_option_none' => 'Select Category',
            'orderby' => 'name',
            'echo' => 1,
            'name' => 'category',
            'id' => '',
            'taxonomy' => 'category',
            'hide_empty' => 0,
         );         
         wp_dropdown_categories( $args );
         ?>

         <br />
         
         <label for="email">Select the region for the post.</label>
         <?php 
         $args = array(
            'show_option_none' => 'Select Region',
            'orderby' => 'name',
            'echo' => 1,
            'name' => 'region',
            'id' => '',
            'taxonomy' => 'region',
            'hide_empty' => 0,
         );         
         wp_dropdown_categories( $args );
         ?>

         <br />

         <label for="counties">Enter the county or counties seperated by a ',' ex. "Ada, Boise, Valley"</label>
         <input id="counties" type="text" name="counties">

         <button id="generate-content-form-submit" data-nonce="<?php echo $nonce; ?>">Generate Post</button>
         <div id="request-spinner" class="spinner-hide">Loading...</div> 
         <div id="put-response-here"></div> 
         

      </div>
   </div>
   <?php wp_footer(); ?>
<?php
}
add_action( 'admin_init', 'chat_gpt_generate_plugin_settings' );
function chat_gpt_generate_plugin_settings() {
	register_setting( 'chat-gpt-generate-settings', 'api_key' );
	register_setting( 'chat-gpt-generate-settings', 'chat_model' );
	register_setting( 'chat-gpt-generate-settings', 'max_tokens' );
   register_setting( 'chat-gpt-generate-settings', 'temperature' );
   register_setting( 'chat-gpt-generate-settings', 'post_status' );
   register_setting( 'chat-gpt-generate-settings', 'author_id' );
}


/* AJAX PLUGIN */
// define the actions for the two hooks created, first for logged in users and the next for logged out users
add_action("wp_ajax_chad_post", "chad_post");
add_action("wp_ajax_check_title_duplicate", "check_title_duplicate");
add_action("wp_ajax_nopriv_chad_post", "please_login");


function check_title_duplicate() {

   // nonce check for an extra layer of security, the function will exit if it fails
   if ( !wp_verify_nonce( $_REQUEST['nonce'], "chad_post_nonce")) {
      exit("Woof Woof Woof");
   }   

   $post_title = $_REQUEST["post_title"];
   $post_title = strtolower($post_title);

   $post_titles = array();
   $result="success";
   $args = array(
      'post_status' => 'any',
  );
   $query = new WP_Query( $args );
   // Check that we have query results. 
   if ( $query->have_posts() ) {
         // Start looping over the query results. 
         while ( $query->have_posts() ) {
            $query->the_post();

            $ptitle = get_the_title();
            $ptitle = strtolower($ptitle);
            if($ptitle == $post_title) {
               wp_reset_postdata();
               
               $error = new WP_Error( '001', 'Post already exists.', 'A post with this title already exists.' );
               wp_send_json_error( $error );
            }
         }
   }
   wp_reset_postdata();

   

   if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      $result = json_encode($result);
      echo $result;
   }
   else {
      header("Location: ".$_SERVER["HTTP_REFERER"]);
   }

   die();
}


// define the function to be fired for logged in users
function chad_post() {

   $args = array(
      //'author' => '2'
      'post_status' => 'any',
  );
  // Custom query. 
  
  $matched_posts_by_title = array();
//   class PostObj {
//    public title;
//    public url;
//   }
$post_titles = array();
//   class PostObj {
//    public title;
//    public url;
//   }
  $query = new WP_Query( $args );
  // Check that we have query results. 
  if ( $query->have_posts() ) {
      // Start looping over the query results. 
      while ( $query->have_posts() ) {
         $post_obj = new stdClass();
         $query->the_post();
         $post_obj->title = get_the_title();
         $i = array_search($post_obj->title, $post_titles);
         if(!$i) {
            $post_obj->url = get_the_permalink();
            array_push($matched_posts_by_title, $post_obj);
            array_push($post_titles, $post_obj->title);
         }

          
      }
  }
  wp_reset_postdata();

  // get all post tags
$tags = get_terms( array(
   'taxonomy' => 'post_tag',
   'hide_empty' => true,
));

$matched_tags = array();
$tag_names = array();
if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
    foreach ( $tags as $tag ) {
        $tag_names[] = $tag->name;

    }
}

  if(count($matched_posts_by_title) > 1) {
   usort($matched_posts_by_title, "cmp_title_str_len");
  }
  foreach($matched_posts_by_title as $match) {

  }


  // Restore original post data. 
  wp_reset_postdata();
   
   // nonce check for an extra layer of security, the function will exit if it fails
   if ( !wp_verify_nonce( $_REQUEST['nonce'], "chad_post_nonce")) {
      exit("Woof Woof Woof");
   }   

   /* Make a post here */

   // get the content
  $post_content = $_REQUEST["post_content"];
  $post_title = $_REQUEST["post_title"];
  $counties_input = sanitize_text_field($_REQUEST["counties"]);
  //echo "\n".$counties_input."\n";
  $selected_category = $_REQUEST["selected_category"];
  //echo "\n the selected category is ".$selected_category."\n";
  $selected_region = $_REQUEST["selected_region"];
  //echo "\n the selected region is ".$selected_region."\n";
  $featured_image = $_REQUEST["featured_image"];
  $post_images = $_REQUEST["post_images"];
  $content_block_array = array();
 

   // class MatchedObj {
   //    public guid;
   //    public replacement;
   // }
   $temp_array = array();
   $matched_titles = array();
   $thing = new stdClass();
   for($x = 0; $x < count($post_content); $x++) {

      //get tags
      foreach($tags as $tag) {
         if(matchTag($post_content[$x], $tag->name)){
            array_push($matched_tags, $tag->name);
         }
      }

      //match titles
      array_push($matched_titles, array());
      foreach($matched_posts_by_title as $title) {
         $pos = stripos($post_content[$x], $title->title);
         
         if($pos) {
            
            $thing->haystack = $post_content[$x];
            $thing->container = array();
            $guid = uniqid();
            $replacement = '<a href="'.$title->url.'" target="_blank">'.substr($thing->haystack,$pos,strlen($title->title)).'</a>';
            array_push($matched_titles[$x], array($title->title, $guid, $replacement));
            $thing = replaceAllMatches($thing, $title, $guid );
            //$matched_titles[$x] = $thing->container;
            $post_content[$x] = $thing->haystack;
         }
      }
   }
   for($x = 0; $x < count($post_content); $x++) {
      
      foreach($matched_titles[$x] as $matched_title) {
         if(count($matched_title) > 0) {
            $post_content[$x] = replace_content_guids($post_content[$x], $matched_title);
              
         }
      }
      if($post_content[$x]) {
         array_push($temp_array, $post_content[$x]);
      }
   }
   $featured_image_id = 0;
  for($i = 0; $i < count($temp_array); $i++) {
      array_push($content_block_array,
         array(
            'name'      => 'core/paragraph',
            'attributes' => array(
                'content' => $temp_array[$i] ? "<p>".$temp_array[$i]."</p>" : "WTF"
            )
         )
      );
      if(count($post_images) > 0 && array_key_exists($i, $post_images)) {

         $the_url = $post_images[$i]['url'] ? $post_images[$i]['url'] : "";

         $the_alt = $post_images[$i]['alt'] ? $post_images[$i]['alt'] : "";
         $the_caption = $post_images[$i]['attribution'] ? $post_images[$i]['attribution'] : "";
         array_push($content_block_array,
            array(
               'name'      => 'core/image',
               'attributes' => array(
                  'url' => $post_images[$i]['url'],
                  'alt' => $post_images[$i]['alt'],
                  'caption' => $post_images[$i]['attribution']
               )
            )
         );
      }
  }

   $post_blocks_content = create_blocks($content_block_array);
   $openai_post_status = get_option( 'post_status' );
   $post_categories = array();
   array_push($post_categories, $selected_category);
   array_push($post_categories, $selected_region);
   
   
   //echo "\n\n";
   //print_r($post_categories);
   //echo "\n\n";
   
   $testparams = array(
    'post_title'     => ucwords(sanitize_text_field($post_title)),
    'post_author'    => $author_id,
    'post_content'   => $post_blocks_content,
    'post_type'      => 'post',
    'post_status'    =>  'publish',
    'post_category'  => $post_categories,
    'tax_input'    => array(
      'region' => array($selected_region)
  ),

   );
   //print_r($testparams);
   $chad_post_id = wp_insert_post($testparams);

   //featured image   
   $image = $featured_image;
   $attachment_id = wp_insert_attachment_from_url($image, $chad_post_id, $post_title);
   set_post_thumbnail($chad_post_id, $attachment_id);
   
   //add relevant tags
   if($matched_tags && count($matched_tags > 0)) {
      //echo "\n\n";
      //print_r($matched_tags);
      //echo "\n\n";
      wp_set_post_terms($chad_post_id, $matched_tags, 'post_tag', true);  
   }

   //update custom fields (county relationship, ...) 
   //counties
   $counties_input = str_replace(' ', '', $counties_input);
   $counties_input = strtolower($counties_input);
   //echo "\n counties input trimmed";
   //print_r($counties_input);
   //echo "\n";
   $counties_array = explode(',', $counties_input);
   //echo "\n counties array trimmed";
   //print_r($counties_array);
   //echo "\n";
   $counties_keywords = implode('+', $counties_array);
   //echo "\n counties keywords trimmed";
   //print_r($counties_keywords);
   //echo "\n";
   //echo "\n\n".$counties_keywords."\n\n";
   //get all counts that match the title
   
   foreach($counties_array as $county){
      $county_args = array(
         'post_type' => 'county',        // Specify the post type (e.g., 'post', 'page')
         'post_status' => 'publish',   // Limit to published posts
         'posts_per_page' => -1,       // Retrieve all matching posts
         's' => $county,     // Specify the title to search for
     );
     //print_r($county_args);
     $county_query = new WP_Query( $county_args );
     
     if ( $county_query->have_posts() ) {
         while ( $county_query->have_posts() ) {
             $county_query->the_post();
             $county_title = get_the_title();
             if(strtolower($county_title) == $county) {
               // get current value
               //echo "\n THERE ARE COUNTY MATCH! \n";
               //echo get_the_title();
               $value = get_field('county_relationship', $chad_post_id, false);
               //echo "\nthe got value of county_relationship";
               //print_r($value);
               // add new id to the array
               $value[] = get_the_ID();
               //echo "\nthe array of the ids";
               //print_r($value);
               // update the field
               update_field('county_relationship', $value, $chad_post_id);
               //echo "\n";
               //print_r($chad_post_id);
               $nvalue = get_field('county_relationship', $chad_post_id, false);
               //echo "the new got value of the county relationship";
               //print_r($nvalue);
            }
         }
         
     } else {
         //echo 'No matching posts found.';
     }
      wp_reset_postdata();
   }
   
   // If above action fails, result type is set to 'error' and like_count set to old value, if success, updated to new_like_count  
   if($like === false) {
      $result['type'] = "error";
      $result['like_count'] = $like_count;
   }
   else {
      $result['type'] = "success";
      $result['like_count'] = $new_like_count;
   }

   
   
   // Check if action was fired via Ajax call. If yes, JS code will be triggered, else the user is redirected to the post page
   // TODO: use this for a spinner or something, this is old tutorial stuff
   if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      //$result = json_encode($result);
      //echo $result;
   }
   else {
      header("Location: ".$_SERVER["HTTP_REFERER"]);
   }
   $response = array(
      'success' => true,
      'message' => 'Data retrieved successfully',
      'data'    => $result,
   );
   wp_send_json($response);
   // don't forget to end your scripts with a die() function - very important
   die();
}

// define the function to be fired for logged out users
// TODO: update this to real stuff, but keep login req. for now
function please_login() {
   echo "You must log in to like";
   die();
}

/**
 * Save the image on the server.
 */
function save_image( $base64_img, $title ) {

	// Upload dir.
	$upload_dir  = wp_upload_dir();
	$upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;

	$img             = str_replace( 'data:image/png;base64,', '', $base64_img );
	$img             = str_replace( ' ', '+', $img );
	$decoded         = base64_decode( $img );
	$filename        = $title . '.png';
	$file_type       = 'image/png';
	$hashed_filename = md5( $filename . microtime() ) . '_' . $filename;

	// Save the image in the uploads directory.
	$upload_file = file_put_contents( $upload_path . $hashed_filename, $decoded );

	$attachment = array(
		'post_mime_type' => $file_type,
		'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $hashed_filename ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
		'guid'           => $upload_dir['url'] . '/' . basename( $hashed_filename )
	);

	$attach_id = wp_insert_attachment( $attachment, $upload_dir['path'] . '/' . $hashed_filename );
   return $attach_id;
}

/**
 * Insert an attachment from a URL address.
 *
 * @param  string   $url            The URL address.
 * @param  int|null $parent_post_id The parent post ID (Optional).
 * @return int|false                The attachment ID on success. False on failure.
 */
function wp_insert_attachment_from_url( $url, $parent_post_id = null, $name ) {

	if ( ! class_exists( 'WP_Http' ) ) {
		require_once ABSPATH . WPINC . '/class-http.php';
	}

   // If the function it's not available, require it.
   if ( ! function_exists( 'download_url' ) ) {
      require_once ABSPATH . 'wp-admin/includes/file.php';
   }

   // Now you can use it!
   $file_url = $url;
   $tmp_file = download_url( $file_url );

   // Sets file final destination.
   $new_file_name = sanitize_file_name($name).wp_generate_uuid4().".png";
   $file_upload = ABSPATH . 'wp-content/uploads/' . $new_file_name;

   // Copies the file to the final destination and deletes temporary file.
   copy( $tmp_file, $file_upload );
   @unlink( $tmp_file );
   $file_path        = $file_upload;
	$file_name        = basename( $file_path );
   $file_type = wp_check_filetype( $new_file_name, null );
   $attachment_title = sanitize_file_name( pathinfo( $new_file_name, PATHINFO_FILENAME ) );
   $wp_upload_dir    = wp_upload_dir();

	// $upload = wp_upload_bits( basename( $url ), null, $response['body'] );
	// if ( ! empty( $upload['error'] ) ) {
	// 	return false;
	// }

	// $file_path        = $upload['file'];
	// $file_name        = basename( $file_path );
	// $file_type        = wp_check_filetype( $file_name, null );
	// $attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
	// $wp_upload_dir    = wp_upload_dir();

	$post_info = array(
		'guid'           => $wp_upload_dir['url'] . '/' . $new_file_name,
		'post_mime_type' => $file_type['type'],
		'post_title'     => $attachment_title,
		'post_content'   => '',
      'post_excerpt' => "Photo generated by Chad G Petey",
		'post_status'    => 'inherit',
	);

	// Create the attachment.
	$attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );

	// Include image.php.
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// Generate the attachment metadata.
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );

	// Assign metadata to attachment.
	wp_update_attachment_metadata( $attach_id, $attach_data );

   //assign alt text to the image
   update_post_meta($attach_id, '_wp_attachment_image_alt', 'An image of '.$name);

	return $attach_id;

}

function create_block( $block_name, $attributes = array(), $content = '' ) {
   $attributes_string = json_encode( $attributes );
   $block_content = '<!-- wp:' . $block_name . ' ' . $attributes_string . ' -->' . $content . '<!-- /wp:' . $block_name . ' -->';
   return $block_content;
}

function create_image_block( $block_name, $attributes = array(), $content = '' ) {
   $attributes_string = '{"className":"size-full"}';
   $block_content = '<!-- wp:' . 'image' . ' ' . $attributes_string . ' -->' . '<div class="wp-block-image size-full"><figure class="aligncenter"><img src="'.$attributes['url'].'" alt="'.$attributes['alt'].'"/><figcaption class="wp-element-caption">'.$attributes['caption'].'</figcaption></figure></div>' . '<!-- /wp:' . 'image' . ' -->';
   return $block_content;
}

function create_blocks( $blocks = array() ) {
   $block_contents = '';
   foreach ( $blocks as $block ) {
      if($block['attributes']['content']) {
         $block_contents .= create_block( $block['name'], $block['attributes'], $block['attributes']['content'] );
      } else if($block['name'] == 'core/image') {
         $block_contents .= create_image_block( $block['name'], $block['attributes'] );
      }
   }
   return $block_contents;
}

function cmp_title_str_len($a, $b) {
   if(strlen($a->title) == strlen($b->title)) {
      return 0;
   }
   return (strlen($a->title) > strlen($b->title)) ? -1 : 1;
}

function replaceAllMatches($thing, $needle, $guid ){

	$pos = stripos($thing->haystack, $needle->title);
	
	if($pos) {

		$matched_obj = new stdClass();
      $matched_obj->guid = $guid;
      $matched_obj->replacement = '<a href="'.$needle->url.'" target="_blank">'.substr($thing->haystack,$pos,strlen($needle->title)).'</a>';
      //array_push($thing->container, $matched_obj ? $matched_obj : null);
      //echo "\n\n haystack1: ".$haystack."\n\n";
      $thing->haystack = substr_replace($thing->haystack, $matched_obj->guid, $pos, strlen($needle->title));
      //echo "\n\n haystack2: ".$haystack."\n\n";

      $thing = replaceAllMatches($thing, $needle, $guid);
	} else {
		return $thing;
	}
	return $thing;
}
function replace_content_guids($haystack, $needle) {
   $pos = stripos($haystack, $needle[1]);
   //echo "\n"."MATCHING ".$needle[1]." IN \n".$haystack."\n\n";
   if($pos) {
      //echo "i found ".$needle[1]." as pos ".$pos." in \n".$haystack."\n\n";
      //$the_url'<a href="'.$needle->url.'" target="_blank">'.substr($thing->haystack,$pos,strlen($needle->title)).'</a>';
      $haystack = substr_replace($haystack, $needle[2], $pos, strlen($needle[1]));
      $haystack = replace_content_guids($haystack, $needle);
   }
   else {
      return $haystack;
   }
   return $haystack;
}

function matchTag($string, $substring) {
   //echo "\n matching ".$substring." to ".$string."\n";
    // Convert both the string and substring to lowercase for case-insensitive matching
    $string = strtolower($string);
    $substring = strtolower($substring);

    // Use strpos to find the position of the substring within the string
    $position = strpos($string, $substring);

    // Check if the substring was found
    if ($position === false) {
        return false; // Substring not found
    } else {
         //echo "\n".$substring." was a match!!!\n";
        return true; // Substring found
    }
}