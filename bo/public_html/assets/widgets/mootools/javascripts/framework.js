var DependentSelect = new Class({
	initialize: function(selectElement, dependency, map) {
		this.selectElement = $(selectElement);
		this.dependency = $$(dependency);
		this.map = new Hash(map);

		this.values = new Hash();
		this.selectElement.getChildren().each(function(opt) {
			this.values.set(opt.value, opt.innerHTML);
		}.bind(this));

		if (this.dependency.length == 1) { // select
			this.fillSelect(this.map.get(this.dependency.getValue()));

			this.dependency.addEvent('change', function() {
				this.fillSelect(this.map.get(this.dependency.getValue()));
			}.bind(this));
		} else { // radios
			this.dependency.each(function (el) {
				if (el.checked)
					this.fillSelect(this.map.get(el.getValue()));
				el.addEvent('change', function() {
					this.fillSelect(this.map.get(el.getValue()));
				}.bind(this));
			}.bind(this));
		}
	},

	fillSelect: function(keys) {
		var selected = null;
		if ($defined(keys) && this.selectElement.selectedIndex >= 0)
			selected = this.selectElement.options[this.selectElement.selectedIndex].value;
		this.selectElement.options.length = 0;
		if ($defined(keys)) {
			for (var i = 0; i < keys.length; i++) {
				this.selectElement.options.add(new Option(this.values.get(keys[i]), keys[i]), i);
				if (keys[i] == selected)
					this.selectElement.selectedIndex = i;
			}
		}
		this.selectElement.fireEvent('change');
	}
});