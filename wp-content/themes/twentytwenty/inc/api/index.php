<?php
add_action('rest_api_init', 'register_get_api');

function register_get_api()
{

	register_rest_route('custom/v2', 'insertform', array(
		'methods' => "POST",
		'callback' => 'insert_form_callback',
		'permission_callback' => '__return_true',
	));

	register_rest_route("custom/v2", "getHomePage", array(
		'methods' => "get",
		'callback' => 'gethomepage_callback',
		'permission_callback' => '__return_true',
	));

	register_rest_route("custom/v2", "getProductPage", array(
		'methods' => "get",
		'callback' => 'getproductpage_callback',
		'permission_callback' => '__return_true',
	));

	register_rest_route('custom/v2', 'subscribeform', array(
		'methods' => "POST",
		'callback' => 'subscribe_form_callback',
		'permission_callback' => '__return_true',
	));

	register_rest_route('custom/v2', 'blog', array(
		'methods' => "GET",
		'callback' => 'getBlogList',
		'permission_callback' => '__return_true',
	));

	register_rest_route('custom/v2', 'top5blog', array(
		'methods' => "GET",
		'callback' => 'top5blog',
		'permission_callback' => '__return_true',
	));

	register_rest_route('custom/v2', 'setpostview', array(
		'methods' => "POST",
		'callback' => 'setPostView',
		'permission_callback' => '__return_true',
	));

	register_rest_route('custom/v2', 'trendingblog', array(
		'methods' => "GET",
		'callback' => 'trendingBlog',
		'permission_callback' => '__return_true',
	));

	register_rest_route('custom/v2', 'post', array(
		'methods' => "GET",
		'callback' => 'postBySlug',
		'permission_callback' => '__return_true',
	));

	register_rest_route('custom/v2', 'categories', array(
		'methods' => "GET",
		'callback' => 'categories',
		'permission_callback' => '__return_true',
	));

	register_rest_route('custom/v2', 'faq', array(
		'methods' => "GET",
		'callback' => 'faq',
		'permission_callback' => '__return_true',
	));

	register_rest_route('custom/v2', 'faqcat', array(
		'methods' => "GET",
		'callback' => 'faqcat',
		'permission_callback' => '__return_true',
	));

	register_rest_route('custom/v2', 'search', array(
		'methods' => "GET",
		'callback' => 'searchapi',
		'permission_callback' => '__return_true',
	));

	register_rest_route('custom/v2', 'resetpostcount', array(
		'methods' => "GET",
		'callback' => 'resetPostCount',
		'permission_callback' => '__return_true',
	));


	register_rest_route('custom/v2', 'test', array(
		'methods' => "GET",
		'callback' => 'test',
		'permission_callback' => '__return_true',
	));
}


function gethomepage_callback()
{
	$args = array(
		'p'         => 142, // ID of a page, post, or custom type
		'post_type' => 'any',
	);

	$homepage = new WP_Query($args);

	$acf = get_fields(142);
	$faq_section = $acf["faq_section"];

	$faqs = $faq_section["faq"];
	$filtered_faqs = [];
	foreach ($faqs as $key => $faq) {
		$faq->post_title = apply_filters('the_title', $faq->post_title);
		$faq->post_content = apply_filters('the_title', $faq->post_content);
		array_push($filtered_faqs, $faq);
	}

	$acf["faq_Section"]["faq"] = $filtered_faqs;

	$homepage->post->acf = $acf;

	return rest_ensure_response($homepage->post);
}

function getproductpage_callback()
{
	$args = array(
		'p'         => 265, // ID of a page, post, or custom type
		'post_type' => 'any',
	);

	$productpage = new WP_Query($args);

	$acf = get_fields(265);
	$faq_section = $acf["faq_section"];

	$faqs = $faq_section["faq"];
	$filtered_faqs = [];
	foreach ($faqs as $key => $faq) {
		$faq->post_title = apply_filters('the_title', $faq->post_title);
		$faq->post_content = apply_filters('the_title', $faq->post_content);
		array_push($filtered_faqs, $faq);
	}

	$acf["faq_Section"]["faq"] = $filtered_faqs;

	$productpage->post->acf = $acf;

	return rest_ensure_response($productpage->post);
}

