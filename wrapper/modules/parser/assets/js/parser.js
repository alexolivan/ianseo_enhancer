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

function processFile(file) {
    if (!file.name.endsWith('.csv')) {
        alert('Por favor, selecciona un archivo CSV válido.');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        renderRawCSV(text, file.name);

        // Transición de la UI: Ocultar zona de subida, mostrar mesa de trabajo
        document.getElementById('dropzone').style.display = 'none';
        document.getElementById('workspace').style.display = 'block';
    };
    reader.readAsText(file);
}

function renderRawCSV(csvText, fileName) {
    const firstLine = csvText.slice(0, csvText.indexOf('\n'));
    const delimiter = (firstLine.split(';').length > firstLine.split(',').length) ? ';' : ',';
    const lines = csvText.split(/\r\n|\n/).filter(line => line.trim() !== '');

    if (lines.length === 0) return;

    // Guardamos estado global en RAM
    currentHeaders = parseCSVLine(lines[0], delimiter);
    rawDataRows = [];
    for (let i = 1; i < lines.length; i++) {
        rawDataRows.push(parseCSVLine(lines[i], delimiter));
    }

    // Pintar información técnica
    document.getElementById('file-info').innerHTML = 
        `📁 <strong>${fileName}</strong> | Filas: <strong>${rawDataRows.length}</strong> | Delimitador: <strong>"${delimiter}"</strong>`;

    // 1. Pintar tabla cruda izquierda
    const thead = document.querySelector('#csv-table thead');
    const tbody = document.querySelector('#csv-table tbody');
    thead.innerHTML = ''; tbody.innerHTML = '';

    const headerRow = document.createElement('tr');
    headerRow.innerHTML = `<th style="width: 40px; text-align: center;">#</th>`;
    currentHeaders.forEach((h, idx) => {
        headerRow.innerHTML += `<th>${h || `Col ${idx}`}</th>`;
    });
    thead.appendChild(headerRow);

    rawDataRows.forEach((rowData, rowIndex) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td style="text-align: center; color: #94a3b8; font-weight: 600; background: #f8fafc;">${rowIndex + 1}</td>`;
        rowData.forEach(cell => {
            const safeCell = cell.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            tr.innerHTML += `<td>${safeCell}</td>`;
        });
        tbody.appendChild(tr);
    });

    // 2. POBLAR DESPLEGABLES IANSEO (¡Aquí ocurre la magia de la conexión!)
    populateIanseoTargets();
}

function populateIanseoTargets() {
    const targetSelects = document.querySelectorAll('.target-field');

    targetSelects.forEach(select => {
        // Reiniciamos opciones preservando la opción por defecto
        select.innerHTML = '<option value="">-- Ignorar --</option>';

        // Inyectamos las columnas detectadas en el CSV
        currentHeaders.forEach((headerText, idx) => {
            const label = headerText ? `Col ${idx}: ${headerText}` : `Col ${idx}`;
            select.innerHTML += `<option value="${idx}">${label}</option>`;
        });

        // LÓGICA DE ESTADOS (Bottom-Up UX)
        const type = select.getAttribute('data-type');
        const isAffiliation = select.classList.contains('affil-code');

	// Busca esta parte en tu función y déjala así:
	if (type && (type.startsWith('passthrough') || type === 'mapping')) {
	    select.disabled = false; // Ahora todos están abiertos para poder elegir la columna antes de mapear
	}

        // Las afiliaciones se gestionan por su propia lógica de cascada (inicialmente bloqueamos 2 y 3)
        const moduleNum = select.getAttribute('data-module');
        if (moduleNum && moduleNum > 1) {
            select.disabled = true;
        } else if (moduleNum === "1") {
            // La afiliación 1 arranca en modo passthrough por defecto, así que habilitamos
            select.disabled = false;
            // El campo Nombre de la afiliación 1 también se habilita
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
    select.addEventListener('change', updateUIState);
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
    currentHeaders.forEach((headerText, idx) => {
        const th = ths[idx + 1];
        if (th) {
            th.innerHTML = headerText || `Col ${idx}`;
            th.classList.remove('th-assigned');
        }
        // Limpiamos colores de todas las celdas de esta columna
        rows.forEach(row => {
            const td = row.querySelectorAll('td')[idx + 1];
            if (td) {
                td.style.backgroundColor = '';
                td.removeAttribute('title');
            }
        });
    });

    let allRequiredAssigned = true;

    // 2. EVALUAR CONTROLES IANSEO (Pintar rojos en UI y verdes en Tabla)
    document.querySelectorAll('.target-field').forEach(select => {
        const colIdx = select.value;
        const isRequired = select.getAttribute('data-required') === "true";
        const fieldName = select.getAttribute('data-field-name');
        const fieldType = select.getAttribute('data-type');

        // Control de Rojos en campos obligatorios
        if (isRequired) {
            if (colIdx === "") {
                select.classList.add('required-pending');
                allRequiredAssigned = false;
            } else {
                select.classList.remove('required-pending');
            }
        }

        // Si hay una columna asignada, procesamos la tabla cruda
        if (colIdx !== "") {
            const targetCol = parseInt(colIdx) + 1;
            const th = ths[targetCol];

            // Pintar Cabecera
            if (th) {
                th.innerHTML = `<strong>${th.innerText}</strong> <br><span style="color:var(--primary); font-size:0.75rem;">[${fieldName}]</span>`;
                th.classList.add('th-assigned');
            }

            // Pintar Celdas de la columna asignada
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;

            rawDataRows.forEach((rowData, rowIndex) => {
                const htmlRow = rows[rowIndex];
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

    // 3. GESTIÓN DEL BOTÓN DE EXPORTACIÓN
    const btnExport = document.getElementById('btn-export');
    if (btnExport) {
        // Solo habilitamos si los 7 obligatorios tienen su columna y hay datos cargados
        if (allRequiredAssigned && rawDataRows.length > 0) {
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
    
    const uniqueValues = [...new Set(rawDataRows.map(row => row[selector.value] ? row[selector.value].trim() : ''))].filter(v => v !== "");
    const existingKeys = Array.from(document.querySelectorAll('#modal-rules-container .rule-key')).map(input => input.value.trim());

    uniqueValues.forEach(val => {
        if (!existingKeys.includes(val)) appendRuleRow(val, "", "");
    });
});

// 4. RENDERIZAR EL CONTENIDO SEGÚN TIPO DE CAMPO
function renderModalRules() {
    const container = document.getElementById('modal-rules-container');
    container.innerHTML = '';
    const rules = profileRulesRAM[currentMappingField] || {};

    // MODO A: MAPEO BOOLEANO PARA EVENTOS (Tú idea de lista separada por comas)
    if (currentMappingType === 'boolean-mapping') {
        const triggers = rules.triggers || ""; // Recuperamos la lista si ya existía
        
        container.innerHTML = `
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
        setTimeout(() => document.getElementById('boolean-triggers-input').focus(), 50);
        return;
    }

    // MODO B: MAPEO ESTÁNDAR / AFILIACIONES (El que ya teníamos)
    const isDoubleOutput = currentMappingField.startsWith('Affil');
    const header = document.createElement('div');
    header.style = "display: flex; gap: 10px; margin-bottom: 12px; font-weight: 700; font-size: 0.75rem; color: #64748b; text-transform: uppercase;";
    header.innerHTML = isDoubleOutput 
        ? `<div style="flex:1">Clave Origen (CSV)</div><div style="flex:1">ID Ianseo *</div><div style="flex:1">Nombre Oficial</div><div style="width:28px"></div>`
        : `<div style="flex:1">Clave Origen (CSV)</div><div style="flex:1">Salida Ianseo *</div><div style="width:28px"></div>`;
    container.appendChild(header);

    const rowsWrapper = document.createElement('div');
    rowsWrapper.id = "rows-wrapper";
    container.appendChild(rowsWrapper);

    const keys = Object.keys(rules);
    keys.forEach(key => appendRuleRow(key, rules[key].out, rules[key].secondary));
    if (keys.length === 0) appendRuleRow("", "", "");

    const btnAdd = document.createElement('button');
    btnAdd.type = "button";
    btnAdd.innerText = "+ Añadir fila vacía";
    btnAdd.style = "margin-top: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; color: var(--primary); font-weight: 600; padding: 0.5rem; width: 100%; cursor: pointer; border-radius: 4px; font-size: 0.85rem; transition: all 0.2s;";
    btnAdd.onclick = () => appendRuleRow("", "", "");
    container.appendChild(btnAdd);
}

