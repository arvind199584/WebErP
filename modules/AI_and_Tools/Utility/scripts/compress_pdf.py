import sys
import subprocess
import os

def get_gs_command():
    """Finds the Ghostscript executable."""
    if sys.platform == 'win32':
        # 1. Check standard PATH first
        try:
            subprocess.run(['gswin64c', '-h'], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            return 'gswin64c'
        except FileNotFoundError:
            pass # Not in PATH, try specific path

        # 2. Check the user-provided path
        gs_path = r"C:\Program Files\gs\gs10.06.0\bin\gswin64c.exe"
        if os.path.exists(gs_path):
            return gs_path
            
        # Fallback for 32-bit if needed
        try:
            subprocess.run(['gswin32c', '-h'], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            return 'gswin32c'
        except FileNotFoundError:
            pass
            
    else: # Linux/macOS
        try:
            subprocess.run(['gs', '-h'], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            return 'gs'
        except FileNotFoundError:
            pass
            
    return None # Not found

def compress_pdf(input_path, output_path, power=3):
    """
    Compresses PDF using Ghostscript.
    """
    quality = {
        1: '/prepress',
        2: '/printer',
        3: '/ebook',
        4: '/screen'
    }
    gs_setting = quality.get(power, '/ebook')
    
    gs_command = get_gs_command()
    if not gs_command:
        print("Error: Ghostscript not found. Please install it or check the path in the script.")
        return False

    command = [
        gs_command,
        '-sDEVICE=pdfwrite',
        '-dCompatibilityLevel=1.4',
        f'-dPDFSETTINGS={gs_setting}',
        '-dNOPAUSE',
        '-dQUIET',
        '-dBATCH',
        f'-sOutputFile={output_path}',
        input_path
    ]
    
    try:
        result = subprocess.run(command, check=True, capture_output=True, text=True)
        return True
    except subprocess.CalledProcessError as e:
        print(f"Ghostscript Error: {e.stderr}")
        return False
    except FileNotFoundError:
        print("Error: Ghostscript command failed. Is it installed and in the PATH?")
        return False

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python compress_pdf.py <input_file> <output_file> [power_level]")
        sys.exit(1)
        
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    power_level = int(sys.argv[3]) if len(sys.argv) > 3 else 3
    
    if compress_pdf(input_file, output_file, power_level):
        print("Success")
    else:
        sys.exit(1)
