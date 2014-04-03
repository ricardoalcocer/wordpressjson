<?php
/*
Plugin Name: WordpressJSON
Plugin URI: http://ricardoalcocer.com
Description: Exposes Wordpress Data in JSON format, much more than only recent posts
Version: 1.8.8
Author: Ricardo Alcocer - @ricardoalcocer
Author URI: http://ricardoalcocer.com
License: MIT - http://alco.mit-license.org
*/
?>
<?php
// ###### START CLASS
class wpwrapper{  
    // Get Category List
    function getCategories($parentCategory='null',$type='JSON'){
		if (!is_null(get_option('taino_exclude_categories'))){
		    $exclude=explode(",",get_option('taino_exclude_categories'));    
		}
		if ($parentCategory == 'null'){    
		    //$category_ids = get_all_category_ids();
		    $categories=get_categories(array('hide_empty'=>0));
		    $out='';
			if(get_option('taino_use_allcategories') == 1){
		    	$out[]=array('cat_id'=>'0','cat_name'=>'#ALLCATEGORIES#');				
			}
		}else{
		    $categories=get_categories( array('parent'=>$parentCategory,'hide_empty'=>0) );    
		}
		
		foreach($categories as $record){
		    if (!in_array($record->cat_ID,$exclude)){
			$out[]=array('cat_id'=>$record->cat_ID,'cat_name'=>$record->name);
		    }
		}
		if($type=='JSON'){
			return json_encode($out);	
		}else{
			return $out;
		}
        	         
    }
	
	// Get Full Category List and data per category
    function getFullCategories(){
		if (!is_null(get_option('taino_exclude_categories'))){
		    $exclude=explode(",",get_option('taino_exclude_categories'));    
		}
		if ($parentCategory == 'null'){    
		    //$category_ids = get_all_category_ids();
		    $categories=get_categories(array('hide_empty'=>0));
		    $out='';
			if(get_option('taino_use_allcategories') == 1){
		    	$out[]=array('cat_id'=>'0','cat_name'=>'#ALLCATEGORIES#');				
			}
		}else{
		    $categories=get_categories( array('parent'=>$parentCategory,'hide_empty'=>0) );    
		}
		
		foreach($categories as $record){
		    if (!in_array($record->cat_ID,$exclude)){
			$out[]=array('cat_id'=>$record->cat_ID,'cat_name'=>$record->name,'cat_posts'=>$this->getFullPosts($record->cat_ID,'ARRAY'));
		    }
		}
		return $out;	         
    }
	
	// Get Pages
	function getPages($type='JSON'){
		// doc: http://codex.wordpress.org/Function_Reference/get_pages
		// taino_tab_show, taino_tab_contact, taino_tab_about (other to come)
		$pages=get_option('taino_include_pages');
		$args = array(
		    'child_of' => 0,
		    'parent' => 0,
		    'post_type' => 'page',
		    'include' => ($pages!='')?$pages:-1,
		    'post_status' => 'publish');
		$pages=get_pages($args);
		foreach($pages as $record){
			$postId=$record->ID;
			$tabIcon=get_post_meta($postId, 'taino_tab_icon', true);
			$out[]=array('page_id'=>$postId,'page_name'=>$record->post_title,'tab_icon' => $tabIcon);
		}
		if($type=='JSON'){
			return json_encode($out);			
		}else{
			return $out;
		}
	}
    
	// Get list of pages and their content
	function getFullPages($type='JSON'){
		// doc: http://codex.wordpress.org/Function_Reference/get_pages
		// taino_tab_show, taino_tab_contact, taino_tab_about (other to come)
		$pages=get_option('taino_include_pages');
		$args = array(
		    'child_of' => 0,
		    'parent' => 0,
		    'post_type' => 'page',
		    'include' => ($pages!='')?$pages:-1,
		    'post_status' => 'publish');
		$pages=get_pages($args);
		foreach($pages as $record){
			$postId=$record->ID;
			$tabIcon=get_post_meta($postId, 'taino_tab_icon', true);
			$out[]=array('page_id'=>$postId,'page_name'=>$record->post_title,'tab_icon' => $tabIcon,'page_content'=>$this->getPostPage($postId));
		}
		if($type=='JSON'){
			return json_encode($out);			
		}else{
			return $out;
		}
	}
	
    function getPostObject($postid){
		// returns full post object including comments collection, images collection and post content
		// not yet implemented
    }
    
