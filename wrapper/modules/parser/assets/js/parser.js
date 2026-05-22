// --- MOTOR JAVASCRIPT STATELESS (PARSER EN RAM) ---
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('file-input');
const workspace = document.getElementById('workspace');
const csvTable = document.getElementById('csv-table');
const fileInfo = document.getElementById('file-info');

// Eventos Drag & Drop
dropzone.addEventListener('click', () => fileInput.click());
dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('dragover');
});
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    if (e.dataTransfer.files.length) processFile(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', (e) => {
    if (e.target.files.length) processFile(e.target.files[0]);
});

// --- MOTOR JAVASCRIPT STATELESS (PARSER EN RAM) ---
let currentHeaders = [];
let rawDataRows = []; 
// Estructura en RAM para almacenar los mapas locales que configure el usuario
let profileRulesRAM = {
    Session: {}, Division: {}, Class: {}, Gender: {}, 
    Affil1: {}, Affil2: {}, Affil3: {}
};
let ageValidationEnabled = false;
let startRow = 2;
let endRow = 2;
let previewModeShowAll = false;

// Variables de Estado de Plantillas y Editor de Formato
let isEditorMode = false;
let currentFormatId = null;
let currentFormatName = "";
let dragDropMode = 'csv'; // 'csv' o 'format'



function processFile(file) {
    if (dragDropMode === 'format') {
        if (!file.name.endsWith('.json')) {
            alert('Por favor, selecciona un archivo JSON de formato válido.');
            return;
        }
        const reader = new FileReader();
        reader.onload = async function(e) {
            try {
                const data = JSON.parse(e.target.result);
                if (!data.name || (!data.mappings && !data.rules)) {
                    alert('El archivo JSON no tiene la estructura de un formato válido.');
                    return;
                }
                
                // Entrar en modo editor automáticamente al importar un formato
                isEditorMode = true;
                currentFormatId = null; // Tratado como nuevo hasta que se guarde
                currentFormatName = data.name;
                
                // Actualizar clases de la UI
                document.body.classList.add('editor-active');
                workspace.classList.add('editor-active');
                
                // Mostrar botones de editor
                document.getElementById('editor-badge').style.display = 'inline-block';
                document.getElementById('btn-delete-profile').style.display = 'none'; // Nuevo, no se borra aún
                document.getElementById('btn-exit-editor').style.display = 'inline-block';
                document.getElementById('btn-export').style.display = 'none';
                
                // Cargar datos
                loadFormatFromData(data);
                
                // Ocultar dropzone, mostrar workspace
                document.getElementById('dropzone').style.display = 'none';
                document.getElementById('workspace').style.display = 'block';
                
                // Cambiar info del archivo
                document.getElementById('file-info').innerText = "Editando plantilla importada: " + data.name;
                
                alert("Plantilla de formato importada correctamente en el Editor. Recuerda hacer clic en Guardar para persistirla.");
            } catch (err) {
                alert("Error al procesar el archivo JSON: " + err.message);
            }
        };
        reader.readAsText(file);
    } else {
        if (!file.name.endsWith('.csv')) {
            alert('Por favor, selecciona un archivo CSV válido.');
            return;
        }

        // Si hay una plantilla seleccionada en el select, la cargamos antes
        const selectedFormatId = document.getElementById('format-select').value;
        if (selectedFormatId !== "") {
            fetch(`api.php?action=get_format&id=${selectedFormatId}`)
                .then(res => res.json())
                .then(json => {
                    if (json.status === 'success') {
                        loadFormatFromData(json.data);
                        currentFormatId = json.data.id;
                        currentFormatName = json.data.name;
                    }
                    // Ahora procesamos y renderizamos el CSV
                    readAndRenderCSV(file);
                })
                .catch(err => {
                    console.error("Error al cargar la plantilla pre-seleccionada", err);
                    readAndRenderCSV(file);
                });
        } else {
            readAndRenderCSV(file);
        }
    }
}

function readAndRenderCSV(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        renderRawCSV(text, file.name);

        // Transición de la UI: Ocultar zona de subida, mostrar mesa de trabajo
        document.getElementById('dropzone').style.display = 'none';
        document.getElementById('workspace').style.display = 'block';
        
        // Control de visibilidad de botones de retorno
        document.getElementById('btn-close-csv').style.display = 'inline-block';
        document.getElementById('btn-exit-editor').style.display = 'none';
        
        // Actualizar etiqueta del archivo
        document.getElementById('file-info').innerText = "Archivo: " + file.name;
    };
    reader.readAsText(file);
}

function renderCSVTable() {
    const thead = document.querySelector('#csv-table thead');
    const tbody = document.querySelector('#csv-table tbody');
    if (!thead || !tbody || !rawDataRows || rawDataRows.length === 0) return;

    thead.innerHTML = '';
    tbody.innerHTML = '';

    // Render columns in header
    const headerRow = document.createElement('tr');
    headerRow.innerHTML = `
        <th class="col-abs-index text-center" style="width: 45px;">#</th>
        <th class="col-rel-index text-center" style="width: 65px; color: var(--primary);">Traductor</th>
    `;
    const numCols = (rawDataRows && rawDataRows[0]) ? rawDataRows[0].length : 0;
    for (let idx = 0; idx < numCols; idx++) {
        headerRow.innerHTML += `<th>Columna ${idx + 1}</th>`;
    }
    thead.appendChild(headerRow);

    // Render data rows
    let translatorIndex = 1;
    rawDataRows.forEach((rowData, rowIndex) => {
        const fileRowNumber = rowIndex + 1; // 1-indexed row number in file
        const isSelected = (fileRowNumber >= startRow && fileRowNumber <= endRow);

        if (!isSelected && !previewModeShowAll) {
            // If not selected and we show only selected, skip rendering entirely
            return;
        }

        const tr = document.createElement('tr');
        tr.setAttribute('data-row-index', rowIndex);
        if (!isSelected) {
            tr.classList.add('row-selected-shading');
        }

        // Absolute index
        tr.innerHTML = `<td class="col-abs-index" style="text-align: center; color: #94a3b8; font-weight: 600;">${fileRowNumber}</td>`;
        // Relative translator index
        if (isSelected) {
            tr.innerHTML += `<td class="col-rel-index" style="text-align: center; color: var(--primary); font-weight: 600;">${translatorIndex++}</td>`;
        } else {
            tr.innerHTML += `<td class="col-rel-index" style="text-align: center; color: #94a3b8;">-</td>`;
        }

        rowData.forEach(cell => {
            const safeCell = cell ? cell.replace(/</g, "&lt;").replace(/>/g, "&gt;") : '';
            tr.innerHTML += `<td>${safeCell}</td>`;
        });
        tbody.appendChild(tr);
    });

    // Actualizar dinámicamente los textos descriptivos de los desplegables basados en el rango
    updateIanseoTargetsDropdowns();

    // Refresh colors and highlights
    if (typeof updateUIState === 'function') updateUIState();
}

function updateIanseoTargetsDropdowns() {
    const targetSelects = document.querySelectorAll('.target-field');
    const firstAvailableRow = rawDataRows && rawDataRows[startRow - 1] ? rawDataRows[startRow - 1] : null;

    targetSelects.forEach(select => {
        const currentVal = select.value;
        const fieldName = select.getAttribute('data-field-name');

        // Reconstruimos el contenido de manera dinámica
        if (fieldName === "Session") {
            select.innerHTML = '<option value="">-- Ignorar / Fijo --</option>';
        } else {
            select.innerHTML = '<option value="">-- Ignorar --</option>';
        }

        const numCols = (rawDataRows && rawDataRows[0]) ? rawDataRows[0].length : 0;
        for (let idx = 0; idx < numCols; idx++) {
            let cellText = "";
            if (firstAvailableRow && firstAvailableRow[idx] !== undefined) {
                cellText = firstAvailableRow[idx].trim();
            }
            const label = cellText ? `Columna ${idx + 1}: "${cellText}"` : `Columna ${idx + 1}`;
            select.innerHTML += `<option value="${idx}">${label}</option>`;
        }

        select.value = currentVal;
    });
}

function renderRawCSV(csvText, fileName) {
    const firstLine = csvText.slice(0, csvText.indexOf('\n'));
    const delimiter = (firstLine.split(';').length > firstLine.split(',').length) ? ';' : ',';
    const lines = csvText.split(/\r\n|\n/).filter(line => line.trim() !== '');

    if (lines.length === 0) return;

    // Guardamos estado global en RAM
    currentHeaders = parseCSVLine(lines[0], delimiter);
    rawDataRows = [];
    for (let i = 0; i < lines.length; i++) {
        rawDataRows.push(parseCSVLine(lines[i], delimiter));
    }

    // Normalizar filas para que todas tengan exactamente el número máximo de columnas detectado
    if (rawDataRows.length > 0) {
        const maxCols = Math.max(...rawDataRows.map(row => row.length));
        for (let i = 0; i < rawDataRows.length; i++) {
            while (rawDataRows[i].length < maxCols) {
                rawDataRows[i].push("");
            }
        }
    }

    // Inicializar límites de Fila Inicial y Final
    startRow = 2; // Por defecto asumiendo que 1 es cabecera
    if (rawDataRows.length < 2) {
        startRow = 1; // Si tiene una sola fila
    }
    endRow = rawDataRows.length;

    // Configurar los campos numéricos de la UI
    const startInput = document.getElementById('start-row');
    const endInput = document.getElementById('end-row');
    if (startInput) {
        startInput.value = startRow;
        startInput.max = rawDataRows.length;
    }
    if (endInput) {
        endInput.value = endRow;
        endInput.max = rawDataRows.length;
    }

    // Pintar información técnica
    document.getElementById('file-info').innerHTML = 
        `📁 <strong>${fileName}</strong> | Filas: <strong>${rawDataRows.length}</strong> | Delimitador: <strong>"${delimiter}"</strong>`;

    // Render table
    renderCSVTable();

    // 2. POBLAR DESPLEGABLES IANSEO (¡Aquí ocurre la magia de la conexión!)
    populateIanseoTargets();
}

// ============================================================================
// --- CONTROLADORES CRUD DE FORMATOS (PERSISTENCIA Y EDICIÓN INVERSA) ---
// ============================================================================

