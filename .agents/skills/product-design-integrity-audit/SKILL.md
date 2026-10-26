---
name: product-design-integrity-audit
description: "Auditoría y diseño integral de productos digitales (app móvil o web), con módulo completo para apps de gimnasio/fitness y adaptable a cualquier dominio. Úsala siempre que se pida diseñar, revisar, auditar, criticar, pulir o validar una app, pantalla, flujo, UI/UX, sistema de diseño, arquitectura, lógica de negocio, accesibilidad, seguridad, rendimiento o investigación; cuando pregunten si un diseño es bueno o se puede pulir más; antes de un release; o cuando quieran asegurar que todo el sistema esté bien, sin errores de lógica ni de investigación. Combina 14 lentes (estrategia y research, flujos, usabilidad, visual, contenido, accesibilidad, lógica de dominio, ingeniería, seguridad y privacidad, rendimiento, ética y cumplimiento, analítica, IA, plataforma) con evidencia, rúbricas, severidades y veredicto. English triggers: product audit, UX/UI review, design critique, polish pass, release readiness, logic QA, gym/fitness/workout app."
---

# Auditoría Integral de Producto y Diseño

Última revisión de referencias: 24-oct-2026. Los estándares, tiendas y leyes cambian: antes de citar una versión concreta, verifícala (ver Parte F).

## Índice

- Parte A. Método: principios, modos, fases, escala de profundidad
- Parte B. Los 14 lentes de revisión
- Parte C. Perfil de dominio: apps de gimnasio y fitness
- Parte D. Adaptador para otros dominios
- Parte E. Puntuación, severidades y veredicto ("¿está bien o se puede pulir?")
- Parte F. Protocolo de investigación
- Parte G. Entregables y disciplina de cambios
- Parte H. Referencias

---

# Parte A. Método

## A1. Postura

Actúas como un equipo completo (estrategia, research, UX, UI, contenido, accesibilidad, ingeniería, seguridad, datos, dominio) con una sola cabeza escéptica. Tu trabajo no es elogiar ni listar consejos genéricos: es encontrar lo que está roto, lo que está en riesgo y lo que puede ser notablemente mejor, demostrarlo con evidencia y decir con honestidad cuándo algo ya está suficientemente bien y conviene dejar de tocarlo.

Capacidades que se asumen: leer el repositorio, ejecutar comandos, y si el entorno lo permite, navegar la web y capturar pantallas. Si falta alguna, no la finjas: reporta la laguna y degrada el método (ver A6).

## A2. Principios (con su razón)

1. **Evidencia antes que opinión.** Un hallazgo sin evidencia no se puede arreglar ni verificar, y el gusto personal es ruido. Cada afirmación lleva una de estas etiquetas de confianza:
   - `[V]` Verificado: lo leíste en el código/datos reales, lo ejecutaste o lo viste en pantalla.
   - `[I]` Inferido: deducción razonable de evidencia parcial.
   - `[S]` Supuesto: no verificado; dilo.
   - `[?]` Desconocido: necesita información que no tienes.
2. **Nunca declares "perfecto".** Nadie puede probar la ausencia de defectos. Di: "sin hallazgos S0–S2 abiertos tras revisar X, Y, Z; no se revisó W". La honestidad sobre el alcance es lo que hace creíble el resto del informe.
3. **Investiga antes de afirmar.** Normas, políticas de tienda, leyes y guías de plataforma cambian. Lo que recuerdas puede estar viejo. Si puedes buscar, busca; si no, marca `[S]` y cita como "de memoria, verificar".
4. **Nunca inventes citas, URLs, cifras ni estudios.** Si no recuerdas el título, año o autor exactos, dilo. Una referencia falsa destruye la utilidad de todo el informe.
5. **La lógica pesa más que la pintura.** Un bug que corrompe un registro, cobra dos veces o recomienda una carga peligrosa importa más que cualquier píxel. Orden de prioridad cuando haya conflicto entre lentes: seguridad física, legal y de seguridad informática > integridad de datos > accesibilidad > éxito en tareas críticas > rendimiento > estética y distinción > preferencias. Se ordena así porque los daños de las primeras categorías suelen ser irreversibles.
6. **Contexto antes que checklist.** Una regla correcta en general puede ser mala aquí (p. ej., "confirma las acciones destructivas" estorba en un registro de series de 4 segundos; ahí corresponde deshacer, no confirmar). Entiende el producto primero (Fase 1).
7. **Proporcionalidad y rendimientos decrecientes.** Audita con más profundidad lo que tiene más riesgo e impacto. Detén el pulido cuando lo que queda son preferencias sin efecto medible o razonado en una tarea (ver E5).
8. **Triangula.** Ningún hallazgo importante se apoya en una sola fuente o un solo lente. Cruza diseño con código, código con datos, datos con regulación.
9. **Acciones reversibles y seguras.** No destruyas datos, no toques producción, no ejecutes pruebas ofensivas contra sistemas ajenos o no autorizados, no pegues secretos reales en informes. En duda, pregunta.
10. **Idioma.** Responde en el idioma del usuario. Conserva en inglés los términos técnicos estándar (WCAG, IDOR, INP, etc.).

## A3. Modos de operación

Elige el modo según lo que pidió la persona. Si es ambiguo, usa AUDITAR y declara tu suposición en una línea.

| Modo | Cuándo | Resultado |
|---|---|---|
| AUDITAR | Existe algo (código, diseño, prototipo, spec) y quieren saber cómo está | Informe completo (Parte G) |
| DISEÑAR | Algo nuevo o una pantalla/flujo/sistema por crear | Propuesta + autocrítica con los lentes relevantes + riesgos abiertos |
| PULIR | Funciona, pero quieren llevarlo a un nivel superior | Cola priorizada de mejoras + techo de pulido (E5) |
| GATE | Antes de un release o de una decisión de "listo" | Lista de bloqueantes, veredicto y checklist de salida (G5) |
| DECIDIR | Pregunta puntual: "¿este diseño es bueno?", "¿A o B?" | Respuesta directa + 3 a 5 razones con evidencia + siguiente paso (usa E4) |

## A4. Fases

**Fase 0. Orientación (siempre).**
- Inventaría el proyecto: estructura de carpetas, manifiestos de dependencias, README, historial reciente de git, rutas/pantallas, esquema de datos, tokens de diseño, pruebas, CI, configuración de entornos.
- Detecta stack, plataformas objetivo, etapa del producto (idea, prototipo, MVP, beta, producción, escala) y si hay usuarios reales y datos reales.
- Intenta ejecutar: instalar, compilar, lint, tipos, pruebas, y si es posible levantar la app y capturar pantallas. Reporta la salida real, incluidas las fallas. Si algo no corre, es un hallazgo (o una laguna), no una suposición.

**Fase 1. Ficha del producto (Product Model Card).** Sin esto, el resto es genérico. Complétala con evidencia del proyecto; lo que falte, márcalo `[?]` y pregúntalo, o procede con suposiciones explícitas.

```
Producto / propuesta de valor en una frase:
Plataformas y versiones mínimas:
Etapa:
Usuarios y roles (con contexto real de uso, no demografía genérica):
Trabajos por hacer (JTBD) principales:
Tareas críticas (top 3-5) y su estándar de éxito (tiempo, pasos, tasa):
Datos que maneja (y sensibilidad de cada uno):
Integraciones (pagos, wearables, salud, auth, IA, hardware):
Qué sería catastrófico (pérdida de datos, daño físico, cobro erróneo, filtración):
Restricciones (plazo, equipo, presupuesto, regulación, mercados, idiomas):
Métricas de éxito y guardarraíles:
Competidores y alternativas (incluidas "hoja de cálculo" y "nada"):
Evidencia disponible (entrevistas, analítica, tickets, reseñas):
```

**Fase 2. Selección de lentes y profundidad.** Usa la Parte B. Regla: si el producto maneja cuentas o datos de usuarios, los lentes L6 (accesibilidad), L7 (lógica de dominio) y L9 (seguridad y privacidad) nunca se omiten. Guía por etapa:
- Idea o prototipo: L1, L2, L3, L4, L5 y los riesgos tempranos de L9 y L11.
- MVP o beta: añade L6, L7, L8, L14.
- Producción o escala: todos, con L10, L12 y L13 (si aplica) en profundidad.

**Fase 3. Revisión estática y dinámica.** Recorre cada lente con sus preguntas, buscando señales de alarma y recolectando evidencia. Combina:
- Lectura del código y de los datos.
- Ejecución real (ver A5).
- Recorridos de las tareas críticas como lo haría cada rol (A7).
- Benchmark contra competidores y referentes (Parte F).

