tinyMCE.init({
	theme : "advanced",
	mode : 'none',
	plugins: 'preview, accepteimage, acceptelink',
	theme_advanced_disable: 'styleselect',
	theme_advanced_buttons1_add : 'forecolor, backcolor',
	theme_advanced_buttons3_add : "preview",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : 'bottom',
	theme_advanced_resizing : true,
	convert_fonts_to_spans : true,
	content_css: '/assets/skins/frontoffice/stylesheets/tinymce.css',
	relative_urls: false,
	fix_list_elements : true, //fixes list to be valid xhtml
	fix_table_elements : true, //fixes tables to be valid xhtml
	doctype: '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
	plugin_preview_width : "800",
    plugin_preview_height : "600",
    plugin_preview_pageurl : "/assets/widgets/tiny_mce/plugins/preview/example.html"
});