async function loadFormatList() {
    try {
        const res = await fetch('api.php?action=list_formats');
        const json = await res.json();
        if (json.status === 'success') {
            const select = document.getElementById('format-select');
            if (select) {
                select.innerHTML = '<option value="">-- Sin plantilla (Empezar en blanco) --</option>';
                json.data.forEach(fmt => {
                    select.innerHTML += `<option value="${fmt.id}">${fmt.name}</option>`;
                });
            }
        }
    } catch(err) {
        console.error("Error al listar formatos:", err);
    }
}

function loadFormatFromData(formatData) {
    // 1. Establecer variables de estado
    currentFormatId = formatData.id || null;
    currentFormatName = formatData.name || "";
    
    // 2. Limpiar RAM
    profileRulesRAM = {
        Session: {}, Division: {}, Class: {}, Gender: {}, 
        Affil1: {}, Affil2: {}, Affil3: {}
    };
    
    // 3. Limpiar selectores de columnas
    document.querySelectorAll('.target-field').forEach(select => {
        select.value = "";
    });
    
    // 4. Cargar mapeos en selectores
    if (Array.isArray(formatData.mappings)) {
        formatData.mappings.forEach(map => {
            const select = document.querySelector(`.target-field[data-field-name="${map.ianseo_field}"]`);
            if (select) {
                select.value = map.csv_column_index;
                
                // Habilitar botón de engranaje (reglas) si corresponde
                const gearBtn = document.querySelector(`.map-trigger[data-field-name="${map.ianseo_field}"]`);
                if (gearBtn) {
                    gearBtn.disabled = false;
                    gearBtn.style.opacity = "1";
                    gearBtn.style.cursor = "pointer";
                }
            }
        });
    }
    
    // 5. Cargar reglas en profileRulesRAM
    if (Array.isArray(formatData.rules)) {
        formatData.rules.forEach(rule => {
            const field = rule.ianseo_field;
            
            // Comprobar si es un mapeo booleano
            const select = document.querySelector(`.target-field[data-field-name="${field}"]`);
            if (select && select.getAttribute('data-type') === 'boolean-mapping') {
                if (rule.input_value === 'triggers') {
                    profileRulesRAM[field] = { triggers: rule.output_value };
                }
            } else if (field === 'Class') {
                let ageObj = {};
                try {
                    ageObj = JSON.parse(rule.secondary_output);
                } catch(e) {
                    ageObj = { ageCorrespondMin: 18, ageCorrespondMax: 50, ageAllowedMin: 18, ageAllowedMax: 50 };
                }
                profileRulesRAM.Class[rule.input_value] = {
                    out: rule.output_value,
                    ageCorrespondMin: ageObj.ageCorrespondMin !== undefined ? ageObj.ageCorrespondMin : 18,
                    ageCorrespondMax: ageObj.ageCorrespondMax !== undefined ? ageObj.ageCorrespondMax : 50,
                    ageAllowedMin: ageObj.ageAllowedMin !== undefined ? ageObj.ageAllowedMin : 18,
                    ageAllowedMax: ageObj.ageAllowedMax !== undefined ? ageObj.ageAllowedMax : 50
                };
            } else if (profileRulesRAM[field]) {
                profileRulesRAM[field][rule.input_value] = {
                    out: rule.output_value,
                    secondary: rule.secondary_output || ""
                };
            }
        });
    }
    
    // 6. Actualizar indicadores de engranaje (verde si tiene datos)
    document.querySelectorAll('.map-trigger').forEach(gearBtn => {
        const fieldName = gearBtn.getAttribute('data-field-name');
        if (fieldName && profileRulesRAM[fieldName]) {
            const hasData = Object.keys(profileRulesRAM[fieldName]).length > 0;
            gearBtn.style.background = hasData ? "#dcfce7" : "#f1f5f9";
        }
    });

    if (typeof updateUIState === 'function') updateUIState();
    
    // Si estamos en modo editor, actualizar la simulación dinámica
    if (isEditorMode) {
        generateDummyCSV();
    }
}

function generateDummyCSV() {
    let maxIdx = -1;
    const mappingsList = [];
    document.querySelectorAll('.target-field').forEach(select => {
        const val = parseInt(select.value);
        if (!isNaN(val) && val >= 0) {
            if (val > maxIdx) maxIdx = val;
            mappingsList.push({ field: select.getAttribute('data-field-name'), idx: val });
        }
    });

    const expColsInput = document.getElementById('editor-expected-cols');
    let expCols = expColsInput ? parseInt(expColsInput.value) : 22;
    if (isNaN(expCols) || expCols < 5) expCols = 22;

    if (maxIdx >= expCols) {
        expCols = maxIdx + 1;
        if (expColsInput) {
            expColsInput.value = expCols;
        }
    }

    const numCols = expCols;
    
    // Generamos 4 filas de datos dummy interactivos
    rawDataRows = [];
    const dummyNames = ["Alejandro", "Maria", "Carlos", "Lucia"];
    const dummyLastNames = ["Olivan", "Garcia", "Fernandez", "Rodriguez"];
    const dummyLicencias = ["98765", "12345", "54321", "67890"];
    
    for (let r = 0; r < 4; r++) {
        const row = [];
        for (let c = 0; c < numCols; c++) {
            const mapped = mappingsList.find(m => m.idx === c);
            if (mapped) {
                const field = mapped.field;
                const rulesKey = field.replace("Code", ""); // Affil1Code -> Affil1
                const rules = profileRulesRAM[rulesKey];
                
                if (rules && typeof rules === 'object' && !rules.triggers) {
                    const keys = Object.keys(rules);
                    if (keys.length > 0) {
                        row.push(keys[r % keys.length]);
                    } else {
                        row.push(`[Valor ${field}]`);
                    }
                } else if (field === 'Bib') {
                    row.push(dummyLicencias[r]);
                } else if (field === 'Name') {
                    row.push(dummyNames[r]);
                } else if (field === 'LastName') {
                    row.push(dummyLastNames[r]);
                } else if (field === 'DOB') {
                    row.push(`199${r}-05-15`);
                } else if (field === 'Subclass') {
                    row.push(r % 2 === 0 ? "J" : "");
                } else {
                    row.push(`Ejemplo ${field}`);
                }
            } else {
                row.push("");
            }
        }
        rawDataRows.push(row);
    }
    
    // Sincronizar límites de fila
    startRow = 1;
    endRow = rawDataRows.length;
    
    const startInput = document.getElementById('start-row');
    const endInput = document.getElementById('end-row');
    if (startInput) startInput.value = startRow;
    if (endInput) endInput.value = endRow;
    
    renderCSVTable();
}

function serializeMappings() {
    const mappings = [];
    document.querySelectorAll('.target-field').forEach(select => {
        const val = select.value;
        if (val !== "") {
            mappings.push({
                ianseo_field: select.getAttribute('data-field-name'),
                csv_column_index: parseInt(val),
                process_mode: select.getAttribute('data-type') || 'passthrough'
            });
        }
    });
    return mappings;
}

function serializeRules() {
    const rules = [];
    const mappingFields = ['Session', 'Division', 'Class', 'Gender', 'Affil1', 'Affil2', 'Affil3'];
    mappingFields.forEach(field => {
        const dict = profileRulesRAM[field];
        if (dict && typeof dict === 'object') {
            if (dict.triggers) {
                rules.push({
                    ianseo_field: field,
                    input_value: 'triggers',
                    output_value: dict.triggers,
                    secondary_output: null
                });
            } else {
                Object.keys(dict).forEach(key => {
                    const val = dict[key];
                    if (field === 'Class') {
                        rules.push({
                            ianseo_field: 'Class',
                            input_value: key,
                            output_value: val.out,
                            secondary_output: JSON.stringify({
                                ageCorrespondMin: val.ageCorrespondMin,
                                ageCorrespondMax: val.ageCorrespondMax,
                                ageAllowedMin: val.ageAllowedMin,
                                ageAllowedMax: val.ageAllowedMax
                            })
                        });
                    } else {
                        rules.push({
                            ianseo_field: field,
                            input_value: key,
                            output_value: val.out,
                            secondary_output: val.secondary || null
                        });
                    }
                });
            }
        }
    });
    return rules;
}

function enterEditorMode(formatName, formatId = null) {
    isEditorMode = true;
    currentFormatId = formatId;
    currentFormatName = formatName;
    
    // Clases CSS
    document.body.classList.add('editor-active');
    workspace.classList.add('editor-active');
    
    // Visibilidad de elementos del editor
    document.getElementById('editor-badge').style.display = 'inline-block';
    document.getElementById('editor-cols-wrapper').style.display = 'flex';
    document.getElementById('btn-delete-profile').style.display = formatId ? 'inline-block' : 'none';
    document.getElementById('btn-exit-editor').style.display = 'inline-block';
    document.getElementById('btn-close-csv').style.display = 'none';
    document.getElementById('btn-export').style.display = 'none';
    
    // Transición UI
    document.getElementById('dropzone').style.display = 'none';
    document.getElementById('workspace').style.display = 'block';
    
    // Etiqueta informativa
    document.getElementById('file-info').innerText = "Editando plantilla: " + formatName;
    
    // Habilitar todos los selectores de destino
    document.querySelectorAll('.target-field').forEach(select => {
        select.disabled = false;
    });
    
    // Habilitar engranajes
    document.querySelectorAll('.map-trigger').forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = "1";
        btn.style.cursor = "pointer";
    });
    
    // Sincronizar selectores tri-estado
    document.querySelectorAll('.event-mode-select').forEach(sel => {
        sel.value = "mapping";
        const block = sel.closest('.event-block');
        if (block) {
            const mappingRow = block.querySelector('.event-mapping-row');
            if (mappingRow) mappingRow.style.display = 'flex';
        }
    });
    
    document.querySelectorAll('.affil-block').forEach(block => {
        const selects = block.querySelectorAll('.target-field');
        selects.forEach(s => s.disabled = false);
        const codeSelect = block.querySelector('.affil-code');
        if (codeSelect) {
            const nextModule = parseInt(codeSelect.getAttribute('data-module')) + 1;
            const nextBlock = document.querySelector(`.affil-block[data-module="${nextModule}"]`);
            if (nextBlock) {
                nextBlock.querySelectorAll('.target-field').forEach(s => s.disabled = false);
            }
        }
    });
    
    // Si es un formato nuevo (id null), inicializar RAM y selects vacíos
    if (!formatId) {
        profileRulesRAM = {
            Session: {}, Division: {}, Class: {}, Gender: {}, 
            Affil1: {}, Affil2: {}, Affil3: {}
        };
        document.querySelectorAll('.target-field').forEach(select => {
            select.value = "";
        });
        document.querySelectorAll('.map-trigger').forEach(btn => {
            btn.style.background = "#f1f5f9";
        });
    }

    // Generar la tabla dummy
    generateDummyCSV();
}

