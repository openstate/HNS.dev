(function() {
	tinymce.create('tinymce.plugins.AccepteLinkPlugin', {
		init : function(ed, url) {
			this.editor = ed;

			// Register commands
			ed.addCommand('mceAccepteLink', function() {
				var se = ed.selection;

				// No selection and not in link
				if (se.isCollapsed() && !ed.dom.getParent(se.getNode(), 'A'))
					return;

				ed.windowManager.open({
					file : url + '/link.htm',
					width : 800 + parseInt(ed.getLang('acceptelink.delta_width', 0), 10),
					height : 680 + parseInt(ed.getLang('acceptelink.delta_height', 0), 10),
					inline : 1
				}, {
					plugin_url : url
				});
			});

			// Register buttons
			ed.addButton('link', {
				title : 'advlink.link_desc',
				cmd : 'mceAccepteLink'
			});

			ed.addShortcut('ctrl+k', 'acceptelink.acceptelink_desc', 'mceAccepteLink');

			ed.onNodeChange.add(function(ed, cm, n, co) {
				cm.setDisabled('link', co && n.nodeName != 'A');
				cm.setActive('link', n.nodeName == 'A' && !n.name);
			});
		},

		getInfo : function() {
			return {
				longname : 'Accepte link',
				author : 'Accepte B.V',
				authorurl : 'http://www.accepte.nl',
				infourl : 'http://www.accepte.nl',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('acceptelink', tinymce.plugins.AccepteLinkPlugin);
})();