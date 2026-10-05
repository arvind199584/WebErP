<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI-Powered Data Entry</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; color: #333; }
        .container { max-width: 800px; margin: auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #0056b3; }
        .ai-form { display: flex; margin-bottom: 20px; }
        .ai-form input[type="text"] { flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px 0 0 4px; }
        .ai-form button { padding: 10px 20px; border: none; background-color: #28a745; color: white; border-radius: 0 4px 4px 0; cursor: pointer; }
        .mic-button { padding: 10px 15px; border: 1px solid #ccc; background-color: #f8f9fa; cursor: pointer; }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .loader { text-align: center; display: none; margin: 20px; }

        /* Confirmation Modal Styles */
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; text-align: center; }
        .modal-content h2 { margin-top: 0; }
        .modal-content ul { list-style-type: none; padding: 0; text-align: left; display: inline-block; }
        .modal-content li { margin-bottom: 8px; }
        .modal-buttons { margin-top: 20px; }
        .modal-buttons button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 0 10px; }
        #confirm-proceed { background-color: #28a745; color: white; }
        #confirm-cancel { background-color: #6c757d; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>AI-Powered Data Entry</h1>
    <p>Speak or type a command to create a new record. For example: <em>"Create a new machine called Tractor 5, brand is Sonalika, fuel is Diesel"</em></p>

    <div class="ai-form">
        <button id="mic-btn" class="mic-button">🎤</button>
        <input type="text" id="ai-command" placeholder="Listening...">
        <button id="ai-submit">Execute</button>
    </div>

    <div id="error-container" class="error" style="display: none;"></div>
    <div id="loader" class="loader">Processing...</div>
</div>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="modal">
    <div class="modal-content">
        <h2 id="confirmation-title">Confirm Action</h2>
        <p>The AI understood the following. Please confirm to proceed:</p>
        <div id="confirmation-details"></div>
        <div class="modal-buttons">
            <button id="confirm-cancel">Cancel</button>
            <button id="confirm-proceed">Proceed</button>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const commandInput = document.getElementById('ai-command');
    const submitButton = document.getElementById('ai-submit');
    const micButton = document.getElementById('mic-btn');
    const errorContainer = document.getElementById('error-container');
    const loader = document.getElementById('loader');
    const confirmationModal = document.getElementById('confirmation-modal');

    let pendingAction = null;

    // --- Voice Recognition ---
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        const recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.lang = 'en-US';

        micButton.addEventListener('click', () => {
            commandInput.placeholder = 'Listening...';
            recognition.start();
        });

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            commandInput.value = transcript;
            commandInput.placeholder = 'Command transcribed...';
        };

        recognition.onerror = (event) => {
            commandInput.placeholder = 'Could not recognize speech.';
            console.error('Speech recognition error', event.error);
        };
    } else {
        micButton.style.display = 'none'; // Hide if not supported
    }

    // --- Form Submission Logic ---
    async function handleCommand() {
        const command = commandInput.value.trim();
        if (!command) {
            alert('Please enter a command.');
            return;
        }

        errorContainer.style.display = 'none';
        loader.style.display = 'block';

        try {
            // Step 1: Send command to AI to get structured action object
            const nlpResponse = await fetch('https://localhost:5000/nlp-action', { // New endpoint
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: "<?php echo \App\Core\CSRFManager::generateToken(); ?>", query: command })
            });

            if (!nlpResponse.ok) {
                const err = await nlpResponse.json();
                throw new Error(err.error || 'Error from NLP Action service.');
            }

            const nlpData = await nlpResponse.json();
            pendingAction = nlpData; // Store the action object

            // Step 2: Show confirmation modal to the user
            showConfirmation(nlpData);

        } catch (error) {
            errorContainer.textContent = error.message;
            errorContainer.style.display = 'block';
        } finally {
            loader.style.display = 'none';
        }
    }

    function showConfirmation(actionData) {
        const title = document.getElementById('confirmation-title');
        const details = document.getElementById('confirmation-details');

        title.textContent = `Confirm: ${actionData.intent.replace(/_/g, ' ')}`;

        let detailsHtml = '<ul>';
        for (const [key, value] of Object.entries(actionData.action_details.data)) {
            detailsHtml += `<li><strong>${key.replace(/_/g, ' ')}:</strong> ${value}</li>`;
        }
        detailsHtml += '</ul>';
        details.innerHTML = detailsHtml;

        confirmationModal.style.display = 'block';
    }

    // --- Modal Button Handlers ---
    document.getElementById('confirm-cancel').addEventListener('click', () => {
        confirmationModal.style.display = 'none';
        pendingAction = null;
    });

    document.getElementById('confirm-proceed').addEventListener('click', async () => {
        if (!pendingAction) return;

        confirmationModal.style.display = 'none';
        loader.style.display = 'block';

        try {
            // Step 3: Send the confirmed action to the PHP backend for execution
            const execResponse = await fetch('?action=executeAction', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(pendingAction)
            });

            if (!execResponse.ok) {
                const err = await execResponse.json();
                throw new Error(err.error || 'Error executing action.');
            }

            const execData = await execResponse.json();
            alert(execData.message); // Show success message
            commandInput.value = ''; // Clear input for next command

        } catch (error) {
            errorContainer.textContent = error.message;
            errorContainer.style.display = 'block';
        } finally {
            loader.style.display = 'none';
            pendingAction = null;
        }
    });

    submitButton.addEventListener('click', handleCommand);
    commandInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            handleCommand();
        }
    });
});
</script>

</body>
</html>