function exitEditorMode() {
    isEditorMode = false;
    currentFormatId = null;
    currentFormatName = "";
    
    document.body.classList.remove('editor-active');
    workspace.classList.remove('editor-active');
    
    document.getElementById('editor-badge').style.display = 'none';
    document.getElementById('editor-cols-wrapper').style.display = 'none';
    const expColsInput = document.getElementById('editor-expected-cols');
    if (expColsInput) expColsInput.value = 22;
    document.getElementById('btn-delete-profile').style.display = 'none';
    document.getElementById('btn-exit-editor').style.display = 'none';
    document.getElementById('btn-close-csv').style.display = 'none';
    document.getElementById('btn-export').style.display = 'inline-block';
    
    document.getElementById('workspace').style.display = 'none';
    document.getElementById('dropzone').style.display = 'flex';
    document.getElementById('file-info').innerText = "";
    
    fileInput.value = "";
    rawDataRows = [];
    currentHeaders = [];
    profileRulesRAM = {
        Session: {}, Division: {}, Class: {}, Gender: {}, 
        Affil1: {}, Affil2: {}, Affil3: {}
    };
    
    document.getElementById('format-select').value = "";
    loadFormatList();
}

function populateIanseoTargets() {
    const targetSelects = document.querySelectorAll('.target-field');

    // Primero actualizamos las opciones dinámicas de los dropdowns
    updateIanseoTargetsDropdowns();

    // Luego inicializamos el estado disabled/enabled
    targetSelects.forEach(select => {
        if (isEditorMode) {
            select.disabled = false;
            return;
        }
        const type = select.getAttribute('data-type');
        if (type && (type.startsWith('passthrough') || type === 'mapping')) {
            select.disabled = false;
        }

        const moduleNum = select.getAttribute('data-module');
        if (moduleNum && moduleNum > 1) {
            select.disabled = true;
        } else if (moduleNum === "1") {
            select.disabled = false;
            const nameSelect = document.querySelector('.target-field[data-field-name="Affil1Name"]');
            if (nameSelect) nameSelect.disabled = false;
        }
    });

    // 🚀 EL PRIMER DISPARO: Forzamos la evaluación visual inicial
    updateUIState();
}

