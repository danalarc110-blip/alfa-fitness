# Mejora del equipo de agentes — protocolo 3.0

Fecha: 2 de octubre de 2026. Base: `3ab0a960e4453fe1d4e35b1934910b08950ebbd7`. Repositorio: https://github.com/danalarc110-blip/alfa-fitness.

Los diez agentes fueron reforzados como equipo portable. Alpha Fitness es su ubicación actual, no su especialización obligatoria. Detectan lenguaje, versiones, framework, SO, scripts, datos y herramientas antes de trabajar. La guía de pilas cubre web, móvil, escritorio, CLI, Python/datos, JVM, .NET, Go, Rust, PHP, C/C++, Swift, Flutter y firmware sin obligar a instalar todas esas tecnologías.

## Mejoras de cada agente

| Agente | Mejora concreta |
| --- | --- |
| Orquestador líder | Equipo proporcional, contrato común Core/UI, revisión base y snapshot, dependencias, propiedad de archivos, integración de commits/diffs y ZIP sin sobrescribir trabajo local, resolución de desacuerdos y cierre con evidencia vigente. |
| Analista | Separa intención, comportamiento actual y decisión; criterios con ejemplos/límites/permisos; propone contratos consumibles; impide convertir ideas o bugs en requisitos. |
| Core | Contratos entre capas, garantías según motor, atomicidad e idempotencia, sistemas nativos/firmware, memoria/temporización y datos/ML cuando apliquen; distingue simulación de hardware y transfiere reproducciones a revisores. |
| UI | Persistencia real, recuperación, doble envío y respuestas tardías; adaptación web/nativo/CLI, contrato con Core y validación por runner/navegador/emulador/dispositivo disponible. |
| QA | Aislamiento efectivo, oráculo independiente, matriz criterio→prueba→evidencia, límites de mocks/SQLite/simuladores, diagnóstico de flakiness sin borrar fallos y estados uniformes. |
| Lógica | Invariantes falsables, secuencias/intercalados mínimos, propiedades pertinentes, separación sospecha/demostración/reproducción y revisión del arreglo en la propiedad original. |
| Seguridad | Fronteras reales por arquitectura, superficies alcanzables, entradas del repositorio tratadas como datos no confiables, pruebas aisladas sin bloqueo innecesario, avisos de dependencia con versión/alcance y remediación comprobable. |
| Documentador final | Manuales reproducibles para cualquier pila, requisitos frente a implementación, procedimientos ejecutados/no ejecutados explícitos, capturas reales, formatos con renderizado disponible y deuda sin activación automática. |
| Diagramador | Notación adecuada, actores/fronteras/relaciones sustentados, estados en DB/memoria/dispositivo, revisión y fuentes por diagrama, validación sintáctica y semántica separadas. |
| Creativo | Ideas como hipótesis con beneficio/coste/riesgo/evidencia, búsqueda de funciones ya existentes, separación de error/fricción/preferencia, prioridad por valor y cooperación sin implementar ni duplicar auditorías. |

## Cómo cooperan

- Todos consumen el mismo contrato de tarea y usan estados comunes. Requisitos, contratos, hallazgos e invariantes tienen IDs cuando hacen falta; las tareas pequeñas no generan burocracia innecesaria.
- El líder delega y asigna escritura. Un especialista puede comunicar hallazgos, pero no reasigna propiedad ni inicia encargos a otros agentes por su cuenta.
- Core y UI acuerdan contrato antes de implementar ambos lados. QA, Lógica y Seguridad revisan riesgos diferentes y reutilizan IDs para consolidar causas compartidas.
- Evidencia ligada a commit o snapshot de trabajo sin commit: si cambia una dependencia, se invalida la comprobación afectada. Build aprobado no equivale a permisos, UX o dominio aprobados.
- Un fallo material conserva CORRECCION_REQUERIDA y mantiene la tarea abierta. Una decisión pendiente detiene solo las tareas que dependen de ella. Dos intentos fallidos por la misma causa requieren nueva hipótesis o bloqueo explicado.
- Manuales finales y diagramas siguen ejecutándose solo por orden explícita para su entregable. Pedir manuales no activa diagramas; mejorar las instrucciones tampoco genera manuales de la aplicación.

