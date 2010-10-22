
(function() {
	tinymce.create('tinymce.plugins.AccepteImagePlugin', {
		init : function(ed, url) {
			// Register commands
			ed.addCommand('mceAccepteImage', function() {
				// Internal image object like a flash placeholder
				if (ed.dom.getAttrib(ed.selection.getNode(), 'class').indexOf('mceItem') != -1)
					return;

				ed.windowManager.open({
					file : url + '/image.htm',
					width : 800 + parseInt(ed.getLang('accepteimage.delta_width', 0), 10),
					height : 630 + parseInt(ed.getLang('acceptemage.delta_height', 0), 10),
					inline : 1
				}, {
					plugin_url : url
				});
			});

			// Register buttons
			ed.addButton('image', {
				title : 'advimage.image_desc',
				cmd : 'mceAccepteImage'
			});
		},

		getInfo : function() {
			return {
				longname : 'Accepte image',
				author : 'Accepte B.V',
				authorurl : 'http://www.accepte.nl',
				infourl : 'http://www.accepte.nl',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('accepteimage', tinymce.plugins.AccepteImagePlugin);
})();