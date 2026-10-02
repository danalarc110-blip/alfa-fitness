# Equipo de 10 agentes para Alpha Fitness

Agentes personalizados para Google Antigravity, en español. El líder coordina nueve especialistas y elige solo los necesarios.

| Agente | Uso |
| --- | --- |
| orquestador-lider | Alcance, delegación, integración y cierre |
| analista-requisitos | Reglas y criterios verificables |
| ingeniero-core | Backend y datos |
| ingeniero-ui | Interfaz y accesibilidad |
| qa-tester | Pruebas y regresión |
| auditor-logica | Invariantes y estados |
| especialista-seguridad | Autenticación, permisos y datos |
| redactor-documentacion | Manual técnico y de usuario finales, solo por orden explícita |
| creador-diagramas | Casos de uso UML, ER, secuencias y otros diagramas, solo por orden explícita |
| revisor-creativo | Errores con evidencia e ideas útiles, sin implementar |

## Distribución e instalación

En este repositorio ya están en `.agents/agents/<nombre>/agent.md`. La carpeta `agentes/` y `agentes.zip` distribuyen el mismo contenido. Para otro proyecto, copiar agentes/AGENTS.md a la raíz y los diez directorios de agentes a `.agents/agents/`. Revisar el descubrimiento con `/agents` en Antigravity. Estos archivos no configuran subagentes nativos de Codex; AGENTS.md sí aporta reglas al trabajo en el repositorio.

## Órdenes de ejemplo

- «Revisor creativo: examina el flujo de membresías y presenta errores e ideas priorizadas, sin cambiar código».
- «Documentador final: crea el manual técnico y el manual de usuario de esta versión».
- «Creador de diagramas: genera los casos de uso UML con fichas y el ER de esta versión».

Durante programación, los dos agentes finales permanecen inactivos. Una orden de manuales no autoriza nuevos diagramas. No hay vigilancia ni ejecución continua por tener instalados los archivos.

## Mantenimiento

Mantener idénticos los agent.md de ambas carpetas y las dos copias de AGENTS.md; regenerar agentes.zip. Consultar GUIA_HERRAMIENTAS.md y el protocolo. El control por orden está en las descripciones, los cuerpos de los dos agentes y el líder; no se inventa una propiedad YAML de activación.

Referencia del formato y descubrimiento: https://antigravity.google/docs/subagents/
