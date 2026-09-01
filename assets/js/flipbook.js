/* Khobor — E-paper flipbook.
   Loads PDF with PDF.js, renders each page to a canvas,
   and feeds the canvas DOM elements into StPageFlip.

   Sharpness: a canvas has two sizes — its CSS box and its bitmap ("backing
   store"). CSS only ever stretches the bitmap, so zooming a canvas rasterised
   at display size just magnifies blurry pixels. Pages are therefore rasterised
   at devicePixelRatio to begin with, and the pages you are actually looking at
   are re-rasterised from the PDF at the current zoom level, which produces
   genuinely new pixels instead of bigger ones. */

(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', init);

	// Page aspect ratio. Drives how tall the book renders, since StPageFlip's
	// 'stretch' sizing derives height from the container width and this ratio.
	var PAGE_RATIO = 1.45;

	// Zoom.
	var ZOOM_MIN  = 1; // The book is already sized to fit its box.
	var ZOOM_MAX  = 3;
	var ZOOM_STEP = 0.25;

	// Rasterising a whole newspaper at max zoom would run to hundreds of MB, so
	// only the visible spread is upgraded, and every canvas is area-capped.
	// Safari refuses canvases over ~16.7M pixels outright, and each canvas costs
	// roughly 4 bytes per pixel. 8MP ≈ 31MB per page — enough for a pin-sharp
	// 300% zoom on a standard-density display, and ~62MB for a visible spread.
	var MAX_CANVAS_AREA = 8e6;
	var RERENDER_DELAY  = 180; // ms; collapses a burst of zoom clicks into one pass.

	function init() {
		var root = document.getElementById('khobor-flipbook');
		if (!root) return;
		if (!window.pdfjsLib || !window.St) {
			// Vendor libs missing. Show a clean message and bail.
			root.innerHTML = '<p>Flipbook libraries failed to load. Make sure assets/vendor/pdfjs and assets/vendor/stpageflip are populated.</p>';
			return;
		}
		var pdfUrl = root.getAttribute('data-pdf');
		if (!pdfUrl) return;

		// Worker.
		if (window.KhoborFlipbook && window.KhoborFlipbook.workerSrc) {
			pdfjsLib.GlobalWorkerOptions.workerSrc = window.KhoborFlipbook.workerSrc;
		}

		var width  = Math.min(root.clientWidth || 800, 800);
		var height = Math.round(width * PAGE_RATIO);

		// Match the screen's real pixel density at 100%, capped at 2 so a 3x
		// phone doesn't rasterise nine times the pixels for the initial load.
		var baseQuality = Math.min(window.devicePixelRatio || 1, 2);

		pdfjsLib.getDocument(pdfUrl).promise.then(function (pdf) {
			var tasks = [];
			for (var i = 1; i <= pdf.numPages; i++) {
				tasks.push(renderPage(pdf, i, width, baseQuality));
			}

			Promise.all(tasks).then(function (canvases) {
				var pages    = [];
				var quality  = [];  // Current raster quality per page index.
				var reqToken = [];  // Guards against out-of-order async swaps.
				var zoom     = 1;   // Declared up here: refreshSharpness() reads it.

				root.innerHTML = '';
				canvases.forEach(function (c, i) {
					var wrap = document.createElement('div');
					wrap.className = 'khobor-flipbook__page';
					wrap.appendChild(c);
					root.appendChild(wrap);
					pages.push(wrap);
					quality[i]  = baseQuality;
					reqToken[i] = 0;
				});

				var pageFlip = new St.PageFlip(root, {
					width: width,
					height: height,
					size: 'stretch',
					minWidth:  315,  maxWidth:  1200,
					minHeight: 420,  maxHeight: 1536,
					showCover: true,
					mobileScrollSupport: true
				});
				pageFlip.loadFromHTML(root.querySelectorAll('.khobor-flipbook__page'));

				// Wire controls.
				var info = document.getElementById('khobor-flipbook-pageinfo');
				function updateInfo() {
					if (!info) return;
					var cur = pageFlip.getCurrentPageIndex() + 1;
					var tot = pdf.numPages;
					info.textContent = cur + ' / ' + tot;
				}
				updateInfo();

				// -------------------------------------------------------------
				// Re-rasterise for sharpness
				// -------------------------------------------------------------

				/**
				 * Indices on screen right now: the current page and its facing
				 * page in the spread.
				 */
				function visiblePages() {
					var idx = pageFlip.getCurrentPageIndex();
					return [idx, idx + 1].filter(function (i) {
						return i >= 0 && i < pages.length;
					});
				}

				function swapCanvas(i, canvas) {
					var wrap = pages[i];
					if (!wrap) return;
					if (wrap.firstChild) wrap.replaceChild(canvas, wrap.firstChild);
					else wrap.appendChild(canvas);
				}

				function setQuality(i, target) {
					// Within a hair of the current raster? Nothing to gain.
					if (Math.abs(quality[i] - target) < 0.05) return;

					quality[i] = target;
					var token = ++reqToken[i];

					renderPage(pdf, i + 1, width, target).then(function (canvas) {
						// A newer request for this page superseded us mid-render.
						if (token !== reqToken[i]) return;
						swapCanvas(i, canvas);
					}).catch(function (err) {
						console.error('Re-render failed for page ' + (i + 1), err);
					});
				}

				var refreshTimer = null;
				function refreshSharpness() {
					clearTimeout(refreshTimer);
					refreshTimer = setTimeout(function () {
						var visible = visiblePages();
						var target  = baseQuality * zoom;

						visible.forEach(function (i) { setQuality(i, target); });

						// Drop pages that scrolled out of the spread back to base
						// quality so memory doesn't grow with every page turned.
						for (var i = 0; i < pages.length; i++) {
							if (visible.indexOf(i) === -1 && quality[i] > baseQuality) {
								setQuality(i, baseQuality);
							}
						}
					}, RERENDER_DELAY);
				}

				pageFlip.on('flip', function () {
					updateInfo();
					if (zoom !== 1) refreshSharpness();
				});

				// -------------------------------------------------------------
				// Zoom
				//
				// StPageFlip reads pointer positions in screen pixels
				// (clientX - getBoundingClientRect().left) but measures its own
				// page geometry in unscaled layout pixels (distElement.offsetWidth).
				// A CSS scale desyncs the two, so the corner hit-zones no longer
				// match where the pages visually are and drag-to-flip dies.
				//
				// While zoomed we therefore switch off pointer interaction on the
				// book: the viewport scrolls to pan, and the ◀ ▶ buttons still turn
				// pages because they call the API directly instead of going through
				// hit detection.
				// -------------------------------------------------------------
				var viewport  = document.getElementById('khobor-flipbook-viewport');
				var zoomLabel = document.getElementById('khobor-flipbook-zoomlevel');

				function applyZoom(next) {
					var previous = zoom;
					zoom = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, Math.round(next * 100) / 100));

					root.style.transform = (zoom === 1) ? '' : 'scale(' + zoom + ')';
					root.classList.toggle('is-zoomed', zoom !== 1);

					if (zoomLabel) zoomLabel.textContent = Math.round(zoom * 100) + '%';

					if (viewport && zoom === 1) {
						viewport.scrollLeft = 0;
						viewport.scrollTop  = 0;
					}

					if (zoom !== previous) refreshSharpness();
				}

				document.querySelectorAll('[data-flip]').forEach(function (btn) {
					btn.addEventListener('click', function () {
						var action = btn.getAttribute('data-flip');
						if (action === 'prev') pageFlip.flipPrev();
						else if (action === 'next') pageFlip.flipNext();
						else if (action === 'zoom-in') applyZoom(zoom + ZOOM_STEP);
						else if (action === 'zoom-out') applyZoom(zoom - ZOOM_STEP);
						else if (action === 'zoom-reset') applyZoom(1);
					});
				});

				applyZoom(1);

				// Drag-to-pan while zoomed. Mouse only: touch devices already pan
				// by scrolling the viewport natively, and hijacking that would
				// break pinch/scroll.
				if (viewport) {
					var pan = null;

					viewport.addEventListener('pointerdown', function (e) {
						if (zoom === 1 || e.pointerType !== 'mouse' || e.button !== 0) return;
						pan = {
							id: e.pointerId,
							x: e.clientX, y: e.clientY,
							left: viewport.scrollLeft, top: viewport.scrollTop
						};
						viewport.setPointerCapture(e.pointerId);
						viewport.classList.add('is-panning');
						e.preventDefault();
					});

					viewport.addEventListener('pointermove', function (e) {
						if (!pan || e.pointerId !== pan.id) return;
						viewport.scrollLeft = pan.left - (e.clientX - pan.x);
						viewport.scrollTop  = pan.top  - (e.clientY - pan.y);
					});

					['pointerup', 'pointercancel'].forEach(function (evt) {
						viewport.addEventListener(evt, function (e) {
							if (!pan || e.pointerId !== pan.id) return;
							try { viewport.releasePointerCapture(pan.id); } catch (err) {}
							pan = null;
							viewport.classList.remove('is-panning');
						});
					});
				}

				// Keyboard nav.
				document.addEventListener('keydown', function (e) {
					if (e.key === 'ArrowLeft') pageFlip.flipPrev();
					else if (e.key === 'ArrowRight') pageFlip.flipNext();
				});
			});
		}, function (err) {
			console.error('PDF load failed', err);
			root.innerHTML = '<p>Could not load PDF.</p>';
		});
	}

	/**
	 * Rasterise one PDF page.
	 *
	 * @param {Object} pdf      PDF.js document.
	 * @param {number} num      1-based page number.
	 * @param {number} cssWidth Display width of a page, in CSS pixels.
	 * @param {number} quality  Bitmap pixels per CSS pixel. 1 = display size,
	 *                          2 = double resolution, and so on.
	 * @return {Promise<HTMLCanvasElement>}
	 */
	function renderPage(pdf, num, cssWidth, quality) {
		return pdf.getPage(num).then(function (page) {
			var natural = page.getViewport({ scale: 1 });
			var scale   = (cssWidth / natural.width) * quality;
			var scaled  = page.getViewport({ scale: scale });

			// Stay under the browser's canvas ceiling; overshooting it yields a
			// blank canvas rather than an error.
			var area = scaled.width * scaled.height;
			if (area > MAX_CANVAS_AREA) {
				scale *= Math.sqrt(MAX_CANVAS_AREA / area);
				scaled = page.getViewport({ scale: scale });
			}

			var canvas    = document.createElement('canvas');
			canvas.width  = Math.round(scaled.width);
			canvas.height = Math.round(scaled.height);

			// The CSS box stays at layout size (main.css sets width/height 100%),
			// so a bigger bitmap buys detail rather than a bigger picture.
			var ctx = canvas.getContext('2d', { alpha: false });

			// An opaque canvas starts black, and PDF.js only paints the page's
			// own marks — anything it leaves untouched needs to be paper white.
			ctx.fillStyle = '#fff';
			ctx.fillRect(0, 0, canvas.width, canvas.height);

			return page.render({ canvasContext: ctx, viewport: scaled }).promise
				.then(function () { return canvas; });
		});
	}
})();
