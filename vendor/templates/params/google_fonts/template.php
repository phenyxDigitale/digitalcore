<?php 
$vc_manager = ephenyx_manager();
?>

<div class="vc_row-fluid vc_shortcode-param vc_column">
    <div class="wpb_element_label"><?php echo $vc_manager->l('Font Family'); ?></div>
    <div class="vc_google_fonts_form_field-font_family-container">
        <select class="vc_google_fonts_form_field-font_family-select" default[font_style]="<?php echo $values['font_style']; ?>">
        <?php
        $fonts = $this->_vc_google_fonts_get_fonts();
       
        foreach ( $fonts as $font_data ): ?>
            <option value="<?php echo $font_data->font_family . ':' . $font_data->font_styles; ?>" data[font_types]="<?php echo $font_data->font_types; ?>" data[font_family]="<?php echo $font_data->font_family; ?>" data[font_styles]="<?php echo $font_data->font_styles; ?>" class="<?php echo build_safe_css_class( $font_data->font_family ); ?>" <?php echo( strtolower( $values['font_family'] ) == strtolower( $font_data->font_family ) || strtolower( $values['font_family'] ) == strtolower( $font_data->font_family ) . ':' . $font_data->font_styles ? 'selected="selected"' : '' ); ?> ><?php echo $font_data->font_family; ?></option>
        <?php endforeach; ?>
        </select>
    </div>
    <?php if (isset( $fields['font_family_description'] ) && strlen( $fields['font_family_description'] ) > 0): ?>
    <span class="vc_description clear"><?php echo $fields['font_family_description']; ?></span>
    <?php endif; ?>
</div>

<?php if ( isset( $fields['no_font_style'] ) && $fields['no_font_style'] === false || !isset( $fields['no_font_style'] ) ):     
    ?>
    <div class="vc_row-fluid vc_shortcode-param vc_column">
        <div class="wpb_element_label"><?php echo $vc_manager->l('Font style'); ?></div>
        <div class="vc_google_fonts_form_field-font_style-container">
            <select class="vc_google_fonts_form_field-font_style-select"></select>
        </div>
    </div>
    <?php if (isset( $fields['font_style_description'] ) && strlen( $fields['font_style_description'] ) > 0): ?>
        <span class="vc_description clear"><?php echo $fields['font_style_description']; ?></span>
    <?php endif; ?>
<?php endif; ?>

<div class="vc_row-fluid vc_shortcode-param vc_column">
    <div class="wpb_element_label"><?php echo $vc_manager->l('Google Fonts preview'); ?>:</div>
    <div class="vc_google_fonts_form_field-preview-container">
        <span><?php echo $vc_manager->l('Grumpy wizards make toxic brew for the evil Queen and Jack.'); ?></span>
    </div>
    <div class="vc_google_fonts_form_field-status-container"><span></span></div>
</div>

<input name="<?php echo $settings['param_name']; ?>" class="wpb_vc_param_value  <?php echo $settings['param_name'] . ' ' . $settings['type']; ?>_field" type="hidden" value="<?php echo $value; ?>" />
