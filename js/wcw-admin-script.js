jQuery(document).ready(function($) {
    var fieldIndex = $('#wcw-fields .wcw-field').length;

    // Cargar los valores guardados de Trello
    var savedBoardId = wcw_admin_data.saved_board_id;
    var savedListId = wcw_admin_data.saved_list_id;
    var savedBoardName = wcw_admin_data.saved_board_name;
    var savedListName = wcw_admin_data.saved_list_name;

    // Función para actualizar la configuración actual
    function updateCurrentConfig(boardName, listName) {
        var configText = boardName && listName ? 
            "Tablero: " + boardName + ", Lista: " + listName :
            "No se ha seleccionado configuración";
        $('#wcw_trello_current_config').val(configText);
        $('#wcw_trello_board_name').val(boardName);
        $('#wcw_trello_list_name').val(listName);
    }

    // Inicializar la configuración actual
    updateCurrentConfig(savedBoardName, savedListName);

    // Agregar campo al formulario
    $('#wcw-add-field').on('click', function() {
        var newField = `
            <div class="wcw-field">
                <input type="text" name="wcw_fields[${fieldIndex}][name]" placeholder="Nombre del campo">
                <input type="text" name="wcw_fields[${fieldIndex}][label]" placeholder="Etiqueta">
                <select name="wcw_fields[${fieldIndex}][type]">
                    <option value="text">Texto</option>
                    <option value="email">Email</option>
                    <option value="number">Número</option>
                    <option value="textarea">Área de texto</option>
                </select>
                <label>
                    <input type="checkbox" name="wcw_fields[${fieldIndex}][required]">
                    Requerido
                </label>
                <button type="button" class="button wcw-remove-field">Eliminar</button>
            </div>
        `;
        $('#wcw-fields').append(newField);
        fieldIndex++;
    });

    // Eliminar campo del formulario
    $(document).on('click', '.wcw-remove-field', function() {
        $(this).closest('.wcw-field').remove();
    });

    // Cargar tableros de Trello
    $('#wcw_load_trello_boards').on('click', function() {
        console.log('Botón de carga de tableros clickeado');
        var apiKey = $('#wcw_trello_api_key').val();
        var token = $('#wcw_trello_token').val();

        console.log('API Key:', apiKey);
        console.log('Token:', token);

        if (!apiKey || !token) {
            $('#wcw_trello_message').html('<p style="color: red;">Por favor, ingrese la API Key y el Token de Trello.</p>');
            console.log('API Key o Token faltantes');
            return;
        }

        $('#wcw_trello_message').html('<p>Cargando tableros...</p>');

        console.log('Enviando solicitud AJAX para cargar tableros');
        console.log('URL de AJAX:', wcw_admin_data.ajax_url);

        $.ajax({
            url: wcw_admin_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wcw_get_trello_boards',
                api_key: apiKey,
                token: token
            },
            success: function(response) {
                console.log('Respuesta AJAX recibida:', response);
                if (response.success && response.data && response.data.length > 0) {
                    var $boardSelect = $('#wcw_trello_board_select');
                    $boardSelect.empty().append($('<option>', {
                        value: '',
                        text: 'Seleccione un tablero'
                    }));
                    $.each(response.data, function(i, board) {
                        $boardSelect.append($('<option>', {
                            value: board.id,
                            text: board.name
                        }));
                    });
                    if (savedBoardId) {
                        $boardSelect.val(savedBoardId).trigger('change');
                    }
                    $('#wcw_trello_message').html('<p style="color: green;">Tableros cargados exitosamente.</p>');
                } else {
                    var errorMessage = response.data || 'No se encontraron tableros o hubo un error al cargarlos.';
                    $('#wcw_trello_message').html('<p style="color: red;">Error: ' + errorMessage + '</p>');
                    console.error('Error en la respuesta:', response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error AJAX:', textStatus, errorThrown);
                console.error('Respuesta completa:', jqXHR.responseText);
                var errorMessage = 'Error al cargar los tableros: ' + errorThrown;
                $('#wcw_trello_message').html('<p style="color: red;">' + errorMessage + '</p>');
            }
        });
    });

    // Manejar cambio en la selección de tablero
    $('#wcw_trello_board_select').change(function() {
        var boardId = $(this).val();
        var boardName = $(this).find('option:selected').text();
        if (boardId) {
            loadTrelloLists(boardId);
        } else {
            $('#wcw_trello_list_select').empty().append($('<option>', {
                value: '',
                text: 'Seleccione una lista'
            }));
            $('#wcw_trello_message').html('');
        }
        updateCurrentConfig(boardName, '');
    });

    // Función para cargar listas de Trello
    function loadTrelloLists(boardId) {
        $('#wcw_trello_message').html('<p>Cargando listas...</p>');
        $.ajax({
            url: wcw_admin_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wcw_get_trello_lists',
                board_id: boardId
            },
            success: function(response) {
                console.log('Respuesta de listas de Trello:', response);
                if (response.success && response.data && response.data.length > 0) {
                    var $listSelect = $('#wcw_trello_list_select');
                    $listSelect.empty().append($('<option>', {
                        value: '',
                        text: 'Seleccione una lista'
                    }));
                    $.each(response.data, function(i, list) {
                        $listSelect.append($('<option>', {
                            value: list.id,
                            text: list.name
                        }));
                    });
                    if (savedListId) {
                        $listSelect.val(savedListId).trigger('change');
                    }
                    $('#wcw_trello_message').html('<p style="color: green;">Listas cargadas exitosamente.</p>');
                } else {
                    var errorMessage = response.data || 'No se encontraron listas o hubo un error al cargarlas.';
                    $('#wcw_trello_message').html('<p style="color: red;">Error: ' + errorMessage + '</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al cargar listas:', textStatus, errorThrown);
                var errorMessage = 'Error al cargar las listas: ' + errorThrown;
                $('#wcw_trello_message').html('<p style="color: red;">' + errorMessage + '</p>');
            }
        });
    }

    // Manejar cambio en la selección de lista
    $('#wcw_trello_list_select').change(function() {
        var listName = $(this).find('option:selected').text();
        var boardName = $('#wcw_trello_board_select').find('option:selected').text();
        updateCurrentConfig(boardName, listName);
    });

    // Cargar tableros si hay credenciales guardadas
    if ($('#wcw_trello_api_key').val() && $('#wcw_trello_token').val()) {
        $('#wcw_load_trello_boards').trigger('click');
    }
});