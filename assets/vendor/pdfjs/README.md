# PDF.js (Mozilla, Apache-2.0)

This theme renders e-paper PDFs in the browser using Mozilla's PDF.js library.
**The library files are not bundled with the theme** so that the theme stays
small and the admin can pick whichever PDF.js version they want.

## What to drop into this folder

Download the **legacy build** of PDF.js (works in all browsers, including
mobile Safari) from the official release page:

```
https://github.com/mozilla/pdf.js/releases
```

Pick a release such as `v3.11.174` (tested with the theme) and download
`pdfjs-3.11.174-legacy-dist.zip`. From the zip, copy these two files into
this directory:

```
build/pdf.min.js              →  assets/vendor/pdfjs/pdf.min.js
build/pdf.worker.min.js       →  assets/vendor/pdfjs/pdf.worker.min.js
```

That's it. The theme will pick them up the next time you load an e-paper page.

## License

PDF.js is licensed under Apache License 2.0. The license file is included in the
release zip; keep a copy alongside the binary if your distribution requires it.

Project: https://github.com/mozilla/pdf.js
