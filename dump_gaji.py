import xml.etree.ElementTree as ET
path = r"D:\project\SKRIPSI\distribusi-gaji-baru\arsitektur-sistem-SIDIGASS (1).drawio"
tree = ET.parse(path)
root = tree.getroot()
for diagram in root.findall('diagram'):
    if diagram.get('name') != 'ACTIVITY DIAGRAM':
        continue
    model = diagram.find('mxGraphModel')
    r = model.find('root')
    print("=== ACTIVITY DIAGRAM CELLS ===")
    for cell in r.findall('mxCell'):
        val = (cell.get('value') or '').strip()
        if any(k in val for k in ['Gaji','gaji','Hitung','hitung','Slip','Laporan','periode','Periode']):
            print(f"id={cell.get('id')[:30]} parent={str(cell.get('parent'))[:20]} value={val!r} style={cell.get('style','')[:60]}")
            geom = cell.find('mxGeometry')
            if geom is not None:
                print(f"  geom x={geom.get('x')} y={geom.get('y')} w={geom.get('width')} h={geom.get('height')}")
