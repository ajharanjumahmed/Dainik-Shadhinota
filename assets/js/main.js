/* Khobor — main client-side script.
   Handles: mobile menu toggle, search panel, dark mode,
   share copy button, photocard generator request. */

(function () {
	'use strict';

	var $ = function (sel, root) { return (root || document).querySelector(sel); };
	var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

	document.addEventListener('DOMContentLoaded', function () {
		initMenuToggle();
		initSearchToggle();
		initDarkMode();
		initShareCopy();
		initPhotocard();
	});

	// -----------------------------------------------------------
	// Mobile menu
	// -----------------------------------------------------------
	function initMenuToggle() {
		var toggle = $('.khobor-menu-toggle');
		var menu   = $('.khobor-mainnav__menu');
		if (!toggle || !menu) return;

		toggle.addEventListener('click', function () {
			var expanded = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', String(!expanded));
			menu.classList.toggle('is-open');
		});

		// Submenu toggles inside mobile view.
		$$('.khobor-nav .has-submenu > a').forEach(function (a) {
			a.addEventListener('click', function (e) {
				if (window.innerWidth >= 768) return;
				e.preventDefault();
				var li = a.parentElement;
				li.classList.toggle('is-open');
				var sub = li.querySelector('.sub-menu, .khobor-submenu');
				if (sub) sub.style.display = li.classList.contains('is-open') ? 'block' : 'none';
				a.setAttribute('aria-expanded', li.classList.contains('is-open') ? 'true' : 'false');
			});
		});
	}

	// -----------------------------------------------------------
	// Search panel
	// -----------------------------------------------------------
	function initSearchToggle() {
		var btn   = $('.khobor-search-toggle');
		var panel = $('.khobor-search-panel');
		if (!btn || !panel) return;
		btn.addEventListener('click', function () {
			var hidden = panel.hasAttribute('hidden');
			if (hidden) panel.removeAttribute('hidden');
			else panel.setAttribute('hidden', '');
			if (!hidden) return;
			var input = panel.querySelector('input[type="search"]');
			if (input) input.focus();
		});
	}

	// -----------------------------------------------------------
	// Dark mode
	// -----------------------------------------------------------
	function initDarkMode() {
		var btn = $('.khobor-darkmode-toggle');
		if (!btn) return;

		var stored;
		try { stored = localStorage.getItem('khobor-dark'); } catch (e) { stored = null; }
		if (stored === '1') {
			document.body.classList.add('khobor-dark');
			btn.setAttribute('aria-pressed', 'true');
		}

		btn.addEventListener('click', function () {
			var isDark = document.body.classList.toggle('khobor-dark');
			btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
			try { localStorage.setItem('khobor-dark', isDark ? '1' : '0'); } catch (e) {}
		});
	}

	// -----------------------------------------------------------
	// Share — copy to clipboard
	// -----------------------------------------------------------
	function initShareCopy() {
		$$('.khobor-share__btn--copy').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var url = btn.getAttribute('data-copy') || location.href;
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(url).then(function () {
						btn.classList.add('is-copied');
						setTimeout(function () { btn.classList.remove('is-copied'); }, 1400);
					});
				} else {
					var ta = document.createElement('textarea');
					ta.value = url;
					document.body.appendChild(ta);
					ta.select();
					try { document.execCommand('copy'); } catch (e) {}
					document.body.removeChild(ta);
					btn.classList.add('is-copied');
					setTimeout(function () { btn.classList.remove('is-copied'); }, 1400);
				}
			});
		});
	}

	// -----------------------------------------------------------
	// Photocard generator (REST POST → server returns image URL)
	// -----------------------------------------------------------
	function initPhotocard() {
		var btn = $('.khobor-photocard-btn');
		if (!btn || !window.KhoborData) return;

		// photocard.js draws the card on a canvas so Bengali is shaped properly.
		// This REST path is the fallback for when that script is absent.
		if (window.KhoborPhotocardCanvas || document.getElementById('khobor-photocard-data')) return;

		var result = $('.khobor-photocard-result');

		btn.addEventListener('click', function () {
			var postId = parseInt(btn.getAttribute('data-post-id'), 10);
			if (!postId) return;
			var originalText = btn.querySelector('span') ? btn.querySelector('span').textContent : btn.textContent;

			btn.classList.add('is-loading');
			if (btn.querySelector('span')) {
				btn.querySelector('span').textContent = KhoborData.i18n.generating;
			}

			fetch(KhoborData.restUrl + 'khobor/v1/photocard', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': KhoborData.restNonce
				},
				body: JSON.stringify({ post_id: postId })
			})
			.then(function (r) {
				if (!r.ok) throw new Error('http ' + r.status);
				return r.json();
			})
			.then(function (data) {
				if (!data || !data.url) throw new Error('bad response');
				result.hidden = false;
				result.innerHTML =
					'<img src="' + escapeAttr(data.url) + '" alt="Photocard" loading="lazy">' +
					'<div class="khobor-photocard-actions">' +
						'<a class="khobor-btn khobor-btn--primary" href="' + escapeAttr(data.url) + '" download>' + escapeHtml('Download') + '</a>' +
						'<a class="khobor-btn khobor-btn--ghost" target="_blank" rel="noopener" href="' + escapeAttr(data.url) + '">' + escapeHtml('Open') + '</a>' +
					'</div>';
			})
			.catch(function () {
				result.hidden = false;
				result.textContent = KhoborData.i18n.photocardErr;
			})
			.finally(function () {
				btn.classList.remove('is-loading');
				if (btn.querySelector('span')) {
					btn.querySelector('span').textContent = originalText;
				}
			});
		});
	}

	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
		});
	}
	function escapeAttr(s) { return escapeHtml(s); }
})();