function resetPostCount()
{

	$args = array(
		'post_type' => 'post',
		'posts_per_page'    => -1,
		'post_status' => 'publish',
		'order'   => 'DESC',
	);

	$queryBlog = new WP_Query($args);
	$blogs = $queryBlog->posts;

	foreach ($blogs as $key => $value) {


		$postID = $value->ID;
		$count_key = 'wpb_post_views_count';
		$count = get_post_meta($postID, $count_key, true);
		if ($count == '') {
			$count = 0;
			delete_post_meta($postID, $count_key);
			add_post_meta($postID, $count_key, '0');
		} else {

			update_post_meta($postID, $count_key, '0');
		}
	}
	return rest_ensure_response(array('status' => 'success'));
}

function test()
{

	$args = array(
		'post_type' => 'post',
		'posts_per_page' => 5,
		'post_status' => 'publish',

	);

	$queryBlog = new WP_Query($args);
	$blogs = $queryBlog->posts;

	$response['blogs'] =  getCustomObjectByPost($blogs);

	return rest_ensure_response($response);
}

function searchapi()
{

	$s = '';


	if (isset($_GET['s'])) {
		$s = trim($_GET['s']);
	}

	$args = array(
		'post_type' => 'post',
		'posts_per_page'    => -1,
		// 'orderby' => array('title' => 'ASC'  ),
		'post_status' => 'publish',
		'order'   => 'DESC',
		's' => $s
	);

	$queryBlog = new WP_Query($args);
	$blogs = $queryBlog->posts;
	$response['blogs'] =  getCustomObjectByPost($blogs);

	return rest_ensure_response($response);
}





function top5blog()
{

	$args = array(
		'post_type' => 'post',
		'posts_per_page' => 5,
		'post_status' => 'publish',

	);

	$queryBlog = new WP_Query($args);
	$blogs = $queryBlog->posts;

	$response['blogs'] =  getCustomObjectByPost($blogs);

	return rest_ensure_response($response);
}


function faqCat()
{

	$allpostCategory =  get_terms('pro_type');
	$cat = '';
	$allblog = true;
	if (isset($_GET['cat'])) {
		$cat = trim($_GET['cat']);
		$allblog = false;
	}

	$response = array();

	// var_dump($allblog);exit;

	$response['categories'][] = array(
		'term_id' => 0,
		'cat_name' => 'All FAQs',
		'slug' => 'all-faqs',
		'active' => $allblog
	);

	foreach ($allpostCategory  as $key => $category) {
		$active = false;
		if (isset($_GET['cat'])) {
			if ($cat == $category->slug) {
				$active = true;
			}
		}

		$response['categories'][] = array(
			'term_id' => $category->term_id,
			'cat_name' => $category->name,
			'slug' => $category->slug,
			'active' => $active
		);
	}

	return rest_ensure_response(array('status' => 'success', 'response' => $response));
}

function faq()
{

	$faq_cat = '';
	$customObject = '';
	if (isset($_GET['faq_cat'])) {
		$faq_cat = trim($_GET['faq_cat']);
		$faq_cat = array(
			'taxonomy' => 'pro_type',
			'field' => 'slug',
			'terms' => $faq_cat
		);
	}

	$args = array(
		'post_type'   => 'faq',
		'post_status' => 'publish',
		'numberposts' => -1,
		'tax_query' => array(
			$faq_cat
		),

	);

	$queryCat = new WP_Query($args);
	$customObject = getCustomObjectByFaq($queryCat);

	return rest_ensure_response(array(
		'status' => 'success',
		'response' => $customObject
	));
}

function getCustomObjectByFaq($faqs)
{

	foreach ($faqs->posts as $faq) {
		$faqs->the_post();
		// echo get_the_title();		

		$postCategory = wp_get_post_terms(get_the_ID(), 'pro_type');


		$data = array();

		foreach ($postCategory  as $key => $category) {

			$data['category'][] = array(
				'term_id' => $category->term_id,
				'cat_name' => $category->name,
				'slug' => $category->slug,
				'category_image' => get_field('category_image', $category),
			);
			$data['category1'] = $category->name;
			$data['image'] = get_field('category_image', $category);
			$data['description'] = $category->description;
		}
		$faq->post_title = get_the_title();
		$faq->the_content = get_the_content();
		$faq->acf = array();
		$faq->acf['text_test'] = get_field('text_test');
		$faq->acf['repeater_test'] = get_field('repeater_test');
		$response[] = array_merge(json_decode(json_encode($faq), true), $data);
	}

	return $response;
}



