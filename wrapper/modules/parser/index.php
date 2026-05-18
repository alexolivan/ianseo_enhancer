<?php
// 1. INTEGRACIÓN CON EL CORE Y AUTENTICACIÓN
// Escalamos 2 niveles desde /modules/parser/ hasta la raíz del wrapper
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../core/auth_checker.php';

// montamos una estructura HTML5 limpia con estilos autocontenidos.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador y Parser CSV - Ianseo Enhancer</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-base: #f8fafc;
            --border-color: #e2e8f0;
            --text-main: #1e293b;
        }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            margin: 0;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        header {
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 1rem;
        }

        /* Zona Drag & Drop (Paso 1) */
        #dropzone {
            border: 3px dashed #cbd5e1;
            border-radius: 12px;
            padding: 4rem 2rem;
            text-align: center;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        #dropzone.dragover {
            border-color: var(--primary);
            background: #eff6ff;
        }
        #file-input {
            display: none;
        }

	/* Mesa de Trabajo / Previsualización (Paso 2) */
        .table-container {
	    flex-grow: 1; /* Se estira para ocupar todo el espacio vertical disponible del panel */
            min-height: 0;

            /*max-height: 82vh;  */
            /*overflow-y: auto;  */
            /*overflow-x: auto;  */
	    overflow: auto;

            border: 1px solid var(--border-color);
            border-radius: 6px;
            margin-top: 1rem;

            /* Scrollbars elegantes */
            scrollbar-width: thin; 
            scrollbar-color: #94a3b8 #f1f5f9;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            text-align: left;
            font-size: 0.9rem;
        }

        /* Cabecera unificada y pegajosa */
        #csv-table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8fafc;
            box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        tr:hover {
            background-color: #f8fafc;
        }

	/* --- DISEÑO PANTALLA DIVIDIDA --- */
        #workspace {
            display: none;
            margin-top: 1.5rem;
        }

        .split-layout {
            display: grid;
            grid-template-columns: 1fr 460px;
            gap: 1.5rem;
            align-items: stretch;
        }

        /* Recuperamos la responsividad: Si la pantalla es pequeña, apilamos los paneles */
        @media (max-width: 1100px) {
            .split-layout {
                grid-template-columns: 1fr;
            }
        }

        .persistence-bar {
            grid-column: 1 / -1;
            background: #ffffff;
            border: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }

        .panel {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        /* ¡LA LÍNEA MÁGICA QUE QUITA EL SCROLL GLOBAL! */
        .left-panel {
            min-width: 0;
	    display: flex;
            flex-direction: column;
        }

	/* Sub-formularios compactos Ianseo [Etiqueta + Rueda + Desplegable] */
	.ianseo-fieldset {
	    border: 2px solid #e2e8f0;
	    border-radius: 8px;
	    padding: 1.25rem 1rem;
	    margin: 0;
	    box-sizing: border-box; /* Obliga a que el padding se calcule hacia adentro, no hacia afuera */
	    width: 100%;
	    min-width: 0; /* Anula el comportamiento cabezota de los fieldsets que causa el desbordamiento */
	}
	.ianseo-fieldset legend {
	    font-weight: 600;
	    color: var(--primary);
	    padding: 0 0.5rem;
	    font-size: 0.95rem;
	}
	.control-row {
	    display: flex;
	    align-items: center;
	    justify-content: space-between;
	    gap: 0.5rem;
	    padding: 0.4rem 0;
	    border-bottom: 1px solid #f1f5f9;
	}
	.control-row:last-child {
	    border-bottom: none;
	}
	.control-label {
	    font-size: 0.85rem;
	    font-weight: 600;
	    width: 120px;
	    white-space: nowrap;
	    overflow: hidden;
	    text-overflow: ellipsis;
	}
	.btn-gear {
	    background: #f1f5f9;
	    border: 1px solid #cbd5e1;
	    border-radius: 4px;
	    width: 28px;
	    height: 28px;
	    display: flex;
	    align-items: center;
	    justify-content: center;
	    cursor: pointer;
	    color: #475569;
	    transition: all 0.2s;
	}
	.btn-gear:hover:not(:disabled) {
	    background: var(--primary);
	    color: white;
	    border-color: var(--primary-hover);
	}
	.btn-gear:disabled {
	    opacity: 0.4;
	    cursor: not-allowed;
	}
	.column-select {
	    flex-grow: 1;
	    width: 140px;
	    padding: 0.25rem 0.5rem;
	    font-size: 0.85rem;
	    border-radius: 4px;
	    border: 1px solid #cbd5e1;
	}
	.column-select:disabled {
	    background-color: #f8fafc;
	    color: #94a3b8;
	    cursor: not-allowed;
	}

	/* Estilos para los módulos de afiliación en cascada */
	.affiliation-block {
	    background: #f8fafc;
	    border: 1px solid #e2e8f0;
	    border-radius: 6px;
	    padding: 0.75rem;
	    margin-top: 1rem;
	}
	.affiliation-block.disabled {
	    opacity: 0.5;
	    pointer-events: none;
	}

	/* --- FORZAR SCROLLBARS PERSISTENTES EN LA TABLA --- */
        .table-container {
            flex-grow: 1; /* Ocupa todo el espacio vertical disponible */
            min-height: 0; /* Vital para que el scroll funcione en Flexbox */

            overflow: auto; /* Activa el scroll interno */
            scrollbar-width: thin; /* Para Firefox */
            scrollbar-color: #94a3b8 #f1f5f9;
        }

        /* Para Chrome, Safari, Edge */
        .table-container::-webkit-scrollbar {
            height: 12px;
            width: 12px;
            display: block;
        }
        .table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 0 0 6px 6px;
        }
        .table-container::-webkit-scrollbar-thumb {
            background-color: #94a3b8;
            border-radius: 6px;
            border: 2px solid #f1f5f9;
        }
        .table-container::-webkit-scrollbar-thumb:hover {
            background-color: #64748b;
        }
	/* --- ALERTAS VISUALES (UX) --- */
	/* Desplegables obligatorios pendientes de mapear */
	.required-pending {
	    border-color: #ef4444 !important; /* Rojo vivo */
	    background-color: #fef2f2 !important; /* Fondo rojizo suave */
	}
	/* Cabecera de tabla asignada */
	.th-assigned {
	    background-color: #eff6ff !important;
	}

	#modal-rules-container {
	    min-height: 200px;
	    max-height: 400px; /* Altura fija para forzar el scroll */
	    overflow-y: auto;
	    padding-right: 10px;
	}
	/* Estilo para los inputs del modal */
	#modal-rules-container input:focus {
	    outline: none;
	    border-color: var(--primary);
	    box-shadow: 0 0 0 2px #eff6ff;
	}
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>Motor de Mapeo e Importación CSV</h1>
        <p>Previsualización y normalización de inscripciones para Ianseo (Stateless RAM Parser)</p>
    </header>

    <div id="dropzone">
        <svg style="width: 64px; height: 64px; color: #94a3b8; margin-bottom: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
        </svg>
        <h3>Arrastra tu archivo CSV aquí</h3>
        <p style="color: #64748b; margin-top: 0.5rem;">o haz clic para explorar tu equipo</p>
        <input type="file" id="file-input" accept=".csv" />
    </div>

    <div id="workspace">

        <div class="persistence-bar">
            <div>
                <span id="file-info" style="font-size: 0.9rem; font-weight: 600; color: #475569;"></span>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <select id="load-profile-select" class="column-select" style="width: 200px;">
                    <option value="">-- Cargar Formato Guardado --</option>
                    </select>
                <button id="btn-save-profile" disabled style="background: #10b981; color: white; border: none; padding: 0.4rem 0.75rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    💾 Guardar Formato
                </button>
                <button id="btn-export" style="background: var(--primary); color: white; border: none; padding: 0.4rem 1rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    🚀 Exportar CSV Ianseo
                </button>
            </div>
        </div>

        <div class="split-layout">
            <div class="panel left-panel">
                <div style="margin-bottom: 0.5rem; font-size: 0.8rem; color: #64748b; text-transform: uppercase; font-weight: 600;">
                    Previsualización del origen (As is)
                </div>
                <div class="table-container" style="margin-top: 0;">
                    <table id="csv-table">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

	    <div class="panel right-panel">
                <fieldset class="ianseo-fieldset">
                    <legend>Configuración de Salida Ianseo</legend>

                    <div style="margin-bottom: 0.5rem; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Datos Principales</div>

                    <div class="control-row">
                        <span class="control-label" title="Campo 1: Dorsal / Licencia">1. Bib / Licencia *</span>
                        <button class="btn-gear" disabled title="Copia directa">-</button>
                        <select class="column-select target-field" data-field-index="1" data-field-name="Bib" data-type="passthrough" data-required="true">
                            <option value="">-- Ignorar --</option>
                        </select>
                    </div>

                    <div class="control-row">
                        <span class="control-label" title="Campo 2: Turno de tiro">2. Sesión *</span>
                        <button class="btn-gear map-trigger" data-field-index="2" data-field-name="Session" title="Mapear turnos a enteros (1, 2...)">⚙️</button>
                        <select class="column-select target-field" data-field-index="2" data-field-name="Session" data-type="mapping" data-required="true" disabled>
                            <option value="">-- Ignorar --</option>
                        </select>
                    </div>

                    <div class="control-row">
                        <span class="control-label" title="Campo 3: División de arco">3. División *</span>
                        <button class="btn-gear map-trigger" data-field-index="3" data-field-name="Division" title="Configurar diccionario">⚙️</button>
                        <select class="column-select target-field" data-field-index="3" data-field-name="Division" data-type="mapping" data-required="true" disabled>
                            <option value="">-- Ignorar --</option>
                        </select>
                    </div>

                    <div class="control-row">
                        <span class="control-label" title="Campo 4: Clase / Edad">4. Clase *</span>
                        <button class="btn-gear map-trigger" data-field-index="4" data-field-name="Class" title="Configurar diccionario">⚙️</button>
                        <select class="column-select target-field" data-field-index="4" data-field-name="Class" data-type="mapping" data-required="true" disabled>
                            <option value="">-- Ignorar --</option>
                        </select>
                    </div>

                    <div style="margin-top: 1rem; margin-bottom: 0.5rem; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Arquero/a</div>

                    <div class="control-row">
                        <span class="control-label" title="Campo 11: Apellidos">11. Apellidos *</span>
                        <button class="btn-gear" disabled title="Texto libre">-</button>
                        <select class="column-select target-field" data-field-index="11" data-field-name="LastName" data-type="passthrough" data-required="true">
                            <option value="">-- Ignorar --</option>
                        </select>
                    </div>

                    <div class="control-row">
                        <span class="control-label" title="Campo 12: Nombre">12. Nombre *</span>
                        <button class="btn-gear" disabled title="Texto libre">-</button>
                        <select class="column-select target-field" data-field-index="12" data-field-name="Name" data-type="passthrough" data-required="true">
                            <option value="">-- Ignorar --</option>
                        </select>
                    </div>

                    <div class="control-row">
                        <span class="control-label" title="Campo 13: Género">13. Género *</span>
                        <button class="btn-gear map-trigger" data-field-index="13" data-field-name="Gender" title="Configurar diccionario">⚙️</button>
                        <select class="column-select target-field" data-field-index="13" data-field-name="Gender" data-type="mapping" data-required="true" disabled>
                            <option value="">-- Ignorar --</option>
                        </select>
                    </div>

                    <div class="control-row">
                        <span class="control-label" title="Campo 16: Fecha de nacimiento (YYYY-MM-DD)">16. Nacimiento</span>
                        <button class="btn-gear" disabled title="Requiere formato YYYY-MM-DD">-</button>
                        <select class="column-select target-field" data-field-index="16" data-field-name="DOB" data-type="passthrough-date">
                            <option value="">-- Ignorar --</option>
                        </select>
                    </div>

                    <div style="margin-top: 1rem; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Afiliaciones (País / Club / Equipo)</div>

                    <div class="affiliation-block" id="affil-1-container">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: #334155;">Principal (Campos 14-15)</span>
                            <select class="column-select affil-mode-select" data-module="1" style="width: auto; font-size: 0.75rem;">
                                <option value="passthrough">Passthrough</option>
                                <option value="mapping">Mapping</option>
                            </select>
                        </div>
                        <div class="control-row">
                            <span class="control-label">14. Código / ID</span>
                            <button class="btn-gear map-trigger affil-gear" data-field-index="14" data-field-name="Affil1" data-module="1" disabled>⚙️</button>
                            <select class="column-select target-field affil-code" data-field-index="14" data-field-name="Affil1Code" data-module="1">
                                <option value="">-- Ignorar --</option>
                            </select>
                        </div>
                        <div class="control-row affil-name-row" id="affil-1-name-row">
                            <span class="control-label">15. Nombre Oficial</span>
                            <button class="btn-gear" disabled>-</button>
                            <select class="column-select target-field" data-field-index="15" data-field-name="Affil1Name" data-module="1">
                                <option value="">-- Ignorar --</option>
                            </select>
                        </div>
                    </div>

                    <div class="affiliation-block disabled" id="affil-2-container">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: #334155;">Secundaria (Campos 18-19)</span>
                            <select class="column-select affil-mode-select" data-module="2" style="width: auto; font-size: 0.75rem;" disabled>
                                <option value="passthrough">Passthrough</option>
                                <option value="mapping">Mapping</option>
                            </select>
                        </div>
                        <div class="control-row">
                            <span class="control-label">18. Código / ID</span>
                            <button class="btn-gear map-trigger affil-gear" data-field-index="18" data-field-name="Affil2" data-module="2" disabled>⚙️</button>
                            <select class="column-select target-field affil-code" data-field-index="18" data-field-name="Affil2Code" data-module="2" disabled>
                                <option value="">-- Ignorar --</option>
                            </select>
                        </div>
                        <div class="control-row affil-name-row" id="affil-2-name-row">
                            <span class="control-label">19. Nombre Oficial</span>
                            <button class="btn-gear" disabled>-</button>
                            <select class="column-select target-field" data-field-index="19" data-field-name="Affil2Name" data-module="2" disabled>
                                <option value="">-- Ignorar --</option>
                            </select>
                        </div>
                    </div>

                    <div class="affiliation-block disabled" id="affil-3-container">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.8rem; font-weight: 700; color: #334155;">Terciaria (Campos 20-21)</span>
                            <select class="column-select affil-mode-select" data-module="3" style="width: auto; font-size: 0.75rem;" disabled>
                                <option value="passthrough">Passthrough</option>
                                <option value="mapping">Mapping</option>
                            </select>
                        </div>
                        <div class="control-row">
                            <span class="control-label">20. Código / ID</span>
                            <button class="btn-gear map-trigger affil-gear" data-field-index="20" data-field-name="Affil3" data-module="3" disabled>⚙️</button>
                            <select class="column-select target-field affil-code" data-field-index="20" data-field-name="Affil3Code" data-module="3" disabled>
                                <option value="">-- Ignorar --</option>
                            </select>
                        </div>
                        <div class="control-row affil-name-row" id="affil-3-name-row">
                            <span class="control-label">21. Nombre Oficial</span>
                            <button class="btn-gear" disabled>-</button>
                            <select class="column-select target-field" data-field-index="21" data-field-name="Affil3Name" data-module="3" disabled>
                                <option value="">-- Ignorar --</option>
                            </select>
                        </div>
                    </div>

		    <!-- BLOQUE D: INSCRIPCIONES A EVENTOS (Siempre visible) -->
                    <div style="margin-top: 1.25rem; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 0.5rem;">
                        Inscripciones a Eventos
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">

                        <!-- 6) Indiv. Div/Class -->
                        <div class="event-block" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.5rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <span class="control-label" style="width: auto; font-weight: 700; color: #334155;">6. Evento Indiv. (Clasif)</span>
                                <select class="column-select event-mode-select" data-field-name="IndDivClass" style="width: 170px; font-size: 0.75rem; background: #fff;">
                                    <option value="force-yes">Forzar Inscripción (1)</option>
                                    <option value="force-no">No Inscrito (Vacío)</option>
                                    <option value="mapping">Depende de columna...</option>
                                </select>
                            </div>
                            <div class="control-row event-mapping-row" style="display: none; border-bottom: none; padding: 0;">
                                <span class="control-label" style="font-size: 0.75rem; color: #64748b;">Columna Origen:</span>
                                <button class="btn-gear map-trigger" data-field-index="6" data-field-name="IndDivClass" title="Mapear valores a 1">⚙️</button>
                                <select class="column-select target-field" data-field-index="6" data-field-name="IndDivClass" data-type="boolean-mapping" disabled>
                                    <option value="">-- Seleccionar --</option>
                                </select>
                            </div>
                        </div>

                        <!-- 7) Team - Division/Class -->
                        <div class="event-block" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.5rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <span class="control-label" style="width: auto; font-weight: 700; color: #334155;">7. Evento Equipos (Clasif)</span>
                                <select class="column-select event-mode-select" data-field-name="TeamDivClass" style="width: 170px; font-size: 0.75rem; background: #fff;">
                                    <option value="force-yes">Forzar Inscripción (1)</option>
                                    <option value="force-no">No Inscrito (Vacío)</option>
                                    <option value="mapping">Depende de columna...</option>
                                </select>
                            </div>
                            <div class="control-row event-mapping-row" style="display: none; border-bottom: none; padding: 0;">
                                <span class="control-label" style="font-size: 0.75rem; color: #64748b;">Columna Origen:</span>
                                <button class="btn-gear map-trigger" data-field-index="7" data-field-name="TeamDivClass" title="Mapear valores a 1">⚙️</button>
                                <select class="column-select target-field" data-field-index="7" data-field-name="TeamDivClass" data-type="boolean-mapping" disabled>
                                    <option value="">-- Seleccionar --</option>
                                </select>
                            </div>
                        </div>

                        <!-- 8) Ind. Events (Eliminatorias/Finales) -->
                        <div class="event-block" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.5rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <span class="control-label" style="width: auto; font-weight: 700; color: #334155;">8. Eliminatorias Indiv.</span>
                                <select class="column-select event-mode-select" data-field-name="IndEvents" style="width: 170px; font-size: 0.75rem; background: #fff;">
                                    <option value="force-yes">Forzar Inscripción (1)</option>
                                    <option value="force-no">No Inscrito (Vacío)</option>
                                    <option value="mapping">Depende de columna...</option>
                                </select>
                            </div>
                            <div class="control-row event-mapping-row" style="display: none; border-bottom: none; padding: 0;">
                                <span class="control-label" style="font-size: 0.75rem; color: #64748b;">Columna Origen:</span>
                                <button class="btn-gear map-trigger" data-field-index="8" data-field-name="IndEvents" title="Mapear valores a 1">⚙️</button>
                                <select class="column-select target-field" data-field-index="8" data-field-name="IndEvents" data-type="boolean-mapping" disabled>
                                    <option value="">-- Seleccionar --</option>
                                </select>
                            </div>
                        </div>

                        <!-- 9) Team Events (Eliminatorias Equipos) -->
                        <div class="event-block" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.5rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <span class="control-label" style="width: auto; font-weight: 700; color: #334155;">9. Eliminatorias Equipos</span>
                                <select class="column-select event-mode-select" data-field-name="TeamEvents" style="width: 170px; font-size: 0.75rem; background: #fff;">
                                    <option value="force-yes">Forzar Inscripción (1)</option>
                                    <option value="force-no">No Inscrito (Vacío)</option>
                                    <option value="mapping">Depende de columna...</option>
                                </select>
                            </div>
                            <div class="control-row event-mapping-row" style="display: none; border-bottom: none; padding: 0;">
                                <span class="control-label" style="font-size: 0.75rem; color: #64748b;">Columna Origen:</span>
                                <button class="btn-gear map-trigger" data-field-index="9" data-field-name="TeamEvents" title="Mapear valores a 1">⚙️</button>
                                <select class="column-select target-field" data-field-index="9" data-field-name="TeamEvents" data-type="boolean-mapping" disabled>
                                    <option value="">-- Seleccionar --</option>
                                </select>
                            </div>
                        </div>

                        <!-- 10) Mixed Team Events -->
                        <div class="event-block" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.5rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <span class="control-label" style="width: auto; font-weight: 700; color: #334155;">10. Equipos Mixtos</span>
                                <select class="column-select event-mode-select" data-field-name="MixedEvents" style="width: 170px; font-size: 0.75rem; background: #fff;">
                                    <option value="force-yes">Forzar Inscripción (1)</option>
                                    <option value="force-no">No Inscrito (Vacío)</option>
                                    <option value="mapping">Depende de columna...</option>
                                </select>
                            </div>
                            <div class="control-row event-mapping-row" style="display: none; border-bottom: none; padding: 0;">
                                <span class="control-label" style="font-size: 0.75rem; color: #64748b;">Columna Origen:</span>
                                <button class="btn-gear map-trigger" data-field-index="10" data-field-name="MixedEvents" title="Mapear valores a 1">⚙️</button>
                                <select class="column-select target-field" data-field-index="10" data-field-name="MixedEvents" data-type="boolean-mapping" disabled>
                                    <option value="">-- Seleccionar --</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <!-- BLOQUE E: CAMPOS EXTRA / OPCIONALES (Plegados) -->
                    <details style="margin-top: 1.5rem; border-top: 1px solid #e2e8f0; padding-top: 0.75rem;">
                        <summary style="font-size: 0.8rem; font-weight: 700; color: var(--primary); cursor: pointer; user-select: none;">
                            + Mostrar Campos Extra (Diana / Subclase)
                        </summary>
                        <div style="padding-top: 0.75rem;">
                            <!-- 5) Target -->
                            <div class="control-row">
                                <span class="control-label">5. Diana (Target)</span>
                                <button class="btn-gear" disabled>-</button>
                                <select class="column-select target-field" data-field-index="5" data-field-name="Target" data-type="passthrough">
                                    <option value="">-- Ignorar --</option>
                                </select>
                            </div>
                            <!-- 17) Subclass -->
                            <div class="control-row">
                                <span class="control-label">17. Subclase</span>
                                <button class="btn-gear" disabled>-</button>
                                <select class="column-select target-field" data-field-index="17" data-field-name="Subclass" data-type="passthrough">
                                    <option value="">-- Ignorar --</option>
                                </select>
                            </div>
                        </div>
                    </details>

                </fieldset>
            </div>

        </div>

        <div id="mapping-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center;">
            <div style="background: white; width: 600px; max-height: 90vh; border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
                <div style="padding: 1rem 1.5rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <h3 id="modal-title" style="margin: 0; font-size: 1.1rem; color: var(--primary);">Configuración de Mapa</h3>
                    <button id="btn-close-modal" style="border: none; background: none; font-size: 1.2rem; cursor: pointer;">×</button>
                </div>
                <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
                    <button id="btn-autopopulate" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 0.4rem 0.75rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer; width: 100%;">
                        ✨ Pre-poblar claves detectadas en la columna origen
                    </button>
                </div>
                <div id="modal-rules-container" style="padding: 1.5rem; overflow-y: auto; flex-grow: 1;">
                    </div>
                <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right;">
                    <button id="btn-save-map" style="background: var(--primary); color: white; border: none; padding: 0.5rem 1.25rem; border-radius: 4px; font-weight: 600; cursor: pointer;">
                        Guardar Mapa en RAM
                    </button>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
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


</script>
</body>
</html>
