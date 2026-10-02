# Guía de Herramientas y Compatibilidad

Esta guía documenta las herramientas usadas por los diez agentes. La lista se mantiene con nombres exactos para evitar fallos de validación al iniciar subagentes.

---

## 1. Herramientas nativas utilizadas

### Inspección del proyecto

| Herramienta | Uso correcto |
| :--- | :--- |
| `view_file` | Leer un archivo o fragmento relevante antes de editarlo. |
| `grep_search` | Buscar símbolos, usos, contratos o patrones dentro del proyecto. |
| `find_by_name` | Localizar archivos o directorios mediante nombres y patrones. |
| `list_dir` | Comprender una zona concreta de la estructura sin recorrer todo el repositorio. |

### Edición y ejecución

| Herramienta | Uso correcto |
| :--- | :--- |
| `write_to_file` | Crear un archivo nuevo o reemplazarlo cuando el alcance lo autorice. |
| `replace_file_content` | Aplicar cambios localizados preservando contenido no relacionado. |
| `run_command` | Ejecutar builds, linters, pruebas, búsquedas o diagnósticos reproducibles. |
| `manage_task` | Consultar, alimentar o detener procesos largos iniciados en segundo plano. |

### Coordinación

| Herramienta | Uso correcto |
| :--- | :--- |
| `invoke_subagent` | Delegar una tarea completa con contexto autosuficiente. |
| `define_subagent` | Crear un especialista temporal solo si ninguno de los diez roles cubre la necesidad. |
| `manage_subagents` | Revisar estados o detener agentes que ya no son necesarios. |
| `send_message` | Comunicar bloqueos, evidencia o cambios de alcance al líder o a otro agente conocido. |
| `schedule` | Programar una comprobación futura; no sustituye una prueba necesaria para cerrar la tarea actual. |

### Investigación y comunicación

| Herramienta | Uso correcto |
| :--- | :--- |
| `search_web` | Consultar información actual cuando pueda haber cambiado; en programación, priorizar fuentes oficiales. |
| `read_url_content` | Leer la fuente primaria encontrada y verificar versión y fecha. |
| `ask_question` | Pedir una decisión material con opciones claras cuando el repositorio no contiene la respuesta. |
| `generate_image` | Crear recursos visuales o conceptos cuando el usuario lo solicite; no sustituye pruebas de renderizado. |
| `list_permissions` | Verificar permisos disponibles antes de una operación sensible. |
| `ask_permission` | Solicitar autorización específica para un recurso o acción bloqueada. |

---

## 2. Navegador y pruebas visuales

Antigravity incluye un subagente de navegador independiente. No se añade `read_browser_page` a los frontmatters porque no figura como herramienta estable del esquema de agentes personalizados y un nombre no reconocido puede dejar el subagente bloqueado.

Cuando una interfaz necesite validación real:

1. `ingeniero-ui` prepara el servidor y define resoluciones, rutas y acciones a comprobar.
2. `orquestador-lider` invoca el agente de navegador disponible en la instalación, o QA usa la herramienta E2E ya configurada en el proyecto.
3. El informe registra navegador, viewport, flujo, capturas/evidencia y limitaciones.

---

## 3. Campos de configuración admitidos

| Campo | Función |
| :--- | :--- |
| `name` | Identificador único del agente. |
| `description` | Señal principal para decidir cuándo delegarle una tarea. |
| `tools` | Lista explícita de herramientas permitidas. |
| `mainAgent` | Permite seleccionarlo como agente principal. |
| `subagent` | Permite invocarlo como subagente. |
| `model` | `inherit`, `flash` o `pro`, según la instalación. |
| `commandExecutionPolicy` | Política de comandos; este paquete usa `sandbox`. |
| `mcpServers` | Servidores MCP específicos del agente. |
| `skills` / `plugins` | Dependencias de habilidades o plugins existentes. |

No agregar propiedades no documentadas para simular capacidades. La autorización real también depende de los permisos heredados del proyecto y del agente padre.

---

## 4. Asignación del paquete

| Agente | Herramientas distintivas | Motivo |
| :--- | :--- | :--- |
| `orquestador-lider` | Coordinación completa, preguntas y permisos | Delimita, delega e integra. |
| `analista-requisitos` | Lectura, búsqueda, documentación y preguntas | Descubre reglas sin tocar producción. |
| `ingeniero-core` | Lectura, edición, comandos e investigación | Implementa y verifica backend/datos. |
| `ingeniero-ui` | Lectura, edición, comandos, investigación e imagen | Implementa interfaz y coordina validación visual. |
| `qa-tester` | Lectura, edición de tests y comandos | Construye reproducciones y ejecuta suites. |
| `auditor-logica` | Lectura, comandos y edición de reproducciones | Prueba invariantes y fallos sutiles. |
| `especialista-seguridad` | Lectura, comandos, investigación y permisos | Audita superficie de ataque sin ampliar privilegios. |

| `redactor-documentacion` | Lectura, edición documental y comandos seguros | Manuales finales únicamente por orden del usuario. |
| `creador-diagramas` | Lectura, edición de fuentes y renderizado | Diagramas únicamente por orden del usuario. |
| `revisor-creativo` | Lectura, comprobaciones seguras e investigación | Examina y recomienda sin modificar producción. |

---

## 5. Servidores MCP

Un MCP solo debe añadirse si existe y la tarea lo necesita. Ejemplos válidos según la instalación:

- Navegador o DevTools para DOM, red, consola, accesibilidad y rendimiento.
- Base de datos para inspección controlada de esquemas y consultas.
- GitHub/GitLab para issues, revisiones o pull requests autorizados.

Reglas:

- Principio de mínimo privilegio.
- Nunca inventar nombres de servidores o herramientas.
- No usar producción para pruebas destructivas.
- No incluir credenciales en `agent.md`.
- Si un permiso falta, informar al líder y pedir solo el alcance necesario.

Referencia oficial: [Custom subagents](https://antigravity.google/docs/subagents/) y [Hooks / nombres de herramientas](https://antigravity.google/docs/hooks).
