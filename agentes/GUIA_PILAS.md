# Adaptación del equipo a tecnologías

Guía de selección, no recetas universales. El repositorio y sus versiones deciden. Descubrir scripts/documentación/CI antes de elegir comandos; inspeccionar efectos de instalación y scripts antes de ejecutarlos. No instalar todas las herramientas de esta tabla. Consultar documentación oficial de la versión pertinente cuando una API, bandera o garantía no esté confirmada.

| Familia | Fuentes para detectar | Verificación pertinente, si disponible | Riesgos específicos |
| --- | --- | --- | --- |
| JS/TS y web | package.json, lockfile, tsconfig, scripts/CI | Scripts reales de tipos, lint, pruebas y build; navegador para flujos | Contratos, asincronía, bundling, estado y SSR/hidratación si existen |
| Python | pyproject.toml, requisitos/lockfile, tox/nox/CI | Runner, lint/tipos y entorno definidos en proyecto | Mutabilidad, serialización, dependencias, datos, procesos y asyncio |
| PHP y frameworks | composer.json/lock, scripts, configuración y pruebas | Scripts/tests reales, sintaxis localizada, compilación de vistas si existe | Auth, ORM/DB, configuración cacheada y efectos de scripts |
| Java/Kotlin JVM | pom.xml, Gradle, wrappers y CI | Wrapper del proyecto para compilar/probar/analizar | Nullabilidad, hilos, transacciones y compatibilidad JVM |
| C#/.NET | sln/csproj, global.json y configuración | SDK del proyecto, build/tests/analyzers | Nullable, async, dispose y plataforma objetivo |
| Go | go.mod/sum, Makefile y CI | Targets o herramientas existentes de tests/build/vet; race si es pertinente y disponible | Goroutines, errores, contexto, carreras y módulos |
| Rust | Cargo.toml/lock, toolchain y CI | Targets de test/check/clippy/fmt existentes | Ownership, unsafe, lifetimes y efectos async |
| C/C++ | CMake/Meson/Make, compilador, flags y tests | Build del proyecto, tests, warnings; sanitizers si toolchain lo permite | UB, memoria, tamaños, ownership y portabilidad ABI |
| Dart/Flutter | pubspec/lock, análisis y proyecto de plataforma | Analyzer/tests/build según scripts; emulador o equipo real para UX | Ciclo de vida, canales nativos, permisos y límites del equipo |
| Swift/Apple | Package.swift, Xcode project y targets | Build/tests compatibles con SO/toolchain | Concurrencia, memoria, lifecycle, permisos; Linux no demuestra iOS |
| Android nativo | Gradle, manifests, SDK/targets y tests | Wrapper, pruebas JVM/instrumentadas disponibles | Lifecycle, back/navigation, permisos y dispositivos |
| SQL/datos/ML | Esquemas, migrations, motor, pipeline y notebooks | Fixtures aislados, integridad y consultas; evaluación reproducible | Aislamiento, precisión, leakage, semilla, provenance y volumen |
| Firmware/Arduino | Plataforma/placa, manifests, sketch y fabricante | Compilación, simulación y hardware disponible separados | Temporización, memoria, pines, tensión/corriente y fallos físicos |
| Shell/CLI/escritorio | Entry points, scripts, empaquetado y SO | Sintaxis/tests/build propios, códigos de salida y ejecución segura | Quoting, PATH, privilegios, señales, recursos y accesibilidad |

En repositorios políglotas verificar consumidores/productores y compatibilidad de datos. No afirmar garantía de motor o hardware a partir de un mock/simulador. No asumir que la máquina local es el sistema del usuario. Registrar runtime, SO y dependencias que realmente se usaron.

## Fuentes y contexto

Este paquete sigue el formato de agentes de Antigravity: https://antigravity.google/docs/subagents/. Alpha Fitness es solo el proyecto donde se guardan; su pila actual se descubre en sus manifests y no limita los roles. Ejemplos de documentación consultada para ese proyecto: https://laravel.com/docs/12.x/testing, https://laravel.com/docs/12.x/database y https://laravel.com/docs/12.x/authorization. En otro proyecto consultar sus propias fuentes primarias.
