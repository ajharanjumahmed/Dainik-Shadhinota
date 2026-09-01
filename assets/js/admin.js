/* Khobor — Admin JS.
   - Wires the "Choose PDF" media picker on the E-Paper screen.
   - Live preview color updates for Customizer. */

(function ($) {
	'use strict';

	$(function () {
		// Media picker buttons (E-Paper CPT, etc.)
		$(document).on('click', '.khobor-media-pick', function (e) {
			e.preventDefault();
			var btn    = $(this);
			var target = btn.attr('data-target');
			var type   = btn.attr('data-type') || 'application/pdf';

			var frame = wp.media({
				title: 'Choose file',
				library: { type: type },
				multiple: false,
				button: { text: 'Use this file' }
			});

			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				$('#' + target).val(att.url).trigger('change');
			});

			frame.open();
		});
	});

	// Customizer live preview (color vars).
	if (typeof wp !== 'undefined' && wp.customize) {
		var keys = ['primary', 'secondary', 'accent', 'text', 'muted', 'bg', 'surface'];
		keys.forEach(function (k) {
			wp.customize('khobor_color_' + k, function (value) {
				value.bind(function (newVal) {
					document.documentElement.style.setProperty('--khobor-' + k, newVal);
				});
			});
		});
	}
})(jQuery);
