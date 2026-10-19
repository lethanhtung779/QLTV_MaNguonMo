import os
import glob
from PIL import Image

def scan_all():
    base_dir = "storage/doi_soat/anh_sach"
    categories = ["khoa_hoc", "tin_hoc", "kinh_te", "thieu_nhi", "van_hoc"]
    
    total = 0
    for cat in categories:
        cat_dir = os.path.join(base_dir, cat)
        files = sorted(os.listdir(cat_dir))
        print(f"\n=================== CATEGORY: {cat} ({len(files)} files) ===================")
        for fname in files:
            fpath = os.path.join(cat_dir, fname)
            fsize = os.path.getsize(fpath)
            ext = os.path.splitext(fname)[1].lower()
            if ext in ['.jpg', '.jpeg', '.png', '.webp']:
                with Image.open(fpath) as im:
                    print(f"[{cat}] {fname} | {im.format} {im.size} | {fsize} bytes")
            else:
                print(f"[{cat}] {fname} | HTML/NON-IMAGE | {fsize} bytes")
            total += 1
    print(f"\nTOTAL FILES: {total}")

if __name__ == "__main__":
    scan_all()
