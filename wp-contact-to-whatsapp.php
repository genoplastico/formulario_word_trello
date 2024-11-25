<?php
/*
Plugin Name: Formulario de Contacto a WhatsApp Mejorado
Description: Crea un formulario de contacto personalizable con ubicación, envía las respuestas a WhatsApp y a Google Sheets.
Version: 3.0
Author: Tu Nombre


*/

if (!defined('ABSPATH')) {
    exit;
    

}

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';


// Función para registrar el menú de administración
function wcw_register_admin_menu() {
    add_menu_page(
        'Configuración del Formulario',
        'Formulario WhatsApp',
        'manage_options',
        'wcw-config',
        'wcw_admin_page',
        'dashicons-format-chat'
    );
}
add_action('admin_menu', 'wcw_register_admin_menu');

// Página de administración
function wcw_admin_page() {
    if (isset($_POST['wcw_save_changes'])) {
        update_option('wcw_fields', $_POST['wcw_fields']);
        update_option('wcw_whatsapp_number', sanitize_text_field($_POST['wcw_whatsapp_number']));
        update_option('wcw_google_sheet_id', sanitize_text_field($_POST['wcw_google_sheet_id']));
        update_option('wcw_google_client_email', sanitize_email($_POST['wcw_google_client_email']));
        update_option('wcw_google_private_key', sanitize_textarea_field($_POST['wcw_google_private_key']));
        update_option('wcw_trello_api_key', sanitize_text_field($_POST['wcw_trello_api_key']));
        update_option('wcw_trello_token', sanitize_text_field($_POST['wcw_trello_token']));
        update_option('wcw_trello_board_id', sanitize_text_field($_POST['wcw_trello_board_select']));
    update_option('wcw_trello_list_id', sanitize_text_field($_POST['wcw_trello_list_select']));
    update_option('wcw_trello_board_name', sanitize_text_field($_POST['wcw_trello_board_name']));
    update_option('wcw_trello_list_name', sanitize_text_field($_POST['wcw_trello_list_name']));
        
        echo '<div class="updated"><p>Configuración guardada.</p></div>';
    }

    $fields = get_option('wcw_fields', array(
        array('name' => 'nombre', 'label' => 'Nombre', 'type' => 'text', 'required' => true),
        array('name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true),
        array('name' => 'mensaje', 'label' => 'Mensaje', 'type' => 'textarea', 'required' => true)
    ));
    $whatsapp_number = get_option('wcw_whatsapp_number', '');
    $google_sheet_id = get_option('wcw_google_sheet_id', '');
    $google_client_email = get_option('wcw_google_client_email', '');
    $google_private_key = get_option('wcw_google_private_key', '');

    ?>
    <div class="wrap">
        <h1>Configuración del Formulario de Contacto a WhatsApp</h1>
        <form method="post" action="">
            <h2>Campos del formulario</h2>
            <div id="wcw-fields">
                <?php foreach ($fields as $index => $field): ?>
                    <div class="wcw-field">
                        <input type="text" name="wcw_fields[<?php echo $index; ?>][name]" value="<?php echo esc_attr($field['name']); ?>" placeholder="Nombre del campo">
                        <input type="text" name="wcw_fields[<?php echo $index; ?>][label]" value="<?php echo esc_attr($field['label']); ?>" placeholder="Etiqueta">
                        <select name="wcw_fields[<?php echo $index; ?>][type]">
                            <option value="text" <?php selected($field['type'], 'text'); ?>>Texto</option>
                            <option value="email" <?php selected($field['type'], 'email'); ?>>Email</option>
                            <option value="number" <?php selected($field['type'], 'number'); ?>>Número</option>
                            <option value="textarea" <?php selected($field['type'], 'textarea'); ?>>Área de texto</option>
                        </select>
                        <label>
                            <input type="checkbox" name="wcw_fields[<?php echo $index; ?>][required]" <?php checked($required, true); ?>>
                            Requerido
                        </label>
                        <button type="button" class="button wcw-remove-field">Eliminar</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="wcw-add-field" class="button">Agregar campo</button>

            <h2>Configuración de WhatsApp</h2>
            <p>
                <label for="wcw_whatsapp_number">Número de WhatsApp:</label>
                <input type="text" id="wcw_whatsapp_number" name="wcw_whatsapp_number" value="<?php echo esc_attr($whatsapp_number); ?>">
            </p>

            <h2>Configuración de Google Sheets</h2>
            <p>
                <label for="wcw_google_sheet_id">ID de la hoja de cálculo:</label>
                <input type="text" id="wcw_google_sheet_id" name="wcw_google_sheet_id" value="<?php echo esc_attr($google_sheet_id); ?>">
            </p>
            <p>
                <label for="wcw_google_client_email">Email del cliente de servicio:</label>
                <input type="email" id="wcw_google_client_email" name="wcw_google_client_email" value="<?php echo esc_attr($google_client_email); ?>">
            </p>
            <p>
                <label for="wcw_google_private_key">Clave privada del cliente de servicio:</label>
                <textarea id="wcw_google_private_key" name="wcw_google_private_key"><?php echo esc_textarea($google_private_key); ?></textarea>
            </p>
            <h2>Configuración de Trello</h2>
            <p>
                <label for="wcw_trello_api_key">API Key de Trello:</label>
                <input type="text" id="wcw_trello_api_key" name="wcw_trello_api_key" value="<?php echo esc_attr(get_option('wcw_trello_api_key', '')); ?>">
            </p>
            <p>
                <label for="wcw_trello_token">Token de Trello:</label>
                <input type="text" id="wcw_trello_token" name="wcw_trello_token" value="<?php echo esc_attr(get_option('wcw_trello_token', '')); ?>">
            </p>
            <p>
                <button type="button" id="wcw_load_trello_boards" class="button">Cargar Tableros de Trello</button>
            </p>
            <div id="wcw_trello_message"></div>
            <p>
                <label for="wcw_trello_board_select">Seleccionar Tablero:</label>
                <select id="wcw_trello_board_select" name="wcw_trello_board_select">
                    <option value="">Seleccione un tablero</option>
                </select>
            </p>
            <p>
                <label for="wcw_trello_list_select">Seleccionar Lista:</label>
                <select id="wcw_trello_list_select" name="wcw_trello_list_select">
                    <option value="">Seleccione una lista</option>
                </select>
            </p>
            <p>
    <label for="wcw_trello_current_config">Configuración actual:</label>
    <textarea id="wcw_trello_current_config" name="wcw_trello_current_config" readonly class="large-text"></textarea>
</p>
<input type="hidden" id="wcw_trello_board_name" name="wcw_trello_board_name" value="">
<input type="hidden" id="wcw_trello_list_name" name="wcw_trello_list_name" value="">

            <p class="submit">
                <input type="submit" name="wcw_save_changes" class="button-primary" value="Guardar cambios">
            </p>
        </form>
    </div>
    <?php
}


function wcw_formulario_contacto() {
    $fields = get_option('wcw_fields', array());
    ob_start();
    ?>
    <form id="wcw-formulario" method="post">
        <?php foreach ($fields as $field): ?>
            <div class="wcw-form-group">
                <label for="<?php echo esc_attr($field['name']); ?>"><?php echo esc_html($field['label']); ?>:</label>
                <?php if ($field['type'] === 'textarea'): ?>
                    <textarea name="<?php echo esc_attr($field['name']); ?>" <?php echo $field['required'] ? 'required' : ''; ?>></textarea>
                <?php else: ?>
                    <input type="<?php echo esc_attr($field['type']); ?>" name="<?php echo esc_attr($field['name']); ?>" <?php echo $field['required'] ? 'required' : ''; ?>>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        
        <input type="hidden" name="action" value="wcw_enviar_formulario">
        <input type="submit" name="wcw_submit" value="Enviar a WhatsApp" class="wcw-submit-btn">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('formulario_contacto_whatsapp', 'wcw_formulario_contacto');

function wcw_crear_tarjeta_trello($datos) {
    $api_key = get_option('wcw_trello_api_key');
    $token = get_option('wcw_trello_token');
    $list_id = get_option('wcw_trello_list_id');

    error_log('API Key: ' . substr($api_key, 0, 5) . '...');
    error_log('Token: ' . substr($token, 0, 5) . '...');
    error_log('List ID: ' . $list_id);

    if (empty($api_key) || empty($token) || empty($list_id)) {
        error_log('Faltan configuraciones de Trello');
        return false;
    }

    $nombre = $datos['nombre'] ?? 'Sin nombre';
    $email = $datos['email'] ?? 'Sin email';
    $mensaje = $datos['mensaje'] ?? 'Sin mensaje';
    $aparato = $datos['Aparato'] ?? 'Sin especificar';

    $card_name = "Nuevo contacto: $nombre - $aparato";
    $card_desc = "Email: $email\nMensaje: $mensaje\nAparato: $aparato";

    $url = "https://api.trello.com/1/cards?idList={$list_id}&key={$api_key}&token={$token}&name=" . urlencode($card_name) . "&desc=" . urlencode($card_desc);

    error_log('URL de la solicitud a Trello: ' . $url);

    $args = array(
        'method' => 'POST',
        'timeout' => 30,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => null,
        'cookies' => array()
    );

    error_log('Intentando crear tarjeta en Trello');
    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        error_log('Error al crear tarjeta en Trello: ' . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    error_log('Código de respuesta HTTP: ' . $response_code);
    error_log('Cuerpo de la respuesta: ' . $body);

    $data = json_decode($body);

    if (isset($data->id)) {
        error_log('Tarjeta creada en Trello con ID: ' . $data->id);
        return true;
    } else {
        error_log('Error al crear tarjeta en Trello. Respuesta: ' . print_r($data, true));
        return false;
    }
}



function wcw_procesar_formulario() {
    error_log('Función wcw_procesar_formulario llamada');
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'wcw_enviar_formulario') {
        error_log('Formulario recibido: ' . print_r($_POST, true));
        $fields = get_option('wcw_fields', array());
        $mensaje_whatsapp = "Nuevo mensaje de contacto:\n\n";
        $datos_sheets = array();

        foreach ($fields as $field) {
            $valor = isset($_POST[$field['name']]) ? sanitize_text_field($_POST[$field['name']]) : '';
            $mensaje_whatsapp .= "{$field['label']}: $valor\n";
            $datos_sheets[] = $valor;
        }

        $numero_whatsapp = get_option('wcw_whatsapp_number', '');
        
        $url_whatsapp = 'https://api.whatsapp.com/send?phone=' . $numero_whatsapp . '&text=' . urlencode($mensaje_whatsapp);
        
        error_log('URL de WhatsApp generada: ' . $url_whatsapp);

        // Enviar datos a Google Sheets
        wcw_enviar_a_google_sheets($datos_sheets);
        
        // Crear tarjeta en Trello
        error_log('Intentando crear tarjeta en Trello con datos: ' . print_r($_POST, true));
        $trello_result = wcw_crear_tarjeta_trello($_POST);
        if ($trello_result) {
            error_log('Tarjeta creada exitosamente en Trello');
        } else {
            error_log('No se pudo crear la tarjeta en Trello');
        }

        wp_send_json($url_whatsapp);
        exit;
    }
}
add_action('wp_ajax_wcw_enviar_formulario', 'wcw_procesar_formulario');
add_action('wp_ajax_nopriv_wcw_enviar_formulario', 'wcw_procesar_formulario');

function wcw_enviar_a_google_sheets($datos) {
    try {
        $client = new \Google_Client();
        $private_key = get_option('wcw_google_private_key');
        $private_key = str_replace(['\\n', '\n'], "\n", $private_key);
        $auth_config = [
            'type' => 'service_account',
            'client_email' => get_option('wcw_google_client_email'),
            'private_key' => $private_key,
        ];
        error_log('Auth config: ' . print_r($auth_config, true));
        $client->setAuthConfig($auth_config);
        $client->addScope(\Google_Service_Sheets::SPREADSHEETS);

        $service = new \Google_Service_Sheets($client);
        $spreadsheetId = get_option('wcw_google_sheet_id');

        $range = 'A1:Z1';
        $body = new \Google_Service_Sheets_ValueRange([
            'values' => [$datos]
        ]);
        $params = [
            'valueInputOption' => 'RAW'
        ];

        $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
        error_log('Datos enviados a Google Sheets: ' . print_r($result, true));
    } catch (Exception $e) {
        error_log('Error al enviar datos a Google Sheets: ' . $e->getMessage());
        error_log('Traza de la excepción: ' . $e->getTraceAsString());
    }
}

function wcw_enqueue_scripts() {
    wp_enqueue_style('wcw-estilos', plugins_url('css/wcw-estilos.css', __FILE__));
    wp_enqueue_script('jquery');
    wp_enqueue_script('wcw-script', plugins_url('js/wcw-script.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('wcw-script', 'wcw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'wcw_enqueue_scripts');

function wcw_enqueue_admin_scripts($hook) {
    // Asegúrate de que los scripts solo se carguen en la página de tu plugin
    if ($hook != 'toplevel_page_wcw-config') {
        return;
    }

    // Registrar y encolar el estilo CSS
    wp_register_style('wcw-admin-style', plugins_url('css/wcw-admin-style.css', __FILE__), array(), '1.0.0');
    wp_enqueue_style('wcw-admin-style');

    // Registrar y encolar el script JavaScript
    wp_register_script('wcw-admin-script', plugins_url('js/wcw-admin-script.js', __FILE__), array('jquery'), '1.0.0', true);
    wp_enqueue_script('wcw-admin-script');

    // Pasar datos al script JavaScript
    wp_localize_script('wcw-admin-script', 'wcw_admin_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wcw_admin_nonce'),
        'saved_board_id' => get_option('wcw_trello_board_id', ''),
        'saved_list_id' => get_option('wcw_trello_list_id', ''),
        'saved_board_name' => get_option('wcw_trello_board_name', ''),
        'saved_list_name' => get_option('wcw_trello_list_name', ''),
        'plugin_url' => plugins_url('', __FILE__),
        'loading_message' => __('Cargando...', 'wp-contact-to-whatsapp'),
        'error_message' => __('Ha ocurrido un error. Por favor, intenta de nuevo.', 'wp-contact-to-whatsapp')
    ));

    // Si necesitas cargar scripts adicionales de WordPress
    wp_enqueue_script('wp-api');
}
add_action('admin_enqueue_scripts', 'wcw_enqueue_admin_scripts');

function wcw_get_trello_lists($board_id) {
    $api_key = get_option('wcw_trello_api_key');
    $token = get_option('wcw_trello_token');
    
    $url = "https://api.trello.com/1/boards/{$board_id}/lists?key={$api_key}&token={$token}";
    
    $response = wp_remote_get($url);
    
    if (is_wp_error($response)) {
        return array();
    }
    
    $lists = json_decode(wp_remote_retrieve_body($response), true);
    
    return $lists;
}

function wcw_ajax_get_trello_boards() {
    error_log('Función wcw_ajax_get_trello_boards llamada');
    
    if (!isset($_POST['api_key']) || !isset($_POST['token'])) {
        error_log('API Key o Token no recibidos');
        wp_send_json_error('API Key o Token no proporcionados');
        return;
    }

    $api_key = sanitize_text_field($_POST['api_key']);
    $token = sanitize_text_field($_POST['token']);
    
    error_log('API Key recibida: ' . substr($api_key, 0, 5) . '...');
    error_log('Token recibido: ' . substr($token, 0, 5) . '...');

    $url = "https://api.trello.com/1/members/me/boards?key={$api_key}&token={$token}";
    
    error_log('URL de solicitud a Trello: ' . $url);

    $response = wp_remote_get($url);
    
    if (is_wp_error($response)) {
        error_log('Error en wp_remote_get: ' . $response->get_error_message());
        wp_send_json_error('Error al conectar con Trello: ' . $response->get_error_message());
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    error_log('Código de respuesta HTTP: ' . $response_code);
    error_log('Cuerpo de la respuesta: ' . substr($body, 0, 100) . '...');

    if ($response_code !== 200) {
        error_log('Error en la respuesta de Trello. Código: ' . $response_code . '. Cuerpo: ' . $body);
        wp_send_json_error('Error en la respuesta de Trello. Código: ' . $response_code . '. Mensaje: ' . $body);
        return;
    }
    
    $boards = json_decode($body, true);
    
    if (empty($boards) || !is_array($boards)) {
        error_log('No se pudieron decodificar los tableros o el formato de respuesta es inválido. Respuesta completa: ' . $body);
        wp_send_json_error('No se pudieron obtener los tableros o el formato de respuesta es inválido.');
        return;
    }

    error_log('Tableros obtenidos exitosamente: ' . count($boards));
    wp_send_json_success($boards);
}
add_action('wp_ajax_wcw_get_trello_boards', 'wcw_ajax_get_trello_boards');
add_action('wp_ajax_nopriv_wcw_get_trello_boards', 'wcw_ajax_get_trello_boards');

function wcw_ajax_get_trello_lists() {
    $board_id = $_POST['board_id'];
    $api_key = get_option('wcw_trello_api_key');
    $token = get_option('wcw_trello_token');
    
    $url = "https://api.trello.com/1/boards/{$board_id}/lists?key={$api_key}&token={$token}";
    
    error_log('Solicitando listas de Trello. URL: ' . $url);
    
    $response = wp_remote_get($url);
    
    if (is_wp_error($response)) {
        error_log('Error al conectar con Trello: ' . $response->get_error_message());
        wp_send_json_error('Error al conectar con Trello: ' . $response->get_error_message());
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        error_log('Error en la respuesta de Trello. Código: ' . $response_code . '. Mensaje: ' . $body);
        wp_send_json_error('Error en la respuesta de Trello. Código: ' . $response_code . '. Mensaje: ' . $body);
        return;
    }
    
    $lists = json_decode($body, true);
    
    if (empty($lists) || !is_array($lists)) {
        error_log('No se pudieron obtener las listas o el formato de respuesta es inválido. Respuesta: ' . $body);
        wp_send_json_error('No se pudieron obtener las listas o el formato de respuesta es inválido.');
        return;
    }
    
    error_log('Listas obtenidas exitosamente: ' . print_r($lists, true));
    wp_send_json_success($lists);
}
add_action('wp_ajax_wcw_get_trello_lists', 'wcw_ajax_get_trello_lists');