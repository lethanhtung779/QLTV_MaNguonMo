"""Optional acceptance check: pip install pymupdf, then run after browser_sprint.cjs."""
import sys
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]/'storage/pdf-qa-deps'))
import pymupdf
folder=Path(__file__).resolve().parents[1]/'storage/receipts-qa'
expected={'loan':'Phiếu mượn sách','return':'Biên nhận trả sách','payment':'Biên nhận thu phí','reader-loan':'Phiếu mượn sách','reader-return':'Biên nhận trả sách','reader-payment':'Biên nhận thu phí'}
checks=0
for name,label in expected.items():
    doc=pymupdf.open(folder/(name+'.pdf'))
    text=''.join(page.get_text() for page in doc)
    assert label in text,(name,'Vietnamese heading missing')
    assert '\ufffd' not in text,(name,'invalid characters')
    checks+=2
    if name not in ['payment','reader-payment']:
        assert 'Độc giả Nguyễn Thị Hồng' in text,(name,'Vietnamese reader name missing')
        assert 'Đường đến thư viện' in text,(name,'Vietnamese book title missing')
        checks+=2
    doc[0].get_pixmap(matrix=pymupdf.Matrix(1.5,1.5)).save(folder/(name+'.png'))
    print('PASS: embedded Vietnamese PDF text and raster render: '+name)
print('PDF checks: '+str(checks)+' passed.')