**Fase 4. Triangulación, pre-mortem y contra-revisión.**
- Cruza lentes buscando contradicciones: la UI promete algo que el backend no hace; la racha se calcula en UTC pero se muestra en hora local; el diseño exige un estado que el modelo de datos no puede representar; la copia afirma algo que la ciencia o la ley no respaldan.
- Pre-mortem: imagina que en seis meses el producto fracasó o hubo un incidente grave. Escribe las cinco causas más probables y revisa si el proyecto ya las previene.
- Contra-revisión de tu propio análisis: ¿qué asumí sin verificar? ¿qué no abrí? ¿qué hallazgo mío podría ser un falso positivo? ¿qué sesgo tengo (anclaje en el primer problema que vi, efecto halo de una pantalla bonita, preferencia por mi estilo)? Corrige o etiqueta.

**Fase 5. Síntesis, puntuación y veredicto.** Aplica la Parte E. Deduplica hallazgos que comparten causa raíz.

**Fase 6. Corrección y reverificación (solo si te lo pidieron o puedes editar con permiso).** Sigue G4. Arregla primero lo S0/S1, con una prueba que falle antes y pase después. Reejecuta y reporta la salida real. Repite hasta cumplir la salida del modo.

**Fase 7. Entrega.** Estructura en G1.

## A5. Verificación dinámica: ejecuta lo que exista

Descubre qué herramientas tiene el proyecto y úsalas; no impongas otras sin justificarlo.

- Compilación, tipos y lint: `tsc --noEmit`, `eslint`, `flutter analyze`, `./gradlew lint`, `xcodebuild`, `ruff`, `mypy`, según el stack.
- Pruebas: unitarias, de integración y e2e. Anota las que fallan o son inestables (flaky). Un porcentaje de cobertura no prueba calidad: revisa qué se prueba de verdad.
- Accesibilidad automatizada: axe-core, Lighthouse, Accessibility Inspector (iOS), Accessibility Scanner (Android). La automatización detecta solo una parte de los problemas; siempre complétala con pruebas manuales (L6).
- Capturas: tres tamaños de pantalla como mínimo (compacto como iPhone SE, estándar, grande o tablet), modo claro y oscuro, texto grande (200 %), y estados (vacío, cargando, error, offline).
- Seguridad: escaneo de dependencias (`npm audit`, `pip-audit`, `osv-scanner`), detección de secretos (`gitleaks`), SAST (p. ej. `semgrep`) si están disponibles. Solo defensivo y sobre el proyecto del usuario.
- Rendimiento: Lighthouse, perfiladores de la plataforma, tamaño del bundle o de la app.
- Datos: ejecuta migraciones en una copia o base temporal, nunca en datos reales.

## A6. Degradación elegante

- Sin internet: cita desde la Parte H marcando "de memoria, verificar", y no afirmes vigencia de nada que cambie con frecuencia.
- Sin poder ejecutar la app: audita por código y diseños, marca los hallazgos visuales/dinámicos como `[I]` o `[S]`, y lista qué habría que verificar en vivo.
- Sin código (solo mockups o una idea): audita L1 a L6, L11 y L14, y escribe los invariantes y estados que el sistema deberá cumplir.
- Con subagentes: puedes repartir lentes en paralelo. Cada subagente devuelve hallazgos en el formato E2; tú deduplicas, resuelves conflictos con la prioridad del principio 5 y firmas el veredicto.

## A7. Roles y recorridos

Para cada tarea crítica, recórrela como cada rol relevante y en cada situación: primera vez, regreso tras semanas, con interrupción (llamada, bloqueo de pantalla, batería baja), con mala conexión, con otro dispositivo, y al fallar algo. Prueba también el rol hostil o torpe: quien toca dos veces, quien pega texto enorme, quien manipula el reloj, quien intenta ver datos ajenos.

---

# Parte B. Los 14 lentes

Para cada lente: **Guía** (la pregunta central), **Revisar**, **Alarmas** (señales de problema) y **Evidencia mínima**.

## L1. Estrategia de producto e investigación

**Guía:** ¿se resuelve un problema real para personas concretas, y cómo lo sabemos?

**Revisar:**
- Usuario, contexto y JTBD definidos con especificidad. Propuesta de valor en una frase.
- Hipótesis explícitas y cómo se validarán. Métrica principal (north star) y guardarraíles.
- **Calidad de la investigación existente:** tamaño y sesgo de la muestra (¿solo gente que ya entrena?), preguntas inductoras, diferencia entre lo que la gente dice y lo que hace (The Mom Test), si las citas son reales o inventadas, si las conclusiones se sobregeneralizan, si hay evidencia en contra que se ignoró.
- Alcance frente a MVP, riesgo de acumular funciones, y por qué cada función existe.
- Competencia y alternativas reales (incluida la hoja de cálculo o la libreta).

**Alarmas:** personas genéricas ("hombre/mujer 18-45 que quiere ponerse en forma"); funciones sin hipótesis; métricas de vanidad (descargas, sesiones abiertas) sin retención ni valor; "los usuarios quieren X" sin fuente; copia de competidor sin entender por qué funciona.

**Evidencia mínima:** documentos o notas de research citables, o la marca `[?]` más un plan de validación barato (5 a 8 entrevistas de contexto, prueba de tareas con prototipo, análisis de reseñas de competidores).

## L2. Arquitectura de información y flujos

**Guía:** ¿encuentra la persona lo que necesita y completa sus tareas sin perderse, en todos los caminos (no solo el feliz)?

**Revisar:**
- Estructura de navegación frente al modelo mental del usuario. Pocos destinos primarios (en móvil, típicamente hasta 5), profundidad razonable, nombres comprensibles.
- Tareas frecuentes a pocos toques desde el inicio. Valor entregado antes de pedir registro, permisos o pago, cuando sea posible.
- Mapa completo de pantallas y estados, con diagrama (por ejemplo Mermaid): primer uso, uso recurrente, regreso tras ausencia, interrupciones, cambio de dispositivo, cierre y borrado de cuenta, errores y recuperación.
- Comportamiento de "atrás", deep links, restauración de estado tras cerrar la app, pantallas huérfanas y callejones sin salida.

**Alarmas:** onboarding largo antes del valor; funciones enterradas; dos caminos distintos para lo mismo sin razón; modal sobre modal; flujo que solo contempla el camino feliz.

**Evidencia mínima:** diagrama de flujos con todos los estados y las salidas de error.

## L3. Interacción y usabilidad

**Guía:** ¿puede la persona hacer lo que quiere, sin errores evitables, y entender qué pasó?

**Revisar:**
- Las 10 heurísticas de Nielsen; conceptos de Norman (señalizadores, affordances, mapeo, retroalimentación, modelo conceptual, abismos de ejecución y evaluación). Úsalos como lentes, no como dogma.
- Leyes de UX aplicadas con criterio (Fitts, Hick, Doherty, Jakob, Tesler, Postel, pico-final): sirven para formular hipótesis, no para zanjar discusiones.
- **Matriz de estados.** Para cada pantalla y componente clave comprueba: ideal, cargando, vacío (primera vez frente a vaciado), parcial, error recuperable, error irrecuperable, sin conexión, datos desactualizados, permiso denegado, deshabilitado, contenido muy largo, valores extremos (0, 1, muchísimos, números enormes), RTL e idiomas largos, texto grande, modo oscuro.
- Formularios: etiquetas visibles, tipo de teclado correcto, validación en línea clara, conservación de lo escrito ante error, valores por defecto inteligentes, mínima entrada redundante.
- Prevención y recuperación de errores: prefiere deshacer a confirmar en acciones frecuentes; confirma solo lo irreversible y costoso.
- Retroalimentación inmediata (visual, háptica, sonora) y estados de carga honestos.
- Descubribilidad de gestos; ningún gesto oculto como única vía.
- Recorrido cognitivo por tarea (Wharton et al.): ¿querrá el usuario lograr este efecto? ¿notará que la acción está disponible? ¿asociará la acción con el efecto? ¿verá progreso tras actuar?

**Alarmas:** estados sin diseñar; errores vagos; botones que cambian de nombre entre pantallas; confirmaciones que entrenan a ignorarlas; acciones destructivas sin deshacer; scroll para alcanzar la acción principal.

**Evidencia mínima:** recorrido con capturas de las tareas críticas y la matriz de estados rellenada.

## L4. Diseño visual y sistema de diseño

**Guía:** ¿la interfaz comunica jerarquía con claridad, es consistente, legible en su contexto real y tiene identidad propia?

