const chatBox = document.getElementById('chat-box');


/**
 * @param sender
 * @param message
 */
function appendMessage(sender, message) {
    const messageElement = document.createElement('div');
    messageElement.classList.add('message', sender);
    chatBox.appendChild(messageElement);

    if (sender === 'assistant') {
        streamMessage(message, messageElement);
    } else {
        messageElement.textContent = message;
    }

    chatBox.scrollTop = chatBox.scrollHeight;
}

/**
 * @param text
 * @param element
 */
function streamMessage(text, element) {
    let index = 0;

    const interval = setInterval(() => {
        if (index < text.length) {
            element.textContent += text[index];
            index++;
            chatBox.scrollTop = chatBox.scrollHeight;
        } else {
            clearInterval(interval);
        }
    }, 40);
}


/**
 * @returns {Promise<void>}
 */
async function sendMessage() {
    const userInput = document.getElementById('user-input').value;
    if (!userInput.trim()) return;

    appendMessage('user', userInput);
    document.getElementById('user-input').value = '';

    const loadingMessage = document.createElement('div');
    loadingMessage.classList.add('message', 'assistant');
    loadingMessage.innerHTML = '<span class="loading">thinking...</span>';
    chatBox.appendChild(loadingMessage);

    try {
        const response = await fetch(chatUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'referer': chatUrl
            },
            body: JSON.stringify({
                message: userInput,
                productId: productId,
                customerId: currentUser
            }),
        });

        const data = await response.json();
        chatBox.removeChild(loadingMessage);

        const responseMessage = data || "I couldn't find an answer to that question.";
        appendMessage('assistant', responseMessage);

    } catch (error) {
        console.error('Error:', error);
        chatBox.removeChild(loadingMessage);
        appendMessage('assistant', 'There was an issue processing your request. Please try again.');
    }
}


/**
 * @param question
 */
function sendSuggestedQuestion(question) {
    document.getElementById('user-input').value = question;
    sendMessage();
}
