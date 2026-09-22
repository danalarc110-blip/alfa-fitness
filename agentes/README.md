# Equipo de 7 Agentes para Google Antigravity

Versión reforzada del equipo de desarrollo. Incluye cinco agentes mejorados y dos nuevos especialistas:

- `analista-requisitos`: elimina ambigüedades y crea criterios de aceptación trazables.
- `especialista-seguridad`: revisa autenticación, autorización, entradas, datos y dependencias.

Todos aplican un protocolo común de comprensión, alcance, evidencia y traspaso de contexto.

---

## Estructura del paquete

```text
agentes/
├── AGENTS.md
├── GUIA_HERRAMIENTAS.md
├── README.md
├── orquestador-lider/agent.md
├── analista-requisitos/agent.md
├── ingeniero-core/agent.md
├── ingeniero-ui/agent.md
├── qa-tester/agent.md
├── auditor-logica/agent.md
└── especialista-seguridad/agent.md
```

## Instalación recomendada

En el proyecto donde se usarán:

```text
proyecto/
├── AGENTS.md                         # Copia de agentes/AGENTS.md
└── .agents/
    └── agents/
        ├── orquestador-lider/agent.md
        ├── analista-requisitos/agent.md
        ├── ingeniero-core/agent.md
        ├── ingeniero-ui/agent.md
        ├── qa-tester/agent.md
        ├── auditor-logica/agent.md
        └── especialista-seguridad/agent.md
```

Después abre el proyecto en Antigravity y usa el panel `/agents` para comprobar que los siete agentes fueron descubiertos. `orquestador-lider` puede seleccionarse como agente principal; los otros seis están configurados como subagentes.

---

## Uso rápido

- Para una petición amplia o poco clara, inicia con `orquestador-lider`; él delegará primero a `analista-requisitos` si hace falta.
- Para una tarea pequeña, invoca solo al especialista correspondiente y a una verificación proporcional.
- Activa siempre `especialista-seguridad` cuando el cambio toque login, roles, permisos, sesiones, pagos, archivos, consultas, datos personales o secretos.
- No copies simultáneamente dos agentes sobre los mismos archivos sin worktrees o propiedad explícita.

---

## Frontmatter compatible

Cada `agent.md` usa propiedades documentadas para agentes personalizados:

```yaml
---
name: ingeniero-core
description: Descripción precisa para que el planificador sepa cuándo delegar.
mainAgent: false
subagent: true
model: inherit
commandExecutionPolicy: sandbox
tools:
  - view_file
  - grep_search
  - find_by_name
  - list_dir
  - write_to_file
  - replace_file_content
  - run_command
  - manage_task
  - send_message
  - search_web
  - read_url_content
---
```

Campos opcionales documentados: `mcpServers`, `skills` y `plugins`. No se incluyen nombres inventados como `enable_write_tools`, `enable_mcp_tools` o `enable_subagent_tools`, porque no pertenecen al esquema actual de agentes Markdown.

> Antigravity advierte que un nombre de herramienta desconocido o mal escrito puede bloquear el inicio de un subagente. Conserva los nombres exactos y confirma cualquier herramienta nueva en tu versión antes de agregarla.

Documentación oficial de referencia: [Custom subagents](https://antigravity.google/docs/subagents/).

---

## Qué se mejoró

- Contexto completo en cada delegación, porque los subagentes no heredan la conversación del padre.
- Diferenciación entre hechos, inferencias, supuestos y recomendaciones.
- Preguntas solo cuando una decisión sea material; primero se inspecciona lo que puede descubrirse.
- Propiedad de archivos y aislamiento para evitar colisiones entre agentes.
- Criterios de aceptación y matriz de trazabilidad requisito → implementación → prueba.
- Puertas separadas de lógica, QA, seguridad, UI y datos.
- Informes con comandos, códigos de salida, pruebas no ejecutadas y riesgos restantes.
- Menor solapamiento entre QA, auditoría lógica y seguridad.
