import sys
from pdf2docx import Converter

def convert_to_docx(input_path, output_path):
    try:
        # Create a converter object
        cv = Converter(input_path)
        
        # Convert to docx
        cv.convert(output_path, start=0, end=None)
        
        # Close the converter
        cv.close()
        
        return True
    except Exception as e:
        print(f"Error during conversion: {e}")
        return False

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python pdf_to_docx.py <input_pdf> <output_docx>")
        sys.exit(1)
        
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    
    if convert_to_docx(input_file, output_file):
        print("Success")
    else:
        sys.exit(1)