    // Get Comments by post
    function getComments($postid){
        $args = array('post_id' => $postid, 'order' => 'ASC', 'status' => 'approve');
        $comments = get_comments($args);
		$out='';
        foreach($comments as $comment){
	    	$out[]=array('comment_author'=>$comment->comment_author,'comment_date'=>$comment->comment_date,'comment_content'=>$comment->comment_content);
        }
		return json_encode($out);
    }
    
	////////////////////////////////////////////////////////////////////////////////
    // Get images in post
    // outputType can be JSON or PLAIN
    // JSON is used when images is called independently, while PLAIN is used when called from 'getGalleries'
    function getImages($post_id,$outputType){
		$args = array(
		    'post_type' => 'attachment',
		    'post_mime_type' => 'image',
		    'numberposts' => -1,
		    'orderby' => 'menu_order',
		    //'orderby' => 'title',
		    'order' => 'ASC',
		    'post_parent' => $post_id
		);
		$images = get_posts($args);

		if($images){
		    foreach($images as $image){
				//el $size se puede cambiar por : thumbnail, medium o large
				//$urls = wp_get_attachment_image_src($image->ID, $size='medium');
				//$urls = wp_get_attachment_image_src($image->ID, array(480,320));
				$urls = wp_get_attachment_image_src($image->ID, $size='full');
				$jsonImages[]=array('imgUrl' => $urls[0]);
		    }
		    if ($outputType == 'JSON'){
				$jsonImages=json_encode($jsonImages);
		    }
		    return $jsonImages;
		}
    }
    
    // Get Post by category
	function getPosts($catid,$type='JSON'){
		global $wpdb;

		if ($catid >0){
			$querystr="select * from tainoapp_posts where term_id = " . $catid . " ORDER BY post_date DESC LIMIT 0,20";	
		}else{
			//$querystr="select * from tainoapp_posts ORDER BY post_date DESC LIMIT 0,20";
			$querystr="SELECT DISTINCT id, post_title, post_date FROM tainoapp_posts ORDER BY post_date DESC LIMIT 0 , 20";
		}
		
		$myposts = $wpdb->get_results($querystr, OBJECT);
		 
		$out='';       
		foreach($myposts as $post) {
		    setup_postdata($post);
		    $id=$post->ID;
		    $title=$this->cleanupString($post->post_title);
		    $date=$post->post_date;
		    $image=get_post_meta($id, 'taino_thumb', true);
			$summary=get_post_meta($id, 'taino_short_summary', true);
		    $link=get_permalink($post->ID);
		    $out[]=array('post_id'=>$id,'post_title'=>$title,'post_date'=>$date,'post_summary'=>$summary,'post_link'=>$link,'post_image'=>$image);
		    }
		if($type=='JSON'){
			return json_encode($out);			
		}else{
			return $out;
		}

	}
	
	// Get Post by category
	function getFullPosts($catid,$type='JSON'){
		global $wpdb;

		//$querystr="select * from tainoapp_posts where term_id = " . $catid;
		if ($catid >0){
			$querystr="select * from tainoapp_posts where term_id = " . $catid . " ORDER BY post_date DESC LIMIT 0,20";	
		}else{
			//$querystr="select * from tainoapp_posts ORDER BY post_date DESC LIMIT 0,20";
			$querystr="SELECT DISTINCT id, post_title, post_date FROM tainoapp_posts ORDER BY post_date DESC LIMIT 0 , 20";
		}
		
		$myposts = $wpdb->get_results($querystr, OBJECT);
		 
		$out='';       
		foreach($myposts as $post) {
		    setup_postdata($post);
		    $id=$post->ID;
		    $title=$this->cleanupString($post->post_title);
		    $date=$post->post_date;
		    $image=get_post_meta($id, 'taino_thumb', true);
			$summary=get_post_meta($id, 'taino_short_summary', true);
		    $link=get_permalink($post->ID);
		    $out[]=array('post_id'=>$id,'post_title'=>$title,'post_date'=>$date,'post_summary'=>$summary,'post_link'=>$link,'post_image'=>$image,'post_page'=>$this->getPostPageEx($id));
		    }
		if($type=='JSON'){
			return json_encode($out);			
		}else{
			return $out;			
		}

	}
	