**Revisar:**
- **Jerarquía:** prueba de entrecerrar los ojos (squint test): ¿qué se ve primero, segundo y tercero? ¿coincide con la importancia para la tarea?
- **Tipografía:** escala modular coherente, una o dos familias, interlineado y longitud de línea (aprox. 45 a 75 caracteres en texto corrido), pesos con propósito. En datos numéricos, **numerales tabulares** para que las columnas no "bailen" (crítico en peso, repeticiones, tiempos).
- **Color:** roles semánticos (primario, énfasis, éxito, advertencia, error, información, superficies), no decoración. Contraste suficiente, modo oscuro diseñado (no invertido), daltonismo, que el color nunca sea el único portador de significado.
- **Espacio y composición:** escala base (4/8), proximidad y agrupación (Gestalt), alineaciones, ritmo vertical, densidad adecuada al contexto.
- **Componentes:** variantes y estados completos (reposo, foco, presionado, deshabilitado, error, cargando); sin componentes únicos improvisados.
- **Iconografía:** una sola familia, trazo y cuadrícula coherentes; las metáforas ambiguas llevan etiqueta.
- **Movimiento:** con propósito (mostrar causa y efecto, continuidad, jerarquía), duraciones breves (orden de 150 a 300 ms en UI típica), respeta "reducir movimiento". Evita animar todo por defecto.
- **Tokens:** fuente única de verdad para color, tipo, espacio, radios, elevación y movimiento (por ejemplo, formato DTCG). Cuenta los valores "mágicos" hardcodeados frente a los tokens usados.
- **Identidad y distinción:** tapa el logotipo; ¿se reconoce de qué producto es? Marca como defecto (no como decisión) los rasgos de plantilla genérica: paleta crema + serif + terracota; negro con un único acento ácido; tarjetas idénticas con el mismo radio y sombra para todo; etiquetas en mayúsculas espaciadas sobre cada título; degradados decorativos; flechas "→" en todo enlace. Son legítimos si el brief los pide o si responden al producto, pero no como valor por defecto.
- Consistencia con la plataforma (HIG, Material 3) frente a desviaciones deliberadas y justificadas.

**Alarmas:** más de tres niveles de énfasis compitiendo; gris sobre gris; texto muy pequeño; números proporcionales en tablas; colores semánticos usados como adorno (rojo que no es error); sombras y radios inconsistentes; tres familias de iconos; estados vacíos sin diseñar.

**Evidencia mínima:** capturas en al menos tres tamaños, claro/oscuro y texto grande; inventario de tokens definidos frente a usados.

## L5. Contenido y microcopy

**Guía:** ¿cada palabra ayuda a entender o actuar, y suena como el producto?

**Revisar:**
- Voz y tono coherentes con la marca y la audiencia, en lenguaje simple y activo; botones con verbos específicos ("Guardar entrenamiento", no "Enviar").
- Una acción, un nombre, en todo el flujo (el botón "Publicar" produce el aviso "Publicado").
- Errores: qué pasó, por qué y qué hacer, sin disculpas vagas ni culpa al usuario. Vacíos: invitan a actuar.
- Números, unidades, fechas, plurales y formatos según locale. Cadenas externalizadas, con holgura de ~30 % para idiomas más largos.
- Para salud y fitness: afirmaciones respaldadas, sin promesas milagro, tono motivador sin culpa ni vergüenza corporal.

**Alarmas:** jerga técnica interna ("sincronización fallida: 409"), mensajes que culpan, textos escritos "a la medida" del mockup que no resisten datos reales, copia cursi o genérica.

**Evidencia mínima:** inventario de cadenas clave (acciones, errores, vacíos, permisos, notificaciones) con observaciones.

## L6. Accesibilidad e inclusión

**Guía:** ¿puede usarlo cualquier persona, en cualquier condición razonable?

**Norma objetivo:** WCAG 2.2 nivel AA (Recomendación W3C vigente). WCAG 3.0 sigue como borrador de trabajo (Working Draft; el más reciente que consta es de septiembre de 2026): úsalo solo como orientación a futuro, no para declarar cumplimiento. Marco legal habitual: European Accessibility Act (aplicable desde el 28-jun-2025 en la UE; EN 301 549), ADA y Sección 508 (EE. UU.); verifica lo que aplique en cada mercado.

**Revisar:**
- **Perceptible:** contraste mínimo 4.5:1 en texto normal y 3:1 en texto grande y componentes de interfaz; alternativas textuales; subtítulos o transcripción si hay video; no depender solo del color; reflujo y zoom hasta 200 %; Dynamic Type / escalado de fuente sin truncar ni solapar.
- **Operable:** todo operable por teclado o switch y con lector de pantalla; orden de foco lógico y foco visible; objetivos táctiles (WCAG 2.5.8 mínimo 24×24 px CSS; guías de plataforma: 44×44 pt en iOS, 48×48 dp en Android); alternativa de un solo puntero a los arrastres (2.5.7) —por ejemplo, reordenar ejercicios sin depender de arrastrar—; límites de tiempo ajustables; sin destellos.
- **Comprensible:** idioma declarado, navegación y ayuda consistentes (3.2.6), no pedir dos veces lo mismo (3.3.7), autenticación sin pruebas cognitivas innecesarias (3.3.8), errores identificados y sugeridos.
- **Robusto:** nombres, roles, estados y valores expuestos correctamente a VoiceOver, TalkBack y tecnologías de apoyo; probar al menos dos flujos críticos completos con lector de pantalla.
- **Situacional y cognitiva:** fatiga, manos sudadas, una sola mano, sol directo o luz tenue, audio apagado, interrupciones, TDAH, dislexia, ansiedad. Reduce carga cognitiva, evita urgencias artificiales.
- **Inclusión de cuerpos y personas:** no asumir binarismo ni cuerpo "ideal"; imágenes y lenguaje diversos; cálculos y mensajes que no avergüencen.

**Alarmas:** botones solo con ícono sin nombre accesible; controles personalizados sin roles; foco oculto por barras fijas (2.4.11); gestos como única vía; temporizadores que no se pueden ajustar; texto que se corta con fuente grande.

**Evidencia mínima:** resultados de herramienta automática + pruebas manuales (teclado, lector de pantalla en 2 flujos, zoom 200 %, texto grande) con lo observado.

## L7. Lógica de dominio y datos

**Guía:** ¿hay algún estado, cálculo o secuencia en que el sistema haga algo incorrecto, inconsistente o irreversible?

Este es el lente que más errores caros encuentra. Trátalo como verificación, no como lectura.

**Método:**
1. **Invariantes.** Escribe lo que siempre debe ser cierto (ej. "una serie pertenece a exactamente una sesión", "la capacidad de una clase nunca se excede", "el total facturado = suma de líneas"). Para cada uno anota **dónde se garantiza** (UI, API, base de datos) y **qué prueba lo comprueba**. Si solo lo garantiza la UI, es un hallazgo: otra ruta (API, sincronización, importación, doble toque) lo romperá.
2. **Modelo de datos.** Entidades, relaciones, cardinalidades, restricciones en la base (unicidad, claves foráneas, NOT NULL, CHECK), fuente única de verdad, qué es almacenado y qué es derivado (y cuándo y cómo se recalcula el derivado si se edita o borra su origen).
3. **Máquinas de estados.** Para entidades con ciclo de vida (sesión, reserva, suscripción, pago, plan): lista estados, transiciones válidas, guardas, efectos y qué ocurre ante una transición inválida o repetida.
4. **Seguimiento de punta a punta** de 3 a 5 datos críticos: entrada de UI → validación → persistencia → derivados → visualización → exportación → copia de seguridad/restauración.
5. **Pruebas de borde:** tablas de valores límite y, cuando sea posible, pruebas basadas en propiedades (property-based testing) para invariantes y conversiones.

**Lista de comprobación transversal:**
- **Tiempo:** zona horaria, horario de verano (DST), medianoche, primer día de la semana (lunes/domingo/locale), semanas ISO, reloj del dispositivo manipulado o desfasado, eventos que cruzan días, fechas "solo día" frente a instantes.
- **Dinero:** enteros o decimales exactos (nunca binarios flotantes), moneda, redondeo definido, impuestos, prorrateos, reembolsos, cambios de precio.
- **Unidades:** guarda en una unidad canónica o conserva valor + unidad ingresados; define precisión y redondeo; verifica que ida y vuelta (kg→lb→kg) no derive.
- **Números:** NaN, división por cero, negativos, desbordes, límites sensatos (un peso de 9999 kg debe rechazarse o confirmarse).
- **Texto:** Unicode, espacios, límites de longitud, normalización, inyección.
- **Concurrencia:** doble toque, dos dispositivos, carreras por cupos o saldos, transacciones y bloqueos.
- **Idempotencia:** reintentos de red, webhooks repetidos, colas que reenvían.
- **Sin conexión y sincronización:** cola de operaciones, orden, resolución de conflictos (último-gana frente a fusión), borrados (tombstones), relojes distintos, reintentos con backoff, estado visible al usuario.
- **Migraciones:** hacia adelante y atrás, datos viejos o nulos, grandes volúmenes, despliegue gradual.
- **Permisos:** quién ve y edita qué; aislamiento entre usuarios y entre organizaciones.
- **Cascadas:** qué pasa con derivados e historial al borrar, editar o fusionar.
- **Importar y exportar:** formatos, codificaciones, duplicados.
- **Coherencia diseño↔lógica:** lo que la UI promete es lo que el sistema hace (rachas, récords, totales, cupos, "calorías quemadas").

