import json

with open('modules/Utility/Python/training_data.json', 'r') as f:
    data = json.load(f)

new_examples = [
    "create a new machine", "add machine", "new machine entry", "insert machine",
    "batao naya machine", "naya machine dalo", "create machine named Tractor",
    "add machine brand Sonalika", "new machine with fuel diesel",
    "create a machine called Mower", "register new equipment", "add new machinery",
    "create record for machine", "insert new tractor", "add truck to inventory",
    "create machine entry for generator", "new machine registration"
]

for ex in new_examples:
    data.append({"text": ex, "intent": "create_machine"})

with open('modules/Utility/Python/training_data.json', 'w') as f:
    json.dump(data, f, indent=4)

print(f"Added {len(new_examples)} examples for create_machine.")
