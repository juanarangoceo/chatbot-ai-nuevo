jQuery(document).ready(function($) {
    const chatLauncher = $('#chat-launcher');
    const chatbotContainer = $('#chatbot-container');
    const messagesContainer = chatbotContainer.find('.chatbot-messages');
    const input = chatbotContainer.find('input');
    const sendButton = chatbotContainer.find('.chatbot-send');
    const toggleButton = chatbotContainer.find('.chatbot-toggle');
    let chatHistory = [];
    let isFirstOpen = true;
    let isTyping = false;

    // Función para formatear la hora
    function getCurrentTime() {
        const now = new Date();
        return now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }

    // Función para mostrar/ocultar el indicador de escritura
    function toggleTyping(show) {
        if (show === isTyping) return;
        isTyping = show;
        
        if (show) {
            const typingElement = $('<div class="chatbot-message bot typing"><div class="typing-indicator"><span></span><span></span><span></span></div></div>');
            messagesContainer.append(typingElement);
        } else {
            messagesContainer.find('.typing').remove();
        }
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }

    // Función para agregar mensajes al chat
    function addMessage(message, isUser = false) {
        const time = getCurrentTime();
        const messageElement = $('<div>')
            .addClass('chatbot-message')
            .addClass(isUser ? 'user' : 'bot');

        const messageContent = $('<div>')
            .addClass('message-content')
            .text(message);

        const messageTime = $('<div>')
            .addClass('message-time')
            .text(time);

        messageElement.append(messageContent, messageTime);

        if (isUser) {
            const statusElement = $('<div class="message-status"><div class="tick"></div></div>');
            messageElement.append(statusElement);
        }
        
        messagesContainer.append(messageElement);
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        
        if (isUser) {
            setTimeout(() => {
                messageElement.find('.tick').addClass('delivered');
            }, 1000);
        }
        
        chatHistory.push({
            role: isUser ? 'user' : 'assistant',
            content: message
        });

        return messageElement;
    }

    // Función para enviar mensaje al servidor
    function sendMessage(message) {
        if (!message || message.trim() === '') return;
        
        sendButton.prop('disabled', true);
        input.prop('disabled', true);
        
        setTimeout(() => {
            toggleTyping(true);
        }, 500);

        $.ajax({
            url: chatbotAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'chatbot_message',
                nonce: chatbotAjax.nonce,
                message: message,
                history: chatHistory
            },
            success: function(response) {
                toggleTyping(false);
                
                if (response.success && response.data) {
                    let botResponse = '';
                    if (response.data.response) {
                        botResponse = response.data.response;
                    } else if (typeof response.data === 'string') {
                        botResponse = response.data;
                    } else {
                        console.error('Formato de respuesta inesperado:', response);
                        botResponse = 'Lo siento, hubo un error en la comunicación. Por favor, intenta de nuevo.';
                    }
                    
                    // Simular tiempo de escritura basado en la longitud del mensaje
                    const typingDelay = Math.min(1500, botResponse.length * 30);
                    setTimeout(() => {
                        addMessage(botResponse);
                    }, typingDelay);
                } else {
                    console.error('Error en la respuesta:', response);
                    setTimeout(() => {
                        addMessage('Lo siento, hubo un error en la comunicación. Por favor, intenta de nuevo.');
                    }, 1000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición:', {xhr, status, error});
                toggleTyping(false);
                setTimeout(() => {
                    addMessage('Lo siento, hubo un error en la comunicación. Por favor, intenta de nuevo.');
                }, 1000);
            },
            complete: function() {
                sendButton.prop('disabled', false);
                input.prop('disabled', false);
                input.focus();
            }
        });
    }

    // Manejar el envío de mensajes
    function handleSend() {
        const message = input.val().trim();
        if (message) {
            addMessage(message, true);
            input.val('');
            sendMessage(message);
        }
    }

    // Event listeners para enviar mensajes
    sendButton.on('click touchstart', function(e) {
        e.preventDefault();
        handleSend();
    });

    input.on('keypress', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            handleSend();
        }
    });

    // Manejar la visibilidad del chatbot
    function toggleChat(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        chatbotContainer.toggleClass('minimized');
        chatLauncher.toggleClass('hidden');
        
        if (!chatbotContainer.hasClass('minimized')) {
            if (isFirstOpen) {
                setTimeout(() => {
                    toggleTyping(true);
                    setTimeout(() => {
                        toggleTyping(false);
                        addMessage('¡Hola! 👋 Soy Juan, tu barista profesional. ¿En qué puedo ayudarte hoy? ☕', false);
                    }, 2000);
                }, 1000);
                isFirstOpen = false;
            }
            input.focus();
            
            // Ajustar la posición del scroll en móviles
            if (window.innerWidth <= 480) {
                setTimeout(function() {
                    window.scrollTo(0, document.body.scrollHeight);
                }, 300);
            }
        }
    }

    // Event listeners para abrir/cerrar el chat
    chatLauncher.on('click touchstart', function(e) {
        e.preventDefault();
        toggleChat(e);
        updateChatState();
    });

    toggleButton.on('click touchstart', function(e) {
        e.preventDefault();
        toggleChat(e);
        updateChatState();
    });

    // Prevenir que el toque en el contenedor del chat cierre el teclado móvil
    chatbotContainer.on('touchstart', function(e) {
        if (!$(e.target).is(input) && !$(e.target).is(sendButton)) {
            e.preventDefault();
        }
    });

    // Ajustar el tamaño en orientación móvil
    $(window).on('resize orientationchange', function() {
        if (!chatbotContainer.hasClass('minimized')) {
            if (window.innerWidth <= 480) {
                chatbotContainer.css({
                    'height': window.innerHeight + 'px',
                    'max-height': window.innerHeight + 'px'
                });
            } else {
                chatbotContainer.css({
                    'height': '',
                    'max-height': ''
                });
            }
        }
    });

    // Guardar estado del chat en localStorage
    const chatState = localStorage.getItem('chatbotState');
    if (chatState === 'open') {
        toggleChat();
    }

    // Actualizar estado en localStorage
    function updateChatState() {
        const state = chatbotContainer.hasClass('minimized') ? 'closed' : 'open';
        localStorage.setItem('chatbotState', state);
    }
}); 