function parseCSVLine(line, delimiter) {
    return line.split(delimiter).map(cell => cell.trim().replace(/^["']|["']$/g, ''));
}

// --- CONTROLADORES DE INTERFAZ (ESQUEMA 21 CAMPOS) ---

// Escuchar cambios en todos los selectores de destino Ianseo
//document.querySelectorAll('.target-field').forEach(select => {
//    select.addEventListener('change', function() {
//        updateTableHeaders();
//        validateDateColumns();
//        // Aquí llamaremos también a la validación de cascada de afiliaciones más adelante
//    });
//});
document.querySelectorAll('.target-field').forEach(select => {
    select.addEventListener('change', updateUIState);
});


// --- CONTROLADORES DE INTERFAZ UNIFICADOS ---

// Escuchar cambios en todos los selectores de destino Ianseo
document.querySelectorAll('.target-field').forEach(select => {
    select.addEventListener('change', function() {
        updateUIState();
        if (isEditorMode) {
            generateDummyCSV();
        }
    });
});

// --- CONTROLADOR DE EVENTOS TRI-ESTADO (Campos 6-10) ---
document.querySelectorAll('.event-mode-select').forEach(modeSelect => {
    modeSelect.addEventListener('change', function() {
        // Encontrar la fila de mapeo hija dentro de este bloque
        const block = this.closest('.event-block');
        const mappingRow = block.querySelector('.event-mapping-row');
        const targetSelect = block.querySelector('.target-field');

        if (this.value === 'mapping') {
            // Mostrar controles de columna y rueda
            mappingRow.style.display = 'flex';
            targetSelect.disabled = false;
        } else {
            // Ocultar controles y deseleccionar columna (limpia la tabla izquierda)
            mappingRow.style.display = 'none';
            targetSelect.value = ""; 
            targetSelect.disabled = true;
            if (typeof updateUIState === 'function') updateUIState();
        }
    });
});

// Llamar a esta función también al final de populateIanseoTargets() para inicializar los rojos
// (Añade updateUIState(); justo antes de cerrar la función populateIanseoTargets)

function updateUIState() {
    const ths = csvTable.querySelectorAll('thead th');
    const tbody = csvTable.querySelector('tbody');
    const rows = tbody ? tbody.querySelectorAll('tr') : [];

    // 1. RESETEO TOTAL: Limpiar cabeceras, celdas y botones
    const numCols = (rawDataRows && rawDataRows[0]) ? rawDataRows[0].length : 0;
    for (let idx = 0; idx < numCols; idx++) {
        const th = ths[idx + 2];
        if (th) {
            th.innerHTML = `Columna ${idx + 1}`;
            th.classList.remove('th-assigned');
        }
        // Limpiamos colores de todas las celdas de esta columna
        rows.forEach(row => {
            const td = row.querySelectorAll('td')[idx + 2];
            if (td) {
                td.style.backgroundColor = '';
                td.removeAttribute('title');
                td.classList.remove('cell-error-class');
            }
        });
    }

    let allRequiredAssigned = true;
    let hasAgeValidationError = false;
    let eventYear = null;

    // 2. SINCRONIZAR OBLIGATORIEDAD DEL CAMPO 16 (DOB) E #event-date
    const dobSelect = document.querySelector('.target-field[data-field-name="DOB"]');
    const eventDateInput = document.getElementById('event-date');

    if (ageValidationEnabled) {
        if (dobSelect) dobSelect.setAttribute('data-required', 'true');
        if (eventDateInput) {
            if (!eventDateInput.value) {
                eventDateInput.classList.add('required-pending');
                allRequiredAssigned = false;
            } else {
                eventDateInput.classList.remove('required-pending');
                eventYear = new Date(eventDateInput.value).getFullYear();
            }
        }
    } else {
        if (dobSelect) dobSelect.setAttribute('data-required', 'false');
        if (eventDateInput) {
            eventDateInput.classList.remove('required-pending');
        }
    }

    // 3. EVALUAR CONTROLES IANSEO (Pintar rojos en UI y verdes en Tabla)
    document.querySelectorAll('.target-field').forEach(select => {
        const colIdx = select.value;
        const isRequired = select.getAttribute('data-required') === "true";
        const fieldName = select.getAttribute('data-field-name');
        const fieldType = select.getAttribute('data-type');

        // Control de Rojos en campos obligatorios e integración de sesión fija
        if (isRequired) {
            let isPending = false;
            if (fieldName === "Session") {
                const sessionFixedInput = document.getElementById('session-fixed-value');
                const sessionGear = document.querySelector('.btn-gear[data-field-name="Session"]');

                if (colIdx === "") {
                    // Mostrar input de sesión fija y deshabilitar engranaje
                    if (sessionFixedInput) sessionFixedInput.style.display = 'block';
                    if (sessionGear) sessionGear.disabled = true;

                    const val = sessionFixedInput ? parseInt(sessionFixedInput.value) : 0;
                    if (isNaN(val) || val < 1) {
                        isPending = true;
                        if (sessionFixedInput) sessionFixedInput.classList.add('required-pending');
                    } else {
                        if (sessionFixedInput) sessionFixedInput.classList.remove('required-pending');
                    }
                } else {
                    // Ocultar input de sesión fija y habilitar engranaje
                    if (sessionFixedInput) {
                        sessionFixedInput.style.display = 'none';
                        sessionFixedInput.classList.remove('required-pending');
                    }
                    if (sessionGear) sessionGear.disabled = false;
                }
            } else {
                if (colIdx === "") {
                    isPending = true;
                }
            }

            if (isPending) {
                select.classList.add('required-pending');
                allRequiredAssigned = false;
            } else {
                select.classList.remove('required-pending');
            }
        }

        // Si hay una columna asignada, procesamos la tabla cruda
        if (colIdx !== "") {
            const targetCol = parseInt(colIdx) + 2;
            const th = ths[targetCol];

            // Pintar Cabecera
            if (th) {
                th.innerHTML = `<strong>${th.innerText}</strong> <br><span style="color:var(--primary); font-size:0.75rem;">[${fieldName}]</span>`;
                th.classList.add('th-assigned');
            }

            // Pintar Celdas de la columna asignada
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;

            rawDataRows.forEach((rowData, rowIndex) => {
                const htmlRow = tbody.querySelector(`tr[data-row-index="${rowIndex}"]`);
                if (!htmlRow) return;

                const cellValue = rowData[colIdx] ? rowData[colIdx].trim() : '';
                const td = htmlRow.querySelectorAll('td')[targetCol];
                if (!td) return;

                if (fieldType === 'passthrough-date') {
                    // Lógica especial para fechas
                    if (cellValue !== "" && !dateRegex.test(cellValue)) {
                        td.style.backgroundColor = '#fed7aa'; // Naranja alerta
                        td.setAttribute('title', 'Formato incorrecto. Ianseo espera YYYY-MM-DD');
                    } else if (cellValue !== "") {
                        td.style.backgroundColor = '#f0fdf4'; // Verde OK
                    }
                } else {
                    // Resto de columnas asignadas (Passthrough o Mapping en uso) se asumen OK
                    if (cellValue !== "") {
                        // Evitamos pisar el color si dos campos apuntan a la misma columna por error
                        if (!td.style.backgroundColor || td.style.backgroundColor === 'rgb(240, 253, 244)') {
                            td.style.backgroundColor = '#f0fdf4'; // Verde suave de éxito
                        }
                    }
                }
            });
        }
    });

    // 4. LÓGICA DE VALIDACIÓN DE EDAD POR REGLAMENTO (Si el toggle está activo)
    if (ageValidationEnabled && eventYear !== null) {
        const classSelect = document.querySelector('.target-field[data-field-name="Class"]');
        const activeDobSelect = document.querySelector('.target-field[data-field-name="DOB"]');
        const genderSelect = document.querySelector('.target-field[data-field-name="Gender"]');

        const classCol = classSelect ? classSelect.value : "";
        const dobCol = activeDobSelect ? activeDobSelect.value : "";
        const genderCol = genderSelect ? genderSelect.value : "";

        if (classCol !== "" && dobCol !== "") {
            rawDataRows.forEach((rowData, rowIndex) => {
                const htmlRow = tbody.querySelector(`tr[data-row-index="${rowIndex}"]`);
                if (!htmlRow) return;

                const rawDob = rowData[dobCol] ? rowData[dobCol].trim() : '';
                const rawClass = rowData[classCol] ? rowData[classCol].trim() : '';
                const rawGender = genderCol !== "" && rowData[genderCol] ? rowData[genderCol].trim() : '';

                const tdClass = htmlRow.querySelectorAll('td')[parseInt(classCol) + 2];
                const tdDob = htmlRow.querySelectorAll('td')[parseInt(dobCol) + 2];

                if (!tdClass) return;

                // Resetear estado anterior
                tdClass.classList.remove('cell-error-class');

                // 1. Extraer año de nacimiento a 31 de diciembre
                let birthYear = null;
                const dateMatch = rawDob.match(/^(\d{4})[-/](\d{2})[-/](\d{2})$/);
                if (dateMatch) {
                    birthYear = parseInt(dateMatch[1]);
                } else {
                    const yearMatch = rawDob.match(/^\d{4}$/);
                    if (yearMatch) birthYear = parseInt(yearMatch[0]);
                }

                if (!birthYear || isNaN(birthYear)) {
                    if (tdDob) {
                        tdDob.style.backgroundColor = '#fed7aa';
                        tdDob.setAttribute('title', 'Fecha de nacimiento requerida o formato incorrecto para validar edad');
                    }
                    tdClass.classList.add('cell-error-class');
                    tdClass.setAttribute('title', 'No se puede validar la clase porque el arquero no tiene un año de nacimiento válido.');
                    hasAgeValidationError = true;
                    return;
                }

                const age = eventYear - birthYear;

                // 2. Normalizar género (heurística/mapeo RAM)
                let gender = "M";
                if (rawGender) {
                    const genderRules = profileRulesRAM.Gender || {};
                    if (genderRules[rawGender]) {
                        gender = genderRules[rawGender].out;
                    } else {
                        const lowerG = rawGender.toLowerCase();
                        if (lowerG.startsWith('w') || lowerG.startsWith('f') || lowerG.includes('mujer') || lowerG.includes('dama')) {
                            gender = "W";
                        }
                    }
                }

                // 3. Cargar regla de la clase declarada
                const classRules = profileRulesRAM.Class || {};
                const rule = classRules[rawClass];

                if (!rule) {
                    tdClass.classList.add('cell-error-class');
                    tdClass.setAttribute('title', `La clase '${rawClass}' no tiene una regla de edad configurada en el mapa.`);
                    hasAgeValidationError = true;
                    return;
                }

                const ageCorrespondMin = rule.ageCorrespondMin !== undefined ? parseInt(rule.ageCorrespondMin) : 0;
                const ageCorrespondMax = rule.ageCorrespondMax !== undefined ? parseInt(rule.ageCorrespondMax) : 99;
                const ageAllowedMin = rule.ageAllowedMin !== undefined ? parseInt(rule.ageAllowedMin) : 0;
                const ageAllowedMax = rule.ageAllowedMax !== undefined ? parseInt(rule.ageAllowedMax) : 99;
                const outCode = rule.out !== undefined ? rule.out : "";

                // 4. Buscar la clase teórica / natural del competidor
                let theoreticalClassKey = null;
                let theoreticalClassRule = null;
                for (const key in classRules) {
                    const r = classRules[key];
                    const cMin = parseInt(r.ageCorrespondMin);
                    const cMax = parseInt(r.ageCorrespondMax);
                    if (!isNaN(cMin) && !isNaN(cMax) && age >= cMin && age <= cMax) {
                        theoreticalClassKey = key;
                        theoreticalClassRule = r;
                        break;
                    }
                }

                // 5. Auditar excepciones de negocio
                
                // A. Edad fuera del límite permitido
                if (age < ageAllowedMin || age > ageAllowedMax) {
                    tdClass.classList.add('cell-error-class');
                    tdClass.setAttribute('title', `Inconsistencia: La edad deportiva del competidor (${age} años) está fuera del rango permitido para '${rawClass}' [${ageAllowedMin} - ${ageAllowedMax} años].`);
                    hasAgeValidationError = true;
                    return;
                }

                if (theoreticalClassRule) {
                    const theoreticalOut = theoreticalClassRule.out !== undefined ? theoreticalClassRule.out : "";
                    
                    // Identificar si teóricamente corresponde a Senior o Veterano
                    const isTheoreticalSenior = (theoreticalOut === "");
                    const isTheoreticalVeterano = (theoreticalOut === "50" || theoreticalClassKey.toLowerCase().includes('veteran') || theoreticalOut.includes('50'));

                    // B. Restricción Senior
                    if (isTheoreticalSenior) {
                        if (outCode !== "") {
                            tdClass.classList.add('cell-error-class');
                            tdClass.setAttribute('title', `Excepción de Reglamento: Los arqueros con edad correspondiente a Senior (${age} años) no pueden competir en ninguna otra categoría.`);
                            hasAgeValidationError = true;
                            return;
                        }
                    }

                    // C. Restricción Veterano
                    if (isTheoreticalVeterano) {
                        if (outCode !== "50" && outCode !== "") {
                            tdClass.classList.add('cell-error-class');
                            tdClass.setAttribute('title', `Excepción de Reglamento: Los arqueros de categoría Veterano (${age} años) únicamente pueden inscribirse en su clase nativa (Veterano) o en Senior.`);
                            hasAgeValidationError = true;
                            return;
                        }
                    }
                }

                // Si todo pasa con éxito
                tdClass.style.backgroundColor = '#f0fdf4';
                tdClass.setAttribute('title', `Validación de edad deportiva exitosa: ${age} años. Clase '${rawClass}' autorizada.`);
            });
        }
    }

    // 5. GESTIÓN DEL BOTÓN DE EXPORTACIÓN
    const btnExport = document.getElementById('btn-export');
    if (btnExport) {
        // Solo habilitamos si los obligatorios están y no hay errores de validación de edad
        if (allRequiredAssigned && rawDataRows.length > 0 && !hasAgeValidationError) {
            btnExport.disabled = false;
            btnExport.style.opacity = '1';
            btnExport.style.cursor = 'pointer';
        } else {
            btnExport.disabled = true;
            btnExport.style.opacity = '0.5';
            btnExport.style.cursor = 'not-allowed';
        }
    }
}


// --- LÓGICA DEL MODAL DE MAPEO (ESTÁNDAR Y BOOLEANO) ---
let currentMappingField = null; 
let currentMappingType = null; // Detectará si es 'mapping' o 'boolean-mapping'
const mappingModal = document.getElementById('mapping-modal');
const modalTitle = document.getElementById('modal-title');
const btnCloseModal = document.getElementById('btn-close-modal');

// 1. ABRIR MODAL
document.querySelectorAll('.map-trigger').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        currentMappingField = this.getAttribute('data-field-name');
        if (!currentMappingField) return;

        // Averiguamos el tipo de campo
        const selector = document.querySelector(`.target-field[data-field-name="${currentMappingField}"]`);
        currentMappingType = selector ? selector.getAttribute('data-type') : 'mapping';

        modalTitle.innerText = `Configuración: ${currentMappingField}`;
        
        // El botón de auto-poblar solo tiene sentido en mapeos estándar
        const btnAuto = document.getElementById('btn-autopopulate');
        if (currentMappingType === 'boolean-mapping') {
            btnAuto.style.display = 'none'; // Lo ocultamos para eventos
        } else {
            btnAuto.style.display = 'block';
            if (!selector || selector.value === "") {
                btnAuto.disabled = true;
                btnAuto.innerText = "⚠️ Selecciona una columna origen primero";
                btnAuto.style.opacity = "0.5";
            } else {
                btnAuto.disabled = false;
                btnAuto.innerText = "✨ Pre-poblar claves detectadas";
                btnAuto.style.opacity = "1";
            }
        }

        renderModalRules();
        mappingModal.style.display = 'flex';
    });
});

// 2. CERRAR MODAL
btnCloseModal.addEventListener('click', closeModal);
mappingModal.addEventListener('click', function(e) {
    if (e.target === mappingModal) closeModal();
});

function closeModal() {
    mappingModal.style.display = 'none';
    currentMappingField = null;
    currentMappingType = null;
}

// 3. AUTO-POBLAR (Solo para estándar)
document.getElementById('btn-autopopulate').addEventListener('click', function() {
    if (this.disabled || currentMappingType === 'boolean-mapping') return;
    const selector = document.querySelector(`.target-field[data-field-name="${currentMappingField}"]`);
    if (!selector || selector.value === "") return;
    
    // Solo tener en cuenta el rango de filas seleccionadas para la traducción
    const rowsInRange = rawDataRows.slice(startRow - 1, endRow);
    const uniqueValues = [...new Set(rowsInRange.map(row => row[selector.value] ? row[selector.value].trim() : ''))].filter(v => v !== "");
    const existingKeys = Array.from(document.querySelectorAll('#modal-rules-container .rule-key')).map(input => input.value.trim());

    // Limpiar la fila de cortesía vacía si es la única y está en blanco
    const allRuleKeys = document.querySelectorAll('#modal-rules-container .rule-key');
    if (allRuleKeys.length === 1 && allRuleKeys[0].value.trim() === "") {
        const wrapper = document.getElementById('rows-wrapper');
        if (wrapper) {
            wrapper.innerHTML = '';
            rowCounter = 0;
        }
    }

    uniqueValues.forEach(val => {
        if (!existingKeys.includes(val)) appendRuleRow(val, "", "");
    });
});

