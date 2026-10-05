import os
import re

root_dir = r'F:\Research\ModPyPhp'
modules_dir = os.path.join(root_dir, 'modules')

count = 0
for dirpath, _, filenames in os.walk(modules_dir):
    for filename in filenames:
        if filename.endswith('.php'):
            filepath = os.path.join(dirpath, filename)
            rel_dir = os.path.relpath(dirpath, root_dir)
            
            parts = rel_dir.split(os.sep)
            if len(parts) >= 2 and parts[0].lower() == 'modules':
                parts[0] = 'Modules'  # Ensure proper TitleCase 'Modules'
                ns_parts = ['App'] + parts
                new_ns = '\\'.join(ns_parts)
                
                with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()
                    
                new_content = re.sub(
                    r"namespace\s+App\\[Mm]odules\\[^;]+;",
                    lambda m: f"namespace {new_ns};",
                    content
                )
                
                if new_content != content:
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(new_content)
                    count += 1

print(f"Successfully updated namespaces in {count} PHP files.")
