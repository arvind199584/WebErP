import sys
import pdfplumber
import pandas as pd

def convert_to_excel(input_path, output_path):
    try:
        all_tables = []
        with pdfplumber.open(input_path) as pdf:
            for i, page in enumerate(pdf.pages):
                # Extract tables from the page
                tables = page.extract_tables()
                for table in tables:
                    # Add a header row to distinguish tables from different pages
                    all_tables.append([f"Page {i+1} - Table"])
                    all_tables.extend(table)
                    all_tables.append([]) # Add a blank row for spacing

        if not all_tables:
            print("No tables found in the PDF.")
            return False

        # Create a Pandas DataFrame
        df = pd.DataFrame(all_tables)
        
        # Write to Excel
        df.to_excel(output_path, index=False, header=False)
        
        return True
    except Exception as e:
        print(f"Error during conversion: {e}")
        return False

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python pdf_to_excel.py <input_pdf> <output_xlsx>")
        sys.exit(1)
        
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    
    if convert_to_excel(input_file, output_file):
        print("Success")
    else:
        sys.exit(1)
