import re
path = r"D:\project\SKRIPSI\distribusi-gaji-baru\arsitektur-sistem-SIDIGASS (1).drawio"
with open(path, encoding='utf-8') as f:
    c = f.read()
diagrams = re.findall(r'<diagram[^>]*name="([^"]*)"', c)
print(diagrams)
for name in diagrams:
    print(f"- {name}")
