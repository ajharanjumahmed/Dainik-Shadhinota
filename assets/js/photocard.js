/* Khobor — Photocard renderer (browser side).
 *
 * WHY THIS RUNS IN THE BROWSER
 * Bengali needs a shaping engine: conjuncts must fuse (ক + ্ + ষ becomes ক্ষ
 * with no visible hasant) and pre-base vowel signs must move ahead of their
 * consonant. PHP's GD draws glyphs through FreeType with no HarfBuzz layer, so
 * it does neither, and headlines come out broken — জ্বালানির renders as
 * জ্‌বালানির. Imagick with the Pango delegate can shape properly, but it isn't
 * installed on most shared hosts.
 *
 * Every browser, however, shapes Bengali correctly, and canvas fillText() goes
 * through that same engine. So the card is drawn here and handed to the user as
 * a download. The PHP renderer stays as the server-side path (og:image, hosts
 * that do have Pango).
 */

(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', init);

	function init() {
		var btn = document.querySelector('.khobor-photocard-btn');
		if (!btn) return;

		var holder = document.getElementById('khobor-photocard-data');
		if (!holder) return;

		var data;
		try {
			data = JSON.parse(holder.textContent);
		} catch (e) {
			return;
		}
		if (!data) return;

		// Tell main.js's REST-based fallback to stand down.
		window.KhoborPhotocardCanvas = true;

		var result = document.querySelector('.khobor-photocard-result');

		btn.addEventListener('click', function () {
			var label = btn.querySelector('span');
			var original = label ? label.textContent : '';

			btn.classList.add('is-loading');
			if (label && window.KhoborData) label.textContent = KhoborData.i18n.generating;

			render(data)
				.then(function (canvas) {
					showResult(result, canvas, data.filename);
				})
				.catch(function (err) {
					if (window.console) console.error('Photocard render failed', err);
					if (result) {
						result.hidden = false;
						result.textContent = (window.KhoborData && KhoborData.i18n.photocardErr) || 'Could not generate photocard';
					}
				})
				.finally(function () {
					btn.classList.remove('is-loading');
					if (label) label.textContent = original;
				});
		});
	}

	// -----------------------------------------------------------------------
	// Rendering
	// -----------------------------------------------------------------------

	function render(d) {
		var S = d.size;
		var L = d.layout;
		var C = d.colors;

		var canvas = document.createElement('canvas');
		canvas.width = S;
		canvas.height = S;
		var ctx = canvas.getContext('2d');

		// Wait for the Bengali webfont, otherwise the first render measures and
		// draws in a fallback face.
		return ensureFont(d.font)
			.then(function () {
				return Promise.all([loadImage(d.image), loadImage(d.logo), loadImage(d.overlay)]);
			})
			.then(function (imgs) {
				var photo = imgs[0], logo = imgs[1], overlay = imgs[2];

				// 1. Paper, then the red panel.
				ctx.fillStyle = C.paper;
				ctx.fillRect(0, 0, S, S);
				ctx.fillStyle = C.panel;
				ctx.fillRect(0, L.red_top, S, S - L.red_top);

				// 2. Masthead, centred on the white strip.
				if (logo) {
					var lh = L.masthead_height;
					var lw = Math.round(logo.width * (lh / logo.height));
					ctx.drawImage(logo, Math.round((S - lw) / 2), L.masthead_top, lw, lh);
				}

				// 3. Gold frame with the photo inset, corners matched.
				ctx.fillStyle = C.border;
				roundRectPath(ctx, L.box_x, L.box_y, L.box_w, L.box_h, L.box_radius);
				ctx.fill();

				var b = L.box_border;
				var ix = L.box_x + b, iy = L.box_y + b;
				var iw = L.box_w - b * 2, ih = L.box_h - b * 2;
				var ir = Math.max(0, L.box_radius - b);

				ctx.save();
				roundRectPath(ctx, ix, iy, iw, ih, ir);
				ctx.clip();

				if (photo) {
					drawCover(ctx, photo, ix, iy, iw, ih);
				} else {
					ctx.fillStyle = '#0f172a';
					ctx.fillRect(ix, iy, iw, ih);
				}
				if (overlay) {
					ctx.drawImage(overlay, ix, iy, iw, ih);
				}
				ctx.restore();

				// 4. Headline — bold, centred, shaped by the browser.
				drawHeadline(ctx, d, S, L, C);

				// 5. Footer strip.
				drawFooter(ctx, d, S, L, C);

				return canvas;
			});
	}

	function drawHeadline(ctx, d, S, L, C) {
		var text = (d.title || '').trim();
		if (!text) return;

		var availW = S - L.headline_pad * 2;
		var availH = L.headline_bottom - L.headline_top;

		var fit = fitText(ctx, text, availW, availH, d.font, d.maxLines);
		if (!fit) return;

		ctx.fillStyle = C.headline;
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.font = '700 ' + fit.size + 'px ' + d.font;

		var blockH = fit.lines.length * fit.lineHeight;
		var y = L.headline_top + (availH - blockH) / 2 + fit.lineHeight / 2;

		fit.lines.forEach(function (line) {
			ctx.fillText(line, S / 2, y);
			y += fit.lineHeight;
		});
	}

	function drawFooter(ctx, d, S, L, C) {
		var f = d.footer || {};
		var pad = L.footer_pad;
		var gap = 20;
		var size = L.footer_size;

		// Shrink until the three spans clear each other. The middle string is
		// centred on the canvas while the outer two are pinned to the margins,
		// so summed widths aren't a sufficient test.
		for (; size > 14; size--) {
			ctx.font = '400 ' + size + 'px ' + d.font;
			var wl = ctx.measureText(f.left || '').width;
			var wc = ctx.measureText(f.center || '').width;
			var wr = ctx.measureText(f.right || '').width;

			var leftEnd = pad + wl;
			var centreStart = (S - wc) / 2;
			var centreEnd = centreStart + wc;
			var rightStart = S - pad - wr;

			if (leftEnd + gap <= centreStart && centreEnd + gap <= rightStart) break;
		}

		ctx.font = '400 ' + size + 'px ' + d.font;
		ctx.fillStyle = C.footer;
		ctx.textBaseline = 'alphabetic';

		ctx.textAlign = 'left';
		ctx.fillText(f.left || '', pad, L.footer_baseline);

		ctx.textAlign = 'center';
		ctx.fillText(f.center || '', S / 2, L.footer_baseline);

		ctx.textAlign = 'right';
		ctx.fillText(f.right || '', S - pad, L.footer_baseline);
	}

	/**
	 * Largest size at which the headline wraps into the allowed lines and fits
	 * the available height. Wrapping only ever splits on spaces, so a Bengali
	 * word is never broken across lines.
	 */
	function fitText(ctx, text, maxW, maxH, font, maxLines) {
		for (var size = 54; size >= 26; size -= 2) {
			ctx.font = '700 ' + size + 'px ' + font;
			var lines = wrap(ctx, text, maxW);
			var lineHeight = Math.round(size * 1.45);

			if (lines.length <= maxLines && lines.length * lineHeight <= maxH) {
				return { size: size, lines: lines, lineHeight: lineHeight };
			}
		}

		ctx.font = '700 26px ' + font;
		var clipped = wrap(ctx, text, maxW).slice(0, maxLines);
		if (clipped.length) clipped[clipped.length - 1] += '…';
		return { size: 26, lines: clipped, lineHeight: 38 };
	}

	function wrap(ctx, text, maxW) {
		var words = text.split(/\s+/).filter(Boolean);
		var lines = [];
		var current = '';

		words.forEach(function (word) {
			var candidate = current ? current + ' ' + word : word;
			// A single over-long word keeps its own line rather than being cut
			// mid-cluster, which would corrupt the shaping.
			if (!current || ctx.measureText(candidate).width <= maxW) {
				current = candidate;
			} else {
				lines.push(current);
				current = word;
			}
		});

		if (current) lines.push(current);
		return lines;
	}

	function drawCover(ctx, img, x, y, w, h) {
		var scale = Math.max(w / img.width, h / img.height);
		var cw = w / scale;
		var ch = h / scale;
		ctx.drawImage(img, (img.width - cw) / 2, (img.height - ch) / 2, cw, ch, x, y, w, h);
	}

	function roundRectPath(ctx, x, y, w, h, r) {
		r = Math.min(r, w / 2, h / 2);
		ctx.beginPath();
		if (ctx.roundRect) {
			ctx.roundRect(x, y, w, h, r);
			return;
		}
		ctx.moveTo(x + r, y);
		ctx.arcTo(x + w, y, x + w, y + h, r);
		ctx.arcTo(x + w, y + h, x, y + h, r);
		ctx.arcTo(x, y + h, x, y, r);
		ctx.arcTo(x, y, x + w, y, r);
		ctx.closePath();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	function ensureFont(font) {
		if (!document.fonts || !document.fonts.load) return Promise.resolve();
		return Promise.all([
			document.fonts.load('700 54px ' + font),
			document.fonts.load('400 26px ' + font)
		]).then(function () {
			return document.fonts.ready;
		}).catch(function () {});
	}

	function loadImage(url) {
		if (!url) return Promise.resolve(null);
		return new Promise(function (resolve) {
			var img = new Image();
			// Needed so the canvas stays untainted and toBlob() works; harmless
			// for same-origin uploads.
			img.crossOrigin = 'anonymous';
			img.onload = function () { resolve(img); };
			img.onerror = function () { resolve(null); }; // Degrade, don't fail.
			img.src = url;
		});
	}

	function showResult(result, canvas, filename) {
		if (!result) return;

		canvas.toBlob(function (blob) {
			if (!blob) return;
			var url = URL.createObjectURL(blob);

			result.hidden = false;
			result.innerHTML = '';

			var preview = new Image();
			preview.src = url;
			preview.alt = 'Photocard';
			result.appendChild(preview);

			var actions = document.createElement('div');
			actions.className = 'khobor-photocard-actions';

			var dl = document.createElement('a');
			dl.className = 'khobor-btn khobor-btn--primary';
			dl.href = url;
			dl.download = filename || 'photocard.png';
			dl.textContent = 'Download';
			actions.appendChild(dl);

			var open = document.createElement('a');
			open.className = 'khobor-btn khobor-btn--ghost';
			open.href = url;
			open.target = '_blank';
			open.rel = 'noopener';
			open.textContent = 'Open';
			actions.appendChild(open);

			result.appendChild(actions);
		}, 'image/png');
	}
})();
