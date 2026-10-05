# Fórmulas de progreso y estadísticas privadas

La clasificación existente se conserva. Cada récord registra carga en kg y repeticiones. Su volumen es `peso_kg × repeticiones`; el nivel es Inicial si el volumen es menor a 300, Intermedio desde 300 hasta menos de 600, y Avanzado desde 600. Son umbrales descriptivos del registro, no una evaluación física ni una comparación adecuada entre personas o ejercicios.

La nueva pantalla de estadísticas agrupa únicamente los récords del cliente conectado por ejercicio, con filtros por ejercicio y fechas. Incluye ejercicios desactivados cuando tienen historial del cliente. Muestra cantidad de registros, carga máxima, suma de volúmenes y nivel del registro de mayor volumen. El volumen de récords guardados no representa todas las sesiones realizadas.

Como dato adicional orientativo se calcula una estimación de una repetición máxima: `peso_kg × (1 + min(repeticiones, 30) / 30)`. Se muestra el máximo de esas estimaciones por ejercicio dentro del filtro. Ejemplo: 60 kg × 5 repeticiones produce 70 kg estimados. La decisión de limitar a 30 repeticiones evita extrapolaciones ilimitadas; a muchas repeticiones la estimación es especialmente incierta. No indica una carga segura, una prescripción ni una medida de salud. No modifica los récords ni los niveles existentes.

Las estrellas del catálogo expresan una calificación del usuario sobre el ejercicio y continúan separadas de carga, volumen y nivel. No se recalculan a partir de levantamientos.

Los ejercicios con máquina o polea se identifican por el nombre existente del catálogo. El esquema no contiene identificadores de máquinas individuales ni un campo de equipamiento; no se inventan agrupaciones por equipo ni se atribuyen dos ejercicios a la misma máquina. Las estadísticas no son públicas y no exponen récords ni identidad de otros clientes, aunque se agregue un `cliente_id` a la URL.
