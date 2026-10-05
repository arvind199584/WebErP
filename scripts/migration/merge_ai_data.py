import json
import os

def merge_json_files(target_file, source_files, is_list=True):
    if is_list:
        merged_data = []
        # Load existing target data
        if os.path.exists(target_file):
            with open(target_file, 'r') as f:
                merged_data = json.load(f)
        
        # Merge sources
        for src in source_files:
            if os.path.exists(src):
                with open(src, 'r') as f:
                    data = json.load(f)
                    # Simple append, maybe check for duplicates by 'text'
                    existing_texts = {item['text'].lower() for item in merged_data}
                    for item in data:
                        if item['text'].lower() not in existing_texts:
                            merged_data.append(item)
                            existing_texts.add(item['text'].lower())
        
        with open(target_file, 'w') as f:
            json.dump(merged_data, f, indent=4)
    else:
        merged_data = {}
        # Load existing target data
        if os.path.exists(target_file):
            with open(target_file, 'r') as f:
                merged_data = json.load(f)
        
        # Merge sources
        for src in source_files:
            if os.path.exists(src):
                with open(src, 'r') as f:
                    data = json.load(f)
                    merged_data.update(data)
        
        with open(target_file, 'w') as f:
            json.dump(merged_data, f, indent=4)

if __name__ == "__main__":
    base_path = "modules/Utility/Python"
    
    # Merge Training Data
    merge_json_files(
        os.path.join(base_path, "training_data.json"),
        [
            os.path.join(base_path, "turf_ai_training.json"),
            os.path.join(base_path, "report_training_data.json")
        ],
        is_list=True
    )
    
    # Merge Intent Maps
    merge_json_files(
        os.path.join(base_path, "intent_map.json"),
        [
            os.path.join(base_path, "turf_intent_map.json"),
            os.path.join(base_path, "report_intent_map.json")
        ],
        is_list=False
    )
    
    print("AI Modules Unified: Training data and Intent maps merged.")
