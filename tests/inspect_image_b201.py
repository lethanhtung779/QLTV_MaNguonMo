import os
from PIL import Image

path = "storage/doi_soat/anh_sach/tin_hoc/8935095633340.jpg"
im = Image.open(path)
print("Format:", im.format, "Size:", im.size, "Mode:", im.mode)
print("Filesize:", os.path.getsize(path))