// --- COMPONENTE INTERACTIVO DE DOBLE SLIDER ---
function createDoubleSliderHTML(idPrefix, label, minVal, maxVal, isEmerald) {
    const themeClass = isEmerald ? "slider-emerald" : "slider-violet";
    return `
        <div class="double-slider-wrapper">
            <div class="double-slider-label">
                <span>${label}</span>
                <span id="${idPrefix}-value-display" style="font-weight: 700;">${minVal} - ${maxVal} años</span>
            </div>
            <div class="double-slider-container ${themeClass}" id="${idPrefix}-slider-container">
                <div class="slider-track-bg"></div>
                <div class="slider-track-active" id="${idPrefix}-track-active"></div>
                <input type="range" class="range-min" id="${idPrefix}-min" min="0" max="99" value="${minVal}">
                <input type="range" class="range-max" id="${idPrefix}-max" min="0" max="99" value="${maxVal}">
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 0.4rem; align-items: center;">
                <span style="font-size: 0.75rem; color: #64748b;">Ajuste manual:</span>
                <input type="number" class="slider-num-min" id="${idPrefix}-min-num" min="0" max="99" value="${minVal}" style="width: 50px; font-size: 0.8rem; padding: 0.15rem 0.3rem; text-align: center; border: 1px solid #cbd5e1; border-radius: 4px; outline: none;">
                <span style="font-size: 0.75rem; color: #94a3b8;">a</span>
                <input type="number" class="slider-num-max" id="${idPrefix}-max-num" min="0" max="99" value="${maxVal}" style="width: 50px; font-size: 0.8rem; padding: 0.15rem 0.3rem; text-align: center; border: 1px solid #cbd5e1; border-radius: 4px; outline: none;">
            </div>
        </div>
    `;
}

function initializeDoubleSlider(idPrefix, onChangeCallback) {
    const rangeMin = document.getElementById(`${idPrefix}-min`);
    const rangeMax = document.getElementById(`${idPrefix}-max`);
    const numMin = document.getElementById(`${idPrefix}-min-num`);
    const numMax = document.getElementById(`${idPrefix}-max-num`);
    const trackActive = document.getElementById(`${idPrefix}-track-active`);
    const valueDisplay = document.getElementById(`${idPrefix}-value-display`);

    if (!rangeMin || !rangeMax || !trackActive || !valueDisplay) return;

    function updateSlider() {
        let valMin = parseInt(rangeMin.value) || 0;
        let valMax = parseInt(rangeMax.value) || 0;

        // Forzar restricción de cruce
        if (valMin > valMax) {
            if (this === rangeMin) {
                rangeMin.value = valMax;
                valMin = valMax;
            } else {
                rangeMax.value = valMin;
                valMax = valMin;
            }
        }

        // Actualizar track activo
        const percentLeft = (valMin / 99) * 100;
        const percentWidth = ((valMax - valMin) / 99) * 100;

        trackActive.style.left = percentLeft + "%";
        trackActive.style.width = percentWidth + "%";

        // Actualizar texto
        valueDisplay.innerHTML = `<strong>${valMin} - ${valMax} años</strong>`;

        // Sincronizar inputs numéricos si existen
        if (numMin && numMin !== document.activeElement) numMin.value = valMin;
        if (numMax && numMax !== document.activeElement) numMax.value = valMax;

        if (onChangeCallback) onChangeCallback();
    }

    function handleNumInput() {
        let valMin = parseInt(numMin.value) || 0;
        let valMax = parseInt(numMax.value) || 0;

        // Limitar entre 0 y 99
        if (valMin < 0) valMin = 0; if (valMin > 99) valMin = 99;
        if (valMax < 0) valMax = 0; if (valMax > 99) valMax = 99;

        // Si se cruzan, forzar
        if (valMin > valMax) {
            if (this === numMin) {
                valMin = valMax;
            } else {
                valMax = valMin;
            }
        }

        rangeMin.value = valMin;
        rangeMax.value = valMax;

        updateSlider();
    }

    rangeMin.addEventListener('input', updateSlider);
    rangeMax.addEventListener('input', updateSlider);

    if (numMin && numMax) {
        numMin.addEventListener('input', handleNumInput);
        numMin.addEventListener('change', handleNumInput);
        numMax.addEventListener('input', handleNumInput);
        numMax.addEventListener('change', handleNumInput);
    }

    // Disparar inicialmente para posicionar el track
    updateSlider();
}

// --- AUDITORÍA DE GAPS Y OVERLAPS (EDAD CORRESPONDIENTE) ---
function auditClassAgeRules() {
    const rulesWrapper = document.getElementById('rows-wrapper');
    const warningContainer = document.getElementById('audit-warning-container');
    if (!rulesWrapper || !warningContainer) return;

    let rulesToAudit = [];

    // Recoger valores actuales de los sliders correspondientes de cada fila
    rulesWrapper.querySelectorAll('.rule-row-container').forEach(rowContainer => {
        const keyInput = rowContainer.querySelector('.rule-key');
        const minInput = rowContainer.querySelector('.range-correspond-min');
        const maxInput = rowContainer.querySelector('.range-correspond-max');

        if (keyInput && minInput && maxInput) {
            const key = keyInput.value.trim();
            const min = parseInt(minInput.value);
            const max = parseInt(maxInput.value);

            if (key !== "") {
                rulesToAudit.push({ key: key, min: min, max: max });
            }
        }
    });

    // Limpiar banner de advertencias
    warningContainer.innerHTML = '';
    warningContainer.style.display = 'none';

    if (rulesToAudit.length === 0) return;

    // Ordenar por edad mínima correspondiente
    rulesToAudit.sort((a, b) => a.min - b.min);

    let warnings = [];

    for (let i = 0; i < rulesToAudit.length; i++) {
        let current = rulesToAudit[i];

        if (current.min > current.max) {
            warnings.push(`La clase <strong>"${current.key}"</strong> tiene un rango inconsistente (${current.min} > ${current.max} años).`);
        }

        if (i < rulesToAudit.length - 1) {
            let next = rulesToAudit[i + 1];

            // 1. Overlap (Solapamiento)
            if (current.max >= next.min) {
                warnings.push(`⚠️ <strong>Solapamiento (Overlap):</strong> La edad de <strong>${next.min} a ${current.max} años</strong> se solapa entre las clases naturales de <strong>"${current.key}"</strong> y <strong>"${next.key}"</strong>.`);
            } 
            // 2. Gap (Hueco)
            else if (current.max + 1 < next.min) {
                const gapMin = current.max + 1;
                const gapMax = next.min - 1;
                const gapStr = gapMin === gapMax ? `${gapMin} años` : `${gapMin} a ${gapMax} años`;
                warnings.push(`⚠️ <strong>Hueco sin cubrir (Gap):</strong> La edad de <strong>${gapStr}</strong> queda desamparada entre las clases naturales de <strong>"${current.key}"</strong> y <strong>"${next.key}"</strong>.`);
            }
        }
    }

    if (warnings.length > 0) {
        warningContainer.innerHTML = `
            <div class="audit-alert-banner">
                <div style="font-weight: 700; margin-bottom: 0.25rem;">⚠️ Auditoría de Edades de Clase:</div>
                <ul style="margin: 0; padding-left: 1.25rem;">
                    ${warnings.map(w => `<li>${w}</li>`).join('')}
                </ul>
            </div>
        `;
        warningContainer.style.display = 'block';
    }
}

let rowCounter = 0; // Para generar IDs únicos de sliders en el DOM del modal

// 4. RENDERIZAR EL CONTENIDO SEGÚN TIPO DE CAMPO
function renderModalRules() {
    rowCounter = 0;
    const container = document.getElementById('modal-rules-container');
    container.innerHTML = '';
    const rules = profileRulesRAM[currentMappingField] || {};

    // Inyectar el banner de advertencias de auditoría al inicio del modal
    const auditContainer = document.createElement('div');
    auditContainer.id = "audit-warning-container";
    auditContainer.style.display = "none";
    container.appendChild(auditContainer);

    // MODO A: MAPEO BOOLEANO PARA EVENTOS
    if (currentMappingType === 'boolean-mapping') {
        const triggers = rules.triggers || ""; // Recuperamos la lista si ya existía
        
        container.innerHTML += `
            <div style="margin-bottom: 1rem; color: #475569; font-size: 0.9rem;">
                Introduce los valores (separados por comas) que significan <strong>"Sí, está inscrito"</strong>.<br>
                <span style="font-size: 0.8rem; color: #64748b;">Ejemplo: <code style="background:#f1f5f9; padding:2px 4px; border-radius:3px;">Sí, X, 1, True, Inscrito</code></span>
            </div>
            <input type="text" id="boolean-triggers-input" value="${triggers}" 
                   placeholder="Valores que activan la inscripción..." 
                   style="width: 100%; padding: 0.75rem; border: 1px solid var(--primary); border-radius: 6px; font-size: 1rem; outline: none; box-shadow: 0 0 0 3px #eff6ff;">
            <div style="margin-top: 0.75rem; font-size: 0.8rem; color: #10b981;">
                Cualquier otro valor en el CSV será ignorado (No inscrito).
            </div>
        `;
        // Dar foco automático para máxima comodidad
        setTimeout(() => {
            const el = document.getElementById('boolean-triggers-input');
            if (el) el.focus();
        }, 50);
        return;
    }

    // MODO B: MAPEO ESTÁNDAR / RANGOS DE EDAD
    const isDoubleOutput = currentMappingField.startsWith('Affil');
    const isClassMapping = (currentMappingField === 'Class');

    const header = document.createElement('div');
    header.style = "display: flex; gap: 10px; margin-bottom: 12px; font-weight: 700; font-size: 0.75rem; color: #64748b; text-transform: uppercase;";
    if (isDoubleOutput) {
        header.innerHTML = `<div style="flex:1">Clave Origen (CSV)</div><div style="flex:1">ID Ianseo *</div><div style="flex:1">Nombre Oficial</div><div style="width:28px"></div>`;
    } else if (isClassMapping) {
        header.innerHTML = `<div style="flex:1">Clave Origen (CSV)</div><div style="flex:1">Salida Ianseo *</div><div style="width:140px; text-align:center;">Reglamento</div><div style="width:28px"></div>`;
    } else {
        header.innerHTML = `<div style="flex:1">Clave Origen (CSV)</div><div style="flex:1">Salida Ianseo *</div><div style="width:28px"></div>`;
    }
    container.appendChild(header);

    const rowsWrapper = document.createElement('div');
    rowsWrapper.id = "rows-wrapper";
    container.appendChild(rowsWrapper);

    const keys = Object.keys(rules);
    keys.forEach(key => {
        const rData = rules[key];
        appendRuleRow(key, rData.out, rData.secondary, rData);
    });
    if (keys.length === 0) {
        appendRuleRow("", "", "", null);
    }

    const btnAdd = document.createElement('button');
    btnAdd.type = "button";
    btnAdd.innerText = "+ Añadir fila vacía";
    btnAdd.style = "margin-top: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; color: var(--primary); font-weight: 600; padding: 0.5rem; width: 100%; cursor: pointer; border-radius: 4px; font-size: 0.85rem; transition: all 0.2s;";
    btnAdd.onclick = () => appendRuleRow("", "", "", null);
    container.appendChild(btnAdd);

    // Si es clase, disparar la auditoría inicial de las reglas existentes
    if (isClassMapping) {
        auditClassAgeRules();
    }
}

