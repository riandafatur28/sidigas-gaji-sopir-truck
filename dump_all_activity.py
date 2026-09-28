import xml.etree.ElementTree as ET
path = r"D:\project\SKRIPSI\distribusi-gaji-baru\arsitektur-sistem-SIDIGASS (1).drawio"
tree = ET.parse(path)
root = tree.getroot()
for diagram in root.findall('diagram'):
    if diagram.get('name') != 'ACTIVITY DIAGRAM':
        continue
    model = diagram.find('mxGraphModel')
    r = model.find('root')
    print("=== All vertices in ACTIVITY DIAGRAM ===")
    for cell in r.findall('mxCell'):
        if cell.get('vertex')=='1':
            val = (cell.get('value') or '').strip()[:60]
            parent = cell.get('parent')
            geom = cell.find('mxGeometry')
            x = geom.get('x') if geom is not None else '?'
            y = geom.get('y') if geom is not None else '?'
            print(f"parent={parent[:20] if parent else ''} id={cell.get('id')[-8:]} x={x} y={y} val={val!r}")
