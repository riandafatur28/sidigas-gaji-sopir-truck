import xml.etree.ElementTree as ET

path = r"D:\project\SKRIPSI\distribusi-gaji-baru\arsitektur-sistem-SIDIGASS (1).drawio"
tree = ET.parse(path)
root = tree.getroot()

for diagram in root.findall('diagram'):
    if diagram.get('name') != 'ACTIVITY DIAGRAM':
        continue
    model = diagram.find('mxGraphModel')
    r = model.find('root')
    # Find all vertices that are direct children of the penggajian swimlane
    # The swimlane id is 8AGBAH3dBqCAF0QWSdv1 (from earlier)
    swimlane_id = "8AGBAH3dBqCAF0QWSdv1"
    # Also check for other swimlanes that might contain gaji nodes
    # Collect all cells with parent == swimlane_id and vertex=1
    nodes = []
    for cell in r.findall('mxCell'):
        if cell.get('parent') == swimlane_id and cell.get('vertex') == '1':
            val = cell.get('value') or ''
            geom = cell.find('mxGeometry')
            if geom is not None:
                y = float(geom.get('y') or 0)
                nodes.append((y, cell))
    nodes.sort(key=lambda x: x[0])
    print(f"Found {len(nodes)} nodes in penggajian swimlane")
    for y, cell in nodes:
        print(f"  y={y} val={cell.get('value')!r} id={cell.get('id')[-8:]}")
    
    # Now re-layout: start y=120, spacing 80, x centered at 80
    start_y = 120
    spacing = 80
    # Swimlane width is 320? Let's check swimlane geometry
    for cell in r.findall('mxCell'):
        if cell.get('id') == swimlane_id:
            geom = cell.find('mxGeometry')
            if geom is not None:
                w = float(geom.get('width') or 320)
                print(f"Swimlane {swimlane_id} w={w} h={geom.get('height')}")
                break
    
    # Reassign positions
    for idx, (old_y, cell) in enumerate(nodes):
        geom = cell.find('mxGeometry')
        # Keep x centered: swimlane x is maybe 0, width 300, node width ~180, so x ~60
        # Use x=70 for all to align vertically
        new_x = 70
        new_y = start_y + idx * spacing
        # Keep width/height as is
        geom.set('x', str(new_x))
        geom.set('y', str(new_y))
        print(f"  -> new y={new_y} for {cell.get('value')!r}")

    # Also need to fix edges to be orthogonal and not messy
    # For now, edges will auto-route with orthogonal style, so just keep them
    # But we should ensure edges have correct entry/exit points (center)
    # We can leave edges as is, they will be rerouted by diagrams.net

tree.write(path, encoding='utf-8', xml_declaration=True)
print("Done tidy")