function categories()
{

	$allpostCategory =  get_terms('category');
	$cat = '';
	$allblog = true;
	if (isset($_GET['cat'])) {
		$cat = trim($_GET['cat']);
		$allblog = false;
	}

	$response = array();

	// var_dump($allblog);exit;

	$response['categories'][] = array(
		'term_id' => 0,
		'cat_name' => 'All Blogs',
		'slug' => '',
		'active' => $allblog
	);

	foreach ($allpostCategory  as $key => $category) {
		$active = false;
		if (isset($_GET['cat'])) {
			if ($cat == $category->slug) {
				$active = true;
			}
		}

		$response['categories'][] = array(
			'term_id' => $category->term_id,
			'cat_name' => $category->name,
			'slug' => $category->slug,
			'active' => $active
		);
	}

	return rest_ensure_response(array('status' => 'success', 'response' => $response));
}


function postBySlug()
{
	$slug = '';
	if (isset($_GET['slug'])) {
		$slug = trim($_GET['slug']);
		$args = array(
			'name'        => $slug,
			'post_type'   => 'post',
			'post_status' => 'publish',
			'numberposts' => 1
		);

		$queryBlog = new WP_Query($args);

		$post_data = $queryBlog->posts;

		$related_posts = array($post_data[0]->ID);

		$args = array(
			'post__in' => $related_posts,
			'status' => 'approve'

		);
		$comments_query = new WP_Comment_Query;
		$comments = $comments_query->query($args);
		// var_dump($comments);exit;

		$response['post'] =  getCustomObjectByPost($post_data);

		$response['related_post'] = getRelatedPostByCategory($response['post'][0]['category'], $post_data[0]->ID);
		$response['related_comment'] = $comments;
		$response['post_count'] = setPostView(array('post_id' => $post_data[0]->ID));
		$_GET['post_id'] = $post_data[0]->ID;
		$response['trendingblog'] = trendingBlog();



		return rest_ensure_response(array('status' => 'success', 'response' => $response));
	} else {
		return rest_ensure_response(array('status' => 'fail'));
	}
}

function getRelatedPostByCategory($category, $postID)
{

	$category_id_list = array();
	foreach ($category as $key => $value) {
		$category_id_list[] = $value['term_id'];
	}
	// var_dump($category_id_list);exit;

	$args = array(
		'post_type'   => 'post',
		'post_status' => 'publish',
		'category__in' => $category_id_list,
		'post__not_in' => array($postID),
		'numberposts' => 8
	);

	$queryBlog = new WP_Query($args);

	$response = getCustomObjectByPost($queryBlog->posts);

	return $response;
}

function trendingBlog()
{

	$post_id = '';
	if (isset($_GET['post_id'])) {
		$post_id = trim($_GET['post_id']);
	}

	$args = array(
		'post_type' => 'post',
		'posts_per_page' => 3,
		'post_status' => 'publish',
		//    'date_query' => array(
		// 	 array(
		// 	 'after' => '-30 days',
		// 	 'column' => 'post_date',
		// 	 )
		// ),
		'meta_key' => 'wpb_post_views_count',
		'orderby' => 'meta_value_num',
		'order' => 'DESC',
		'post__not_in' => array($post_id)
	);

	$queryBlog = new WP_Query($args);
	$blogs = $queryBlog->posts;
	$response['blogs'] =  getCustomObjectByPost($blogs);

	return rest_ensure_response($response);
}


function setPostView($req)
{

	if (isset($req['post_id'])) {
		$postID = $req['post_id'];
		$count_key = 'wpb_post_views_count';
		$count = get_post_meta($postID, $count_key, true);
		if ($count == '') {
			$count = 0;
			delete_post_meta($postID, $count_key);
			add_post_meta($postID, $count_key, '0');
		} else {
			$count++;
			update_post_meta($postID, $count_key, $count);
		}
		return rest_ensure_response(array('status' => 'success', 'count' => $count));
	} else {
		return rest_ensure_response(array('status' => 'fail'));
	}
}


function getBlogList()
{

	$slug = '';
	if (isset($_GET['slug'])) {
		$slug = trim($_GET['slug']);
	}
	$tagslug = '';
	if (isset($_GET['tagslug'])) {
		$tagslug = trim($_GET['tagslug']);
	}

	$args = array(
		'post_type' => 'post',
		'numberposts' => -1,
		'post_status' => 'publish',
		'category_name' => $slug,
		'tag' => $tagslug

	);

	$queryBlog = new WP_Query($args);
	$blogs = $queryBlog->posts;
	// var_dump($slug) ;exit;

	$response['blogs'] =  getCustomObjectByPost($blogs);

	$response['hottags'] = get_tags();

	$featuredblog = wp_get_recent_posts(array('numberposts' => 1, 'category_name' => $slug, 'post_status' => 'publish'));


	$featuredblog = json_decode(json_encode($featuredblog), FALSE);
	$response['featuredblog'] =  getCustomObjectByPost($featuredblog);

	$response['yoast_meta']['yoast_wpseo_metadesc'] = get_post_meta(9, '_yoast_wpseo_metadesc', true);
	$response['yoast_meta']['yoast_wpseo_canonical'] = get_post_meta(9, '_yoast_wpseo_canonical', true);
	$response['yoast_meta']['yoast_wpseo_title'] = get_post_meta(9, '_yoast_wpseo_title', true);


	return rest_ensure_response($response);
}