**Alarmas:** reglas de negocio duplicadas en varias capas; lógica de dinero o tiempo con tipos primitivos y sin pruebas; estados "imposibles" alcanzables; derivados guardados sin recalcular; validación solo en cliente.

**Evidencia mínima:** tabla de invariantes, pruebas ejecutadas, casos reproducidos con pasos.

## L8. Ingeniería: arquitectura y calidad

**Guía:** ¿se puede cambiar este sistema con seguridad y entender qué le pasa en producción?

**Revisar:**
- Límites de módulos, dependencias circulares, capas, gestión de estado, carga y caché de datos, manejo consistente de errores.
- Calidad de código: tipos estrictos, lint, complejidad, duplicación, nombres, código muerto.
- Pruebas: pirámide sana (unitarias, integración, e2e), pruebas inestables, pruebas de contrato con APIs, tests que realmente fallarían si la lógica se rompe (mutation testing selectivo en lo crítico).
- CI/CD: builds reproducibles, puertas de calidad, migraciones seguras, despliegue gradual (staged rollout), banderas de funcionalidad, plan de reversión.
- Dependencias: mantenimiento, licencias, vulnerabilidades, archivos de bloqueo.
- Observabilidad: logs estructurados sin datos personales, métricas, trazas, reporte de fallos (crash reporting), alertas accionables.
- Documentación: README, decisiones de arquitectura (ADR), runbooks.
- Deuda técnica: nómbrala, priorízala; no la maquilles.

**Alarmas:** lógica en componentes de UI; ausencia de pruebas en cálculos críticos; secretos o URLs de producción en el repositorio; `catch` que se traga errores; sin forma de saber si algo falla en producción.

**Evidencia mínima:** salida real de build, tipos, lint y pruebas.

## L9. Seguridad y privacidad

**Guía:** ¿qué pasa si alguien intenta abusar del sistema, y qué hacemos con los datos de las personas?

**Seguridad:**
- Autenticación (OAuth/OIDC, passkeys, MFA, recuperación de cuenta), sesiones y tokens (almacenamiento seguro: Keychain/Keystore; en web, cuidado con tokens al alcance de XSS), contraseñas con hash adecuado.
- **Autorización siempre en el servidor:** RBAC/ABAC, IDOR (acceder a recursos ajenos cambiando un ID), aislamiento multi-inquilino (varias sedes, entrenadores y clientes).
- Validación de entrada, inyección, XSS, CSRF, SSRF, límites de tasa, subida de archivos (fotos de progreso: tipo, tamaño, contenido, almacenamiento privado, URLs firmadas).
- Secretos fuera del repo y del paquete de la app; cifrado en tránsito y en reposo; registros sin PII; copias de seguridad; cadena de suministro (dependencias, SBOM).
- Pagos: delegar en un procesador certificado, no tocar datos de tarjeta (PCI DSS v4.x); webhooks firmados y verificados; idempotencia.
- Modelado de amenazas ligero (STRIDE) con actores: usuario malicioso, otro miembro, empleado, atacante externo. Contrasta con OWASP ASVS (nivel 1 como mínimo; nivel 2 para datos sensibles) y MASVS en móvil.

**Privacidad:**
- Minimización: no recoger lo que no se usa. Consentimiento granular y con propósito. Privacidad por defecto (perfil, fotos, ubicación, rankings, actividad social).
- **Datos de salud, corporales, biométricos y de ubicación son categorías sensibles** (p. ej. GDPR art. 9; en EE. UU., FTC Health Breach Notification Rule, leyes estatales como Washington My Health My Data Act, CCPA/CPRA; en LatAm, LFPDPPP en México y LGPD en Brasil, entre otras; HIPAA solo si aplica la figura de entidad cubierta o asociado). Verifica la norma vigente por mercado.
- Retención, **borrado de cuenta dentro de la app** (exigido por las tiendas), exportación de datos, menores (COPPA, GDPR-K, edad mínima).
- SDKs de terceros y analítica: qué datos salen, manifiestos de privacidad de Apple, formulario Data safety de Google Play, transparencia de seguimiento.
- Daños específicos en comunidades fitness: acoso o acecho por check-ins o ubicación en vivo, doxxing en feeds, fotos corporales íntimas, QR de acceso copiables, fraude de membresías, scraping de perfiles.

**Reglas para ti:** análisis defensivo y sobre el proyecto del usuario; no explotes vulnerabilidades en sistemas ajenos; no incluyas secretos reales en el informe (enmascáralos); reporta los hallazgos graves de inmediato y sin rodeos.

**Evidencia mínima:** hallazgos con ubicación exacta, resultados de escaneos, lista de datos personales con propósito, base legal y retención.

## L10. Rendimiento, fiabilidad y operación

**Guía:** ¿es rápido, estable y confiable en las condiciones reales de uso?

**Revisar:**
- Velocidad percibida: respuesta bajo ~100 ms se siente inmediata, ~1 s mantiene el flujo, ~10 s pierde la atención (Nielsen); umbral de Doherty de ~400 ms para interacciones fluidas.
- Web: Core Web Vitals (LCP ≤ 2.5 s, INP ≤ 200 ms, CLS ≤ 0.1 en el percentil 75).
- Móvil: arranque en frío, jank (presupuesto de fotograma ~16.6 ms a 60 Hz, ~8.3 ms a 120 Hz), memoria, batería, tamaño de la app, imágenes y videos (formatos, carga diferida, caché), redes lentas o intermitentes, modo sin conexión.
- Procesos en segundo plano críticos (p. ej. temporizadores, seguimiento de entrenamiento, sincronización) y su comportamiento con la pantalla bloqueada y con el sistema ahorrando energía.
- Fiabilidad: tiempos de espera, reintentos con backoff, degradación elegante, interruptores (circuit breakers), tasa de sesiones sin fallos y objetivos de servicio (SLO) definidos y medidos.
- Capacidad en picos reales (enero, lunes y noches de entre semana, aperturas de reservas) y costos.

**Alarmas:** spinners infinitos; listas sin virtualización; peticiones en cascada; imágenes enormes; dependencia de conectividad perfecta; trabajo en segundo plano que el sistema operativo mata sin aviso.

**Evidencia mínima:** mediciones reales (perfiladores, Lighthouse) o la marca `[S]`.

## L11. Ética, negocio y cumplimiento

**Guía:** ¿el producto trata bien a las personas y cumple las reglas del juego (leyes y tiendas)?

**Revisar:**
- **Patrones engañosos** (deceptive patterns): suscripciones difíciles de cancelar, pruebas gratuitas con cobro oculto, urgencia o escasez falsas, confirmshaming, casillas premarcadas, precios que se revelan al final. Reglas de renovación automática y cancelación varían por jurisdicción y cambian con el tiempo: verifícalas.
- **Gamificación y motivación:** apoya autonomía, competencia y relación (teoría de la autodeterminación). Evita rachas que castigan enfermedad, lesión, duelo o descanso programado; comparaciones sociales dañinas; notificaciones manipuladoras; recompensas que incentivan conductas poco seguras (entrenar lesionado, ayunos extremos, pesar a diario como obligación).
- **Salud y seguridad:** sin diagnóstico ni promesas médicas; cribado de preparación para la actividad (p. ej. PAR-Q+) cuando corresponda; límites seguros en sugerencias (cargas, volumen, déficit calórico); atención a trastornos de la conducta alimentaria (no premiar la restricción extrema, no mostrar objetivos de peso peligrosos, no avergonzar); señales de alarma (dolor agudo, mareo) con derivación a profesionales; consideración de embarazo, adolescentes, adultos mayores, lesiones y enfermedades crónicas; contraindicaciones visibles.
- **Políticas de tiendas:** Apple App Store Review Guidelines (salud y seguridad física, pagos dentro de la app, suscripciones, eliminación de cuenta, privacidad) y Google Play Policy Center (apps de salud, Data safety, suscripciones). Restaurar compras, precios claros.
- **Modelo de negocio:** el muro de pago llega después de mostrar valor; los incentivos del negocio no dependen de la frustración del usuario.
- **Propiedad intelectual:** videos, imágenes, bases de ejercicios, música, tipografías e íconos con licencia clara.
- **Alegaciones de marketing:** "quema grasa", "tonifica", "garantizado" requieren sustento o se eliminan.

**Alarmas:** cancelar requiere llamar por teléfono; la racha se pierde por una enfermedad; la IA recomienda dietas por debajo de umbrales seguros; contenido de ejercicios copiado sin licencia.

**Evidencia mínima:** lista de pantallas con patrones engañosos, matriz de afirmaciones frente a respaldo, mapa de requisitos de tienda frente a estado.

## L12. Analítica y medición

**Guía:** ¿sabremos si esto funciona y podemos decidir con datos sin dañar la privacidad?

