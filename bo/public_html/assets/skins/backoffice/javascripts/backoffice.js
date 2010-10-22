window.addEvent('domready', function() {
	$$('a.entry-delete, a.folder-delete').addEvent('click', function(e) {
		e = new Event(e);
		
		if (confirm(confirmMessage)) {
			return;
		}
		e.preventDefault();
	});
	
	$$('a.delete').addEvent('click', function(e) {
		e = new Event(e);
		if (this.hasClass('disabled')) {
			e.preventDefault();
			return;
		}
		
		if (confirm(confirmMessage)) {
			return;
		}
		e.preventDefault();
	});
	
	$$('table.list tr').each(function(tr) {
		var options = tr.getElement('div.options');
		if (options != undefined) {
			var item = options.getFirst();
		}
		if (item != undefined && (item.hasClass('entry-edit') || item.hasClass('entry-view') || item.hasClass('folder-view') || item.hasClass('folder-edit'))) {						
			tr.getChildren('td').each(function(td, i) {
				
				if (!td.hasClass('noclick')) {
					td.addEvent('click', function(e) {
						window.location = item.getProperty('href');
					});
					td.setStyle('cursor', 'pointer');
				}
			});
		}
	});	
});