function getCustomObjectByPost($blogs)
{

	$response = [];

	foreach ($blogs as $post) {

		$post->post_modified = date("F j, Y", strtotime($post->post_modified));

		$postCategory = get_the_category($post->ID);

		$data = array();

		foreach ($postCategory  as $key => $category) {

			$data['category'][] = array(
				'term_id' => $category->term_id,
				'cat_name' => $category->cat_name,
				'slug' => $category->slug,
			);
		}

		$featured_image = getFeatureImageById($post);

		$data['image'] = $featured_image;
		$data['acf'] = get_fields($post->ID);
		$data['tags'] = get_the_tags($post->ID);
		$data['read_time'] = round(str_word_count($post->post_content) / 120);

		$data['yoast_meta']['yoast_wpseo_metadesc'] = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
		$data['yoast_meta']['yoast_wpseo_canonical'] = get_post_meta($post->ID, '_yoast_wpseo_canonical', true);
		$data['yoast_meta']['yoast_wpseo_title'] = get_post_meta($post->ID, '_yoast_wpseo_title', true);


		$response[] = array_merge(json_decode(json_encode($post), true), $data);
	}

	return $response;
}


function getFeatureImageById($post)
{


	// This is taken from WP_REST_Attachments_Controller::prepare_item_for_response().
	$image_id = get_post_thumbnail_id($post->ID);
	$image = get_post($image_id);

	$featured_image['id']            = $image_id;
	$featured_image['alt_text']      = get_post_meta($image_id, '_wp_attachment_image_alt', true);
	$featured_image['media_type']    = wp_attachment_is_image($image_id) ? 'image' : 'file';
	$featured_image['media_details'] = wp_get_attachment_metadata($image_id);
	$featured_image['source_url']    = wp_get_attachment_url($image_id);

	if (empty($featured_image['media_details'])) {
		$featured_image['media_details'] = new stdClass;
	} elseif (!empty($featured_image['media_details']['sizes'])) {
		$img_url_basename = wp_basename($featured_image['source_url']);
		foreach ($featured_image['media_details']['sizes'] as $size => &$size_data) {
			$image_src = wp_get_attachment_image_src($image_id, $size);
			if (!$image_src) {
				continue;
			}
			$size_data['source_url'] = $image_src[0];
		}
	} elseif (is_string($featured_image['media_details'])) {
		// This was added to work around conflicts with plugins that cause
		// wp_get_attachment_metadata() to return a string.
		$featured_image['media_details'] = new stdClass();
		$featured_image['media_details']->sizes = new stdClass();
	} else {
		$featured_image['media_details']['sizes'] = new stdClass;
	}
	return $featured_image;
}


function insert_form_callback($req)
{

	global $wpdb;

	$wpdb->insert("wp_submitted_form", array(
		"first_name" => $req['full_name'],
		"last_name" => '',
		"email" => $req['email'],
		"mobile" => $req['mobile'],
		"city" => $req['city'],
		"purpose" => $req['purpose'],
		"message" => $req['message']
	));

	return rest_ensure_response(array('status' => 'success'));
}


function subscribe_form_callback($req)
{

	global $wpdb;

	$wpdb->insert("wp_subscribe_form", array(
		"email" => $req['email']
	));


	$api_key = '2c5af167431444a02f450a0d47044c76-us4';
	$list_id = 'cde4497e33';
	$email = $req['email'];
	$status = 'subscribed'; // subscribed, cleaned, pending

	$args = array(
		'method' => 'PUT',
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode('user:' . $api_key)
		),
		'body' => json_encode(array(
			'email_address' => $email,
			'status'        => $status
		))
	);
	$response = wp_remote_post('https://' . substr($api_key, strpos($api_key, '-') + 1) . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower($email)), $args);

	$body = json_decode($response['body']);

	if ($response['response']['code'] == 200 && $body->status == $status) {
		return rest_ensure_response(array('status' => 'success'));
	} else {
		return rest_ensure_response(array('status' => 'fail'));
	}
}
