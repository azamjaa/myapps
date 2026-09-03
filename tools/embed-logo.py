from pathlib import Path
import re

uri = Path(r'd:\myapps\server\myapps\assets\logo-data-uri.txt').read_text().strip()

idx = Path(r'd:\myapps\Index.html')
html = idx.read_text(encoding='utf-8')
html2 = re.sub(
    r'src="https://www\.keda\.gov\.my/myapps/assets/logo-keda\.png"',
    f'src="{uri}"',
    html,
)
html2 = re.sub(
    r'src="https://www\.keda\.gov\.my/wp-content/uploads/2023/12/HEADER-dummy-transparent\.png"',
    f'src="{uri}"',
    html2,
)
if uri not in html2:
    raise SystemExit('GAS Index: logo URI not injected')
idx.write_text(html2, encoding='utf-8')
print('Index.html updated', idx.stat().st_size)

srv = Path(r'd:\myapps\server\myapps\index.html')
shtml = srv.read_text(encoding='utf-8')
shtml2 = shtml.replace('src="assets/logo-keda.png"', f'src="{uri}"')
shtml2 = shtml2.replace('href="assets/logo-keda.png"', f'href="{uri}"')
if uri not in shtml2:
    raise SystemExit('server index: logo URI not injected')
srv.write_text(shtml2, encoding='utf-8')
print('server index.html updated', srv.stat().st_size)

Path(r'd:\myapps\server\myapps\UPLOAD-index.html').write_text(shtml2, encoding='utf-8')
print('UPLOAD-index.html ready')