## Evaluación de escenarios

Dos revisores independientes aplicaron las instrucciones en ejercicios de razonamiento, solo lectura. No ejecutaron aplicaciones ni Antigravity. Se corrigieron los hallazgos y se realizó una segunda revisión acotada.

| Escenario evaluado | Conducta revisada y resultado |
| --- | --- |
| Python API + React; ZIP de colaborador, trabajo local, doble reserva, API ambigua, permiso fallido y revisión antigua | Conservar trabajo local; ZIP aislado y diff sin inferir borrados; contrato con escritor único; clarificar solo decisiones materiales; reproducir efectos y permisos; actualizar evidencia afectada. No cerrar por build verde ni generar manuales/diagramas con un informe de avance. |
| Flutter + Rust; navegación e ideas; sin emulador | UI/QA y creativo según encargo; Core solo si cambia backend/contrato. Buscar runners/dispositivo disponibles y declarar cobertura. No equiparar widget/build a interacción nativa completa ni implementar ideas sin alcance. |
| Firmware C++; temporización intermitente; compilador/simulador | Core/Lógica/QA; definir propiedad y fuentes, explorar intercalados/overflow/temporización, conservar fallo, declarar pruebas simuladas y medición física pendiente. No prometer comportamiento físico desde compilación. |
| Manual técnico y usuario; capturas faltantes; sin orden de diagramas | Redactor y apoyo puntual; fuentes y procedimientos trazables; borrador con capturas/validaciones pendientes. Diagramador inactivo; no reemplazar capturas reales por imágenes generadas. |

Hallazgos corregidos durante la evaluación: ejemplos de activación cruzados; estados de entrega divergentes; regla documental «el código gana»; reproducción versus demostración estática sin clasificación uniforme; identidad de revisión en árbol sucio; base incierta de ZIP; instrucciones web tratadas como universales; enrutamiento insuficiente de Core a firmware; petición de un agente de navegador no garantizado; y lenguaje de certificación más fuerte que el alcance disponible.

## Verificación del paquete

Se verifican YAML, campos/tipos requeridos, nombres únicos y coincidentes con directorios, herramientas sin nombres nuevos respecto al paquete base, modelo heredado y política sandbox. Los diez agent.md se mantienen idénticos en agentes/ y .agents/agents/; AGENTS.md raíz coincide con agentes/AGENTS.md. Se comprueban referencias, diff sin errores de espacio y contenido íntegro de agentes.zip tras regenerarlo.

Las pruebas funcionales de Alpha Fitness no forman parte de esta validación: no se modificó código de la aplicación. El informe evalúa instrucciones y colaboración, no demuestra dominio práctico de todas las tecnologías ni ausencia de errores.

## Límites reales

No se ejecutó el descubrimiento de agentes ni sus herramientas dentro de Antigravity. El cumplimiento del modelo, el acceso a dispositivos y servicios y los resultados de implementación requieren pruebas en el entorno real. Los escenarios son evaluaciones del protocolo, no ejecuciones E2E. Estos archivos usan el formato de Antigravity; otros hosts requieren adaptación, o pueden aplicar las responsabilidades secuencialmente sin fingir subagentes.

No se asigna un 10/10 por redacción: las mejoras están justificadas por comportamientos, hallazgos corregidos y límites de evidencia. El siguiente paso práctico es ejecutar un encargo real acotado en la instalación y comparar criterios→resultado→pruebas.

Fuentes primarias consultadas: https://antigravity.google/docs/subagents/. Para contrastar el contexto actual de Alpha Fitness: https://laravel.com/docs/12.x/testing, https://laravel.com/docs/12.x/database y https://laravel.com/docs/12.x/authorization. Las fuentes de Laravel no limitan el alcance portable de los agentes; otro proyecto requiere su propia documentación oficial.
