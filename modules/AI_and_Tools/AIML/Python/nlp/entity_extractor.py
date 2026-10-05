import re
from datetime import datetime
from fuzzywuzzy import process

KNOWN_OFFICES = []

def set_known_offices(offices):
    global KNOWN_OFFICES
    KNOWN_OFFICES = offices

def resolve_office_entity(user_input):
    if not user_input or not KNOWN_OFFICES:
        return user_input
    
    # Try fuzzy matching against known offices
    best_match, score = process.extractOne(user_input, KNOWN_OFFICES)
    if score > 70:
        return best_match
    return user_input

def extract_entities(query: str) -> dict:
    entities = {}
    query = query.lower()

    # --- Date Entity ---
    # Look for Month YYYY or Month-YYYY
    months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december',
              'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec']
    
    month_regex = r'\b(' + '|'.join(months) + r')\b'
    year_regex = r'\b(20\d{2})\b'
    
    month_match = re.search(month_regex, query)
    year_match = re.search(year_regex, query)
    
    if month_match and year_match:
        month_str = month_match.group(1)
        year_str = year_match.group(1)
        
        # Convert month name to number
        for i, m in enumerate(months[:12]):
            if month_str.startswith(m[:3]):
                entities['date'] = f"{year_str}-{i+1:02d}"
                break
    elif year_match:
        entities['date'] = year_match.group(1)

    # --- Sorting Entity ---
    if any(w in query for w in ['earliest', 'oldest', 'sabse purane', 'purana', 'pahle']):
        entities['sort_direction'] = 'ASC'
    elif any(w in query for w in ['latest', 'most recent', 'naya', 'abhipreet', 'sabse naya']):
        entities['sort_direction'] = 'DESC'

    # --- Designation Entity ---
    common_designations = [
        'computer operator', 'data entry operator', 'security guard', 
        'receptionist', 'supervisor', 'clerk', 'peon', 'beladar', 'driver'
    ]
    for d in common_designations:
        if d in query:
            entities['designation'] = d
            break

    # --- Item/Machine/Name Entities ---
    common_items = ['petrol', 'diesel', 'engine oil', 'sand', 'fertilizer', 'seeds']
    for i in common_items:
        if i in query:
            entities['item'] = i
            break

    common_machines = ['generator', 'truck', 'mower', 'tractor', 'pump']
    for m in common_machines:
        if m in query:
            entities['machine'] = m
            break

    # --- Machine Creation Specific Entities ---
    # Extract brand/make
    brand_match = re.search(r'\bbrand\s+(?:is|of|as)?\s*([\w\s]+?)(?:$|(?=\s+(?:and|with|for|,)))', query)
    if brand_match:
        entities['brand'] = brand_match.group(1).strip()
    
    # Extract machine name
    name_match = re.search(r'\b(?:machine\s+called|machine\s+named|called|named)\s+([\w\s]+?)(?:$|(?=\s+(?:and|with|for|,)))', query)
    if name_match:
        entities['machine_name'] = name_match.group(1).strip()

    # Extract fuel type
    fuel_match = re.search(r'\bfuel\s+(?:is|of|as)?\s*([\w\s]+?)(?:$|(?=\s+(?:and|with|for|,)))', query)
    if fuel_match:
        fuel_val = fuel_match.group(1).strip()
        if 'diesel' in fuel_val: entities['fuel_type'] = 1 # Example ID mapping
        elif 'petrol' in fuel_val: entities['fuel_type'] = 2

    # --- Office Entity ---
    raw_office = None
    # Look for English prepositions first
    match_eng = re.search(r'(?:at|in|from)\s+([\w\s]+?)(?:$|(?=\s+(?:and|with|for|me|mein|ka|ki)))', query)
    if match_eng:
        raw_office = match_eng.group(1).strip()

    # If not found, look for Hindi/Hinglish context words
    if not raw_office:
        match_hindi = re.search(r'([\w\s]+?)\s+(?:me|mein|ka|ki)\b', query)
        if match_hindi:
            words = match_hindi.group(1).strip().split()
            # More comprehensive stop-word list
            stop_words = ['show', 'list', 'wages', 'bill', 'kya', 'hai', 'ka', 'ki', 'ko', 'aur', 'and', 'the', 'a', 'an', 'dikhao', 'batao', 'soochi']
            filtered = [w for w in words if w not in stop_words]
            if filtered:
                # Take the last few words as they are most likely the office name
                raw_office = " ".join(filtered[-3:])

    if raw_office:
        entities['office'] = resolve_office_entity(raw_office)

    return entities
