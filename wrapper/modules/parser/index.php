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
    <link rel="stylesheet" href="assets/css/parser.css">
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
		<div id="event-context-bar" style="display: flex; gap: 1.5rem; background: #ffffff; border: 1px solid var(--border-color); padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; align-items: center; box-shadow: 0 1px 2px rgba(0,0,0,0.02); flex-wrap: wrap;">
        	    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <label for="event-date" style="font-size: 0.85rem; font-weight: 600; color: #475569;">📅 Fecha Torneo:</label>
                        <input type="date" id="event-date" style="padding: 0.35rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; outline: none; border-left: 3px solid var(--primary);">
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <label for="start-row" style="font-size: 0.85rem; font-weight: 600; color: #475569;">🔢 Fila Inicial:</label>
                        <input type="number" id="start-row" min="1" value="2" style="padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; width: 60px; font-size: 0.85rem; outline: none; text-align: center;">
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <label for="end-row" style="font-size: 0.85rem; font-weight: 600; color: #475569;">🏁 Fila Final:</label>
                        <input type="number" id="end-row" min="1" value="2" style="padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; width: 70px; font-size: 0.85rem; outline: none; text-align: center;">
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: auto;">
                        <input type="checkbox" id="preview-mode-toggle" style="width: auto; cursor: pointer; margin: 0;">
                        <label for="preview-mode-toggle" style="font-size: 0.85rem; font-weight: 600; color: #475569; cursor: pointer; user-select: none;">👁️ Mostrar todo (sombreado)</label>
                    </div>
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
                        <div style="display: flex; align-items: center; gap: 6px; flex: 1;">
                            <button class="btn-gear map-trigger" data-field-index="2" data-field-name="Session" title="Mapear turnos a enteros (1, 2...)">⚙️</button>
                            <select class="column-select target-field" data-field-index="2" data-field-name="Session" data-type="mapping" data-required="true" disabled style="flex: 1; min-width: 0;">
                                <option value="">-- Ignorar / Fijo --</option>
                            </select>
                            <input type="number" id="session-fixed-value" min="1" step="1" placeholder="Fijo (ej: 1)" style="width: 90px; padding: 0.35rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; outline: none; border-left: 3px solid var(--primary); display: none;" title="Introducir número de sesión fija">
                        </div>
                    </div>

                    <div class="control-row">
                        <span class="control-label" title="Campo 3: División de arco">3. División *</span>
                        <button class="btn-gear map-trigger" data-field-index="3" data-field-name="Division" title="Configurar diccionario">⚙️</button>
                        <select class="column-select target-field" data-field-index="3" data-field-name="Division" data-type="mapping" data-required="true" disabled>
                            <option value="">-- Ignorar --</option>
                        </select>
                    </div>

                    <div class="control-row" style="border-bottom: none; padding-bottom: 0;">
                        <span class="control-label" title="Campo 4: Clase / Edad">4. Clase *</span>
                        <button class="btn-gear map-trigger" data-field-index="4" data-field-name="Class" title="Configurar diccionario">⚙️</button>
                        <select class="column-select target-field" data-field-index="4" data-field-name="Class" data-type="mapping" data-required="true" disabled>
                            <option value="">-- Ignorar --</option>
                        </select>
                    </div>
                    <div class="age-validation-toggle-row" style="margin-left: 2rem; margin-top: 0.15rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                        <input type="checkbox" id="class-age-validation-toggle" style="width: auto; cursor: pointer; margin: 0;">
                        <label for="class-age-validation-toggle" style="font-size: 0.75rem; color: #475569; cursor: pointer; user-select: none; font-weight: 600;">
                            ⚡ Activar validación por edad (requiere Campo 16)
                        </label>
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

<script src="assets/js/parser.js"></script>

</body>
</html>