    function xgetPosts($catid){
    	// original that works
        global $post;
        
        $myposts = get_posts('numberposts=20&post_status=\'publish\'&category='.$catid);
        $out='';       
        foreach($myposts as $post) {
            setup_postdata($post);
		    $id=$post->ID;
		    $title=$this->cleanupString($post->post_title);
		    $date=$post->post_date;
		    //$image=$thumb;
		    $order=get_post_meta($id, 'taino_post_order', true);
		    $image=get_post_meta($id, 'taino_thumb', true);
			$summary=get_post_meta($id, 'taino_short_summary', true);
		    $link=get_permalink($post->ID);
		    $out[]=array('post_id'=>$id,'post_title'=>$title,'post_date'=>$date,'post_summary'=>$summary,'post_link'=>$link,'post_image'=>$image,'post_order'=>$order);
			
	        }
        return json_encode($out);	    
    }
 	
    // Get Post Gallery Id
 	function getPostGalleryId($post_id){
 		if (get_post_meta($post_id, 'taino_gallery_id', true) != ''){
			$out['galleryId']=get_post_meta($post_id, 'taino_gallery_id', true); 			
 		}
		return json_encode($out);
 	}
	
    // Get Mobile Only
    function getMobileOnly($catid){
        global $post;
        
        $myposts = get_posts('numberposts=20&post_status=\'publish\'&category='.$catid);
        $out='';       
        foreach($myposts as $post) {
            setup_postdata($post);
		    $id=$post->ID;
		    $title=$this->cleanupString($post->post_title);
		    $date=$post->post_date;
		    $post_content=$post->post_content;
		    //$image=$thumb;
		    $image=get_post_meta($id, 'taino_thumb', true);
		    $link=get_permalink($post->ID);
		    $out[]=array('post_id'=>$id,'post_title'=>$title,'post_date'=>$date,'post_content'=>$post_content,'post_image'=>$image,'post_link'=>$link,'post_summary'=>$image);
        }
        return json_encode($out);	    
    }
    
    // Get Galleries
    function getGalleries($catid){
        global $post;
        
        $myposts = get_posts('numberposts=20&post_status=\'publish\'&category='.$catid);
        $out='';       
        foreach($myposts as $post) {
            setup_postdata($post);
		    $id=$post->ID;
		    $title=$this->cleanupString($post->post_title);
		    $date=$post->post_date;
			$summary=get_post_meta($id, 'taino_short_summary', true);
		    $image=get_post_meta($id, 'taino_thumb', true);
		    $images=$this->getImages($post->ID,'PLAIN');
		    $post_content=$post->post_content;
		    $link=get_permalink($post->ID);
		    $out[]=array('post_id'=>$id,'post_title'=>$title,'post_date'=>$date,'post_content'=>$post_content,'post_image'=>$image,'post_link'=>$link,'post_summary'=>$summary,'gallery'=>$images);
        }
        return json_encode($out);	    
    }
    
    // Get Events
    function getEvents($catid,$type='JSON'){
        global $post;
        
		//$myposts = get_posts('category='.$catid.'&orderby=event_date');
		$myposts=get_posts('category='.$catid.'&meta_key=event_date&orderby=meta_value&order=ASC&post_status=\'publish\'');

        $out='';       
        foreach($myposts as $post) {
            setup_postdata($post);
		    $id=$post->ID;
		    $title=$this->cleanupString($post->post_title);
		    $date=$post->post_date;
		    $image=$thumb;
		    $link=get_permalink($post->ID);
		    $post_content=$post->post_content;
		    $eventDate=get_post_meta($id, 'event_date', true);
		    $eventTime=get_post_meta($id, 'event_time', true);
		    $eventVenue=get_post_meta($id, 'event_venue', true);
		    $out[]=array('post_id'=>$id,'post_title'=>$title,'post_date'=>$date,'post_content'=>$post_content,'post_image'=>$image,'post_link'=>$link,'post_image'=>$image,'event_date'=>$eventDate,'event_time'=>$eventTime,'event_venue'=>$eventVenue);
        }
		if($type=='JSON'){
        	return json_encode($out);			
		}else{
			return $out;
		}
	    
    }
 	
	// Get session
	function getSession(){
		$categories=$this->getFullCategories('null','ARRAY');
		$pages=$this->getFullPages('ARRAY');
		$session=array('categories'=>$categories,'pages'=>$pages);
		return json_encode($session);
	}
 
