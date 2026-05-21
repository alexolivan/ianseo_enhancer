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
                td.classList.remove('cell-error-class');
            }
        });
    });

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
                const htmlRow = rows[rowIndex];
                if (!htmlRow) return;

                const rawDob = rowData[dobCol] ? rowData[dobCol].trim() : '';
                const rawClass = rowData[classCol] ? rowData[classCol].trim() : '';
                const rawGender = genderCol !== "" && rowData[genderCol] ? rowData[genderCol].trim() : '';

                const tdClass = htmlRow.querySelectorAll('td')[parseInt(classCol) + 1];
                const tdDob = htmlRow.querySelectorAll('td')[parseInt(dobCol) + 1];

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
    
    const uniqueValues = [...new Set(rawDataRows.map(row => row[selector.value] ? row[selector.value].trim() : ''))].filter(v => v !== "");
    const existingKeys = Array.from(document.querySelectorAll('#modal-rules-container .rule-key')).map(input => input.value.trim());

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
        </div>
    `;
}

function initializeDoubleSlider(idPrefix, onChangeCallback) {
    const rangeMin = document.getElementById(`${idPrefix}-min`);
    const rangeMax = document.getElementById(`${idPrefix}-max`);
    const trackActive = document.getElementById(`${idPrefix}-track-active`);
    const valueDisplay = document.getElementById(`${idPrefix}-value-display`);

    if (!rangeMin || !rangeMax || !trackActive || !valueDisplay) return;

    function updateSlider() {
        let valMin = parseInt(rangeMin.value);
        let valMax = parseInt(rangeMax.value);

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

        if (onChangeCallback) onChangeCallback();
    }

    rangeMin.addEventListener('input', updateSlider);
    rangeMax.addEventListener('input', updateSlider);

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
});


// ============================================================================
// --- CONTROLADORES DE CONTEXTO DEL EVENTO (FECHA Y CABECERAS) ---
// ============================================================================
// 1. Escuchar cambios en el selector de filas a saltar
document.getElementById('skip-rows').addEventListener('input', function() {
    let skipCount = parseInt(this.value) || 0;
    if (skipCount < 0) {
        this.value = 0;
        skipCount = 0;
    }

    const tbody = document.querySelector('#csv-table tbody');
    // QUITAMOS el 'window.' que estaba matando el proceso
    if (!tbody || !rawDataRows || rawDataRows.length === 0) return; 

    tbody.innerHTML = ''; // Limpiamos la tabla actual

    // Iteramos desde la fila indicada por el usuario hasta el final
    for (let i = skipCount; i < rawDataRows.length; i++) {
        const row = rawDataRows[i];
        const tr = document.createElement('tr');

        tr.innerHTML = `<td style="text-align: center; color: #94a3b8; font-weight: 600; background: #f8fafc;">${i + 1}</td>`;

        row.forEach(cellData => {
            const td = document.createElement('td');
            // Sanitizamos igual que en la carga inicial
            td.innerHTML = cellData.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    }

    // Forzamos un repintado de los colores para que los verdes/naranjas se mantengan
    if (typeof updateUIState === 'function') updateUIState();
});

// 2. Pre-rellenar la fecha del torneo con la fecha de hoy al cargar la página e inicializar listeners
document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.getElementById('event-date');
    if (dateInput) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        dateInput.value = `${yyyy}-${mm}-${dd}`;
    }

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
    }
});

