from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import openai
from dotenv import load_dotenv

# Cargar variables de entorno
load_dotenv()

# Configurar OpenAI
openai.api_key = os.getenv('OPENAI_API_KEY')

# Inicializar Flask
app = Flask(__name__)

# Configurar CORS
allowed_origins = os.getenv('ALLOWED_ORIGINS', '*').split(',')
CORS(app, resources={r"/*": {"origins": allowed_origins}})

@app.route('/chat', methods=['POST'])
def chat():
    try:
        data = request.json
        message = data.get('message', '')
        chat_history = data.get('chat_history', [])

        # Preparar el historial del chat para OpenAI
        messages = [
            {"role": "system", "content": "Eres Juan, un barista profesional experto en café. Proporciona respuestas breves y concisas, enfocadas en ayudar a los clientes con sus preguntas sobre café y productos relacionados."}
        ]

        # Agregar el historial del chat
        for chat in chat_history:
            messages.append({
                "role": "user" if chat['sender'] == 'user' else "assistant",
                "content": chat['message']
            })

        # Agregar el mensaje actual
        messages.append({"role": "user", "content": message})

        # Llamar a la API de OpenAI
        response = openai.ChatCompletion.create(
            model="gpt-3.5-turbo",
            messages=messages,
            max_tokens=150,
            temperature=0.7
        )

        # Extraer la respuesta
        bot_response = response.choices[0].message.content

        return jsonify({
            "success": True,
            "response": bot_response
        })

    except Exception as e:
        print(f"Error: {str(e)}")
        return jsonify({
            "success": False,
            "error": "Lo siento, hubo un error al procesar tu mensaje."
        }), 500

if __name__ == '__main__':
    app.run(debug=True)
