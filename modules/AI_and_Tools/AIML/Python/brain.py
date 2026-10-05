# This is the main entry point for running the Flask application.
# It imports the app object from the refactored core_nlp module and runs it.

from nlp.core_nlp import app
import ssl
import os

if __name__ == '__main__':
    # Use certificates generated for the project
    cert_path = os.path.abspath(os.path.join(os.path.dirname(__file__), '../../../certificates/cert.pem'))
    key_path = os.path.abspath(os.path.join(os.path.dirname(__file__), '../../../certificates/key.pem'))
    
    if os.path.exists(cert_path) and os.path.exists(key_path):
        print(f"--- Starting AI Server with SSL (https://0.0.0.0:5000) ---")
        app.run(host='0.0.0.0', port=5000, debug=False, ssl_context=(cert_path, key_path))
    else:
        print(f"--- Starting AI Server without SSL (http://0.0.0.0:5000) ---")
        app.run(host='0.0.0.0', port=5000, debug=False)
