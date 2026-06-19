# Test fonts

These `*.json` files are the standard PDF "core 14" font definitions consumed by
the `tecnickcom/tc-lib-pdf` library (which ships no fonts of its own). They are
used only by `TcLibPdfEngineTest` via the `K_PATH_FONTS` constant so the engine
can render real PDFs during CI.

## Provenance

The metric data was generated from the URW++ "base35" Type 1 fonts shipped with
most Linux distributions (`fonts-urw-base35`), which are metric-compatible
replacements for the standard PDF core fonts:

| tc-lib font | source AFM            |
|-------------|-----------------------|
| helvetica*  | NimbusSans-*          |
| times*      | NimbusRoman-*         |
| courier*    | NimbusMonoPS-*        |
| symbol      | StandardSymbolsPS     |
| zapfdingbats| D050000L              |

The core 14 fonts are referenced by name and are **not embedded** in generated
PDFs; these files carry only font metrics and encoding tables, no glyph outlines.

The URW++ base35 fonts are distributed under the AGPLv3 with a font exception.
See <https://github.com/ArtifexSoftware/urw-base35-fonts>.
