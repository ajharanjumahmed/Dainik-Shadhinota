/* Khobor — Breaking news ticker.
   The CSS animation handles motion; we duplicate the items
   once so the loop is seamless. We also wire the pause button. */

(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', function () {
		var ticker = document.querySelector('.khobor-ticker');
		if (!ticker) return;
		var track  = ticker.querySelector('.khobor-ticker__track');
		if (!track) return;

		// Duplicate items for seamless loop.
		var items = track.innerHTML;
		track.innerHTML = items + items;

		// Pause button.
		var btn = ticker.querySelector('.khobor-ticker__pause');
		if (btn) {
			btn.addEventListener('click', function () {
				var paused = ticker.classList.toggle('is-paused');
				btn.textContent = paused ? '▶' : '⏸';
				btn.setAttribute('aria-label', paused ? 'Resume ticker' : 'Pause ticker');
			});
		}

		// Pause when tab is hidden, to save CPU.
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) ticker.classList.add('is-paused');
			else if (!btn || btn.textContent !== '▶') ticker.classList.remove('is-paused');
		});
	});
})();
