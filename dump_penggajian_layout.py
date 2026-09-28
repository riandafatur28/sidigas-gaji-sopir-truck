import xml.etree.ElementTree as ET
path = r"D:\project\SKRIPSI\distribusi-gaji-baru\arsitektur-sistem-SIDIGASS (1).drawio"
tree = ET.parse(path)
root = tree.getroot()
for diagram in root.findall('diagram'):
    if diagram.get('name') != 'ACTIVITY DIAGRAM':
        continue
    model = diagram.find('mxGraphModel')
    r = model.find('root')
    # Find the penggajian container
    # Look for parent 8AGBAH3dBqCAF0QWSdv1
    print("=== Penggajian Activity Nodes (parent 8AGBAH...) ===")
    nodes = []
    for cell in r.findall('mxCell'):
        if cell.get('parent') == '8AGBAH3dBqCAF0QWSdv1' and cell.get('vertex') == '1':
            val = cell.get('value') or ''
            geom = cell.find('mxGeometry')
            x = int(float(geom.get('x') or 0))
            y = int(float(geom.get('y') or 0))
            w = geom.get('width')
            h = geom.get('height')
            nodes.append((y, val, cell.get('id'), x, w, h))
    nodes.sort()
    for y,val,cid,x,w,h in nodes:
        print(f"y={y:4} x={x:3} id={cid[-8:]} val={val!r}")
    print("\n=== Edges ===")
    for cell in r.findall('mxCell'):
        if cell.get('edge')=='1' and cell.get('parent')=='8AGBAH3dBqCAF0QWSdv1':
            print(f"edge {cell.get('id')[-8:]}: {cell.get('source')[-8:]} -> {cell.get('target')[-8:]} value={cell.get('value')!r}")