// 5. INYECTOR DE FILAS (Adaptado para incorporar los sliders en la Clase)
function appendRuleRow(keyVal, outVal, secVal, rData) {
    const wrapper = document.getElementById('rows-wrapper');
    if (!wrapper) return;
    const isDoubleOutput = currentMappingField.startsWith('Affil');
    const isClassMapping = (currentMappingField === 'Class');
    rowCounter++;

    const rowContainer = document.createElement('div');
    rowContainer.className = "rule-row-container";
    rowContainer.style = "margin-bottom: 12px;";

    const mainRow = document.createElement('div');
    mainRow.className = "rule-row";
    mainRow.style = "display: flex; gap: 10px; align-items: center;";

    if (isDoubleOutput) {
        mainRow.innerHTML = `
            <input type="text" value="${keyVal}" class="rule-key" placeholder="Ej: BCN" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="text" value="${outVal}" class="rule-out" placeholder="ID (Ej: 2011)" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="text" value="${secVal}" class="rule-secondary" placeholder="Nombre" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;">
            <button type="button" class="btn-delete-row" style="width:28px; height:28px; border:none; background:#fef2f2; color:#ef4444; border-radius:4px; cursor:pointer; font-weight:bold;">×</button>
        `;
    } else if (isClassMapping) {
        mainRow.innerHTML = `
            <input type="text" value="${keyVal}" class="rule-key" placeholder="Texto CSV" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="text" value="${outVal}" class="rule-out" placeholder="Salida" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;">
            <button type="button" class="btn-toggle-config btn-gear" title="Configurar Edades" style="width: 140px; display: flex; align-items: center; justify-content: center; gap: 4px; font-size: 0.8rem;">
                ⚙️ Configurar Edades
            </button>
            <button type="button" class="btn-delete-row" style="width:28px; height:28px; border:none; background:#fef2f2; color:#ef4444; border-radius:4px; cursor:pointer; font-weight:bold;">×</button>
        `;
    } else {
        mainRow.innerHTML = `
            <input type="text" value="${keyVal}" class="rule-key" placeholder="Texto CSV" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="text" value="${outVal}" class="rule-out" placeholder="Salida" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;">
            <button type="button" class="btn-delete-row" style="width:28px; height:28px; border:none; background:#fef2f2; color:#ef4444; border-radius:4px; cursor:pointer; font-weight:bold;">×</button>
        `;
    }

    rowContainer.appendChild(mainRow);

    // Si es clase, inyectar el panel colapsable del sliders dobles
    if (isClassMapping) {
        const cMin = (rData && rData.ageCorrespondMin !== undefined) ? rData.ageCorrespondMin : 18;
        const cMax = (rData && rData.ageCorrespondMax !== undefined) ? rData.ageCorrespondMax : 49;
        const aMin = (rData && rData.ageAllowedMin !== undefined) ? rData.ageAllowedMin : 0;
        const aMax = (rData && rData.ageAllowedMax !== undefined) ? rData.ageAllowedMax : 99;

        const configPanel = document.createElement('div');
        configPanel.className = "class-age-config-panel";
        configPanel.style.display = "none"; // Oculto por defecto

        const idCorrespond = `class-age-correspond-${rowCounter}`;
        const idAllowed = `class-age-allowed-${rowCounter}`;

        configPanel.innerHTML = `
            <div style="font-weight: 700; font-size: 0.8rem; margin-bottom: 0.5rem; color: var(--primary);">Configuración de Reglamento de Edad:</div>
            ${createDoubleSliderHTML(idCorrespond, "Rango de Edad Correspondiente (Clase Natural):", cMin, cMax, true)}
            ${createDoubleSliderHTML(idAllowed, "Rango de Edad Permitida / Elegible (Inscripción):", aMin, aMax, false)}
            
            <input type="hidden" class="range-correspond-min" id="${idCorrespond}-min-val" value="${cMin}">
            <input type="hidden" class="range-correspond-max" id="${idCorrespond}-max-val" value="${cMax}">
            <input type="hidden" class="range-allowed-min" id="${idAllowed}-min-val" value="${aMin}">
            <input type="hidden" class="range-allowed-max" id="${idAllowed}-max-val" value="${aMax}">
        `;

        rowContainer.appendChild(configPanel);

        // Control del colapso interactivo
        const btnToggle = mainRow.querySelector('.btn-toggle-config');
        btnToggle.onclick = () => {
            const isVisible = (configPanel.style.display === "block");
            configPanel.style.display = isVisible ? "none" : "block";
            btnToggle.classList.toggle('active', !isVisible);
        };

        // Inicializar los sliders interactivos
        setTimeout(() => {
            initializeDoubleSlider(idCorrespond, () => {
                const minVal = document.getElementById(`${idCorrespond}-min`).value;
                const maxVal = document.getElementById(`${idCorrespond}-max`).value;
                document.getElementById(`${idCorrespond}-min-val`).value = minVal;
                document.getElementById(`${idCorrespond}-max-val`).value = maxVal;
                auditClassAgeRules(); // Auditar solapamientos al deslizar
            });

            initializeDoubleSlider(idAllowed, () => {
                const minVal = document.getElementById(`${idAllowed}-min`).value;
                const maxVal = document.getElementById(`${idAllowed}-max`).value;
                document.getElementById(`${idAllowed}-min-val`).value = minVal;
                document.getElementById(`${idAllowed}-max-val`).value = maxVal;
            });
        }, 0);
    }

    // Evento del botón de eliminar fila
    mainRow.querySelector('.btn-delete-row').onclick = () => {
        rowContainer.remove();
        if (isClassMapping) auditClassAgeRules();
    };

    // Auditar al modificar la clave origen (CSV) para actualizar nombres en la auditoría al vuelo
    const keyInput = mainRow.querySelector('.rule-key');
    if (keyInput && isClassMapping) {
        keyInput.addEventListener('input', auditClassAgeRules);
    }

    wrapper.appendChild(rowContainer);
}

// 6. GUARDAR EN RAM
document.getElementById('btn-save-map').addEventListener('click', function() {
    profileRulesRAM[currentMappingField] = {}; // Limpiamos estado previo
    
    if (currentMappingType === 'boolean-mapping') {
        const triggers = document.getElementById('boolean-triggers-input').value.trim();
        if (triggers !== "") {
            profileRulesRAM[currentMappingField] = { triggers: triggers };
        }
    } else {
        // Guardado estándar de filas (e inyección de sliders si es Clase)
        const wrapper = document.getElementById('rows-wrapper');
        if (wrapper) {
            wrapper.querySelectorAll('.rule-row-container').forEach(rowContainer => {
                const keyInput = rowContainer.querySelector('.rule-key');
                const outInput = rowContainer.querySelector('.rule-out');
                
                if (keyInput && outInput) {
                    const key = keyInput.value.trim();
                    const out = outInput.value.trim();
                    
                    if (key !== "") {
                        if (currentMappingField === 'Class') {
                            const cMin = rowContainer.querySelector('.range-correspond-min').value;
                            const cMax = rowContainer.querySelector('.range-correspond-max').value;
                            const aMin = rowContainer.querySelector('.range-allowed-min').value;
                            const aMax = rowContainer.querySelector('.range-allowed-max').value;

                            profileRulesRAM.Class[key] = {
                                out: out,
                                ageCorrespondMin: parseInt(cMin) || 0,
                                ageCorrespondMax: parseInt(cMax) || 99,
                                ageAllowedMin: parseInt(aMin) || 0,
                                ageAllowedMax: parseInt(aMax) || 99
                            };
                        } else {
                            const sec = rowContainer.querySelector('.rule-secondary') ? rowContainer.querySelector('.rule-secondary').value.trim() : "";
                            profileRulesRAM[currentMappingField][key] = { out: out, secondary: sec };
                        }
                    }
                }
            });
        }
    }


    // Feedback visual (engranaje verde si hay algo configurado)
    const gearBtn = document.querySelector(`.map-trigger[data-field-name="${currentMappingField}"]`);
    if (gearBtn) {
        const hasData = Object.keys(profileRulesRAM[currentMappingField]).length > 0;
        gearBtn.style.background = hasData ? "#dcfce7" : "#f1f5f9";
    }

    closeModal();
    if (typeof updateUIState === 'function') updateUIState();
    if (isEditorMode) generateDummyCSV();
});


// ============================================================================
// --- CONTROLADORES DE CONTEXTO DEL EVENTO (FECHA Y CABECERAS) ---
// ============================================================================

// 1. Escuchar cambios en los selectores de rango de filas
const startRowInput = document.getElementById('start-row');
if (startRowInput) {
    startRowInput.addEventListener('change', function() {
        let val = parseInt(this.value) || 1;
        if (val < 1) val = 1;
        if (rawDataRows.length && val > rawDataRows.length) val = rawDataRows.length;
        this.value = val;
        startRow = val;

        if (startRow > endRow) {
            endRow = startRow;
            const endInput = document.getElementById('end-row');
            if (endInput) endInput.value = endRow;
        }
        renderCSVTable();
    });
    startRowInput.addEventListener('input', function() {
        let val = parseInt(this.value);
        if (!isNaN(val) && val >= 1 && (!rawDataRows.length || val <= rawDataRows.length)) {
            startRow = val;
            if (startRow > endRow) {
                endRow = startRow;
                const endInput = document.getElementById('end-row');
                if (endInput) endInput.value = endRow;
            }
            renderCSVTable();
        }
    });
}

