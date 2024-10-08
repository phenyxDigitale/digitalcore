<?php

$title = $grid_columns_count = $grid_teasers_count = $grid_layout = $grid_link = $grid_link_target = '';
$grid_template = $grid_thumb_size = $grid_posttypes = $grid_layout_mode = $grid_taxomonies = $grid_categories = $posts_in = $posts_not_in = '';
$grid_content = $el_class = $width = $orderby = $order = $el_position = $isotope_item = '';

extract(Composer::shortcode_atts([
	'title'              => '',
	'grid_columns_count' => 4,
	'grid_teasers_count' => 8,
	'grid_layout'        => 'title_thumbnail_text', // title_thumbnail_text, thumbnail_title_text, thumbnail_text, thumbnail_title, thumbnail, title_text
	'grid_link'          => 'link_post', // link_post, link_image, link_image_post, link_no
	'grid_link_target'   => '_self',
	'grid_template'      => 'grid', //grid, carousel
	'grid_thumb_size'    => 'thumbnail',
	'grid_posttypes'     => '',
	'grid_taxomonies'    => '',
	'grid_categories'    => '',
	'grid_layout_mode'   => 'fitRows',
	'posts_in'           => '',
	'posts_not_in'       => '',
	'grid_content'       => 'teaser', // teaser, content
	'el_class'           => '',
	'width'              => '1/1',
	'orderby'            => NULL,
	'order'              => 'DESC',
	'el_position'        => '',
], $atts));

if ($grid_template == 'grid' || $grid_template == 'filtered_grid') {
	Context::getContext()->controller->addCSS(_EPH_ADMIN_THEME_DIR_ . '/composer/isotope/css/style.css');
	Context::getContext()->controller->addJS(_EPH_ADMIN_THEME_DIR_ . '/composer/isotope/dist/isotope.pkgd.min.js');
	$isotope_item = 'isotope-item ';
} else
if ($grid_template == 'carousel') {
	Context::getContext()->controller->addJS(_EPH_ADMIN_THEME_DIR_ . '/composer/jcarousellite/jcarousellite_1.0.1.min.js');
	$isotope_item = '';
}

if ($grid_link == 'link_image' || $grid_link == 'link_image_post') {
	Context::getContext()->controller->addCSS(_EPH_ADMIN_THEME_DIR_ . '/composer/prettyphoto/css/prettyPhoto.css');
	Context::getContext()->controller->addJS(_EPH_ADMIN_THEME_DIR_ . '/composer/prettyphoto/js/jquery.prettyPhoto.js');
}

$output = '';

$el_class = $this->getExtraClass($el_class);
$width = '';
$li_span_class = translateColumnsCountToSpanClass($grid_columns_count);

$query_args = [];

$not_in = [];

if ($posts_not_in != '') {
	$posts_not_in = str_ireplace(" ", "", $posts_not_in);
	$not_in = explode(",", $posts_not_in);
}

$link_target = $grid_link_target == '_blank' ? ' target="_blank"' : '';

//exclude current post/page from query

if ($posts_in == '') {
	global $post;
	array_push($not_in, $post->ID);
} else
if ($posts_in != '') {
	$posts_in = str_ireplace(" ", "", $posts_in);
	$query_args['post__in'] = explode(",", $posts_in);
}

if ($posts_in == '' || $posts_not_in != '') {
	$query_args['post__not_in'] = $not_in;
}

// Post teasers count

if ($grid_teasers_count != '' && !is_numeric($grid_teasers_count)) {
	$grid_teasers_count = -1;
}

if ($grid_teasers_count != '' && is_numeric($grid_teasers_count)) {
	$query_args['posts_per_page'] = $grid_teasers_count;
}

// Post types
$pt = [];

if ($grid_posttypes != '') {
	$grid_posttypes = explode(",", $grid_posttypes);

	foreach ($grid_posttypes as $post_type) {
		array_push($pt, $post_type);
	}

	$query_args['post_type'] = $pt;
}

// Taxonomies

$taxonomies = [];

