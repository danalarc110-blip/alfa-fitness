# Equipo portable de 10 agentes — protocolo 3.2

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

En este repositorio ya están en `.agents/agents/<nombre>/agent.md`. La carpeta `agentes/` y `agentes.zip` distribuyen el mismo contenido. Para instalar en otro proyecto:

1. Revisar sus reglas locales (`AGENTS.md`, `GEMINI.md` u otras reconocidas por el host) y los agentes ya instalados antes de copiar.
2. Copiar el paquete `agentes/`; si hay archivos existentes, comparar el diff y preservar sus cambios.
3. Si no existen reglas locales, crear AGENTS.md raíz a partir del protocolo. Si existen, conservarlas e integrar solo cláusulas compatibles o una instrucción para consultar `agentes/AGENTS.md` como protocolo de colaboración subordinado a las reglas del proyecto y del usuario/host. No reemplazar reglas locales por rutina.
4. Copiar los diez directorios de agentes a `.agents/agents/`. Si hay identificadores coincidentes, resolver la actualización con el diff antes de reemplazarlos; no instalar duplicados con el mismo nombre.

Revisar el descubrimiento con `/agents` en Antigravity. Estos archivos no configuran subagentes nativos de Codex; AGENTS.md sí aporta reglas al trabajo en el repositorio.

## Órdenes de ejemplo

- «Revisor creativo: examina el flujo de membresías y presenta errores e ideas priorizadas, sin cambiar código».
- «Documentador final: crea el manual técnico y el manual de usuario de esta versión».
- «Creador de diagramas: genera los casos de uso UML con fichas y el ER de esta versión».

Durante programación, los dos agentes finales permanecen inactivos. Una orden de manuales no autoriza nuevos diagramas. No hay vigilancia ni ejecución continua por tener instalados los archivos.

## Mantenimiento

En este repositorio mantenedor, mantener idénticos los agent.md de ambas carpetas y las dos copias de AGENTS.md; regenerar agentes.zip. En proyectos receptores conservar sus reglas propias: no exigir que su AGENTS.md raíz sea idéntico al del paquete. Consultar GUIA_HERRAMIENTAS.md y el protocolo. El control por orden está en las descripciones, los cuerpos de los dos agentes y el líder; no se inventa una propiedad YAML de activación.

Referencia del formato y descubrimiento: https://antigravity.google/docs/subagents/

## Colaboración y portabilidad

Todos leen el protocolo común y detectan runtimes, versiones, scripts, SO y restricciones. GUIA_PILAS.md ayuda a adaptar pruebas y herramientas a web, móvil, escritorio, CLI, datos, sistemas y firmware. No instalan otra tecnología por rutina ni afirman pruebas imposibles en el entorno.

El líder fija revisión, propiedad, dependencias y contratos; Core/UI implementan, QA verifica, Lógica/Seguridad revisan riesgos y el Creativo propone. T-ID/R-ID/C-ID/H-ID enlazan contexto y evidencia cuando hace falta. Los diez roles comparten estados de entrega; las plantillas antiguas quedan subordinadas al protocolo. Cambios en código, configuración, dependencias, datos de prueba o entorno invalidan la evidencia afectada; confirmar el contexto realmente ejecutado.

Cambios de colaboradores se integran por commits/diffs sobre la base actual, preservando trabajo ajeno y verificando el resultado combinado. Si la herramienta no ofrece subagentes, los roles se aplican secuencialmente y se declara la independencia limitada.

Evaluación y mejoras de cada rol: [INFORME_MEJORA_EQUIPO.md](INFORME_MEJORA_EQUIPO.md).

Correcciones recientes: [REVISION_3_2.md](REVISION_3_2.md). Revisión anterior: [REVISION_3_1.md](REVISION_3_1.md).
