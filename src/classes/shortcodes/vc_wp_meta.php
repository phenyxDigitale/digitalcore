<?php
$output = $title = $el_class = '';
extract(Composer::shortcode_atts([
	'title'    => __('Meta'),
	'el_class' => '',
], $atts));

$el_class = $this->getExtraClass($el_class);

$output = '<div class="vc_wp_meta wpb_content_element' . $el_class . '">';
$type = 'WP_Widget_Meta';
$args = [];

ob_start();
the_widget($type, $atts, $args);
$output .= ob_get_clean();

$output .= '</div>' . $this->endBlockComment('vc_wp_meta') . "\n";

echo $output;