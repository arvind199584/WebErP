import sys
import re

def generate_alias(text, office_name="", office_code=""):
    # 1. Basic cleaning
    text = text.lower()
    office_name = office_name.lower()
    office_code = office_code.lower()
    
    # 2. Remove specific phrases first
    phrases_to_remove = [
        "deployment of", 
        "providing of", 
        "hiring of", 
        "services of",
        "estimate for"
    ]
    for phrase in phrases_to_remove:
        text = text.replace(phrase, "")
        
    # 3. Remove Office Name and Code
    if office_name:
        text = text.replace(office_name, "")
    if office_code:
        text = text.replace(office_code, "")

    # 4. Remove special characters
    text = re.sub(r'[^a-z0-9\s]', '', text)
    
    # 5. Define stop words (common words to ignore)
    stop_words = {
        'the', 'is', 'at', 'which', 'on', 'for', 'of', 'and', 'a', 'an', 'to', 'in', 
        'regarding', 'subject', 'approval', 'sanction', 'expenditure', 'estimate', 
        'provision', 'providing', 'fixing', 'supply', 'installation', 'various', 'office'
    }
    
    # 6. Tokenize
    words = text.split()
    
    # 7. Filter words
    keywords = [w for w in words if w not in stop_words and len(w) > 2]
    
    # 8. Select top 4 words
    summary_words = keywords[:4]
    
    # 9. Format output
    return " ".join(summary_words).title()

if __name__ == "__main__":
    if len(sys.argv) > 1:
        input_text = sys.argv[1]
        
        # Optional arguments for office name and code
        off_name = sys.argv[2] if len(sys.argv) > 2 else ""
        off_code = sys.argv[3] if len(sys.argv) > 3 else ""
        
        print(generate_alias(input_text, off_name, off_code))
    else:
        print("Error: No input text provided")