    // Get post page
    function getPostPage($postid){
       $mypost = get_post($postid);
       $out='';
        
       setup_postdata($mypost);
       
       // this is a heredoc ;)
       $css=<<<CSS
       <html>
               <head>
               <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
               <meta name = "viewport" content = "user-scalable = no">
               #CSS#
               <script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
               <script type="text/javascript">
                   $(document).ready(function(){
                       $('a').each(function(){
                            $(this).bind('click',function(){
                               var theURL=this.getAttribute('href');
                               var eventObject = new Object;
                               var a = 'URL';
                               eventObject[a] = theURL;
                               // Fire Titanium Event to Open the URL in a new Window
                               Ti.App.fireEvent('openBrowser',eventObject);
                               return false;
                            });                                
                        })                 
                    });
               </script>		
CSS;
       if (get_option('taino_custom_css') !=''){
	   	$css = str_replace('#CSS#',get_option('taino_custom_css'),$css);
       }else{
	    $css = str_replace('#CSS#','<style type=text/css>body{font-family: Arial} #title{font-size:26px;font-weight:bold} #date{font-style:italic} #content{font-size:16px}</style>',$css);
       }
       
       $out .= $css;
       $out .= '<base href="'.get_bloginfo('url').'/">';
       $out .= '</head>';
       $out .= '<body>';
       
       $out .= "<div id=title>".$this->cleanupString($mypost->post_title) . "</div>";
       $out .= "<div id=date>".$mypost->post_date . "</div>";
       //$out .= "<div id=content>".str_replace('<a ','<a target=_blank ',$mypost->post_content) . "</div>";
	   $out .= "<div id=content>".str_replace('<a ','<a target=_blank ',do_shortcode(apply_filters('the_content', $mypost->post_content))) . "</div>";
 
       $closing=<<<CLOSING
               </body>
       </html>
CLOSING;
       $out .= $closing;
       return $out;      
    }
    
	// Get post page Android
    function getPostPageAndroid($postid){
       $mypost = get_post($postid);
       $out='';
        
       setup_postdata($mypost);
       
       // this is a heredoc ;)
       $css=<<<CSS
       <html>
               <head>
               <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
               #CSS#
               <script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
               <script type="text/javascript">
                   $(document).ready(function(){
                       $('a').each(function(){
                            $(this).bind('click',function(){
                               var theURL=this.getAttribute('href');
                               var eventObject = new Object;
                               var a = 'URL';
                               eventObject[a] = theURL;
                               // Fire Titanium Event to Open the URL in a new Window
                               Ti.App.fireEvent('openBrowser',eventObject);
                               return false;
                            });                                
                        })                 
                    });
               </script>		
CSS;
       if (get_option('taino_custom_css') !=''){
	   	$css = str_replace('#CSS#',get_option('taino_custom_css'),$css);
       }else{
	    $css = str_replace('#CSS#','<style type=text/css>body{font-family: Arial} #title{font-size:26px;font-weight:bold} #date{font-style:italic} #content{font-size:16px}</style>',$css);
       }
       
	   $out .= $css;
       $out .= '<base href="'.get_bloginfo('url').'/">';
	   $out .= '</head>';
       $out .= '<body>';
       
       $out .= "<div id=title>".$this->cleanupString($mypost->post_title) . "</div>";
       $out .= "<div id=date>".$mypost->post_date . "</div>";
	   
	   // get fixed content from webservice
	   $post_content=do_shortcode(apply_filters('the_content', $mypost->post_content));
	   $payload=array('input'=>$post_content);
       $post_content=$this->postHTTPData('http://api.gettainoapp.com/fixandroidvideos/',$payload);
	   //
	   
	   $out .= "<div id=content>". $post_content . "</div>";
 
       $closing=<<<CLOSING
               </body>
       </html>
CLOSING;
       $out .= $closing;
       return $out;      
    }

