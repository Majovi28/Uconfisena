<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel del Gerente General - CONFISENA</title>
<link rel="stylesheet" href="gestor-compartido.css">
</head>
<body class="panel-admin panel-gerencia">
<aside class="barra-lateral">
  <a class="marca" href="gerente.php"><span class="marca-simbolo">C</span><span>Confi<strong>sena</strong></span></a>
  <p class="etiqueta-menu">SUPERVISIÓN</p>
  <nav class="navegacion">
    <a class="activo" href="gerente.php"><span>⌂</span> Inicio</a>
    <a href="#pedidos"><span>▤</span> Pedidos</a>
    <a href="#apartados"><span>▣</span> Apartados</a>
    <a href="#tiendas"><span>▥</span> Tiendas</a>
    <a href="#inventario"><span>▦</span> Inventario</a>
  </nav>
  <a class="cerrar-lateral" href="cerrar_sesion.php"><span>↪</span> Cerrar sesión</a>
</aside>
<div class="zona-principal">
  <header class="barra-superior">
    <div class="saludo"><strong>Bienvenido, <?= $nombreSeguro ?></strong><span>Panel de Gerencia General</span></div>
    <span class="perfil-rol">Gerente General</span>
  </header>
  <main class="contenido-panel">
    <section class="portada-panel">
      <div><p class="sobre-titulo">PLATAFORMA CONFISENA</p><h1>Visión general del sistema.</h1><p>Supervisa la actividad, los movimientos y el estado de la plataforma.</p></div>
      <div class="portada-inicial">C</div>
    </section>
        <?php if ($error !== ""): ?>
        <div class="mensaje-error">
            <?= escapar($error) ?>
        </div>
    <?php endif; ?>


    <section class="indicadores-panel">
      <article class="indicador"><span class="indicador-icono violeta">♙</span><div><span>Usuarios registrados</span><strong><?= $usuarios ?></strong></div></article>
      <article class="indicador"><span class="indicador-icono lavanda">▤</span><div><span>Tiendas registradas</span><strong><?= $tiendas ?></strong></div></article>
      <article class="indicador"><span class="indicador-icono verde">♧</span><div><span>Vendedores registrados</span><strong><?= $vendedores ?></strong></div></article>
      <article class="indicador"><span class="indicador-icono coral">▦</span><div><span>Productos del catálogo</span><strong><?= $productos ?></strong></div></article>
      <article class="indicador"><span class="indicador-icono violeta">▣</span><div><span>Unidades en inventario</span><strong><?= $unidadesInventario ?></strong></div></article>
      <article class="indicador"><span class="indicador-icono coral">▤</span><div><span>Pedidos pendientes</span><strong><?= $pedidosPendientes ?></strong></div></article>
      <article class="indicador"><span class="indicador-icono lavanda">▣</span><div><span>Apartados pendientes</span><strong><?= $apartadosPendientes ?></strong></div></article>
      <article class="indicador"><span class="indicador-icono verde">$</span><div><span>Pagos pendientes</span><strong><?= $pagosPendientes ?></strong></div></article>
    </section>
    <section class="bloque-panel accesos-gerencia">
      <div class="titulo-bloque"><div><h2>Accesos rápidos</h2><p>Salta directamente a los registros que supervisas.</p></div></div>
      <div class="accesos-panel">
        <a class="acceso-panel acceso-violeta" href="#pedidos"><span class="acceso-icono">▤</span><span><strong>Últimos pedidos</strong><small>Estado de compras y pagos</small></span><b>›</b></a>
        <a class="acceso-panel acceso-lavanda" href="#apartados"><span class="acceso-icono">▣</span><span><strong>Últimos apartados</strong><small>Reservas y saldos pendientes</small></span><b>›</b></a>
        <a class="acceso-panel acceso-coral" href="#tiendas"><span class="acceso-icono">▥</span><span><strong>Tiendas registradas</strong><small>Actividad de las tiendas</small></span><b>›</b></a>
        <a class="acceso-panel acceso-violeta" href="#inventario"><span class="acceso-icono">▦</span><span><strong>Alertas de inventario</strong><small>Productos con pocas unidades</small></span><b>›</b></a>
      </div>
    </section>
        <!-- COMPRAS -->
    <section class="seccion bloque-panel" id="pedidos">
        <h2>Últimos pedidos</h2>
        <p class="subtitulo">
            Seguimiento de las compras registradas por los usuarios.
        </p>

        <div class="tabla-contenedor">

            <?php if (empty($ultimosPedidos)): ?>
                <p class="vacio">Todavía no hay pedidos registrados.</p>
            <?php else: ?>

                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Entrega</th>
                            <th>Total del pedido</th>
                            <th>Estado del pedido</th>
                            <th>Estado del pago</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($ultimosPedidos as $pedido): ?>
                            <tr>
                                <td>#<?= (int)$pedido["id_pedido"] ?></td>

                                <td>
                                    <?= escapar(
                                        trim(
                                            ($pedido["nombre"] ?? "") . " " .
                                            ($pedido["apellido"] ?? "")
                                        ) ?: "Usuario"
                                    ) ?>
                                    <br>
                                    <small><?= escapar($pedido["correo"] ?? "") ?></small>
                                </td>

                                <td><?= escapar($pedido["fecha_pedido"]) ?></td>

                                <td>
                                    <?= escapar(
                                        $pedido["fecha_entrega"] ?: "Por confirmar"
                                    ) ?>
                                </td>

                                <td><?= pesos($pedido["total"]) ?></td>

                                <td><?= escapar($pedido["estado_pedido"]) ?></td>

                                <td><?= escapar($pedido["estado_pago"] ?? "Sin registro") ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </section>

    <!-- APARTADOS -->
    <section class="seccion">
        <h2>Últimos apartados</h2>
        <p class="subtitulo">
            Seguimiento de las reservas y sus saldos pendientes.
        </p>

        <div class="tabla-contenedor">

            <?php if (empty($ultimosApartados)): ?>
                <p class="vacio">Todavía no hay apartados registrados.</p>
            <?php else: ?>

                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th>Fecha del apartado</th>
                            <th>Fecha de entrega</th>
                            <th>Abono registrado</th>
                            <th>Saldo pendiente</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($ultimosApartados as $apartado): ?>
                            <tr>
                                <td>#<?= (int)$apartado["id_apartado"] ?></td>

                                <td>
                                    <?= escapar(
                                        trim(
                                            ($apartado["nombre"] ?? "") . " " .
                                            ($apartado["apellido"] ?? "")
                                        ) ?: "Usuario"
                                    ) ?>
                                </td>

                                <td><?= escapar($apartado["nombre_producto"]) ?></td>

                                <td><?= escapar($apartado["fecha_apartado"]) ?></td>

                                <td>
                                    <?= escapar(
                                        $apartado["fecha_entrega"] ?: "Por confirmar"
                                    ) ?>
                                </td>

                                <td><?= pesos($apartado["abono"]) ?></td>

                                <td><?= pesos($apartado["saldo"]) ?></td>

                                <td><?= escapar($apartado["estado"]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </section>

    <!-- TIENDAS -->
    <section class="seccion">
        <h2>Tiendas registradas</h2>
        <p class="subtitulo">
            Consulta las tiendas y la cantidad de vendedores asociados.
            Esta sección es únicamente de lectura.
        </p>

        <div class="tabla-contenedor">

            <?php if (empty($tiendasRegistradas)): ?>
                <p class="vacio">Todavía no hay tiendas registradas.</p>
            <?php else: ?>

                <table>
                    <thead>
                        <tr>
                            <th>Tienda</th>
                            <th>Localidad</th>
                            <th>Vendedores asociados</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($tiendasRegistradas as $tienda): ?>
                            <tr>
                                <td><?= escapar($tienda["nombre_tienda"]) ?></td>

                                <td><?= escapar($tienda["localidad"]) ?></td>

                                <td><?= (int)$tienda["vendedores"] ?></td>

                                <td>
                                    <?= (int)$tienda["estado"] === 1
                                        ? "Activa"
                                        : "Inactiva" ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </section>

    <!-- INVENTARIO -->
    <section class="seccion">
        <h2>Alertas de inventario</h2>
        <p class="subtitulo">
            Productos con cinco unidades disponibles o menos.
        </p>

        <div class="tabla-contenedor">

            <?php if (empty($inventarioBajo)): ?>
                <p class="vacio">
                    No hay productos con existencias bajas en este momento.
                </p>
            <?php else: ?>

                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Unidades disponibles</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($inventarioBajo as $producto): ?>
                            <tr>
                                <td><?= escapar($producto["nombre_producto"]) ?></td>
                                <td><?= (int)$producto["unidades"] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </section>

    <section class="seccion nota bloque-panel">
        <h3>Funciones del Gerente General</h3>
        <p>
            Este panel está destinado a supervisar la actividad de la plataforma.
            El Gerente General puede revisar los indicadores y los registros,
            pero no dispone de funciones para registrar tiendas ni vendedores
            desde este panel.
        </p>
    </section>


  </main>
  <footer class="pie-panel">CONFISENA · Plataforma de gestión y control</footer>
</div>
</body>
</html>