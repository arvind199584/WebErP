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
            depth = len(rel_dir.split(os.sep))
            back_to_root = '/'.join(['..'] * depth)
            
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
                
            # Regex to catch any require/require_once referencing /core/
            new_content = re.sub(
                r"(require(?:_once)?\s+__DIR__\s*\.\s*['\"])(?:[^'\"]*?/)*core/",
                rf"\1/{back_to_root}/core/",
                content
            )
            # Clean up double slashes if generated (e.g. '//')
            new_content = re.sub(r"['\"]//+", "'/", new_content)
            
            if new_content != content:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                count += 1

print(f"Successfully updated relative core includes in {count} PHP files.")