	// Get post page Android
    function getPostPageEx($postid){
       $mypost = get_post($postid);
       $out='';
        
       setup_postdata($mypost);
       
       // this is a heredoc ;)
       $css=<<<CSS
       <html>
               <head>
               <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
               <meta name = "viewport" content = "user-scalable = no">
               #CSS#
               <script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
               <script type="text/javascript">
                   $(document).ready(function(){
                       $('a').each(function(){
                            $(this).bind('click',function(){
                               var theURL=this.getAttribute('href');
                               var eventObject = new Object;
                               var a = 'URL';
                               eventObject[a] = theURL;
                               // Fire Titanium Event to Open the URL in a new Window
                               Ti.App.fireEvent('openBrowser',eventObject);
                               return false;
                            });                                
                        })                 
                    });
               </script>		
CSS;
       if (get_option('taino_custom_css') !=''){
	   	$css = str_replace('#CSS#',get_option('taino_custom_css'),$css);
       }else{
	    $css = str_replace('#CSS#','<style type=text/css>body{font-family: Arial} #title{font-size:26px;font-weight:bold} #date{font-style:italic} #content{font-size:16px}</style>',$css);
       }
       
	   $out .= $css;
       $out .= '<base href="'.get_bloginfo('url').'/">';
	   $out .= '</head>';
       $out .= '<body>';
       
       $out .= "<div id=title>".$this->cleanupString($mypost->post_title) . "</div>";
       $out .= "<div id=date>".$mypost->post_date . "</div>";
	   
	   // get fixed content from webservice
	   $post_content=do_shortcode(apply_filters('the_content', $mypost->post_content));
	   $payload=array('input'=>$post_content);
       $post_content=$this->postHTTPData('http://api.gettainoapp.com/fixandroidvideos/',$payload);
	   //
	   
	   $out .= "<div id=content>". $post_content . "</div>";
 
       $closing=<<<CLOSING
               </body>
       </html>
CLOSING;
       $out .= $closing;
       return $out;      
    }
    
    // Get post page Android
    function getPostPageRaw($postid){
       $mypost = get_post($postid);
       $out='';
        
       setup_postdata($mypost);
       
       $title = $this->cleanupString($mypost->post_title);
       $date  = $mypost->post_date;
       $post_content=do_shortcode(apply_filters('the_content', $mypost->post_content));
       
       //get images in this post
       $payload=array('htmldata'=>$post_content);
       $imagesJSON=$this->postHTTPData('http://api.gettainoapp.com/api/getImagesFromHTML.php',$payload);
       //
       
       $post_content=$this->br2nl($post_content,'br p');
       $post_content=strip_tags($post_content);
       
       $out=array(
	     'title' 	=>$title,
	     'date'	=>$date,
	     'content'	=>$post_content,
	     'images'	=>json_decode($imagesJSON,true)
	     );
       
      return json_encode($out);      
    }
    
    // Fix string in case of special characters
    function cleanupString($data){
       $out=$data;
       //$out=str_replace('&','&amp;',$out);
       //$out=html_entity_decode($out);
       $out=htmlspecialchars($out);
       //$out=htmlentities($out);
       return $out;
    }
    
    // Performs HTTP Post (for webservices)
    function postHTTPData($url,$postdata){
        // $postdata must be received in associative array format
        $service_url = $url;
        $curl = curl_init($service_url);       
        $curl_post_data=$postdata;
        //echo var_dump($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
        $curl_response = curl_exec($curl);
        curl_close($curl);
        return $curl_response; 
    }
    
    function br2nl($text, $tags = "br"){
        $tags = explode(" ", $tags);
        foreach($tags as $tag){
            $text = eregi_replace("<" . $tag . "[^>]*>", "\n", $text);
            $text = eregi_replace("</" . $tag . "[^>]*>", "\n", $text);
        }
        return($text);
     }


	//
	function remoteConfig($data){
		// receives data in JSON format	
		// sample JSON:
		/* {"exclude":"1",
			"pages":"",
			"galleries":"",
			"events":"",
			"mobile":"",
			"defaultcats":""}
		 */	
		 // sample CALL:
		 // http://somain.com/?tainoapp=remoteconfig&tainoallextra={"exclude":"1","pages":"","galleries":"","events":"","mobile":"","defaultcats":"","css":""}
		 //
		if (get_option('taino_allow_remote_config') == 1){
			$data=stripcslashes($data);
			$dataArray=json_decode($data);
			
			update_option('taino_exclude_categories',$dataArray->exclude);
			update_option('taino_galleries_category',$dataArray->galleries);
			update_option('taino_mobileonly_category',$dataArray->mobile);
			update_option('taino_events_category',$dataArray->events);
			update_option('taino_default_category',$dataArray->defaultcat);
			//update_option('taino_custom_css',$dataArray->css);
			//update_option('taino_use_allcategories',);
			update_option('taino_include_pages',$dataArray->pages);	
			return "Configuration saved!\n$data";
		}else{
			return "Remote config is disabled by Admin";
		}
	}
}
// ##################################### END CLASS
?><?php
/**
 * This is where the actual plugin starts.  The code above is a PHP class to access the functions and make it more readable
 */