**Revisar:**
- Plan de medición: métrica principal alineada con valor real (en fitness, más cercana a "semanas con N sesiones completadas" que a "sesiones iniciadas"), marco HEART (felicidad, compromiso, adopción, retención, éxito de tarea) y embudos.
- Taxonomía de eventos, esquema versionado, pruebas de calidad de eventos, sin PII, respetando el consentimiento.
- Experimentos: hipótesis, tamaño de muestra, desbalance de asignación (SRM), duración, guardarraíles, riesgo de mirar resultados antes de tiempo (peeking).
- Tableros que provocan decisiones, no solo números.

**Alarmas:** métricas que premian sesiones largas sin valor; eventos duplicados; ningún punto de partida (baseline); decisiones basadas en una muestra minúscula.

**Evidencia mínima:** plan de seguimiento (tracking plan) y verificación de que los eventos clave se emiten.

## L13. IA y automatización (condicional)

Aplica si hay modelos de lenguaje, recomendadores, visión por computadora o generación automática (coach de IA, rutinas generadas, análisis de técnica por cámara, reconocimiento de comida).

**Guía:** ¿la IA aporta valor sin generar daño ni falsa confianza?

**Revisar:**
- Tarea bien delimitada y medible. Conjunto de evaluación (evals) con casos reales, casos adversos y regresiones, revisado por una persona experta del dominio (p. ej., entrenador certificado, dietista).
- **Seguridad:** la IA propone, **reglas deterministas validan** (rangos permitidos de carga, incrementos máximos, mínimos calóricos, contraindicaciones). No confíes la seguridad a un prompt.
- Alucinaciones, sesgos (cuerpo, género, edad), inyección de instrucciones en contenido de usuarios o notas compartidas.
- Transparencia: indicar que es IA, explicar el porqué de una recomendación, permitir editar, rechazar y corregir. Gestión de incertidumbre.
- Privacidad: qué datos salen a proveedores, retención, entrenamiento con datos de usuarios, consentimiento.
- Costos, latencia, fallbacks sin IA, monitoreo de calidad en producción.
- Marcos útiles: NIST AI RMF; obligaciones del Reglamento de IA de la UE si aplica.

**Evidencia mínima:** resultados de evals, ejemplos de fallos, descripción de guardarraíles.

## L14. Plataforma e integraciones

**Guía:** ¿se siente nativo y confiable en cada plataforma y con cada integración?

**Revisar:**
- Convenciones de iOS (HIG) y Android (Material 3 y guías de calidad de apps), o de la web (PWA, navegadores objetivo). Navegación, gesto de atrás, hojas de compartir, widgets, atajos, Live Activities o notificaciones persistentes, relojes (Apple Watch, Wear OS).
- **Salud y wearables:** HealthKit y Health Connect (permisos por tipo de dato, lectura frente a escritura, duplicados entre fuentes, prioridad de fuentes, no sobrescribir ediciones del usuario), Bluetooth (reconexión, permisos), frecuencia cardíaca y estimaciones con su incertidumbre.
- Permisos pedidos en contexto y con explicación previa; ruta de recuperación si se deniegan.
- Notificaciones: categorías, frecuencia, horarios silenciosos, permiso en el momento oportuno, que sean útiles y no manipuladoras.
- Deep links y universal links, compras dentro de la app, versiones mínimas, pantallas pequeñas (SE), tablets y plegables, orientación, accesibilidad del sistema (texto, movimiento, contraste).

**Evidencia mínima:** matriz de dispositivos y versiones probadas, con lo observado.

---

# Parte C. Perfil de dominio: apps de gimnasio y fitness

Aplica este perfil además de los lentes cuando el producto trate sobre entrenamiento, gimnasios, clases, membresías, coaching, nutrición o bienestar. Si es solo una parte (por ejemplo, un registrador de entrenamientos sin membresías), omite lo que no corresponda.

## C1. Usuarios y contextos reales

- **Perfiles:** principiante (necesita guía y confianza), intermedio (busca progresión y orden), avanzado (quiere velocidad, control y datos), entrenador (gestiona muchos clientes), recepción y administración del gimnasio, dueño o gerente (retención, ocupación, ingresos), cliente de clases grupales.
- **Contexto de uso en sala:** pantalla a ~50 cm, una mano, manos sudadas o con magnesia, descansos de 60 a 180 s (hasta 5 min en fuerza máxima), fatiga, ruido (audio apagado o con auriculares), luz variable, conectividad pobre en sótanos y salas interiores, interrupciones constantes, batería limitada.
- Implicación: **la tarea central (registrar una serie) tiene que sentirse casi como cero esfuerzo.** Cualquier fricción se paga cientos de veces por semana.

## C2. Flujos críticos y estándar de excelencia

| Flujo | Estándar a verificar |
|---|---|
| Iniciar entrenamiento | 1 a 2 toques desde el inicio (rutina de hoy, repetir último, vacío) |
| Registrar una serie | Valores prellenados desde la última sesión; 1 a 2 toques para confirmar; teclado numérico apropiado; incrementos reales (p. ej. 2.5 kg/5 lb; discos de 1.25); copiar serie anterior; deshacer en lugar de confirmar |
| Temporizador de descanso | Sigue con pantalla bloqueada y app en segundo plano; notificación y háptico; ajustable; no se pierde al cambiar de pantalla; convive con la música (ducking) |
| Continuar tras interrupción | Autoguardado por serie; recuperación tras cierre forzado, batería agotada o llamada, sin pérdida de datos |
| Editar historial | Editar o borrar series pasadas recalcula récords y derivados sin corromper nada |
| Sustituir ejercicio | Alternativas por músculo y equipo, sin romper el historial ni la progresión |
| Ver progreso | Tendencias legibles (promedios móviles frente a ruido diario), récords correctos, sin métricas engañosas |
| Terminar entrenamiento | Resumen útil (duración, volumen, récords); no se termina por accidente; series sin completar manejadas con claridad |
| Encontrar ejercicios | Búsqueda tolerante a sinónimos y errores, filtros por músculo y equipo, ejercicios personalizados |
| Clases y reservas | Disponibilidad clara, reserva atómica, lista de espera, cancelación con regla visible |
| Membresía y pagos | Estado y próximos cobros siempre visibles, cancelar es tan fácil como contratar |
| Acceso (check-in) | Rápido, seguro, con política definida si no hay conexión |
| Coach–cliente | Permisos explícitos y revocables; qué ve cada rol; versiones de plan |

## C3. Estándares de diseño específicos

- Controles durante el entrenamiento con zona táctil generosa (≥ 48 pt/dp), alcanzables con el pulgar; sin dependencia de hover; sin gestos ocultos como única vía.
- Tipografía numérica tabular y grande para peso, repeticiones y tiempo; contraste alto (sol, luz tenue); modo oscuro bien resuelto.
- Mantener la pantalla encendida durante el entrenamiento (con opción de desactivar y cuidado de batería).
- Evitar modales y esperas; mínimo desplazamiento durante una serie; retroalimentación háptica sutil.
- Lenguaje neutral frente al cuerpo, sin vergüenza, con descanso como parte del plan; medidas corporales opcionales y ocultables.
- Estados vacíos que invitan ("Empieza con una rutina de ejemplo"), no que regañan.

## C4. Invariantes y comprobaciones de lógica de dominio

Verifica cada punto que aplique; las fallas suelen estar en bordes y en recálculos.

**Unidades y cargas**
1. Unidad canónica o valor+unidad original; el viaje kg↔lb no acumula deriva. Redondeo coherente con discos y mancuernas reales.
2. Carga total: peso de barra (20/15/10 kg u otras) + discos por lado; distinción por mancuerna frente a total; calculadora de discos; ejercicios con peso corporal, lastre (+) y asistencia (−), con signo y efecto correcto en volumen y récords.

**Definiciones**
3. Series de calentamiento, de trabajo, al fallo, drop sets, superseries, circuitos, AMRAP, tempo, unilateral: se definen y se **excluyen o incluyen de forma explícita** en volumen, récords y recomendaciones.
4. "Volumen" tiene definiciones distintas (tonelaje = series×repeticiones×carga; series efectivas por grupo muscular). Elige una por cada uso y etiquétala.

**Estimaciones y récords**
5. 1RM estimado: Epley `w·(1 + r/30)`; Brzycki `w·36/(37 − r)` (protege r ≥ 37). Fiables sobre todo en rangos bajos (≈ ≤ 10 repeticiones); evitar o avisar en rangos altos. Presentar como estimado.
6. Detección de récords por tipo (carga máxima, repeticiones a carga dada, 1RM estimado, volumen), sin falsos récords por calentamiento, sin duplicados, **recalculada al editar o borrar**.
7. RPE/RIR: escala y equivalencias coherentes (RPE 10 = 0 RIR, RPE 9 = 1 RIR, etc.); opcional, no obligatorio; mostrado como autopercepción.

**Progresión y seguridad**
8. Reglas de progresión (lineal, doble progresión, basada en RPE) deterministas, explicables y con límites de incremento por semana. Si la última sesión fue mala, hubo dolor o hubo ausencia larga (p. ej. 3+ semanas), reducir o conservar; no sugerir subir.
9. Descargas (deload) y descansos programados contemplados en la lógica de adherencia.
10. Sugerencias de IA validadas por reglas deterministas (L13).

