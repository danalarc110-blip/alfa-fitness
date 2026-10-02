# Equipo portable de 10 agentes — protocolo 3.0

Agentes personalizados para Google Antigravity, en español e independientes de tecnología. Se guardan en Alpha Fitness, pero detectan y se adaptan a la pila de cada proyecto. El líder coordina nueve especialistas y elige solo los necesarios.

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

En este repositorio ya están en `.agents/agents/<nombre>/agent.md`. La carpeta `agentes/` y `agentes.zip` distribuyen el mismo contenido. Para otro proyecto, copiar el paquete completo `agentes/`, copiar agentes/AGENTS.md a la raíz y los diez directorios de agentes a `.agents/agents/`. Revisar el descubrimiento con `/agents` en Antigravity. Estos archivos no configuran subagentes nativos de Codex; AGENTS.md sí aporta reglas al trabajo en el repositorio.

## Órdenes de ejemplo

- «Revisor creativo: examina el flujo de membresías y presenta errores e ideas priorizadas, sin cambiar código».
- «Documentador final: crea el manual técnico y el manual de usuario de esta versión».
- «Creador de diagramas: genera los casos de uso UML con fichas y el ER de esta versión».

Durante programación, los dos agentes finales permanecen inactivos. Una orden de manuales no autoriza nuevos diagramas. No hay vigilancia ni ejecución continua por tener instalados los archivos.

## Mantenimiento

Mantener idénticos los agent.md de ambas carpetas y las dos copias de AGENTS.md; regenerar agentes.zip. Consultar GUIA_HERRAMIENTAS.md y el protocolo. El control por orden está en las descripciones, los cuerpos de los dos agentes y el líder; no se inventa una propiedad YAML de activación.

Referencia del formato y descubrimiento: https://antigravity.google/docs/subagents/

## Colaboración y portabilidad

Todos leen el protocolo común y detectan runtimes, versiones, scripts, SO y restricciones. GUIA_PILAS.md ayuda a adaptar pruebas y herramientas a web, móvil, escritorio, CLI, datos, sistemas y firmware. No instalan otra tecnología por rutina ni afirman pruebas imposibles en el entorno.

El líder fija revisión, propiedad, dependencias y contratos; Core/UI implementan, QA verifica, Lógica/Seguridad revisan riesgos y el Creativo propone. T-ID/R-ID/C-ID/H-ID enlazan contexto y evidencia cuando hace falta. Los diez roles comparten estados de entrega; las plantillas antiguas quedan subordinadas al protocolo. Las revisiones se invalidan solo cuando el código del que dependen cambia.

Cambios de colaboradores se integran por commits/diffs sobre la base actual, preservando trabajo ajeno y verificando el resultado combinado. Si la herramienta no ofrece subagentes, los roles se aplican secuencialmente y se declara la independencia limitada.

Evaluación y mejoras de cada rol: [INFORME_MEJORA_EQUIPO.md](INFORME_MEJORA_EQUIPO.md).