// ----------------------------------------------------------------------------------------------
// #############################################################  Handle Custom Request
// ----------------------------------------------------------------------------------------------

// Register the new query variables
// This ensures that the new vars are available to the next functions
function my_plugin_query_vars($vars) {
    $vars[] = 'tainoapp';
    $vars[] = 'tainoappextra';
    return $vars;
}
add_filter('query_vars', 'my_plugin_query_vars');


// Get in the way of an actual request and see if it belongs to us
//
function my_plugin_parse_request($wp) {
    if (array_key_exists('tainoapp', $wp->query_vars)) {        
        // Set character encoding
        header('Content-Type: text/plain; charset=UTF-8');

        // Instantiate custom class
        $wp_tl_wrapper=new wpwrapper;
        
        switch($wp->query_vars['tainoapp']){
            case 'categories':
                // get categories
				$extra=$wp->query_vars["tainoappextra"];
				if ($extra != ''){
				    // parent was specified
				    die($wp_tl_wrapper->getCategories($extra));    
				}else{
				    // all categories
				    die($wp_tl_wrapper->getCategories());    
				}
                break;
            case 'posts':
                // get posts in category.  should include limit for sql
                $extra=$wp->query_vars["tainoappextra"];
                die($wp_tl_wrapper->getPosts($extra));
                break;
            case 'fullposts':
                // get posts in category.  should include limit for sql
                $extra=$wp->query_vars["tainoappextra"];
                die($wp_tl_wrapper->getFullPosts($extra));
                break;
            case 'postpage':
                // get actual post html
                $extra=$wp->query_vars["tainoappextra"];
                die($wp_tl_wrapper->getPostPage($extra));
                break;
			case 'postpageandroid':
                // get actual post html
                $extra=$wp->query_vars["tainoappextra"];
                die($wp_tl_wrapper->getPostPageAndroid($extra));
                break;
	    case 'postpageex':
                // get actual post html
                $extra=$wp->query_vars["tainoappextra"];
                die($wp_tl_wrapper->getPostPageEx($extra));
                break;
	    case 'postpageraw':
                // get actual post html
                $extra=$wp->query_vars["tainoappextra"];
                die($wp_tl_wrapper->getPostPageRaw($extra));
                break;
	    case 'postgallery':
                // get gallery id for particular post
                $extra=$wp->query_vars["tainoappextra"];
                die($wp_tl_wrapper->getPostGalleryId($extra));
                break;
		    case 'comments':
					// get comments from post
	                $extra=$wp->query_vars["tainoappextra"];
	                die($wp_tl_wrapper->getComments($extra));
	                break;
		    case 'images':
		    	// get images from post
	                $extra=$wp->query_vars["tainoappextra"];
	                die($wp_tl_wrapper->getImages($extra,'JSON'));
	                break;
		    case 'galleries':
				// get posts pre-defined as galleries
				if(get_option('taino_galleries_category')!=''){
				    die($wp_tl_wrapper->getGalleries(get_option('taino_galleries_category')));    
				}else{
				    die('Galleries category not defined');    
				}
                break;
	   		 case 'mobileonly':
				// get posts in mobile-only category
				if(get_option('taino_mobileonly_category')!=''){
				    die($wp_tl_wrapper->getMobileOnly(get_option('taino_mobileonly_category')));    
				}else{
				    die('Mobile-only category not defined');    
				}
		        break;
    		case 'events':
				// get posts in events category
				if(get_option('taino_events_category')!=''){
				    die($wp_tl_wrapper->getEvents(get_option('taino_events_category')));    
				}else{
				    die('Events category not defined');    
				}
                break;
			case "settings":
				//$out["defaultCategory"]=get_option('taino_default_category');
				$out[]=array('defaultCategory' => get_option('taino_default_category'),'excludeCategories' => get_option('taino_exclude_categories'));
				die(json_encode($out));
				break;
			case "pages":
				die($wp_tl_wrapper->getPages());
				break;
			case "session":
				die($wp_tl_wrapper->getSession());
				break;
			case "remoteconfig":
				$extra=$wp->query_vars["tainoappextra"];
                die($wp_tl_wrapper->remoteConfig($extra));
                break;
            default:
				$theText = "Invalid TaínoApp values";
				die($theText);
        }
    }
}
add_action('parse_request', 'my_plugin_parse_request');