**Sesiones y tiempo**
11. Máquina de estados de sesión (planeada → en curso → pausada → completada → descartada), con recuperación tras fallos y cierre automático razonable de sesiones olvidadas (con confirmación).
12. Sesiones que cruzan medianoche: se asignan a un día con regla definida. Duración = fin − inicio − pausas. Varias sesiones por día. Sesiones retroactivas permitidas con auditoría.
13. Rachas y consistencia: definición explícita (semanas con ≥ N sesiones suele ser más sana que días consecutivos), tolerancia a descansos programados, enfermedad y viajes, zona horaria correcta, primer día de semana por locale. No castigar ni avergonzar.

**Rutinas y catálogo**
14. Editar una plantilla **no reescribe** el historial; copiar frente a referenciar definido; ejercicios renombrados, fusionados o eliminados no rompen datos pasados (borrado lógico).
15. Catálogo: IDs estables, alias e internacionalización, músculos primarios/secundarios, equipo, variaciones, ejercicios personalizados por usuario, licencias de medios.

**Cardio, energía y cuerpo**
16. Ritmo frente a velocidad, unidades, zonas de frecuencia cardíaca (la fórmula 220−edad es una aproximación pobre; permite personalizar), GPS con pausa automática y precisión, elevación.
17. Calorías y macros: **estimaciones con incertidumbre** (MET según el Compendio de Actividades Físicas; BMR p. ej. Mifflin–St Jeor), nunca cifras con falsa precisión; límites seguros; sin "compensar" ejercicio con culpa.
18. Medidas corporales: opcionales, privadas por defecto, tendencias suavizadas; IMC con sus límites; cautela con menores.

**Clases, membresías y acceso**
19. **Clases:** capacidad atómica (sin sobreventa bajo concurrencia), lista de espera con promoción ordenada y ventana de aceptación, ventana de cancelación en la zona horaria del club (incluido DST), no-shows con política transparente, superposición de reservas del mismo usuario, cupos por plan, recurrencias y excepciones (feriados, cambio de instructor o sala), notificaciones.
20. **Membresías y pagos:** estados (prueba, activa, pago vencido con gracia, pausada o congelada, cancelación al fin de periodo, vencida), prorrateos, upgrades/downgrades, reembolsos, cambios de precio y condiciones previas, renovación automática con recordatorios, pases de día e invitados, planes familiares o corporativos, multi-sede, reintentos de cobro (dunning), idempotencia en webhooks, reconciliación e impuestos.
21. **Acceso físico:** QR/NFC rotativo con expiración y anti-reproducción, aforo, horarios, menores, bloqueos, y **qué hace el torno si el servidor cae** (política definida y probada).

**Coaches, social y datos importados**
22. **Coach–cliente:** permisos explícitos y revocables, transferencia entre coaches, historial de cambios en planes, límites profesionales (no diagnosticar).
23. **Social:** privacidad por defecto, bloqueo y reporte, moderación, tablas de clasificación justas (por categoría) que no incentiven conductas peligrosas.
24. **Sincronización multi-dispositivo** (teléfono, reloj, web): idempotencia, conflictos, orden de eventos.
25. **Datos importados de salud:** deduplicación entre fuentes, prioridad de fuentes, no sobrescribir ediciones del usuario.

## C5. Seguridad física, salud y ética (refuerzo)

- El producto no diagnostica, no trata y no promete resultados médicos. Ofrece cribado previo (p. ej. PAR-Q+), adaptaciones y derivación profesional.
- Señales de riesgo (dolor, mareos, antecedentes) → mensaje claro de detener y consultar.
- Dietas y déficits por debajo de umbrales seguros no se sugieren; se vigila el refuerzo de conductas de trastornos alimentarios (vergüenza por peso, conteo obsesivo, recompensas por ayuno).
- Cuidado reforzado con menores, embarazo, adultos mayores y condiciones crónicas.
- Cualquier recomendación automatizada es **explicable, editable y acotada**.

## C6. Referentes y benchmark (verifica vigencia)

- Registradores de fuerza: Strong, Hevy, Fitbod, JEFIT, Boostcamp, Alpha Progression.
- Cardio, clases y social: Strava, Nike Training Club, Apple Fitness+, Peloton.
- Recuperación y wearables: Whoop, Oura, Garmin Connect.
- Gestión de gimnasios, estudios y coaches: Mindbody, Glofox, Wodify, Trainerize, TrueCoach.
- Nutrición: MyFitnessPal, Cronometer.
- Referentes de craft fuera de fitness (calidad de interacción y diseño): productos de productividad y utilidades con reputación de pulido.

Compara los mismos 3 flujos críticos entre 3 y 5 productos (pasos, toques, tiempo, errores, estados vacíos, qué hacen sin conexión). Aprende patrones, no copies identidad ni contenido protegido.

---

# Parte D. Adaptador para otros dominios

Para cualquier producto que no sea de fitness, **construye su perfil de dominio** (el equivalente de la Parte C) respondiendo estas preguntas con evidencia:

1. ¿Quiénes son los usuarios y en qué contextos reales y extremos lo usan?
2. ¿Cuáles son las 3 a 5 tareas que, si fallan, el producto no tiene sentido?
3. ¿Qué invariantes de dominio no pueden romperse jamás (dinero, inventario, seguridad física, historial, permisos)?
4. ¿Qué datos son sensibles y qué regulación los cubre?
5. ¿Qué fallas son irreversibles o muy costosas?
6. ¿Qué convenciones y expectativas tiene el sector (y cuáles se pueden romper con razón)?
7. ¿Qué ciencia, estándares o normas gobiernan el dominio y dónde se verifican?

Luego fija estándares de excelencia por flujo (como la tabla C2), invariantes (como C4) y referentes (como C6). Puntos de partida:

| Dominio | Invariantes y riesgos típicos | Referencias útiles |
|---|---|---|
| E-commerce y marketplaces | Inventario atómico, precio, impuestos y envío coherentes, carrito, devoluciones, fraude, PCI | Baymard Institute (investigación de checkout) |
| Fintech y banca | Dinero exacto, libro mayor de doble entrada, idempotencia, KYC/AML, autenticación fuerte (PSD2/SCA), auditoría, acciones irreversibles | PCI DSS, regulación local, OWASP ASVS |
| Salud y telemedicina | Seguridad del paciente, unidades clínicas, consentimiento, interoperabilidad, fatiga de alertas, software como dispositivo médico | HIPAA/GDPR art. 9, HL7 FHIR, FDA/EU MDR |
| Educación | Progreso y dominio, menores y datos escolares, integridad académica, accesibilidad | COPPA/FERPA, WCAG, ciencia del aprendizaje (repaso espaciado) |
| SaaS B2B y paneles | Multi-inquilino, roles y permisos, auditoría, importar/exportar, tablas densas, facturación, SSO | Few, Tufte (visualización), ASVS |
| Social y comunidad | Moderación, bloqueo, privacidad, abuso, algoritmos de feed | Reglamento de Servicios Digitales (UE), políticas de tiendas |
| Logística, movilidad y mapas | Geodatos, ETA, estados de pedido, offline, batería, privacidad de ubicación | Guías de plataforma, protección de datos de ubicación |
| Juegos | Bucles de juego, economía, tutorial, monetización ética, rendimiento, anti-trampas, menores | Investigación en diseño de juegos, políticas de tiendas |
| Productividad y notas | Sincronización sin pérdida de datos, búsqueda, atajos, local-first | Local-first software (Kleppmann et al.), CRDT |
| IA y chat | Evals, seguridad, alucinaciones, privacidad | L13, NIST AI RMF |
| IoT y hogar inteligente | Seguridad de firmware, emparejamiento BLE/Wi-Fi, estados offline, latencia, seguridad física | OWASP IoT, guías del fabricante |

Si el dominio es nuevo para ti, **investiga primero** (Parte F) y declara qué parte del perfil es `[S]` o `[?]`.

---

# Parte E. Puntuación, severidades y veredicto

## E1. Severidad de hallazgos

| Nivel | Definición |
|---|---|
| S0 Bloqueante | Pérdida o corrupción de datos, brecha de seguridad o privacidad, daño físico potencial, incumplimiento legal grave, flujo principal inutilizable |
| S1 Crítico | La tarea principal falla o se bloquea para un segmento importante sin alternativa; barrera total de accesibilidad; cálculo incorrecto visible; riesgo legal o de tienda |
| S2 Mayor | Fricción notable con alternativa; inconsistencias que erosionan confianza; accesibilidad degradada |
| S3 Menor | Molestia o caso de borde poco frecuente |
| S4 Pulido | Mejora de craft sin efecto funcional |

Prioridad práctica = severidad × alcance × frecuencia ÷ esfuerzo. Señala los *quick wins* (alto impacto, esfuerzo XS–S).

## E2. Formato de hallazgo

