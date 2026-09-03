from pathlib import Path
p = Path(r'd:\myapps\Index.html')
h = p.read_text(encoding='utf-8')
h2 = h.replace(
    'chart.umd.min.js" defer></script>',
    'chart.umd.min.js"></script>'
)
p.write_text(h2, encoding='utf-8')
print('changed' if h != h2 else 'unchanged')