// ------------------------------------------------------------------------
// ##########################################  Admin panel
// ------------------------------------------------------------------------

/* When plugin is activated */
register_activation_hook(__FILE__,'setDefaultOptions');

/* When plugin is deactivation*/
//register_deactivation_hook( __FILE__, 'removeOptions' );

function setDefaultOptions() {
    /* Creates new database field */
    //add_option("my_first_data", 'Testing !! My Plugin is Working Fine.', 'This is my first plugin panel data.', 'yes');
	global $wpdb;
	
	$tablePrefix=$wpdb->prefix;
  			
	$view="CREATE VIEW tainoapp_meta as SELECT post_id, meta_value FROM ".$tablePrefix."postmeta WHERE meta_key = 'taino_post_order'";

	$wpdb->query($view);
	
	$view="CREATE VIEW tainoapp_territory
	as SELECT post_id id, meta_value territory FROM `".$tablePrefix."postmeta` WHERE meta_key = 'taino_territory'";
	$wpdb->query($view);
	
	$view="CREATE VIEW tainoapp_posts as
	select p.*, t.term_id, t.name tag, ifnull(terr.territory, '') territory
	from ".$tablePrefix."posts p inner join ".$tablePrefix."term_relationships tr on p.id = tr.object_id inner join ".$tablePrefix."terms t on tr.term_taxonomy_id = t.term_id left outer join tainoapp_meta tm on p.id = tm.post_id left outer join tainoapp_territory terr on p.id = terr.id
	where p.post_type = 'post' and p.post_status = 'publish'
	order by ifnull(tm.meta_value, 0)";
	
	$wpdb->query($view);

	$view="CREATE VIEW tainoapp_galleries
	as SELECT p.* FROM tainoapp_posts p inner join ".$tablePrefix."options o on p.term_id = o.option_value
	where o.option_name = 'taino_galleries_category'";
	$wpdb->query($view);
	
	update_option('taino_allow_remote_config', 1);
}

function removeOptions() {
    /* Deletes the database field */
    delete_option('taino_exclude_categories');
    delete_option('taino_galleries_category');
    delete_option('taino_mobileonly_category');
    delete_option('taino_events_category');
    delete_option('taino_story_template');
	delete_option('taino_default_category');
    delete_option('taino_custom_css');
    delete_option('taino_use_allcategories');
	delete_option('taino_include_pages');
}

if ( is_admin() ){
    add_action('admin_menu', 'setAdminMenu');
    add_action('admin_init', 'register_options');
    
    function setAdminMenu() {
		add_options_page('Ta&iacute;noApp Settings', 'Ta&iacute;noApp Settings', 'administrator','tainoapp_settings', 'tainoAppSettingsPage');
    }
}

function register_options(){
    // these are the options that will be retrieved and saved by the plugin
    register_setting('tainoPluginSettings', 'taino_exclude_categories');
    register_setting('tainoPluginSettings', 'taino_galleries_category');
    register_setting('tainoPluginSettings', 'taino_mobileonly_category');
    register_setting('tainoPluginSettings', 'taino_events_category');
    register_setting('tainoPluginSettings', 'taino_story_template');
	register_setting('tainoPluginSettings', 'taino_default_category');
    register_setting('tainoPluginSettings', 'taino_custom_css');
	register_setting('tainoPluginSettings', 'taino_use_allcategories');
	register_setting('tainoPluginSettings', 'taino_include_pages');
	register_setting('tainoPluginSettings', 'taino_allow_remote_config');
}

// Change what's hidden by default
// Solves bug in WP 3.1.1 that was not displaying the custom fields option
// http://wordpress.org/support/topic/extra-fields-dissapeared-in-new-post
function be_hidden_meta_boxes($hidden, $screen){
    if ( 'post' == $screen->base || 'page' == $screen->base )
    $hidden = array('slugdiv', 'trackbacksdiv', 'postexcerpt', 'commentstatusdiv', 'commentsdiv', 'authordiv', 'revisionsdiv');
    // removed 'postcustom',
    return $hidden;
}
add_filter('default_hidden_meta_boxes', 'be_hidden_meta_boxes', 10, 2);
//

