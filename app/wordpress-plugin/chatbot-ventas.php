/*
Plugin Name: Chatbot Ventas
Plugin URI: https://tudominio.com/chatbot-ventas
Description: Un chatbot con estilo WhatsApp para atención al cliente
Version: 1.0.0
Author: Tu Nombre
Author URI: https://tudominio.com
License: GPL v2 or later
Text Domain: chatbot-ventas
*/

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('CHATBOT_VENTAS_VERSION', '1.0.0');
define('CHATBOT_VENTAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHATBOT_VENTAS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Definir la URL del servidor si no está definida
if (!defined('CHATBOT_SERVER_URL')) {
    define('CHATBOT_SERVER_URL', 'https://chatbot-ai-nuevo.onrender.com/chat');
}

// Inicialización del plugin
function chatbot_ventas_init() {
    // No necesitamos registrar los scripts aquí, lo haremos directamente
}

// Hook de activación del plugin
register_activation_hook(__FILE__, 'chatbot_ventas_activate');
function chatbot_ventas_activate() {
    // Configuración inicial
    if (!get_option('chatbot_server_url')) {
        update_option('chatbot_server_url', CHATBOT_SERVER_URL);
    }
}

// Registrar los hooks principales directamente
add_action('wp_enqueue_scripts', 'chatbot_ventas_enqueue_scripts');
add_action('wp_footer', 'chatbot_ventas_add_container');
add_action('wp_ajax_chatbot_message', 'chatbot_ventas_handle_message');
add_action('wp_ajax_nopriv_chatbot_message', 'chatbot_ventas_handle_message');

// Manejar las solicitudes AJAX
function chatbot_ventas_handle_message() {
    error_log('Iniciando procesamiento de mensaje del chatbot');
    
    check_ajax_referer('chatbot_nonce', 'nonce');
    
    $message = sanitize_text_field($_POST['message']);
    $chat_history = isset($_POST['history']) ? $_POST['history'] : array();
    
    error_log('Mensaje recibido: ' . $message);
    error_log('Historial: ' . print_r($chat_history, true));
    
    // Obtener la URL del servidor configurada
    $server_url = get_option('chatbot_server_url', CHATBOT_SERVER_URL);
    error_log('URL del servidor: ' . $server_url);
    
    // Configurar la solicitud al servidor del chatbot
    $response = wp_remote_post($server_url, array(
        'body' => json_encode(array(
            'message' => $message,
            'history' => $chat_history
        )),
        'headers' => array(
            'Content-Type' => 'application/json'
        ),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        error_log('Error en wp_remote_post: ' . $response->get_error_message());
        wp_send_json_error('Error en la comunicación con el chatbot: ' . $response->get_error_message());
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    error_log('Código de respuesta: ' . $response_code);
    error_log('Cuerpo de la respuesta: ' . $body);
    
    if ($response_code !== 200) {
        wp_send_json_error('Error en la respuesta del servidor: ' . $response_code);
        return;
    }
    
    $decoded_body = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Error al decodificar JSON: ' . json_last_error_msg());
        wp_send_json_error('Error al procesar la respuesta del servidor');
        return;
    }
    
    wp_send_json_success($decoded_body);
}

// Registrar scripts y estilos
function chatbot_ventas_enqueue_scripts() {
    // Registrar y encolar CSS con forzado de caché
    $css_version = CHATBOT_VENTAS_VERSION . '.' . time();
    
    // Asegurarnos que jQuery está cargado
    wp_enqueue_script('jquery');
    
    // Registrar y encolar CSS
    wp_register_style(
        'chatbot-styles',
        CHATBOT_VENTAS_PLUGIN_URL . 'css/chatbot.css',
        array(),
        $css_version
    );
    wp_enqueue_style('chatbot-styles');
    
    // Registrar y encolar JavaScript
    wp_register_script(
        'chatbot-script',
        CHATBOT_VENTAS_PLUGIN_URL . 'js/chatbot.js',
        array('jquery'),
        $css_version,
        true
    );
    wp_enqueue_script('chatbot-script');
    
    // Pasar variables a JavaScript
    wp_localize_script('chatbot-script', 'chatbotAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('chatbot_nonce'),
        'version' => CHATBOT_VENTAS_VERSION,
        'debug' => WP_DEBUG,
        'pluginUrl' => CHATBOT_VENTAS_PLUGIN_URL
    ));

    // Debug info
    error_log('Chatbot scripts and styles enqueued. Version: ' . $css_version);
    error_log('Plugin URL: ' . CHATBOT_VENTAS_PLUGIN_URL);
}

// Asegurarnos que los scripts se cargan en el momento correcto
add_action('wp_enqueue_scripts', 'chatbot_ventas_enqueue_scripts', 999);

// Agregar el contenedor del chatbot al footer con prioridad alta
add_action('wp_footer', 'chatbot_ventas_add_container', 999);

// Agregar el contenedor del chatbot al footer
function chatbot_ventas_add_container() {
    ?>
    <!-- Botón launcher del chatbot -->
    <div class="chat-launcher" id="chat-launcher">
        <svg class="chat-launcher-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M20.5 3.4A12.1 12.1 0 0012 0 12 12 0 001.7 17.8L0 24l6.3-1.7c2.8 1.5 5.5 2.3 8.4 2.3a12 12 0 0012-11.4 12 12 0 00-6.2-9.8z" fill="#25D366"/>
            <path d="M1.6 17.5L0 24l6.3-1.7c2.7 1.5 5.3 2.3 8.2 2.3 9.1 0 16.5-7.4 16.5-16.5S23.6 0 14.5 0 1.6 7.4 1.6 16.5c0 3 .8 5.6 2.3 8.2z" fill="#25D366"/>
            <path d="M12 0C5.4 0 0 5.4 0 12c0 2.6.9 5.1 2.3 7.1L0 24l5-1.3C6.9 23.5 9.4 24 12 24c6.6 0 12-5.4 12-12S18.6 0 12 0zm0 22c-2.4 0-4.7-.7-6.6-2l-.5-.3-4.5 1.2 1.2-4.5-.3-.5c-1.3-2-2-4.3-2-6.9 0-5.5 4.5-10 10-10s10 4.5 10 10-4.5 10-10 10z" fill="#FFF"/>
        </svg>
    </div>

    <!-- Contenedor principal del chatbot -->
    <div id="chatbot-container" class="chatbot-container minimized">
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <div class="chatbot-avatar">
                    <img src="<?php echo CHATBOT_VENTAS_PLUGIN_URL; ?>img/barista-avatar.png" alt="Juan - Barista Profesional">
                </div>
                <div class="chatbot-title">
                    <h3>Juan - Barista Profesional</h3>
                    <span class="chatbot-status">En línea</span>
                </div>
            </div>
            <button class="chatbot-toggle">×</button>
        </div>
        <div class="chatbot-messages"></div>
        <div class="chatbot-input">
            <input type="text" placeholder="Escribe tu mensaje aquí...">
            <button class="chatbot-send">
                <svg viewBox="0 0 24 24" width="24" height="24">
                    <path fill="#ffffff" d="M1.101 21.757L23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Debug info -->
    <?php if (WP_DEBUG): ?>
    <script>
        console.log('Chatbot loaded - Version: <?php echo CHATBOT_VENTAS_VERSION; ?>');
        console.log('Plugin URL: <?php echo CHATBOT_VENTAS_PLUGIN_URL; ?>');
    </script>
    <?php endif; ?>
    <?php
}

// Agregar página de configuración en el admin
function chatbot_ventas_add_admin_menu() {
    add_menu_page(
        'Configuración del Chatbot',
        'Chatbot Ventas',
        'manage_options',
        'chatbot-ventas',
        'chatbot_ventas_admin_page',
        'dashicons-format-chat',
        30
    );
}
add_action('admin_menu', 'chatbot_ventas_add_admin_menu');

// Página de configuración
function chatbot_ventas_admin_page() {
    // Guardar cambios
    if (isset($_POST['submit'])) {
        if (isset($_POST['chatbot_server_url'])) {
            update_option('chatbot_server_url', sanitize_text_field($_POST['chatbot_server_url']));
        }
        echo '<div class="notice notice-success"><p>Configuración actualizada correctamente.</p></div>';
    }

    // Obtener valores actuales
    $server_url = get_option('chatbot_server_url', CHATBOT_SERVER_URL);
    ?>
    <div class="wrap">
        <h1>Configuración del Chatbot de Ventas</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">URL del Servidor</th>
                    <td>
                        <input type="text" name="chatbot_server_url" value="<?php echo esc_attr($server_url); ?>" class="regular-text">
                        <p class="description">URL del servidor de Render (ejemplo: https://chatbot-open-ai.onrender.com/chat)</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
} 