<?php
extract(Composer::shortcode_atts([
	'el_width'     => '',
	'style'        => '',
	'color'        => '',
	'accent_color' => '',
	'el_class'     => '',
], $atts));

echo Composer::do_shortcode('[vc_text_separator style="' . $style . '" color="' . $color . '" accent_color="' . $accent_color . '" el_width="' . $el_width . '" el_class="' . $el_class . '"]');