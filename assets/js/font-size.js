/* Khobor — Font size adjuster.
   Applies a body class corresponding to the chosen step. */

(function () {
	'use strict';
	var STEPS = ['khobor-font-size-sm', 'khobor-font-size-md', 'khobor-font-size-lg', 'khobor-font-size-xl'];
	var DEFAULT_INDEX = 1; // md

	document.addEventListener('DOMContentLoaded', function () {
		var sizer = document.querySelector('.khobor-font-sizer');
		if (!sizer) return;

		var index = readStored();
		applyClass(index);
		markActive(sizer, index);

		sizer.addEventListener('click', function (e) {
			var btn = e.target.closest('.khobor-font-sizer__btn');
			if (!btn) return;
			var step = parseInt(btn.getAttribute('data-step'), 10);
			if (isNaN(step)) return;

			if (step === 0) {
				index = DEFAULT_INDEX;
			} else {
				index = Math.max(0, Math.min(STEPS.length - 1, index + step));
			}
			applyClass(index);
			markActive(sizer, index);
			persist(index);
		});
	});

	function readStored() {
		try {
			var v = localStorage.getItem('khobor-font-step');
			if (v === null) return DEFAULT_INDEX;
			var n = parseInt(v, 10);
			return isNaN(n) ? DEFAULT_INDEX : Math.max(0, Math.min(STEPS.length - 1, n));
		} catch (e) { return DEFAULT_INDEX; }
	}
	function persist(i) {
		try { localStorage.setItem('khobor-font-step', String(i)); } catch (e) {}
	}
	function applyClass(i) {
		var body = document.body;
		STEPS.forEach(function (c) { body.classList.remove(c); });
		body.classList.add(STEPS[i]);
	}
	function markActive(sizer, i) {
		var resetBtn = sizer.querySelector('.khobor-font-sizer__btn--reset');
		if (resetBtn) {
			resetBtn.classList.toggle('is-active', i === DEFAULT_INDEX);
		}
	}
})();
