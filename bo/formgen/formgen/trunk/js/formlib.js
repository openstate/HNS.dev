var Form = new Class({
	initialize: function(form, checks, events, giveFocus) {
		this.form = $(form);

		// Bind checks
		this.checks = checks;
		for (var i = 0; i < this.checks.length; i++)
			this.checks[i] = this.checks[i].bind(this);

		// Bind events
		for (var i = 0; i < events.length; i++) {
			events[i].binder(events[i].event, events[i].handler.bindWithEvent(this, [this.form]));
			events[i].handler.bind(this)(null, this.form);
		}

		this.autoValidate = false;
		this.form.addEvent('submit', function(event) {
			this.autoValidate = true;
			if (!this.validate() && event) {
				var e = new Event(event);
				e.stop();
				event.stopped = true;
			}
		}.bindWithEvent(this));

		if ($pick(giveFocus, true)) {
			var inputs = this.form.getElementsBySelector('input[type!=hidden], textarea, select');
			if (inputs.length > 0) inputs[0].focus();
		}

		this.tabs = [];
		// Init tabs
		this.form.getElements('.tab-block').each(function(el) {
			this.tabs.push(new SimpleTabs(el, { selector: 'h4' } ));
		}, this);
	},

	validate: function(event) {
		var result = true;
		for (var i = 0; i < this.checks.length; i++) {
			if (!this.checks[i](this.form))
				result = false;
		}
		return result;
	},

	disable: function(elem, enabled) {
		elem.getElementsBySelector('input, textarea, select').each(function(el) {
			el.disabled = !enabled;
		});
	},

	hide: function(elem, visible) {
		if (visible)
			elem.setStyle('display', '');
		else
			elem.setStyle('display', 'none');
	},

	setVisibility: function(elem, visible) {
		this.hide(elem, visible);
		if (elem.hasClass('error')) {
			this.tabs.each(function(tabGroup) {
				tabGroup.tabs.each(function(tab) {
					if (tab.container.getElements('.error').some(function(item) {
						return !item.getParent().getParent().hasClass('tab-menu') && item.getStyle('display') != 'none';
					}))
						tab.toggle.getFirst().addClass('error');
					else
						tab.toggle.getFirst().removeClass('error');
				});
			});
		}
	}
});

function getRadioValue(radio) {
	if (!$defined(radio))
		return '';
	if (!radio.length)
		if (radio.checked)
			return radio.value;
		else
			return '';

	for (var i = 0; i < radio.length; i++)
		if (radio[i].checked)
			return radio[i].value;
	return '';
}

function getMultiCheckValue(check) {
	if (!$defined(check))
		return '';
	if (!check.length)
		if (check.checked)
			return check.value;
		else
			return '';

	var result = [];
	for (var i = 0; i < check.length; i++)
		if (check[i].checked)
			result.push(check[i].value);
	return result.join('||');
}

function bindRadioEvent(radio, event, handler) {
	if (!$defined(radio))
		return;
	if (!radio.length)
		radio.addEvent(event, handler);
	else
		for (var i = 0; i < radio.length; i++)
			$(radio[i]).addEvent(event, handler);
}

function bindDateEvent(form, name, event, handler) {
	['[Day]', '[Month]', '[Year]', '[Hour]', '[Minute]', '[Second]'].each(function(sub) {
		if ($defined(form[name+sub]))
			$(form[name+sub]).addEvent(event, handler);
	});
}

function getDateValue(form, name) {
	var date = [], time = [];
	['[Year]', '[Month]', '[Day]'].each(function(sub) {
		if ($defined(form[name+sub]) && form[name+sub].value != '')
			date.push(form[name+sub].value);
	});
	date = date.join('-');

	['[Hour]', '[Minute]', '[Second]'].each(function(sub) {
		if ($defined(form[name+sub]) && form[name+sub].value != '')
			time.push(form[name+sub].value);
	});
	time = time.join(':');

	if (date != '' && time != '')
		return date+' '+time;
	else
		return date+time;
}

function initCalendar(form, name) {
	var inputs = [];
	var largestInput = null;
	[['[Year]', 'Y'], ['[Month]', 'm'], ['[Day]', 'd']].each(function(sub) {
		if ($defined(form[name+sub[0]])) {
			inputs.push({ elem: form[name+sub[0]], format: sub[1] });
			if (!largestInput)
				largestInput = form[name+sub[0]];
		}
	});

	if (!largestInput) return; // Time fields only

	new Calendar({
			elem: largestInput,
			inputs: inputs
		},
		{ offset: 1 }
	);
}

function initDependentSelect(form, name, dependency, map) {
	new DependentSelect(form[name], form[dependency], map);
}

var FocusHints = new Class({
	initialize: function(elem) {
		this.hints = $(elem).getElements('.focus-hint');
		this.hints.each(function(el) {
			// Find all inputs within
			var inputs = el.getPrevious().getElementsBySelector('input, textarea, select').addEvent('focus', function(ev) {
				el.setStyle('display', '');
			}).addEvent('blur', function(ev) {
				el.setStyle('display', 'none');
			});

			el.setStyle('display', 'none');
		});
	}
});
