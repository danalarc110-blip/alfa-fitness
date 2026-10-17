const formularioVenta = document.getElementById('form-venta');

if (formularioVenta) {
    const configVenta = JSON.parse(document.getElementById('venta-config').textContent);
    const productosVenta = new Map(configVenta.productos.map(producto => [String(producto.id), producto]));
    const carritoVenta = new Map();
    const contenedorItems = document.getElementById('items-contenedor');
    let ventaRevisada = false;
    let ventaEnviando = false;

    const monedaVenta = centavos => '$' + (centavos / 100).toFixed(2);
    const mensajeVenta = mensaje => { document.getElementById('venta-mensaje').textContent = mensaje; };

    const mostrarSeccion = (seccion, enfocar = true) => {
        if (ventaEnviando) return;
        document.getElementById('venta-nueva').classList.toggle('hidden', seccion !== 'nueva');
        document.getElementById('venta-historial').classList.toggle('hidden', seccion !== 'historial');
        document.querySelectorAll('[data-venta-ir][aria-pressed]').forEach(boton => {
            boton.setAttribute('aria-pressed', String(boton.dataset.ventaIr === seccion));
        });
        if (enfocar) {
            const destino = seccion === 'nueva'
                ? document.getElementById(ventaRevisada ? 'venta-revision-titulo' : 'venta-busqueda')
                : document.querySelector('#venta-historial [name=q]');
            destino.focus();
        }
    };
    document.querySelectorAll('[data-venta-ir]').forEach(boton => {
        boton.addEventListener('click', () => mostrarSeccion(boton.dataset.ventaIr));
    });

    function editarVenta() {
        if (ventaEnviando) return;
        ventaRevisada = false;
        document.getElementById('venta-edicion').classList.remove('hidden');
        document.getElementById('venta-revision').classList.add('hidden');
        document.getElementById('venta-editar').classList.add('hidden');
        document.getElementById('venta-revisar').classList.remove('hidden');
        document.getElementById('venta-registrar').classList.add('hidden');
        document.getElementById('venta-registrar').disabled = true;
    }

    function recalcularTotal() {
        let totalCentavos = 0;
        contenedorItems.querySelectorAll('.item-fila').forEach(fila => {
            const item = carritoVenta.get(fila.dataset.productoId);
            const cantidad = Number(item.cantidad);
            const subtotal = Number.isInteger(cantidad) && cantidad > 0 ? item.producto.precio_centavos * cantidad : 0;
            fila.querySelector('.fila-subtotal').textContent = monedaVenta(subtotal);
            fila.querySelector('.cantidad-menos').disabled = cantidad <= 1;
            fila.querySelector('.cantidad-mas').disabled = !item.producto.activo || cantidad >= Math.min(item.producto.stock, 999);
            totalCentavos += subtotal;
        });
        document.getElementById('gran-total').textContent = monedaVenta(totalCentavos);
        document.getElementById('venta-vacia').classList.toggle('hidden', carritoVenta.size > 0);
        const unidades = [...carritoVenta.values()].reduce((total, item) => {
            const cantidad = Number(item.cantidad);
            return total + (Number.isInteger(cantidad) && cantidad > 0 ? cantidad : 0);
        }, 0);
        document.getElementById('venta-conteo').textContent = unidades + (unidades === 1 ? ' unidad' : ' unidades');
        document.getElementById('venta-revisar').disabled = carritoVenta.size === 0;
        document.getElementById('venta-ir-carrito').textContent = 'Ver carrito (' + unidades + ') · ' + monedaVenta(totalCentavos) + ' ↓';
        document.querySelectorAll('[data-anadir-producto]').forEach(boton => {
            const item = carritoVenta.get(boton.dataset.anadirProducto);
            boton.textContent = item ? 'Añadido (' + item.cantidad + ')' : 'Añadir';
        });
    }

    function renderizarCarrito() {
        contenedorItems.replaceChildren();
        let indice = 0;
        carritoVenta.forEach((item, productoId) => {
            const producto = item.producto;
            const fila = document.createElement('div');
            fila.dataset.productoId = productoId;
            fila.className = 'item-fila bg-black/40 border border-white/10 p-3 rounded-xl';
            fila.innerHTML = '<div class="flex justify-between gap-2"><div class="min-w-0"><p class="fila-nombre text-xs font-semibold break-words"></p><p class="fila-precio text-[11px] text-gray-400 mt-0.5"></p></div><button type="button" class="quitar-producto text-xs text-red-400 hover:text-red-300 shrink-0 px-2 py-1">Quitar</button></div><div class="flex flex-wrap items-center justify-between gap-2 mt-2"><div class="flex items-center gap-1"><button type="button" class="cantidad-menos w-9 h-9 rounded-lg bg-white/5 text-lg">−</button><input type="number" min="1" step="1" required class="input-cantidad w-16 bg-black/60 border border-white/10 rounded-lg px-2 py-2 text-xs text-center text-white"><button type="button" class="cantidad-mas w-9 h-9 rounded-lg bg-white/5 text-lg">+</button></div><span class="fila-subtotal text-sm font-bold text-yellow-400"></span></div><input type="hidden" class="input-producto">';
            fila.querySelector('.fila-nombre').textContent = producto.nombre;
            fila.querySelector('.fila-precio').textContent = monedaVenta(producto.precio_centavos) + ' por unidad · ' + (producto.activo && producto.stock > 0 ? 'Stock: ' + producto.stock : 'No disponible: retira este producto');
            const cantidad = fila.querySelector('.input-cantidad');
            cantidad.name = 'items[' + indice + '][cantidad]';
            cantidad.value = String(item.cantidad);
            cantidad.max = producto.activo && producto.stock > 0 ? Math.min(producto.stock, 999) : 999;
            cantidad.setAttribute('aria-label', 'Cantidad de ' + producto.nombre);
            fila.querySelector('.input-producto').name = 'items[' + indice + '][producto_id]';
            fila.querySelector('.input-producto').value = productoId;
            fila.querySelector('.quitar-producto').setAttribute('aria-label', 'Quitar ' + producto.nombre);
            fila.querySelector('.cantidad-menos').setAttribute('aria-label', 'Disminuir cantidad de ' + producto.nombre);
            fila.querySelector('.cantidad-mas').setAttribute('aria-label', 'Aumentar cantidad de ' + producto.nombre);
            cantidad.addEventListener('input', () => {
                if (ventaEnviando) return;
                item.cantidad = cantidad.value;
                editarVenta();
                recalcularTotal();
            });
            fila.querySelector('.cantidad-menos').addEventListener('click', () => ajustarCantidadVenta(productoId, -1));
            fila.querySelector('.cantidad-mas').addEventListener('click', () => ajustarCantidadVenta(productoId, 1));
            fila.querySelector('.quitar-producto').addEventListener('click', () => {
                if (ventaEnviando) return;
                carritoVenta.delete(productoId);
                editarVenta();
                renderizarCarrito();
                mensajeVenta(producto.nombre + ' retirado de la venta.');
                (contenedorItems.querySelector('.input-cantidad') || document.getElementById('venta-busqueda')).focus();
            });
            contenedorItems.append(fila);
            indice++;
        });
        recalcularTotal();
    }

    function ajustarCantidadVenta(productoId, cambio) {
        if (ventaEnviando) return;
        const item = carritoVenta.get(productoId);
        const actual = Number(item.cantidad);
        const nueva = (Number.isInteger(actual) && actual > 0 ? actual : 0) + cambio;
        if (nueva < 1) {
            mensajeVenta('Usa Quitar para retirar el producto.');
            return;
        }
        if (!item.producto.activo || (cambio > 0 && nueva > Math.min(item.producto.stock, 999))) {
            mensajeVenta('No puedes añadir más unidades de ' + item.producto.nombre + '. Stock disponible: ' + item.producto.stock + '.');
            return;
        }
        item.cantidad = nueva;
        editarVenta();
        renderizarCarrito();
        // El ID procede del catálogo del servidor, no de entradas arbitrarias.
        [...contenedorItems.children].find(fila => fila.dataset.productoId === productoId)?.querySelector('.input-cantidad').focus();
        mensajeVenta(item.producto.nombre + ': ' + nueva + ' unidades.');
    }

    document.querySelectorAll('[data-anadir-producto]').forEach(boton => {
        boton.addEventListener('click', () => {
            if (ventaEnviando) return;
            const productoId = boton.dataset.anadirProducto;
            const producto = productosVenta.get(productoId);
            if (!producto?.activo || producto.stock < 1) return;
            if (carritoVenta.has(productoId)) {
                ajustarCantidadVenta(productoId, 1);
                return;
            }
            if (carritoVenta.size >= 100) {
                mensajeVenta('La venta admite hasta 100 productos distintos.');
                return;
            }
            carritoVenta.set(productoId, {producto, cantidad: 1});
            editarVenta();
            renderizarCarrito();
            mensajeVenta(producto.nombre + ' añadido a la venta.');
        });
    });

    const normalizarBusqueda = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    let categoriaVenta = '';
    function filtrarProductos() {
        const busqueda = normalizarBusqueda(document.getElementById('venta-busqueda').value);
        let visibles = 0;
        document.querySelectorAll('.venta-producto').forEach(tarjeta => {
            const coincide = (!categoriaVenta || tarjeta.dataset.categoria === categoriaVenta)
                && normalizarBusqueda(tarjeta.dataset.busqueda).includes(busqueda);
            tarjeta.classList.toggle('hidden', !coincide);
            if (coincide) visibles++;
        });
        document.getElementById('venta-sin-resultados').classList.toggle('hidden', visibles > 0);
        document.getElementById('venta-resultados').textContent = visibles + (visibles === 1 ? ' producto' : ' productos');
    }
    document.getElementById('venta-busqueda').addEventListener('input', filtrarProductos);
    document.getElementById('venta-busqueda').addEventListener('keydown', event => {
        if (event.key === 'Enter') event.preventDefault();
    });
    document.querySelectorAll('[data-venta-categoria]').forEach(boton => {
        boton.addEventListener('click', () => {
            categoriaVenta = boton.dataset.ventaCategoria;
            document.querySelectorAll('[data-venta-categoria]').forEach(opcion => opcion.setAttribute('aria-pressed', String(opcion === boton)));
            filtrarProductos();
        });
    });
    filtrarProductos();

    function revisarVenta() {
        if (ventaEnviando) return;
        if (!carritoVenta.size) {
            mensajeVenta('Añade al menos un producto a la venta.');
            document.getElementById('venta-busqueda').focus();
            return;
        }
        for (const item of carritoVenta.values()) {
            if (!item.producto.activo || item.producto.stock < 1) {
                mensajeVenta('Retira ' + item.producto.nombre + ': ya no está disponible para la venta.');
                return;
            }
        }
        if (!formularioVenta.reportValidity()) return;
        const resumen = document.getElementById('venta-resumen-items');
        resumen.replaceChildren();
        carritoVenta.forEach(item => {
            const linea = document.createElement('li');
            linea.className = 'flex justify-between gap-3';
            const nombre = document.createElement('span');
            nombre.textContent = item.cantidad + ' × ' + item.producto.nombre;
            nombre.className = 'break-words min-w-0';
            const subtotal = document.createElement('strong');
            subtotal.className = 'shrink-0';
            subtotal.textContent = monedaVenta(item.producto.precio_centavos * Number(item.cantidad));
            linea.append(nombre, subtotal);
            resumen.append(linea);
        });
        const cliente = document.getElementById('venta-cliente');
        document.getElementById('venta-resumen-cliente').textContent = 'Cliente: ' + cliente.options[cliente.selectedIndex].textContent;
        document.getElementById('venta-resumen-pago').textContent = 'Pago presencial: ' + document.getElementById('venta-metodo').value;
        document.getElementById('venta-resumen-notas').textContent = document.getElementById('venta-notas').value;
        document.getElementById('venta-edicion').classList.add('hidden');
        document.getElementById('venta-revision').classList.remove('hidden');
        document.getElementById('venta-editar').classList.remove('hidden');
        document.getElementById('venta-revisar').classList.add('hidden');
        document.getElementById('venta-registrar').classList.remove('hidden');
        document.getElementById('venta-registrar').disabled = false;
        ventaRevisada = true;
        mensajeVenta('Comprueba el cliente, los productos y el pago antes de registrar.');
        document.getElementById('venta-revision-titulo').focus();
    }

    document.getElementById('venta-revisar').addEventListener('click', revisarVenta);
    document.getElementById('venta-editar').addEventListener('click', () => {
        editarVenta();
        document.getElementById('venta-busqueda').focus();
    });
    ['venta-cliente', 'venta-metodo', 'venta-notas'].forEach(id => document.getElementById(id).addEventListener('input', editarVenta));
    formularioVenta.addEventListener('submit', event => {
        if (ventaEnviando || !ventaRevisada) {
            event.preventDefault();
            if (!ventaEnviando) revisarVenta();
            return;
        }
        ventaEnviando = true;
        formularioVenta.setAttribute('aria-busy', 'true');
        const registrar = document.getElementById('venta-registrar');
        registrar.disabled = true;
        registrar.textContent = 'Registrando...';
        document.getElementById('venta-editar').disabled = true;
        document.querySelectorAll('[data-venta-ir]').forEach(boton => { boton.disabled = true; });
        mensajeVenta('Registrando la venta. Espera el comprobante.');
    });
    window.addEventListener('pageshow', event => {
        if (!event.persisted) return;
        ventaEnviando = false;
        formularioVenta.removeAttribute('aria-busy');
        document.getElementById('venta-registrar').textContent = 'Registrar venta';
        document.getElementById('venta-editar').disabled = false;
        document.querySelectorAll('[data-venta-ir]').forEach(boton => { boton.disabled = false; });
        editarVenta();
    });

    configVenta.itemsAnteriores.forEach(item => {
        const productoId = typeof item.producto_id === 'number' || typeof item.producto_id === 'string' ? String(item.producto_id) : '';
        const producto = productosVenta.get(productoId) || {id: productoId, nombre: 'Producto no disponible (#' + productoId + ')', stock: 0, activo: false, precio_centavos: 0};
        const cantidad = typeof item.cantidad === 'number' || typeof item.cantidad === 'string' ? item.cantidad : '';
        const anterior = carritoVenta.get(productoId);
        if (anterior && Number.isInteger(Number(anterior.cantidad)) && Number.isInteger(Number(cantidad))) {
            anterior.cantidad = Number(anterior.cantidad) + Number(cantidad);
        } else {
            carritoVenta.set(productoId, {producto, cantidad});
        }
    });
    renderizarCarrito();
    if (configVenta.recuperarVenta) {
        mostrarSeccion('nueva', false);
        document.getElementById('venta-errores')?.focus();
    }
}
