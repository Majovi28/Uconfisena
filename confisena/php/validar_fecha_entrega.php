<?php
/**
 * Validación centralizada de fecha y franja de entrega para CONFISENA.
 * Zona horaria oficial del proyecto: Colombia.
 */
function confisenaFechaHorarioValido(string $fecha, string $horario, ?string &$mensaje = null): bool
{
    $zona = new DateTimeZone('America/Bogota');
    $ahora = new DateTimeImmutable('now', $zona);
    $seleccion = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha, $zona);
    $errores = DateTimeImmutable::getLastErrors();

    if (!$seleccion || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0)) || $seleccion->format('Y-m-d') !== $fecha) {
        $mensaje = 'La fecha de entrega no es válida.';
        return false;
    }

    if ($seleccion->format('Y-m-d') < $ahora->format('Y-m-d')) {
        $mensaje = 'No puedes seleccionar una fecha anterior al día de hoy.';
        return false;
    }

    $inicios = ['Manana' => '08:00', 'Tarde' => '14:00'];
    if (!isset($inicios[$horario])) {
        $mensaje = 'Selecciona una franja de entrega válida.';
        return false;
    }

    if ($seleccion->format('Y-m-d') === $ahora->format('Y-m-d')) {
        $inicioFranja = new DateTimeImmutable($fecha . ' ' . $inicios[$horario] . ':00', $zona);
        if ($ahora >= $inicioFranja) {
            $mensaje = 'La franja seleccionada ya comenzó. Elige otra franja o una fecha posterior.';
            return false;
        }
    }

    return true;
}
