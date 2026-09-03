import openpyxl
from collections import Counter

wb = openpyxl.load_workbook(r'd:\myapps\tools\myapps-live.xlsx', read_only=True, data_only=True)
ws = wb['Users']
rows = list(ws.iter_rows(values_only=True))
header = [str(h or '').strip().lower() for h in rows[0]]
idx = {h: i for i, h in enumerate(header)}
c = Counter()
admins = []
for row in rows[1:]:
    row = list(row) + [None] * max(0, len(header) - len(row))
    role = str(row[idx['role']] or '').strip().lower()
    c[role or '(kosong)'] += 1
    if role in ('admin', 'super_admin'):
        admins.append({
            'id_user': row[idx['id_user']],
            'no_staf': row[idx['no_staf']],
            'nama': row[idx['nama']],
            'emel': row[idx['emel']],
            'role': role,
            'aktif': row[idx['aktif']],
            'id_status_staf': row[idx['id_status_staf']],
            'last_login': row[idx['last_login']],
        })

print('ROLE_COUNTS', dict(c))
print('---')
for a in admins:
    print(
        a['role'],
        '| aktif=', a['aktif'],
        '| status=', a['id_status_staf'],
        '|', a['nama'],
        '|', a['emel'],
        '| id=', a['id_user'],
        '| no_staf=', a['no_staf'],
        '| last_login=', a['last_login'],
    )
print('TOTAL_ADMIN', len(admins))
