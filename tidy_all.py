import xml.etree.ElementTree as ET
path = r"D:\project\SKRIPSI\distribusi-gaji-baru\arsitektur-sistem-SIDIGASS (1).drawio"
tree = ET.parse(path)
root = tree.getroot()
for diagram in root.findall('diagram'):
    if diagram.get('name') != 'ACTIVITY DIAGRAM':
        continue
    model = diagram.find('mxGraphModel')
    r = model.find('root')
    # Find all swimlanes (cells with no value but with geometry width > 300)
    # For now, just tidy all vertices globally
    vertices = []
    for cell in r.findall('mxCell'):
        if cell.get('vertex') == '1':
            val = cell.get('value') or ''
            # Keep swimlane containers (they have no value or are swimlanes)
            # We want to tidy only the inner nodes, not the swimlane containers themselves
            # Swimlanes have style containing 'swimlane'
            style = cell.get('style') or ''
            if 'swimlane' in style:
                continue
            geom = cell.find('mxGeometry')
            if geom is None:
                continue
            y = float(geom.get('y') or 0)
            x = float(geom.get('x') or 0)
            vertices.append((y, x, cell))
    # Group by parent (swimlane)
    from collections import defaultdict
    by_parent = defaultdict(list)
    for y,x,cell in vertices:
        by_parent[cell.get('parent')].append((y, cell))
    for parent, lst in by_parent.items():
        lst.sort(key=lambda t: t[0])
        print(f"Parent {parent[:20]} has {len(lst)} nodes")
        # Find swimlane width to center
        swim_w = 320
        for cell in r.findall('mxCell'):
            if cell.get('id') == parent:
                g = cell.find('mxGeometry')
                if g is not None and g.get('width'):
                    swim_w = float(g.get('width'))
                break
        start_y = 80
        spacing = 70
        for idx, (old_y, cell) in enumerate(lst):
            geom = cell.find('mxGeometry')
            # Keep original width/height
            w = float(geom.get('width') or 180)
            # Center x in swimlane (swimlane x is maybe 0, width 320, so x = (swim_w - w)/2
            new_x = (swim_w - w)/2
            # But swimlane's own x offset matters - keep relative
            # For simplicity, set x to 60-80
            new_x = 60
            if w < 150:
                new_x = 80
            new_y = start_y + idx * spacing
            geom.set('x', str(new_x))
            geom.set('y', str(new_y))
            # print
            # print(f"  {cell.get('value')[:30]!r} -> y {new_y}")

tree.write(path, encoding='utf-8', xml_declaration=True)
print("tidy done")