const endRowInput = document.getElementById('end-row');
if (endRowInput) {
    endRowInput.addEventListener('change', function() {
        let val = parseInt(this.value) || 1;
        if (val < 1) val = 1;
        if (rawDataRows.length && val > rawDataRows.length) val = rawDataRows.length;
        this.value = val;
        endRow = val;

        if (endRow < startRow) {
            startRow = endRow;
            const startInput = document.getElementById('start-row');
            if (startInput) startInput.value = startRow;
        }
        renderCSVTable();
    });
    endRowInput.addEventListener('input', function() {
        let val = parseInt(this.value);
        if (!isNaN(val) && val >= 1 && (!rawDataRows.length || val <= rawDataRows.length)) {
            endRow = val;
            if (endRow < startRow) {
                startRow = endRow;
                const startInput = document.getElementById('start-row');
                if (startInput) startInput.value = startRow;
            }
            renderCSVTable();
        }
    });
}

const previewModeToggle = document.getElementById('preview-mode-toggle');
if (previewModeToggle) {
    previewModeToggle.addEventListener('change', function() {
        previewModeShowAll = this.checked;
        renderCSVTable();
    });
}

// 2. Pre-rellenar la fecha del torneo con la fecha de hoy al cargar la página e inicializar listeners
document.addEventListener('DOMContentLoaded', () => {
    // 3. Listener del toggle de validación por edad
    const ageToggle = document.getElementById('class-age-validation-toggle');
    if (ageToggle) {
        ageToggle.addEventListener('change', function() {
            ageValidationEnabled = this.checked;
            if (typeof updateUIState === 'function') updateUIState();
        });
        // Sincronizar estado inicial
        ageValidationEnabled = ageToggle.checked;
    }

    // 4. Listener de la fecha del torneo para repintar en tiempo real
    const eventDateInput = document.getElementById('event-date');
    if (eventDateInput) {
        eventDateInput.addEventListener('change', function() {
            if (typeof updateUIState === 'function') updateUIState();
        });
        eventDateInput.addEventListener('input', function() {
            if (typeof updateUIState === 'function') updateUIState();
        });
    }

    // 4b. Listener del input de sesión fija para repintar y validar en tiempo real
    const sessionFixedInput = document.getElementById('session-fixed-value');
    if (sessionFixedInput) {
        sessionFixedInput.addEventListener('change', function() {
            if (typeof updateUIState === 'function') updateUIState();
        });
        sessionFixedInput.addEventListener('input', function() {
            if (typeof updateUIState === 'function') updateUIState();
        });
    }

    // 5. Listener de exportación
    const btnExport = document.getElementById('btn-export');
    if (btnExport) {
        btnExport.addEventListener('click', function(e) {
            e.preventDefault();
            if (this.disabled) return;

            // Verificar si el rango es válido
            if (startRow > endRow || startRow < 1 || endRow > rawDataRows.length) {
                alert('El rango de filas seleccionado no es válido.');
                return;
            }

            // Si hay validación de edad activa, la fecha de torneo es estrictamente requerida
            if (ageValidationEnabled) {
                const dateInput = document.getElementById('event-date');
                if (!dateInput || !dateInput.value) {
                    alert('Error: La fecha del torneo es obligatoria para la validación por edad de las clases.');
                    return;
                }
            }

            // Compilar los datos del CSV
            const outputLines = [];

            // Obtener mapeadores de columnas
            const getSelectedColIdx = (fieldName) => {
                const select = document.querySelector(`.target-field[data-field-name="${fieldName}"]`);
                if (select && select.value !== "") {
                    return parseInt(select.value);
                }
                return -1;
            };

            // Recorrer las filas dentro del rango [startRow, endRow]
            for (let i = startRow - 1; i <= endRow - 1; i++) {
                const rowData = rawDataRows[i];
                if (!rowData) continue;

                const exportCols = [];

                // 1. Bib
                let colIdx = getSelectedColIdx("Bib");
                exportCols.push(colIdx !== -1 ? rowData[colIdx] : "");

                // 2. Session
                colIdx = getSelectedColIdx("Session");
                if (colIdx !== -1) {
                    const raw = rowData[colIdx];
                    exportCols.push((profileRulesRAM.Session[raw] && profileRulesRAM.Session[raw].out) || raw || "");
                } else {
                    const fixedInput = document.getElementById('session-fixed-value');
                    exportCols.push(fixedInput ? fixedInput.value : "");
                }

                // 3. Division
                colIdx = getSelectedColIdx("Division");
                if (colIdx !== -1) {
                    const raw = rowData[colIdx];
                    exportCols.push((profileRulesRAM.Division[raw] && profileRulesRAM.Division[raw].out) || raw || "");
                } else {
                    exportCols.push("");
                }

                // 4. Class
                colIdx = getSelectedColIdx("Class");
                if (colIdx !== -1) {
                    const raw = rowData[colIdx];
                    exportCols.push((profileRulesRAM.Class[raw] && profileRulesRAM.Class[raw].out) || raw || "");
                } else {
                    exportCols.push("");
                }

                // 5. Target
                colIdx = getSelectedColIdx("Target");
                exportCols.push(colIdx !== -1 ? rowData[colIdx] : "");

                // Helper para mapear booleanos
                const getBoolVal = (fieldName) => {
                    const idx = getSelectedColIdx(fieldName);
                    if (idx !== -1) {
                        const raw = rowData[idx];
                        const triggers = profileRulesRAM[fieldName] && profileRulesRAM[fieldName].triggers;
                        if (triggers) {
                            const triggerList = triggers.split(',').map(t => t.trim().toLowerCase());
                            return triggerList.includes(raw.trim().toLowerCase()) ? "1" : "0";
                        }
                        return "0";
                    }
                    return "";
                };

                // 6. IndDivClass
                exportCols.push(getBoolVal("IndDivClass"));
                // 7. TeamDivClass
                exportCols.push(getBoolVal("TeamDivClass"));
                // 8. IndEvents
                exportCols.push(getBoolVal("IndEvents"));
                // 9. TeamEvents
                exportCols.push(getBoolVal("TeamEvents"));
                // 10. MixedEvents
                exportCols.push(getBoolVal("MixedEvents"));

                // 11. LastName
                colIdx = getSelectedColIdx("LastName");
                exportCols.push(colIdx !== -1 ? rowData[colIdx] : "");

                // 12. Name
                colIdx = getSelectedColIdx("Name");
                exportCols.push(colIdx !== -1 ? rowData[colIdx] : "");

                // 13. Gender
                colIdx = getSelectedColIdx("Gender");
                if (colIdx !== -1) {
                    const raw = rowData[colIdx];
                    exportCols.push((profileRulesRAM.Gender[raw] && profileRulesRAM.Gender[raw].out) || raw || "");
                } else {
                    exportCols.push("");
                }

                // 14. Affil1Code
                colIdx = getSelectedColIdx("Affil1Code");
                if (colIdx !== -1) {
                    const raw = rowData[colIdx];
                    exportCols.push((profileRulesRAM.Affil1[raw] && profileRulesRAM.Affil1[raw].out) || raw || "");
                } else {
                    exportCols.push("");
                }

                // 15. Affil1Name
                colIdx = getSelectedColIdx("Affil1Name");
                exportCols.push(colIdx !== -1 ? rowData[colIdx] : "");

                // 16. DOB
                colIdx = getSelectedColIdx("DOB");
                exportCols.push(colIdx !== -1 ? rowData[colIdx] : "");

                // 17. Subclass
                colIdx = getSelectedColIdx("Subclass");
                exportCols.push(colIdx !== -1 ? rowData[colIdx] : "");

                // 18. Affil2Code
                colIdx = getSelectedColIdx("Affil2Code");
                if (colIdx !== -1) {
                    const raw = rowData[colIdx];
                    exportCols.push((profileRulesRAM.Affil2[raw] && profileRulesRAM.Affil2[raw].out) || raw || "");
                } else {
                    exportCols.push("");
                }

                // 19. Affil2Name
                colIdx = getSelectedColIdx("Affil2Name");
                exportCols.push(colIdx !== -1 ? rowData[colIdx] : "");

                // 20. Affil3Code
                colIdx = getSelectedColIdx("Affil3Code");
                if (colIdx !== -1) {
                    const raw = rowData[colIdx];
                    exportCols.push((profileRulesRAM.Affil3[raw] && profileRulesRAM.Affil3[raw].out) || raw || "");
                } else {
                    exportCols.push("");
                }

                // 21. Affil3Name
                colIdx = getSelectedColIdx("Affil3Name");
                exportCols.push(colIdx !== -1 ? rowData[colIdx] : "");

                // Escapar todas las celdas y unir con ";"
                const escapedLine = exportCols.map(val => {
                    if (val === null || val === undefined) return "";
                    let s = String(val);
                    if (s.includes('"') || s.includes(';') || s.includes('\n') || s.includes('\r')) {
                        s = s.replace(/"/g, '""');
                        return `"${s}"`;
                    }
                    return s;
                }).join(';');

                outputLines.push(escapedLine);
            }

            // Crear Blob y forzar descarga
            const csvBlobContent = outputLines.join('\r\n');
            const blob = new Blob([csvBlobContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", "traduccion_ianseo.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }

    // ============================================================================
    // --- SUSCRIPCIÓN DE EVENTOS CRUD Y CONTROLES AVANZADOS ---
    // ============================================================================
    
    // Cargar la lista inicial de formatos al iniciar
    loadFormatList();

    const modeCsvBtn = document.getElementById('mode-csv-btn');
    const modeFormatBtn = document.getElementById('mode-format-btn');
    const dropzoneTitle = document.getElementById('dropzone-title');
    const dropzoneSubtitle = document.getElementById('dropzone-subtitle');
    const fileInput = document.getElementById('file-input');

    if (modeCsvBtn && modeFormatBtn) {
        modeCsvBtn.addEventListener('click', () => {
            dragDropMode = 'csv';
            modeCsvBtn.classList.add('active-toggle');
            modeCsvBtn.style.background = 'var(--primary)';
            modeCsvBtn.style.color = 'white';
            
            modeFormatBtn.classList.remove('active-toggle');
            modeFormatBtn.style.background = 'transparent';
            modeFormatBtn.style.color = '#64748b';
            
            if (dropzoneTitle) dropzoneTitle.innerText = "Arrastra tu archivo CSV aquí";
            if (dropzoneSubtitle) dropzoneSubtitle.innerText = "o haz clic para explorar tu equipo";
            if (fileInput) {
                fileInput.accept = ".csv";
                fileInput.value = "";
            }
        });

        modeFormatBtn.addEventListener('click', () => {
            dragDropMode = 'format';
            modeFormatBtn.classList.add('active-toggle');
            modeFormatBtn.style.background = 'var(--primary)';
            modeFormatBtn.style.color = 'white';
            
            modeCsvBtn.classList.remove('active-toggle');
            modeCsvBtn.style.background = 'transparent';
            modeCsvBtn.style.color = '#64748b';
            
            if (dropzoneTitle) dropzoneTitle.innerText = "Arrastra tu archivo JSON aquí";
            if (dropzoneSubtitle) dropzoneSubtitle.innerText = "para importar y editar una plantilla de formato";
            if (fileInput) {
                fileInput.accept = ".json";
                fileInput.value = "";
            }
        });
    }

    const formatSelect = document.getElementById('format-select');
    const btnEditFormat = document.getElementById('btn-edit-format');

    if (formatSelect) {
        formatSelect.addEventListener('change', function() {
            const val = this.value;
            if (val === "") {
                // Reset format state
                currentFormatId = null;
                currentFormatName = "";
                if (btnEditFormat) {
                    btnEditFormat.disabled = true;
                    btnEditFormat.style.opacity = "0.6";
                    btnEditFormat.style.cursor = "not-allowed";
                }
                
                // Si ya hay un CSV cargado, restaurar mapeos a vacío
                profileRulesRAM = {
                    Session: {}, Division: {}, Class: {}, Gender: {}, 
                    Affil1: {}, Affil2: {}, Affil3: {}
                };
                document.querySelectorAll('.target-field').forEach(select => {
                    select.value = "";
                });
                document.querySelectorAll('.map-trigger').forEach(btn => {
                    btn.style.background = "#f1f5f9";
                });
                if (typeof updateUIState === 'function') updateUIState();
            } else {
                if (btnEditFormat) {
                    btnEditFormat.disabled = false;
                    btnEditFormat.style.opacity = "1";
                    btnEditFormat.style.cursor = "pointer";
                }
                
                // Si ya tenemos un CSV visualizado, cargamos e inyectamos los mapeos inmediatamente
                fetch(`api.php?action=get_format&id=${val}`)
                    .then(res => res.json())
                    .then(json => {
                        if (json.status === 'success') {
                            loadFormatFromData(json.data);
                        }
                    })
                    .catch(err => console.error("Error al cargar formato:", err));
            }
        });
    }

    // Botón Nuevo Formato
    const btnNewFormat = document.getElementById('btn-new-format');
    if (btnNewFormat) {
        btnNewFormat.addEventListener('click', () => {
            const name = prompt("Por favor, introduce el nombre del nuevo formato:");
            if (name && name.trim() !== "") {
                enterEditorMode(name.trim(), null);
            }
        });
    }

    // Botón Editar Formato
    if (btnEditFormat) {
        btnEditFormat.addEventListener('click', () => {
            const selectedVal = formatSelect.value;
            if (selectedVal !== "") {
                fetch(`api.php?action=get_format&id=${selectedVal}`)
                    .then(res => res.json())
                    .then(json => {
                        if (json.status === 'success') {
                            enterEditorMode(json.data.name, json.data.id);
                            loadFormatFromData(json.data);
                        }
                    })
                    .catch(err => console.error("Error al cargar formato para editar:", err));
            } else {
                alert("Por favor, selecciona una plantilla para editar.");
            }
        });
    }

    // Botón Salir del Editor
    const btnExitEditor = document.getElementById('btn-exit-editor');
    if (btnExitEditor) {
        btnExitEditor.addEventListener('click', () => {
            if (confirm("¿Estás seguro de que deseas salir del editor? Se perderán los cambios no guardados.")) {
                exitEditorMode();
            }
        });
    }

    // Botón Cerrar Archivo (Retorno a Bienvenida)
    const btnCloseCSV = document.getElementById('btn-close-csv');
    if (btnCloseCSV) {
        btnCloseCSV.addEventListener('click', () => {
            if (confirm("¿Estás seguro de que deseas cerrar el archivo actual? Se perderán los mapeos locales no guardados.")) {
                exitEditorMode();
            }
        });
    }

    // Control dinámico de cantidad de columnas en el editor
    const editorExpectedColsInput = document.getElementById('editor-expected-cols');
    if (editorExpectedColsInput) {
        editorExpectedColsInput.addEventListener('input', () => {
            let val = parseInt(editorExpectedColsInput.value);
            if (isNaN(val) || val < 5) return;
            if (isEditorMode) {
                generateDummyCSV();
            }
        });
        editorExpectedColsInput.addEventListener('change', () => {
            let val = parseInt(editorExpectedColsInput.value);
            if (isNaN(val) || val < 5) {
                val = 22;
                editorExpectedColsInput.value = 22;
            }
            if (isEditorMode) {
                generateDummyCSV();
            }
        });
    }

    // Botón Guardar Formato
    const btnSaveProfile = document.getElementById('btn-save-profile');
    if (btnSaveProfile) {
        btnSaveProfile.addEventListener('click', async () => {
            // Si no estamos en modo editor, y hay un formato cargado, pedir confirmación explicativa
            if (!isEditorMode) {
                if (!currentFormatId) {
                    alert("No hay ningún formato cargado para guardar. Entra en el Editor o usa 'Guardar Como...' para crear uno.");
                    return;
                }
                const conf = confirm(`¿Estás seguro de que deseas guardar las modificaciones sobre la plantilla "${currentFormatName}" desde fuera del Editor?`);
                if (!conf) return;
            } else {
                // En modo editor, si no tenemos nombre (id null), podemos re-confirmar el nombre
                if (!currentFormatName) {
                    const name = prompt("Introduce el nombre para el formato:");
                    if (!name || name.trim() === "") return;
                    currentFormatName = name.trim();
                }
            }

            const payload = {
                id: currentFormatId,
                name: currentFormatName,
                mappings: serializeMappings(),
                rules: serializeRules()
            };

            try {
                const res = await fetch('api.php?action=save_format', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert(`Plantilla "${currentFormatName}" guardada correctamente.`);
                    currentFormatId = json.id; // Asignar ID si es nuevo
                    
                    // Si estamos en modo editor, actualizar botón borrar para que sea visible
                    if (isEditorMode) {
                        const delBtn = document.getElementById('btn-delete-profile');
                        if (delBtn) delBtn.style.display = 'inline-block';
                        
                        // Actualizar info del archivo
                        document.getElementById('file-info').innerText = "Editando plantilla: " + currentFormatName;
                    }
                    
                    await loadFormatList();
                    if (formatSelect) {
                        formatSelect.value = currentFormatId;
                        if (btnEditFormat) {
                            btnEditFormat.disabled = false;
                            btnEditFormat.style.opacity = "1";
                            btnEditFormat.style.cursor = "pointer";
                        }
                    }
                } else {
                    alert("Error al guardar la plantilla: " + json.message);
                }
            } catch(err) {
                alert("Error de conexión al guardar la plantilla.");
                console.error(err);
            }
        });
    }

    // Botón Guardar Como...
    const btnSaveAsProfile = document.getElementById('btn-save-as-profile');
    if (btnSaveAsProfile) {
        btnSaveAsProfile.addEventListener('click', async () => {
            const defaultName = currentFormatName ? currentFormatName + " - copia" : "Nueva Plantilla";
            const newName = prompt("Guardar como... Introduce el nombre para la copia del formato:", defaultName);
            if (!newName || newName.trim() === "") return;

            const payload = {
                id: null, // Nuevo registro
                name: newName.trim(),
                mappings: serializeMappings(),
                rules: serializeRules()
            };

            try {
                const res = await fetch('api.php?action=save_format', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert(`Nueva plantilla "${newName.trim()}" creada correctamente.`);
                    currentFormatId = json.id;
                    currentFormatName = newName.trim();
                    
                    // Si estábamos en modo editor, actualizar estado visual
                    if (isEditorMode) {
                        const delBtn = document.getElementById('btn-delete-profile');
                        if (delBtn) delBtn.style.display = 'inline-block';
                        document.getElementById('file-info').innerText = "Editando plantilla: " + currentFormatName;
                    }
                    
                    await loadFormatList();
                    if (formatSelect) {
                        formatSelect.value = currentFormatId;
                        if (btnEditFormat) {
                            btnEditFormat.disabled = false;
                            btnEditFormat.style.opacity = "1";
                            btnEditFormat.style.cursor = "pointer";
                        }
                    }
                } else {
                    alert("Error al guardar la plantilla: " + json.message);
                }
            } catch(err) {
                alert("Error de conexión al duplicar la plantilla.");
                console.error(err);
            }
        });
    }

    // Botón Borrar Formato
    const btnDeleteProfile = document.getElementById('btn-delete-profile');
    if (btnDeleteProfile) {
        btnDeleteProfile.addEventListener('click', async () => {
            if (!currentFormatId) return;
            const conf = confirm(`¿Estás seguro de que deseas eliminar permanentemente la plantilla "${currentFormatName}"? Esta acción no se puede deshacer.`);
            if (!conf) return;

            try {
                const res = await fetch('api.php?action=delete_format', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: currentFormatId })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert("Plantilla de formato eliminada correctamente.");
                    exitEditorMode();
                } else {
                    alert("Error al eliminar la plantilla: " + json.message);
                }
            } catch(err) {
                alert("Error de conexión al eliminar la plantilla.");
                console.error(err);
            }
        });
    }

    // Botón Exportar Formato (JSON)
    const btnExportProfileJson = document.getElementById('btn-export-profile-json');
    if (btnExportProfileJson) {
        btnExportProfileJson.addEventListener('click', () => {
            const name = currentFormatName || "formato_sin_nombre";
            const payload = {
                name: name,
                mappings: serializeMappings(),
                rules: serializeRules()
            };

            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(payload, null, 4));
            const dlAnchorElem = document.createElement('a');
            dlAnchorElem.setAttribute("href", dataStr);
            
            const sanitizedName = name.toLowerCase().replace(/\s+/g, '_') + "_format.json";
            dlAnchorElem.setAttribute("download", sanitizedName);
            dlAnchorElem.click();
        });
    }
});