if ($grid_taxomonies != '') {
	$grid_taxomonies = explode(",", $grid_taxomonies);

	foreach ($grid_taxomonies as $taxom) {
		array_push($taxonomies, $taxom);
	}

}

// Narrow by categories

if ($grid_categories != '') {
	$grid_categories = explode(",", $grid_categories);
	$gc = [];

	foreach ($grid_categories as $grid_cat) {
		array_push($gc, $grid_cat);
	}

	$gc = implode(",", $gc);
	////http://snipplr.com/view/17434/wordpress-get-category-slug/
	$query_args['category_name'] = $gc;

	$taxonomies = get_taxonomies('', 'object');
	$query_args['tax_query'] = ['relation' => 'OR'];

	foreach ($taxonomies as $t) {

		if (in_array($t->object_type[0], $pt)) {
			$query_args['tax_query'][] = [
				'taxonomy' => $t->name, //$t->name,//'portfolio_category',
				'terms'    => $grid_categories,
				'field'    => 'slug',
			];
		}

	}

}

// Order posts

if ($orderby != NULL) {
	$query_args['orderby'] = $orderby;
}

$query_args['order'] = $order;

// Run query
$my_query = new WP_Query($query_args);

$teasers = '';
$teaser_categories = [];

if ($grid_template == 'filtered_grid' && empty($grid_taxomonies)) {
	$taxonomies = get_object_taxonomies(!empty($query_args['post_type']) ? $query_args['post_type'] : get_post_types(['public' => false, 'name' => 'attachment'], 'names', 'NOT'));
}

$posts_Ids = [];

