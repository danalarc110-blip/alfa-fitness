<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de Compra #{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0b0d10; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f3f4f6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0b0d10; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 580px; background-color: #121418; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 28px 32px; background: linear-gradient(180deg, rgba(250,204,21,0.12) 0%, rgba(18,20,24,0) 100%); border-bottom: 1px solid rgba(255,255,255,0.08); text-align: center;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 2px; color: #facc15; text-transform: uppercase;">ALPHA FITNESS</h1>
                            <p style="margin: 4px 0 0 0; font-size: 13px; color: #9ca3af; letter-spacing: 0.5px;">Comprobante Digital de Compra</p>
                        </td>
                    </tr>

                    <!-- Metadata -->
                    <tr>
                        <td style="padding: 24px 32px 16px 32px;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="font-size: 13px; color: #9ca3af; padding-bottom: 6px;">
                                        <strong style="color: #ffffff;">Ticket N°:</strong> #{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td align="right" style="font-size: 13px; color: #9ca3af; padding-bottom: 6px;">
                                        <strong style="color: #ffffff;">Fecha:</strong> {{ $venta->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 13px; color: #9ca3af;">
                                        <strong style="color: #ffffff;">Cliente:</strong> {{ $venta->cliente ? $venta->cliente->nombre : 'Venta en Mostrador' }}
                                    </td>
                                    <td align="right" style="font-size: 13px; color: #9ca3af;">
                                        <strong style="color: #ffffff;">Pago:</strong> {{ $venta->metodo_pago }}
                                    </td>
                                </tr>
                                @if($venta->user)
                                <tr>
                                    <td colspan="2" style="font-size: 12px; color: #6b7280; padding-top: 6px;">
                                        Atendido por: {{ $venta->user->name }}
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    <!-- Items Table -->
                    <tr>
                        <td style="padding: 10px 32px 24px 32px;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; margin-top: 8px;">
                                <thead>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                        <th align="left" style="font-size: 11px; text-transform: uppercase; color: #9ca3af; padding: 10px 0; font-weight: 600;">Cant. & Producto</th>
                                        <th align="right" style="font-size: 11px; text-transform: uppercase; color: #9ca3af; padding: 10px 0; font-weight: 600;">Precio</th>
                                        <th align="right" style="font-size: 11px; text-transform: uppercase; color: #9ca3af; padding: 10px 0; font-weight: 600;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($venta->detalles as $detalle)
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td style="padding: 12px 0; font-size: 13px;">
                                            <span style="display: inline-block; background-color: rgba(250,204,21,0.15); color: #facc15; font-weight: 700; font-size: 11px; padding: 2px 7px; border-radius: 4px; margin-right: 6px;">{{ $detalle->cantidad }}x</span>
                                            <strong style="color: #ffffff;">{{ $detalle->producto->nombre ?? 'Producto' }}</strong>
                                        </td>
                                        <td align="right" style="padding: 12px 0; font-size: 13px; color: #9ca3af;">
                                            ${{ number_format($detalle->precio_unitario, 2) }}
                                        </td>
                                        <td align="right" style="padding: 12px 0; font-size: 13px; font-weight: 600; color: #ffffff;">
                                            ${{ number_format($detalle->subtotal, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" align="right" style="padding: 18px 0 6px 0; font-size: 14px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Total Pagado:
                                        </td>
                                        <td align="right" style="padding: 18px 0 6px 0; font-size: 22px; font-weight: 800; color: #facc15;">
                                            ${{ number_format($venta->total, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </td>
                    </tr>

                    @if($venta->notas)
                    <tr>
                        <td style="padding: 0 32px 18px 32px;">
                            <div style="background-color: rgba(255,255,255,0.03); border-left: 3px solid #facc15; padding: 10px 14px; border-radius: 4px;">
                                <p style="margin: 0; font-size: 12px; color: #9ca3af;"><strong style="color: #ffffff;">Nota:</strong> {{ $venta->notas }}</p>
                            </div>
                        </td>
                    </tr>
                    @endif

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 32px; background-color: rgba(0,0,0,0.3); border-top: 1px solid rgba(255,255,255,0.06); text-align: center;">
                            <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 600; color: #facc15;">¡Gracias por tu compra en Alpha Fitness!</p>
                            <p style="margin: 0; font-size: 11px; color: #6b7280;">Cada repetición, cada serie y cada día cuenta. Este es un comprobante electrónico oficial.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