// 5. INYECTOR DE FILAS (Solo para estándar)
function appendRuleRow(keyVal, outVal, secVal) {
    const wrapper = document.getElementById('rows-wrapper');
    if (!wrapper) return;
    const isDoubleOutput = currentMappingField.startsWith('Affil');
    const row = document.createElement('div');
    row.className = "rule-row";
    row.style = "display: flex; gap: 10px; margin-bottom: 8px; align-items: center;";
    row.innerHTML = isDoubleOutput 
        ? `<input type="text" value="${keyVal}" class="rule-key" placeholder="Ej: BCN" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;"><input type="text" value="${outVal}" class="rule-out" placeholder="ID (Ej: 2011)" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;"><input type="text" value="${secVal}" class="rule-secondary" placeholder="Nombre" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;"><button type="button" onclick="this.parentElement.remove()" style="width:28px; height:28px; border:none; background:#fef2f2; color:#ef4444; border-radius:4px; cursor:pointer; font-weight:bold;">×</button>`
        : `<input type="text" value="${keyVal}" class="rule-key" placeholder="Texto CSV" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;"><input type="text" value="${outVal}" class="rule-out" placeholder="Salida" style="flex:1; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;"><button type="button" onclick="this.parentElement.remove()" style="width:28px; height:28px; border:none; background:#fef2f2; color:#ef4444; border-radius:4px; cursor:pointer; font-weight:bold;">×</button>`;
    wrapper.appendChild(row);
}

// 6. GUARDAR EN RAM
document.getElementById('btn-save-map').addEventListener('click', function() {
    profileRulesRAM[currentMappingField] = {}; // Limpiamos estado previo
    
    if (currentMappingType === 'boolean-mapping') {
        // Guardamos la cadena tal cual, sin procesar. El PHP se encargará del split(',')
        const triggers = document.getElementById('boolean-triggers-input').value.trim();
        if (triggers !== "") {
            profileRulesRAM[currentMappingField] = { triggers: triggers };
        }
    } else {
        // Guardado estándar de filas
        const wrapper = document.getElementById('rows-wrapper');
        if (wrapper) {
            wrapper.querySelectorAll('.rule-row').forEach(row => {
                const key = row.querySelector('.rule-key').value.trim();
                const out = row.querySelector('.rule-out').value.trim();
                const sec = row.querySelector('.rule-secondary') ? row.querySelector('.rule-secondary').value.trim() : "";
                if (key !== "") profileRulesRAM[currentMappingField][key] = { out: out, secondary: sec };
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
});
