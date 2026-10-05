(function() {
    function initVoiceInput() {
        // Select all text inputs and textareas
        const inputs = document.querySelectorAll('input[type="text"], textarea');
        
        inputs.forEach(input => {
            // Avoid duplicate initialization
            if (input.dataset.voiceInitialized || input.readOnly || input.disabled) return;
            input.dataset.voiceInitialized = "true";

            // Create a wrapper for the input and the mic button
            const wrapper = document.createElement('div');
            wrapper.className = 'voice-input-wrapper';
            wrapper.style.position = 'relative';
            wrapper.style.display = 'block';
            wrapper.style.width = '100%';

            // Move input into wrapper
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            // Create Mic Button
            const micBtn = document.createElement('button');
            micBtn.type = 'button';
            micBtn.className = 'voice-mic-btn';
            micBtn.innerHTML = '🎤';
            micBtn.title = 'Speak to fill';
            
            // Basic styling for the mic button
            Object.assign(micBtn.style, {
                position: 'absolute',
                right: '8px',
                top: input.tagName === 'TEXTAREA' ? '10px' : '50%',
                transform: input.tagName === 'TEXTAREA' ? 'none' : 'translateY(-50%)',
                border: 'none',
                background: 'transparent',
                cursor: 'pointer',
                zIndex: '10',
                padding: '2px',
                fontSize: '16px',
                opacity: '0.6',
                transition: 'opacity 0.2s'
            });

            micBtn.onmouseover = () => micBtn.style.opacity = '1';
            micBtn.onmouseout = () => { if (!micBtn.classList.contains('recording')) micBtn.style.opacity = '0.6'; };

            wrapper.appendChild(micBtn);

            // Check for Speech Recognition support
            if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
                micBtn.style.display = 'none';
                return;
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-IN'; // Default to Indian English

            micBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (micBtn.classList.contains('recording')) {
                    recognition.stop();
                } else {
                    recognition.start();
                }
            });

            recognition.onstart = () => {
                micBtn.classList.add('recording');
                micBtn.innerHTML = '🛑';
                micBtn.style.opacity = '1';
                input.placeholder_old = input.placeholder;
                input.placeholder = 'Listening...';
            };

            recognition.onend = () => {
                micBtn.classList.remove('recording');
                micBtn.innerHTML = '🎤';
                micBtn.style.opacity = '0.6';
                input.placeholder = input.placeholder_old || '';
            };

            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                input.value = transcript;
                // Trigger change/input events for any listeners (like validation or HTMX)
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.dispatchEvent(new Event('input', { bubbles: true }));
            };

            recognition.onerror = (event) => {
                console.error('Speech recognition error:', event.error);
                micBtn.classList.remove('recording');
                micBtn.innerHTML = '🎤';
            };
        });
    }

    // Initialize on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVoiceInput);
    } else {
        initVoiceInput();
    }

    // Support HTMX content updates
    document.addEventListener('htmx:afterOnLoad', initVoiceInput);
    document.addEventListener('htmx:historyRestore', initVoiceInput);
})();
