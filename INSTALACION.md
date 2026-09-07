# Alpha Fitness — paquete visual instalable

## Instalación

Descomprime el contenido de esta carpeta en la raíz de tu proyecto Laravel y permite combinar carpetas.

Estructura incluida:
- `public/images/` — imágenes de ejercicios.
- `resources/js/animations.js` — motor de animaciones mejorado.
- `resources/css/alpha-effects.css` — efectos visuales.

Después, en `resources/css/app.css`, añade al final:

```css
@import './alpha-effects.css';
```

Después ejecuta:

```bash
npm run build
```

**Nota:** `animations-enhanced.js` fue colocado como `resources/js/animations.js`, por lo que al copiarlo reemplazará el archivo existente con ese nombre. Haz una copia de seguridad si quieres conservar la versión anterior.
