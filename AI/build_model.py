import os
import subprocess
import sys

def main():
    print("Step 1: Running generator.py to generate/update the Modelfile...")
    generator_script = os.path.join(os.path.dirname(__file__), "generator.py")
    result = subprocess.run([sys.executable, generator_script], capture_output=True, text=True)
    
    if result.returncode != 0:
        print("Error generating Modelfile:")
        print(result.stderr)
        sys.exit(1)
        
    print(result.stdout.strip())
    
    print("\nStep 2: Creating Ollama model 'hinglish-crud-llama' (This may take a minute)...")
    modelfile_path = os.path.join(os.path.dirname(__file__), "Modelfile")
    
    try:
        # Run Ollama create command
        # It runs: ollama create hinglish-crud-llama -f Modelfile
        process = subprocess.Popen(
            ["ollama", "create", "hinglish-crud-llama", "-f", modelfile_path],
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            bufsize=1
        )
        
        # Read and print output in real-time
        for line in process.stdout:
            print(line, end="")
            
        process.wait()
        
        if process.returncode == 0:
            print("\nSuccess! Custom model 'hinglish-crud-llama' has been created.")
            print("You can verify it by running 'ollama list' in your terminal.")
        else:
            print(f"\nError: Ollama build failed with exit code {process.returncode}")
            
    except FileNotFoundError:
        print("\nError: 'ollama' command not found in your system PATH.")
        print("Please verify Ollama is installed and running, then try again.")
        sys.exit(1)

if __name__ == "__main__":
    main()