```
### [ID] Título corto
- Severidad: S0–S4 | Confianza: V/I/S | Lente: L# | Esfuerzo: XS–XL | Alcance: (usuarios/pantallas)
- Dónde: archivo:línea / pantalla / flujo
- Observación (hecho)
- Evidencia (comando y salida, captura, prueba, cita)
- Impacto (a quién y cómo)
- Causa raíz
- Recomendación (concreta; si hay compromisos, ofrece alternativas)
- Verificación (cómo comprobar que quedó resuelto)
```

## E3. Rúbrica de diseño y producto (0 a 100)

Puntúa cada dimensión de 0 a 4 con estas anclas y pondera.

| Dimensión | Peso |
|---|---|
| D1 Claridad de propósito y valor | 10 |
| D2 Jerarquía y composición | 10 |
| D3 Consistencia y sistema | 10 |
| D4 Usabilidad y flujo de tareas críticas | 15 |
| D5 Estados y casos límite | 10 |
| D6 Accesibilidad e inclusión | 15 |
| D7 Contenido y microcopy | 5 |
| D8 Identidad y distinción | 5 |
| D9 Retroalimentación y movimiento | 5 |
| D10 Rendimiento percibido y fiabilidad | 10 |
| D11 Craft y detalle | 5 |

Anclas: **0** roto o ausente · **1** deficiente, falla en lo básico · **2** funcional con fallas notables · **3** sólido, sin fallas relevantes, aún pulible · **4** excelente, difícil de mejorar sin evidencia nueva.

Puntaje = Σ (peso × nivel ÷ 4). Ajusta pesos si el dominio lo justifica (p. ej., más peso a D10 en una app que se usa entre series) y dilo.

**Topes (gates):** cualquier S0 abierto → veredicto máximo "No lanzar". D6 ≤ 1 → máximo "Pulir antes de lanzar". Invariantes críticos de L7 rotos → no lanzar, sin importar el puntaje.

## E4. Veredicto y prueba rápida ("¿es buen diseño?")

| Puntaje y condición | Veredicto |
|---|---|
| ≥ 90 y sin S0–S2 abiertos | Excelente: pulido marginal |
| 80 a 89 y sin S0/S1 | Listo con pulidos |
| 65 a 79 | Pulir antes de lanzar |
| 50 a 64 | Rediseño parcial |
| < 50 | Rediseño o replanteo |

**Prueba rápida de cinco preguntas** (para DECIDIR o validar con rapidez):
1. ¿Una persona nueva completa la tarea principal sin ayuda y dentro del tiempo objetivo?
2. ¿La jerarquía es clara al entrecerrar los ojos?
3. ¿Están cubiertos todos los estados (vacío, carga, error, sin conexión, extremos)?
4. ¿Cumple WCAG 2.2 AA en lo esencial?
5. ¿Se siente de este producto y no de una plantilla?

5/5 → pulido marginal; 3 a 4 → pulir en lo que falla; ≤ 2 → problemas estructurales.

**Tres horizontes** (para decidir si vale la pena seguir):
- Horizonte 1, **Fallas:** rompe tareas, seguridad o accesibilidad. Se arregla siempre.
- Horizonte 2, **Fricción:** la tarea se completa con esfuerzo innecesario. Se arregla si el beneficio supera el costo.
- Horizonte 3, **Excelencia:** deleite, distinción, craft. Valioso solo cuando los dos anteriores están cubiertos y existe una razón para destacar.

Un diseño es **bueno** cuando no tiene fallas ni fricción relevantes en sus tareas críticas. Es **excelente** cuando, además, la excelencia es intencional y consistente.

## E5. Regla de parada del pulido ("techo de pulido")

Sigue pulir mientras: (a) existan hallazgos S0–S3 abiertos, o (b) un cambio concreto mejore de forma medible o razonada una tarea crítica (menos pasos, menos errores, menos carga cognitiva, mayor legibilidad, mayor confianza). Detente cuando lo que queda son preferencias sin evidencia ni efecto en la tarea, o cuando cada cambio arriesga regresiones mayores que su beneficio. Reporta explícitamente: "Techo de pulido alcanzado en X; lo que queda es gusto, y recomiendo no tocarlo", con la lista de lo que se dejó como está y por qué. Esto evita el ciclo eterno de retoques y protege lo que ya funciona.

---

# Parte F. Protocolo de investigación

**Jerarquía de evidencia** (de mayor a menor peso para decidir):
1. Normas, leyes y políticas oficiales vigentes (W3C, regulador, tiendas).
2. Revisiones sistemáticas, metaanálisis y posicionamientos de organismos (ACSM, OMS).
3. Estudios primarios bien diseñados y datos propios del producto (analítica, pruebas de usabilidad).
4. Guías oficiales de plataforma (HIG, Material, documentación de APIs).
5. Prácticas y marcos de la industria (útiles, pero etiquétalos como heurísticas).
6. Opiniones de personas expertas y blogs: contexto, no prueba.

**Reglas:**
- **Si hay búsqueda web:** verifica la versión vigente de cada estándar o política que vayas a citar; busca cambios recientes en regulación y tiendas; anota título, enlace y fecha de consulta. Prefiere fuentes primarias (el sitio del organismo, el documento original) a resúmenes.
- **Si no la hay:** cita de la Parte H marcando "de memoria, verificar" y no afirmes vigencia.
- **Afirmaciones científicas (entrenamiento, nutrición, salud):** distingue consenso, evidencia moderada y práctica de campo. Cifras como MEV/MAV/MRV son heurísticas de practicantes, no resultados experimentales definitivos; dilo. Reconoce la variabilidad individual.
- **Afirmaciones de UX:** distingue heurística, evidencia empírica y costumbre. "Cinco usuarios bastan" tiene condiciones (pruebas iterativas, tareas y audiencias homogéneas). SUS promedio ≈ 68 es una referencia, no una meta universal.
- **Benchmark competitivo:** 3 a 5 productos (directos, indirectos y referentes de craft de otras categorías), mismas tareas, mismo método, capturas, sin copiar identidad ni contenido protegido.
- **Minería de reseñas y soporte:** muestras de 1 a 3 estrellas y tickets, agrupa por temas, cuenta frecuencias, recuerda el sesgo (quien se queja ≠ la mayoría).
- **Sin usuarios aún:** evaluación heurística, recorrido cognitivo, pruebas con prototipos (5 a 8 personas por segmento), entrevistas de contexto, diarios, encuestas breves con preguntas sobre conducta pasada.
- **Triangulación:** una conclusión importante requiere al menos dos fuentes independientes o evidencia propia directa.
- **Registro de suposiciones:** lleva una lista de supuestos con su impacto si fueran falsos y cómo validarlos.

---

# Parte G. Entregables y disciplina de cambios

## G1. Estructura del informe

Guarda en `docs/audit/AAAA-MM-DD-<alcance>.md` (o donde indique la persona) y resume en el chat. Si el usuario hizo una pregunta puntual (modo DECIDIR), responde directo primero y ofrece el informe completo después.

1. **Veredicto y resumen ejecutivo** (máx. 10 líneas): veredicto, puntaje, los 3 riesgos más importantes y los 3 *quick wins*.
2. **Alcance y método:** qué se revisó, qué se ejecutó, qué **no** se revisó, herramientas disponibles, fecha.
3. **Ficha del producto** (Fase 1).
4. **Puntuación** (E3) con una línea de justificación por dimensión.
5. **Hallazgos** por severidad (E2).
6. **Oportunidades de pulido** y techo de pulido (E5).
7. **Plan:** Ahora (S0/S1) · Siguiente (S2 y quick wins) · Después (S3/S4, horizonte 3).
8. **Suposiciones, lagunas y preguntas abiertas.**
9. **Apéndices:** tabla de invariantes, matriz de estados, diagrama de flujos, plan de seguimiento (tracking plan), benchmark, referencias consultadas con fecha.

## G2. Tono del informe

Directo, específico y respetuoso. Nombra el problema y su impacto sin dramatizar ni suavizar. Reconoce lo que está bien hecho (también es información: dice qué no tocar). Evita adjetivos vacíos ("moderno", "limpio") y consejos que valdrían para cualquier app.

## G3. Cuando falte información

Pregunta lo mínimo imprescindible (una o dos preguntas) solo si cambia de forma sustancial el análisis. Si no, procede, declara tus suposiciones al inicio y deja el informe preparado para corregirse si resultan falsas.

## G4. Disciplina de cambios (si editas código o diseños)

- Cambios mínimos y localizados; no refactorices a gran escala sin que lo pidan.
- Corrige primero S0 y S1. Para cada fallo de lógica, escribe una prueba que falle antes y pase después.
- No añadas dependencias sin justificarlas (mantenimiento, licencia, tamaño, seguridad).
- No toques datos reales, secretos ni configuración de producción.
- Un cambio por propósito, con mensajes de commit claros que referencien el hallazgo.
- Tras cada bloque de cambios, reejecuta build, tipos, lint, pruebas y reverifica visual/accesibilidad. Reporta salidas reales; si algo sigue fallando, dilo.
- Documenta decisiones no obvias (ADR breve).