// to exclude categories from search
function excludeCats($query){
    $tempcats='';
    if ($query->is_search || $query->is_home || $query->is_feed){
	$tempcats[]=get_option('taino_events_category') * -1;
	$tempcats[]=get_option('taino_galleries_category') * -1;
	$tempcats[]=get_option('taino_mobileonly_category') * -1;
	$query->set('cat',implode(',',$tempcats));
	//$query->set('category__not_in', $tempcats);
    }
    return $query;
}
add_filter('pre_get_posts','excludeCats');
//

function tainoAppSettingsPage() {
?>

<div>
<div class=wrap>
    <h2>Ta&iacute;noApp Settings</h2>
    v1.8.8
</div>
<h3>Use this administration page to set options that Ta&iacute;noApp will use to configure the data that will be sent to your Native Mobile App.</h3>
<form method="post" action="options.php">
<?php settings_fields('tainoPluginSettings'); ?>

    <table width="100%">
    <tr valign="top">
	    <th width="235" align=left scope="row">Allow remote configuration?:</th>
		<td width="650">
			<input type="checkbox" name="taino_allow_remote_config" id="taino_allow_remote_config" value="1" <?php checked( '1', get_option( 'taino_allow_remote_config' ) ); ?> />
		</td>
	</tr>
	<tr valign="top">
	    <th width="235" align=left scope="row">Categories to exclude from main menu:</th>
		<td width="650">
		    <input name="taino_exclude_categories" type="text" id="taino_exclude_categories" value="<?php echo get_option('taino_exclude_categories'); ?>" />(ex. 1,2,5,76)
		</td>
	</tr>
	<tr valign="top">
	    <th width="235" align=left scope="row">Pages to include on mobile:</th>
		<td width="650">
		    <input name="taino_include_pages" type="text" id="taino_include_pages" value="<?php echo get_option('taino_include_pages'); ?>" />(ex. 1,2,5,76)
		</td>
	</tr>
	<tr valign="top">
	    <th width="235" align=left scope="row">Photo Galleries Category:</th>
		<td width="650">
		    <input name="taino_galleries_category" type="text" id="taino_galleries_category" value="<?php echo get_option('taino_galleries_category'); ?>" />(ex. 30.  This category must be excluded.)
		</td>
	</tr>
	<tr valign="top">
	    <th width="235" align=left scope="row">Events Category:</th>
		<td width="650">
		    <input name="taino_events_category" type="text" id="taino_events_category" value="<?php echo get_option('taino_events_category'); ?>" />(ex. 30.  This category must be excluded.)
		</td>
	</tr>
	<tr valign="top">
	    <th width="235" align=left scope="row">Category for mobile-only content:</th>
		<td width="650">
		    <input name="taino_mobileonly_category" type="text" id="taino_mobileonly_category" value="<?php echo get_option('taino_mobileonly_category'); ?>" />(ex. 30.  This category must be excluded.)
		</td>
	</tr>
	<tr valign="top">
	    <th width="235" align=left scope="row">Default category:</th>
		<td width="650">
		    <input name="taino_default_category" type="text" id="taino_default_category" value="<?php echo get_option('taino_default_category'); ?>" />
		</td>
	</tr>
	<!--<tr valign="top">
	    <th width="235" align=left scope="row">Story Template:</th>
		<td width="650">
		    <textarea name="taino_story_template" cols=70 rows=20 id="taino_story_template"> <?php echo get_option('taino_story_template'); ?> </textarea>
		</td>
	</tr>-->
	<tr valign="top">
	    <th width="235" align=left scope="row">Custom CSS:</th>
		<td width="650">
		    <textarea name="taino_custom_css" id="taino_custom_css" cols=50 rows=10><?php echo get_option('taino_custom_css') ?></textarea>
		</td>
	</tr>
	<tr valign="top">
	    <th width="235" align=left scope="row">Use "All Categories" Entry:</th>
		<td width="650">
			<input type="checkbox" name="taino_use_allcategories" id="taino_use_allcategories" value="1" <?php checked( '1', get_option( 'taino_use_allcategories' ) ); ?> />
		</td>
	</tr>
    </table>
    <input type="hidden" name="action" value="update" />
    <p>
	<input type="submit" value="<?php _e('Save Changes') ?>" />
    </p>
    </form>
</div>
	
<?php
} // Closing tag for tainoAppSettingsPage
?>