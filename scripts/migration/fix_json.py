import json
import re

file_path = 'modules/Utility/Python/training_data.json'
with open(file_path, 'r') as f:
    content = f.read()

# Try to find all JSON lists and merge them, or just fix the multiple ']' and '['
# Actually, I've appended `][` or similar.
# Let's just find all objects and put them in one list.
matches = re.findall(r'\{[^{}]+\}', content)
all_items = []
for m in matches:
    try:
        item = json.loads(m)
        all_items.append(item)
    except:
        pass

with open(file_path, 'w') as f:
    json.dump(all_items, f, indent=4)

print(f"Fixed training_data.json: {len(all_items)} items recovered.")