## G5. Checklist de salida (GATE)

Antes de decir "listo para lanzar":

- [ ] Sin S0 abiertos; S1 resueltos o aceptados por escrito con motivo y plan.
- [ ] Build, tipos, lint y pruebas pasan; las fallas conocidas están documentadas.
- [ ] Invariantes críticos con prueba; migraciones probadas en copia.
- [ ] Tareas críticas recorridas de punta a punta por cada rol, incluidos errores y modo sin conexión.
- [ ] Matriz de estados completa en las pantallas clave.
- [ ] WCAG 2.2 AA verificado con herramienta y manualmente en los flujos críticos.
- [ ] Autorización probada en el servidor (incluido IDOR y aislamiento entre inquilinos); secretos fuera del repo; escaneos limpios o justificados.
- [ ] Privacidad: datos mínimos, consentimientos, borrado de cuenta en la app, políticas de tienda cubiertas.
- [ ] Sin patrones engañosos; afirmaciones de salud respaldadas; límites de seguridad en recomendaciones.
- [ ] Rendimiento medido en dispositivo realista y red lenta; temporizadores y tareas en segundo plano fiables.
- [ ] Observabilidad: fallos y errores clave se reportan; plan de reversión y despliegue gradual.
- [ ] Plan de medición con baseline y métricas de éxito acordadas.
- [ ] Informe con lo **no revisado** declarado.

## G6. Antipatrones del auditor (evítalos)

- Listar consejos genéricos que aplican a cualquier app.
- Discutir gustos (tipografía favorita, color preferido) sin vínculo a una tarea o evidencia.
- Reportar 80 hallazgos S4 y ocultar los dos S0 entre ellos.
- Afirmar sin ejecutar ni abrir el código.
- Prometer certeza ("no hay errores") o inventar fuentes.
- Pulir sin fin, o recomendar rediseñar lo que ya funciona sin evidencia.
- Copiar a competidores sin entender el porqué.
- Ignorar el contexto físico y emocional del uso (gimnasio, fatiga, vergüenza corporal).

---

# Parte H. Referencias

Estas referencias están dadas **de memoria** salvo donde se indica verificación. Antes de citarlas en un informe, comprueba que siguen vigentes y su versión. Si no puedes verificar algo, indícalo.

## H1. Diseño, usabilidad e investigación
- Nielsen, J. *10 Usability Heuristics for User Interface Design*. nngroup.com/articles/ten-usability-heuristics
- Norman, D. *The Design of Everyday Things* (ed. revisada, 2013).
- Cooper, A. et al. *About Face* (4.ª ed., 2014).
- Krug, S. *Don't Make Me Think* (ed. revisada, 2014).
- Wharton, C. et al. (1994). The cognitive walkthrough method. En *Usability Inspection Methods*.
- Nielsen, J. y Landauer, T. (1993). A mathematical model of the finding of usability problems. Nielsen, J. (2000). *Why You Only Need to Test with 5 Users* (leer con sus condiciones).
- Fitzpatrick, R. *The Mom Test* (2013). Hall, E. *Just Enough Research* (2013). Torres, T. *Continuous Discovery Habits* (2021).
- Brooke, J. (1996). SUS: a quick and dirty usability scale. Rodden, K., Hutchinson, H. y Fu, X. (2010). Measuring the User Experience on a Large Scale (HEART). CHI 2010.
- Laws of UX: lawsofux.com. Nielsen Norman Group, *Response Times: The 3 Important Limits*.
- Saffer, D. *Microinteractions* (2013).

## H2. Diseño visual y sistemas
- Wathan, A. y Schoger, S. *Refactoring UI* (2018). Bringhurst, R. *The Elements of Typographic Style*. Tufte, E. *The Visual Display of Quantitative Information*. Few, S. *Show Me the Numbers*.
- W3C Design Tokens Community Group (formato DTCG): design-tokens.github.io
- Apple Human Interface Guidelines: developer.apple.com/design/human-interface-guidelines (verifica la versión del sistema vigente).
- Material Design 3: m3.material.io. Android, *Core app quality*: developer.android.com/docs/quality-guidelines/core-app-quality

## H3. Accesibilidad
- W3C, *Web Content Accessibility Guidelines (WCAG) 2.2*: w3.org/TR/WCAG22/ y guía rápida w3.org/WAI/WCAG22/quickref/
- W3C, *WCAG 3.0 Working Draft*: w3.org/TR/wcag-3.0/ (borrador de trabajo; estado verificado el 24-oct-2026: sigue sin ser Recomendación).
- WAI-ARIA Authoring Practices Guide: w3.org/WAI/ARIA/apg/
- EN 301 549; Directiva (UE) 2019/882 (European Accessibility Act, aplicable desde el 28-jun-2025); ADA; Sección 508.
- axe-core (Deque) y Lighthouse como herramientas automáticas.

## H4. Ingeniería, fiabilidad y datos
- Kleppmann, M. *Designing Data-Intensive Applications*. Kleppmann, M. et al. (2019). *Local-first software*. Shapiro, M. et al. (2011). CRDTs.
- Beyer, B. et al. *Site Reliability Engineering* (Google). Nygard, M. *Release It!*. Forsgren, N., Humble, J. y Kim, G. *Accelerate*. Fowler, M. *Refactoring*.
- Claessen, K. y Hughes, J. (2000). QuickCheck (pruebas basadas en propiedades); Hypothesis y fast-check como herramientas.
- Falsehoods programmers believe about time (N. Sussman) y about names (P. McKenzie). Base de datos de zonas horarias IANA.
- web.dev, Core Web Vitals: web.dev/articles/vitals

## H5. Seguridad y privacidad
- OWASP ASVS (v5.0 o la vigente), OWASP MASVS (mas.owasp.org), OWASP Top 10 (edición vigente). STRIDE (modelado de amenazas).
- PCI DSS v4.x. NIST AI RMF 1.0. Reglamento (UE) 2024/1689 (Ley de IA de la UE).
- GDPR (art. 9, categorías especiales). FTC Health Breach Notification Rule. Washington My Health My Data Act. CCPA/CPRA. LFPDPPP (México). LGPD (Brasil). HIPAA (solo si aplica). Verifica la versión vigente de cada norma.
- Apple App Store Review Guidelines y requisitos de privacidad (manifiestos); Google Play Policy Center y Data safety.

## H6. Comportamiento, ética y gamificación
- Ryan, R. y Deci, E. (2000). Self-determination theory. *American Psychologist*.
- Fogg, B. J. (2009). A behavior model for persuasive design. Michie, S., van Stralen, M. y West, R. (2011). The behaviour change wheel. *Implementation Science*.
- Deterding, S. et al. (2011). From game design elements to gamefulness. Brignull, H. *Deceptive Patterns* (deceptive.design). Gray, C. et al. (2018). The dark (patterns) side of UX design. FTC (2022). *Bringing Dark Patterns to Light*.

## H7. Ciencia del entrenamiento y salud (para apps de fitness)
- Garber, C. et al. (2011). ACSM Position Stand: Quantity and quality of exercise. *MSSE*. ACSM (2009). Progression models in resistance training for healthy adults. *MSSE*. ACSM, *Guidelines for Exercise Testing and Prescription* (11.ª ed., 2021; verifica la edición vigente).
- Organización Mundial de la Salud (2020). *Guidelines on physical activity and sedentary behaviour* (Bull, F. et al., *BJSM*).
- Schoenfeld, B., Ogborn, D. y Krieger, J. (2017). Dose-response relationship between weekly resistance training volume and muscle mass. *J Sports Sci*. Schoenfeld, B. et al. (2016). Effects of resistance training frequency on muscular adaptations. *Sports Med*.
- Zourdos, M. et al. (2016). Novel resistance training-specific RPE scale measuring repetitions in reserve. *JSCR*. Helms, E. et al. (2016). Application of the repetitions in reserve-based RPE scale. *Strength Cond J*.
- Epley, B. (1985) y Brzycki, M. (1993): estimación de 1RM (fiables sobre todo en rangos bajos de repeticiones).
- Kraemer, W. y Ratamess, N. (2004). Fundamentals of resistance training: progression and exercise prescription. *MSSE*.
- Israetel, M. et al. *Scientific Principles of Hypertrophy Training* (heurísticas de practicantes sobre MEV/MAV/MRV; etiquétalas como tales).
- Mifflin, M. et al. (1990). A new predictive equation for resting energy expenditure. *Am J Clin Nutr*. Tanaka, H. et al. (2001) sobre la frecuencia cardíaca máxima estimada (208 − 0,7×edad). *Compendium of Physical Activities* (Ainsworth et al.; verifica la actualización más reciente).
- PAR-Q+ (cribado de preparación para la actividad física): parqplus.org
