"""Independent offline scanner verification.

The Python packages are optional and CairoSVG/pyzbar also need native libraries.
Missing scanner dependencies fail by default so a green test run always means
that decoding really happened. Local smoke runs may opt into ``--allow-skip``.

Usage: python tests/verify_barcode_decoding.py [--allow-skip]
"""
import argparse
import pathlib
import subprocess
import sys
import tempfile


def load_scanner_dependencies():
    """Load optional modules and preserve the useful native-library error."""
    try:
        import cairosvg
        from PIL import Image
        from pyzbar.pyzbar import decode
    except (ImportError, OSError) as error:
        return None, error
    return (cairosvg, Image, decode), None


parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument(
    "--allow-skip",
    action="store_true",
    help="return success when the optional Cairo/ZBar stack is unavailable",
)
args = parser.parse_args()

dependencies, dependency_error = load_scanner_dependencies()
if dependencies is None:
    message = (
        "SKIP independent barcode decoding: optional CairoSVG/ZBar stack is "
        f"unavailable ({dependency_error}). Install requirements-test.txt and "
        "the native Cairo and ZBar libraries."
    )
    print(message, file=sys.stderr)
    raise SystemExit(0 if args.allow_skip else 1)

cairosvg, Image, decode = dependencies

ROOT = pathlib.Path(__file__).resolve().parents[1]
SAMPLES = (
    "B810-0001",                    # QR Version 1
    "COPY-2026-X1-LONG",            # QR Version 2
    "COPY-2026-LONG-CODE-000000001",# QR Version 3
    "COPY-2026-LONG-CODE-000000000000000000000000000001",  # QR Version 4
)
with tempfile.TemporaryDirectory() as temporary:
    for code in SAMPLES:
        for fmt, method in (("QRCODE", "renderQrSvg"), ("CODE128", "renderBarcodeSvg")):
            script = ("require 'app/Services/BarcodeService.php'; "
                      "echo (new Library\\Services\\BarcodeService())->" + method + "(" + repr(code) + ");")
            result = subprocess.run(["php", "-r", script], cwd=ROOT, capture_output=True, check=True)
            picture = pathlib.Path(temporary) / (fmt + "_" + code + ".png")
            cairosvg.svg2png(bytestring=result.stdout, write_to=str(picture), scale=3)
            decoded = [(symbol.type, symbol.data.decode("utf-8")) for symbol in decode(Image.open(picture))]
            if (fmt, code) not in decoded:
                raise AssertionError(f"{fmt} decode mismatch for {code}: {decoded}")
            print(f"PASS {fmt}: {code}")
print(f"All {len(SAMPLES) * 2} independent decode checks PASS")
