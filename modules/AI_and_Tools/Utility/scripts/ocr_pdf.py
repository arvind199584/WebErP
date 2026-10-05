import sys
import subprocess

def ocr_pdf(input_path, output_path):
    # Command to run ocrmypdf
    # Requires: pip install ocrmypdf
    # And system dependency: tesseract-ocr
    
    command = [
        'ocrmypdf',
        '--skip-text', # Skip pages that already have text
        '--jobs', '4', # Use 4 cores
        input_path,
        output_path
    ]
    
    try:
        subprocess.run(command, check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        return True
    except subprocess.CalledProcessError as e:
        print(f"Error: {e.stderr.decode()}")
        return False
    except FileNotFoundError:
        print("Error: ocrmypdf not found. Please install it (pip install ocrmypdf) and tesseract-ocr.")
        return False

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python ocr_pdf.py <input_file> <output_file>")
        sys.exit(1)
        
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    
    if ocr_pdf(input_file, output_file):
        print("Success")
    else:
        sys.exit(1)