while ($my_query->have_posts()) {
	$link_title_start = $link_image_start = $p_link = $link_image_end = $p_img_large = '';

	$my_query->the_post();

	$posts_Ids[] = $my_query->post->ID;

	$categories_css = '';

	if ($grid_template == 'filtered_grid') {
		$post_categories = wp_get_object_terms($my_query->post->ID, array_values($taxonomies));

		if (!is_wp_error($post_categories)) {

			foreach ($post_categories as $cat) {

				if (!in_array($cat->term_id, $teaser_categories)) {
					$teaser_categories[] = $cat->term_id;
				}

				$categories_css .= ' grid-cat-' . $cat->term_id;
			}

		}

	}

	$post_title = the_title("", "", false);
	$post_id = $my_query->post->ID;

	$teaser_post_type = 'posts_grid_teaser_' . $my_query->post->post_type . ' ';

	if ($grid_content == 'teaser') {
		$content = get_the_excerpt();
	} else {
		$content = get_the_content();
		$content = apply_filters('the_content');
		$content = str_replace(']]>', ']]&gt;', $content);
	}

	$content = wpautop($content);
	$link = '';
	$thumbnail = '';

	if ($grid_link != 'link_no') {
		$link = '<a class="more-link" href="' . get_permalink($post_id) . '"' . $link_target . ' title="' . sprintf(esc_attr__('Permalink to %s', the_title_attribute('echo=0')) . '">' . __("Read more", "js_composer") . '</a>';
		}

		$mask_markup = '';

		if (in_array($grid_layout, ['title_thumbnail_text', 'thumbnail_title_text', 'thumbnail_text', 'thumbnail_title', 'thumbnail', 'title_text'])) {
			$post_thumbnail = $p_img_large = '';

			$post_thumbnail = getImageBySize(['post_id' => $post_id, 'thumb_size' => $grid_thumb_size]);
			$thumbnail = $post_thumbnail['thumbnail'];
			$p_img_large = $post_thumbnail['p_img_large'];

		}

		if ($grid_link != 'link_no') {
			$p_video = '';

			if ($grid_link == 'link_image' || $grid_link == 'link_image_post') {
				$p_video = get_post_meta($post_id, "_p_video", true);
			}

			if ($grid_link == 'link_post') {
				$link_image_start = '<a class="link_image" href="' . get_permalink($post_id) . '"' . $link_target . ' title="' . sprintf(esc_attr__('Permalink to %s', 'js_composer'), the_title_attribute('echo=0')) . '">';
				$link_title_start = '<a class="link_title" href="' . get_permalink($post_id) . '"' . $link_target . ' title="' . sprintf(esc_attr__('Permalink to %s', 'js_composer'), the_title_attribute('echo=0')) . '">';
			} else
			if ($grid_link == 'link_image') {

				if ($p_video != "") {
					$p_link = $p_video;
				} else {
					$p_link = $p_img_large[0];
				}

				$link_image_start = '<a class="link_image prettyphoto" href="' . $p_link . '"' . $link_target . ' title="' . the_title_attribute('echo=0') . '">';
				$link_title_start = '<a class="link_title prettyphoto" href="' . $p_link . '"' . $link_target . ' title="' . the_title_attribute('echo=0') . '">';
			} else
			if ($grid_link == 'link_image_post') {

				if ($p_video != "") {
					$p_link = $p_video;
				} else {
					$p_link = $p_img_large[0];
				}

				$link_image_start = '<a class="link_image prettyphoto" href="' . $p_link . '"' . $link_target . ' title="' . the_title_attribute('echo=0') . '">';
				$link_title_start = '<a class="link_title" href="' . get_permalink($post_id) . '"' . $link_target . ' title="' . sprintf(esc_attr__('Permalink to %s', 'js_composer'), the_title_attribute('echo=0')) . '">';
			}

			$link_title_end = $link_image_end = '</a>';
		} else {
			$link_image_start = '';
			$link_title_start = '';
			$link_title_end = $link_image_end = '';
		}

		$teasers .= '<li class="' . $isotope_item . $li_span_class . $categories_css . '">';
		// If grid layout is: Title + Thumbnail + Text

		if ($grid_layout == 'title_thumbnail_text') {

			if ($post_title) {
				$to_filter = '<h2 class="post-title">' . $link_title_start . $post_title . $link_title_end . '</h2>';
				$teasers .= $to_filter;
			}

			if ($thumbnail) {
				$to_filter = '<div class="post-thumb">' . $link_image_start . $thumbnail . $link_image_end . '</div>';
				$teasers .= $to_filter;
			}

			if ($content) {
				$to_filter = '<div class="entry-content">' . $content . '</div>';
				$teasers .= $to_filter;
			}

		}
		// If grid layout is: Thumbnail + Title + Text
		else
		if ($grid_layout == 'thumbnail_title_text') {

			if ($thumbnail) {
				$to_filter = '<div class="post-thumb">' . $link_image_start . $thumbnail . $link_image_end . '</div>';
				$teasers .= $to_filter;
			}

			if ($post_title) {
				$to_filter = '<h2 class="post-title">' . $link_title_start . $post_title . $link_title_end . '</h2>';
				$teasers .= $to_filter;
			}

			if ($content) {
				$to_filter = '<div class="entry-content">' . $content . '</div>';
				$teasers .= $to_filter;
			}

		}
		// If grid layout is: Thumbnail + Text
		else
		if ($grid_layout == 'thumbnail_text') {

			if ($thumbnail) {
				$to_filter = '<div class="post-thumb">' . $link_image_start . $thumbnail . $link_image_end . '</div>';
				$teasers .= $to_filter;
			}

			if ($content) {
				$to_filter = '<div class="entry-content">' . $content . '</div>';
				$teasers .= $to_filter;
			}

		}
		// If grid layout is: Thumbnail + Title
		else
		if ($grid_layout == 'thumbnail_title') {

			if ($thumbnail) {
				$to_filter = '<div class="post-thumb">' . $link_image_start . $thumbnail . $link_image_end . '</div>';
				$teasers .= $to_filter;
			}

			if ($post_title) {
				$to_filter = '<h2 class="post-title">' . $link_title_start . $post_title . $link_title_end . '</h2>';
				$teasers .= $to_filter;
			}

		}
		// If grid layout is: Thumbnail
		else
		if ($grid_layout == 'thumbnail') {

			if ($thumbnail) {
				$to_filter = '<div class="post-thumb">' . $link_image_start . $thumbnail . $link_image_end . '</div>';
				$teasers .= $to_filter;
			}

		}
		// If grid layout is: Title + Text
		else
		if ($grid_layout == 'title_text') {

			if ($post_title) {
				$to_filter = '<h2 class="post-title">' . $link_title_start . $post_title . $link_title_end . '</h2>';
				$teasers .= $to_filter;
			}

			if ($content) {
				$to_filter = '<div class="entry-content">' . $content . '</div>';
				$teasers .= $to_filter;
			}

		}

		$teasers .= '</li> ' . $this->endBlockComment('single teaser');
	}
	// endwhile loop
	wp_reset_query();

	if ($grid_template == 'filtered_grid' && $teasers && !empty($teaser_categories)) {
		/*
			$categories_list = wp_list_categories(array(
				'orderby' => 'name',
				'walker' => new Teaser_Grid_Category_Walker(),
				'include' => implode(',', $teaser_categories),
				'show_option_none'   => __('No categories', 'js_composer'),
				'echo' => false
			));
		*/
		$categories_array = get_terms(array_values($taxonomies), [
			'orderby' => 'name',
			'include' => implode(',', $teaser_categories),
		]);

		$categories_list_output = '<ul class="categories_filter vc_clearfix">';
		$categories_list_output .= '<li class="active"><a href="#" data-filter="*">' . __('All', 'js_composer') . '</a></li>';

		if (!is_wp_error($categories_array)) {

			foreach ($categories_array as $cat) {
				$categories_list_output .= '<li><a href="#" data-filter=".grid-cat-' . $cat->term_id . '">' . esc_attr($cat->name) . '</a></li>';
			}

		}

		$categories_list_output .= '</ul><div class="vc_clearfix"></div>';
	} else {
		$categories_list_output = '';
	}

	if ($teasers) {
		$teasers = '<div class="teaser_grid_container">' . $categories_list_output . '<ul class="wpb_thumbnails wpb_thumbnails-fluid vc_clearfix" data-layout-mode="' . $grid_layout_mode . '">' . $teasers . '</ul></div>';
	} else {
		$teasers = __("Nothing found.", "js_composer");
	}

	$posttypes_teasers = '';

	if (is_array($grid_posttypes)) {
		//$posttypes_teasers_ar = explode(",", $grid_posttypes);
		$posttypes_teasers_ar = $grid_posttypes;

		foreach ($posttypes_teasers_ar as $post_type) {
			$posttypes_teasers .= 'wpb_teaser_grid_' . $post_type . ' ';
		}

	}

	$grid_class = 'wpb_' . $grid_template . ' columns_count_' . $grid_columns_count . ' grid_layout-' . $grid_layout . ' ' . $grid_layout . '_' . $li_span_class . ' ' . 'columns_count_' . $grid_columns_count . '_' . $grid_layout . ' ' . $posttypes_teasers;
	$css_class = 'wpb_teaser_grid wpb_content_element ' . $grid_class . $width . $el_class;

	$output .= "\n\t" . '<div class="' . $css_class . '">';
	$output .= "\n\t\t" . '<div class="wpb_wrapper">';
//$output .= ($title != '' ) ? "\n\t\t\t".'<h2 class="wpb_heading wpb_teaser_grid_heading">'.$title.'</h2>' : '';
	$output .= wpb_widget_title(['title' => $title, 'extraclass' => 'wpb_teaser_grid_heading']);

	if ($grid_template == 'carousel') {
		$output .= apply_filters('vc_teaser_grid_carousel_arrows', '<a href="#" class="prev">&larr;</a> <a href="#" class="next">&rarr;</a>');
	}

	$output .= $teasers;
	$output .= "\n\t\t" . '</div> ' . $this->endBlockComment('.wpb_wrapper');
	$output .= "\n\t" . '</div> ' . $this->endBlockComment('.wpb_teaser_grid');

	echo $output;