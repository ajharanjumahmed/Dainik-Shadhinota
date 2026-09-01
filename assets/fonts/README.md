# Fonts Directory

The theme loads body and heading fonts (Hind Siliguri, Noto Sans Bengali)
from Google Fonts at runtime, so nothing has to be placed here for the
**front end** to work.

## Server-side photocard generator (optional but recommended)

The photocard generator runs server-side with PHP + Imagick. To render
Bangla text on the generated photocards, Imagick needs a Bengali TTF on
disk. The PHP code looks for the following files (first match wins):

```
assets/fonts/NotoSansBengali-Bold.ttf      ← drop here for best results
assets/fonts/HindSiliguri-Bold.ttf
assets/fonts/SolaimanLipi.ttf
/usr/share/fonts/truetype/noto/NotoSansBengali-Bold.ttf   (Linux system)
/usr/share/fonts/truetype/lohit-bengali/Lohit-Bengali.ttf (Linux system)
```

### How to get Noto Sans Bengali

Free under the SIL Open Font License (commercial use allowed):

1. Visit https://fonts.google.com/noto/specimen/Noto+Sans+Bengali
2. Click **Download family** → unzip
3. Copy `NotoSansBengali-Bold.ttf` into this `assets/fonts/` directory

If the theme can't find a Bengali font, photocards will still generate,
but Bangla text may render as boxes/tofu.

## License notes for resellers

If you intend to distribute the theme as part of a paid bundle, **do not**
include font files in the zip without including the corresponding license.
Either:

- Ship the theme **without** fonts and instruct end users to download Noto
  Sans Bengali themselves (recommended — keeps the zip small and avoids
  licensing footguns), or
- Bundle Noto Sans Bengali **and** include `LICENSE` and `OFL.txt` from
  the Google Fonts download alongside it.
