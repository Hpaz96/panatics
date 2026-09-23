<?php
// =============================================
//  SAVE.PHP - Procesa altas/bajas/modificaciones
//  de todos los módulos (cliente, tecnico, equipo,
//  reparacion, pago, fotos)
// =============================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$conn = conectarBD();
$accion = $_POST['accion'] ?? '';
$modulo = $_POST['modulo'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$esAdmin = esAdmin();
$tecnicoId = tecnicoIdActual();

// Función de escape rápida
function e($campo) {
    global $conn;
    return isset($_POST[$campo]) ? $conn->real_escape_string(trim($_POST[$campo])) : '';
}

$ruta = null; // ruta de retorno por defecto

switch ($modulo) {

    // =========================== CLIENTE ===========================
    case 'cliente':
        // Técnicos: pueden crear, y editar solo clientes con reparación asignada a ellos
        if (!$esAdmin && $accion == 'editar') {
            $chk = $conn->query(
                "SELECT r.Id_reparacion FROM reparacion r
                 INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
                 WHERE e.Id_cliente = $id AND r.Id_tecnico = $tecnicoId LIMIT 1"
            );
            if (!$chk || $chk->num_rows == 0) {
                header('Location: ' . BASE_URL . 'cliente/index.php?err=' . urlencode('Solo el técnico designado puede modificar este cliente.'));
                exit;
            }
        }
        $ruta = 'cliente/index.php';
        $redireccion = function ($success, $mensaje) use ($ruta) {
            $tipo = $success ? 'msj' : 'err';
            header('Location: ' . BASE_URL . $ruta . '?' . $tipo . '=' . urlencode($mensaje));
            exit;
        };

        $nombre = e('nombre'); $apellido = e('apellido'); $telefono = e('telefono');
        $correo = e('correo'); $sucursal = e('sucursal'); $rfc = e('rfc');
        $fecha = e('fecha');
        // convert datetime-local a MySQL
        if ($fecha !== '') { $fecha = date('Y-m-d H:i:s', strtotime($fecha)); }

        if ($accion == 'crear') {
            $sql = "INSERT INTO cliente (nombre, apellido, telefono, correo, fecha, sucursal, rfc)
                    VALUES ('$nombre','$apellido','$telefono','$correo','$fecha','$sucursal','$rfc')";
            $ok = $conn->query($sql);
            if ($ok) {
                $nuevoId = $conn->insert_id;
                registrarBitacora($conn, 'cliente', 'crear', $nuevoId, 'Cliente: ' . $nombre . ' ' . $apellido);
                if (($_POST['proceso'] ?? '') === '1') {
                    header('Location: ' . BASE_URL . 'equipo/create.php?cliente=' . $nuevoId . '&msj=' . urlencode('Cliente registrado correctamente. Continúa con el equipo.'));
                    exit;
                }
            }
            $redireccion($ok, $ok ? 'Cliente registrado correctamente.' : 'Error al guardar: ' . $conn->error);
        } elseif ($accion == 'editar') {
            $sql = "UPDATE cliente SET nombre='$nombre', apellido='$apellido', telefono='$telefono',
                    correo='$correo', fecha='$fecha', sucursal='$sucursal', rfc='$rfc'
                    WHERE Id_cliente = $id";
            $ok = $conn->query($sql);
            if ($ok) { registrarBitacora($conn, 'cliente', 'editar', $id, 'Cliente: ' . $nombre . ' ' . $apellido); }
            $redireccion($ok, $ok ? 'Cliente actualizado correctamente.' : 'Error al actualizar: ' . $conn->error);
        }
        break;

    // =========================== TECNICO ===========================
    case 'tecnico':
        if (!$esAdmin) {
            header('Location: ' . BASE_URL . 'tecnico/index.php?err=' . urlencode('Solo el administrador puede administrar técnicos.'));
            exit;
        }
        $ruta = 'tecnico/index.php';
        $redireccion = function ($success, $mensaje) use ($ruta) {
            $tipo = $success ? 'msj' : 'err';
            header('Location: ' . BASE_URL . $ruta . '?' . $tipo . '=' . urlencode($mensaje));
            exit;
        };

        $nombre = e('nombre'); $apellido = e('apellido'); $telefono = e('telefono');
        $correo = e('correo'); $puesto = e('puesto'); $zona = e('zona');
        $user = e('User'); $pass = e('pass');
        $role = e('role');
        if (!in_array($role, ['tecnico', 'admin'], true)) { $role = 'tecnico'; }

        // Evita que un administrador se quite su propio acceso
        if (($accion == 'editar') && $id === $tecnicoId && $role !== 'admin') {
            header('Location: ' . BASE_URL . 'tecnico/index.php?err=' . urlencode('No puedes quitar tu propio rol de administrador.'));
            exit;
        }

        if ($accion == 'crear') {
            $sql = "INSERT INTO tecnico (nombre, apellido, telefono, correo, puesto, zona, `User`, `pass`, `role`)
                    VALUES ('$nombre','$apellido','$telefono','$correo','$puesto','$zona','$user','$pass','$role')";
            $ok = $conn->query($sql);
            if ($ok) { registrarBitacora($conn, 'tecnico', 'crear', $conn->insert_id, 'Técnico: ' . $nombre . ' ' . $apellido . ' (' . $role . ')'); }
            $redireccion($ok, $ok ? 'Técnico registrado correctamente.' : 'Error al guardar: ' . $conn->error);
        } elseif ($accion == 'editar') {
            $sql = "UPDATE tecnico SET nombre='$nombre', apellido='$apellido', telefono='$telefono',
                    correo='$correo', puesto='$puesto', zona='$zona', `User`='$user', `pass`='$pass', `role`='$role'
                    WHERE Id_tecnico = $id";
            $ok = $conn->query($sql);
            if ($ok) { registrarBitacora($conn, 'tecnico', 'editar', $id, 'Técnico #' . $id . ': ' . $nombre . ' ' . $apellido . ' (' . $role . ')'); }
            $redireccion($ok, $ok ? 'Técnico actualizado correctamente.' : 'Error al actualizar: ' . $conn->error);
        }
        break;

    // =========================== EQUIPO ===========================
    case 'equipo':
        if (!$esAdmin && $accion != 'crear' && $accion != 'editar') {
            header('Location: ' . BASE_URL . 'equipo/index.php?err=' . urlencode('Solo el administrador puede eliminar equipos.'));
            exit;
        }
        $ruta = 'equipo/index.php';
        $redireccion = function ($success, $mensaje) use ($ruta) {
            $tipo = $success ? 'msj' : 'err';
            header('Location: ' . BASE_URL . $ruta . '?' . $tipo . '=' . urlencode($mensaje));
            exit;
        };

        $Id_cliente = (int)e('Id_cliente');
        $tipo_equipo = e('tipo_equipo'); $marca = e('marca');
        $modelo = e('modelo'); $serie = e('serie'); $color = e('color');
        $accesorios = e('accesorios'); $estado_fisico = e('estado_fisico');
        $observaciones = e('observaciones');

        if ($accion == 'crear') {
            $sql = "INSERT INTO equipo (Id_cliente, tipo_equipo, marca, modelo, serie, color, accesorios, estado_fisico, observaciones)
                    VALUES ($Id_cliente,'$tipo_equipo','$marca','$modelo','$serie','$color','$accesorios','$estado_fisico','$observaciones')";
            $ok = $conn->query($sql);
            if ($ok) {
                $nuevoId = $conn->insert_id;
                registrarBitacora($conn, 'equipo', 'crear', $nuevoId, 'Equipo: ' . $marca . ' ' . $modelo . ' (cliente #' . $Id_cliente . ')');
                if (($_POST['proceso'] ?? '') === '1') {
                    header('Location: ' . BASE_URL . 'reparacion/create.php?equipo=' . $nuevoId . '&msj=' . urlencode('Equipo registrado correctamente. Continúa con la reparación.'));
                    exit;
                }
            }
            $redireccion($ok, $ok ? 'Equipo registrado correctamente.' : 'Error al guardar: ' . $conn->error);
        } elseif ($accion == 'editar') {
            if (!$esAdmin) {
                $chk = $conn->query("SELECT r.Id_reparacion FROM reparacion r WHERE r.Id_equipo = $id AND r.Id_tecnico = $tecnicoId LIMIT 1");
                if (!$chk || $chk->num_rows == 0) {
                    header('Location: ' . BASE_URL . 'equipo/index.php?err=' . urlencode('Solo el técnico agendado puede editar este equipo.'));
                    exit;
                }
            }
            $sql = "UPDATE equipo SET Id_cliente=$Id_cliente, tipo_equipo='$tipo_equipo', marca='$marca',
                    modelo='$modelo', serie='$serie', color='$color', accesorios='$accesorios',
                    estado_fisico='$estado_fisico', observaciones='$observaciones' WHERE Id_equipo = $id";
            $ok = $conn->query($sql);
            if ($ok) { registrarBitacora($conn, 'equipo', 'editar', $id, 'Equipo #' . $id . ': ' . $marca . ' ' . $modelo); }
            $redireccion($ok, $ok ? 'Equipo actualizado correctamente.' : 'Error al actualizar: ' . $conn->error);
        }
        break;

    // =========================== REPARACION ===========================
    case 'reparacion':
        $ruta = 'reparacion/index.php';
        $redireccion = function ($success, $mensaje) use ($ruta) {
            $tipo = $success ? 'msj' : 'err';
            header('Location: ' . BASE_URL . $ruta . '?' . $tipo . '=' . urlencode($mensaje));
            exit;
        };

        $Id_equipo = (int)e('Id_equipo');
        $Id_tecnico = (int)e('Id_tecnico');
        $estatus = e('estatus');
        $falla = e('falla_reportada'); $diagnostico = e('diagnostico');
        $trabajo = e('trabajo_realizado'); $observaciones = e('observaciones');
        $zonaH = e('zona_falla_h'); $zonaS = e('zona_falla_s');
        $costo_total = (float)e('costo_total');
        $costo_estimado = (float)e('costo_estimado');
        $f1 = e('fecha_recepcion');
        if ($f1 !== '') { $f1 = date('Y-m-d H:i:s', strtotime($f1)); }

        // Un técnico siempre se asigna las reparaciones a sí mismo
        if ($accion == 'crear' && !$esAdmin) {
            $Id_tecnico = $tecnicoId;
        }
        // Un técnico solo puede modificar sus propias reparaciones
        if ($accion == 'editar' && !$esAdmin) {
            if ($Id_tecnico !== $tecnicoId) {
                header('Location: ' . BASE_URL . 'reparacion/index.php?err=' . urlencode('No puedes asignar la reparación a otro técnico.'));
                exit;
            }
            $chk = $conn->query("SELECT Id_reparacion FROM reparacion WHERE Id_reparacion = $id AND Id_tecnico = $tecnicoId");
            if ($chk->num_rows == 0) {
                header('Location: ' . BASE_URL . 'reparacion/index.php?err=' . urlencode('Solo puedes modificar tus propias reparaciones.'));
                exit;
            }
            $Id_tecnico = $tecnicoId;
        }

        if ($accion == 'crear') {
            $feEntrega = ($estatus === 'entregado') ? date('Y-m-d H:i:s') : 'NULL';
            $sql = "INSERT INTO reparacion (Id_equipo, Id_tecnico, fecha_recepcion, zona_falla_h, zona_falla_s,
                    falla_reportada, diagnostico, trabajo_realizado, estatus, costo_total, costo_estimado, observaciones, fecha_entrega)
                    VALUES ($Id_equipo,$Id_tecnico,'$f1','$zonaH','$zonaS','$falla','$diagnostico','$trabajo','$estatus',$costo_total,$costo_estimado,'$observaciones',$feEntrega)";
            $ok = $conn->query($sql);
            if ($ok) {
                $nuevoId = $conn->insert_id;
                registrarBitacora($conn, 'reparacion', 'crear', $nuevoId, 'Reparación del equipo #' . $Id_equipo);
                if (($_POST['proceso'] ?? '') === '1') {
                    header('Location: ' . BASE_URL . 'fotos/create.php?rep=' . $nuevoId . '&msj=' . urlencode('Reparación registrada correctamente. Continúa con las fotos.'));
                    exit;
                }
            }
            $redireccion($ok, $ok ? 'Reparación registrada correctamente.' : 'Error al guardar: ' . $conn->error);
        } elseif ($accion == 'editar') {
            // Fecha de entrega: se registra automáticamente cuando el estatus cambia a "entregado"
            $prev = $conn->query("SELECT estatus, fecha_entrega FROM reparacion WHERE Id_reparacion = $id");
            $prevRow = ($prev && $prev->num_rows) ? $prev->fetch_assoc() : array('estatus' => '', 'fecha_entrega' => null);
            if ($estatus === 'entregado' && ($prevRow['estatus'] !== 'entregado' || $prevRow['fecha_entrega'] === null)) {
                $feEntrega = "'" . date('Y-m-d H:i:s') . "'";
            } elseif ($prevRow['fecha_entrega'] === null) {
                $feEntrega = 'NULL';
            } else {
                $feEntrega = "'" . $prevRow['fecha_entrega'] . "'";
            }
            $sql = "UPDATE reparacion SET Id_equipo=$Id_equipo, Id_tecnico=$Id_tecnico,
                    fecha_recepcion='$f1', zona_falla_h='$zonaH', zona_falla_s='$zonaS',
                    falla_reportada='$falla', diagnostico='$diagnostico', trabajo_realizado='$trabajo',
                    estatus='$estatus', costo_total=$costo_total, costo_estimado=$costo_estimado, observaciones='$observaciones',
                    fecha_entrega=$feEntrega
                    WHERE Id_reparacion = $id";
            $ok = $conn->query($sql);
            if ($ok) { registrarBitacora($conn, 'reparacion', 'editar', $id, 'Reparación #' . $id . ': ' . $estatus); }
            $redireccion($ok, $ok ? 'Reparación actualizada correctamente.' : 'Error al actualizar: ' . $conn->error);
        }
        break;

    // =========================== PAGO ===========================
    case 'pago':
        if (!$esAdmin && !in_array($accion, ['crear', 'editar'])) {
            header('Location: ' . BASE_URL . 'pago/index.php?err=' . urlencode('Solo el administrador puede eliminar pagos.'));
            exit;
        }
        // Los técnicos regresan al listado de reparaciones
        $ruta = $esAdmin ? 'pago/index.php' : 'reparacion/index.php';
        $redireccion = function ($success, $mensaje) use ($ruta) {
            $tipo = $success ? 'msj' : 'err';
            header('Location: ' . BASE_URL . $ruta . '?' . $tipo . '=' . urlencode($mensaje));
            exit;
        };

        $Id_reparacion = (int)e('Id_reparacion');
        $tipo_pago = e('tipo_pago');
        $monto = (float)e('monto');
        $anticipo = (float)e('anticipo');
        $salfo = (float)e('saldo_pendiente');
        $referencia = e('referencia');
        $fp = e('fecha_pago');
        if ($fp !== '') { $fp = date('Y-m-d H:i:s', strtotime($fp)); }

        // Descuento de estudiante: 25% sobre el monto total
        // (aplicado por JS en el formulario; aquí se re-aplica solo si el
        // monto aún llega como total, evitando el doble descuento)
        if (isset($_POST['descuento_estudiante']) && $monto > 0) {
            $costo = 0.0;
            $cr = $conn->query("SELECT costo_total FROM reparacion WHERE Id_reparacion = $Id_reparacion");
            if ($cr && $cr->num_rows) {
                $costo = (float)$cr->fetch_assoc()['costo_total'];
            }
            if (abs($monto - $costo) < 0.005) {
                $monto = round($monto * 0.75, 2);
            }
            $salfo = round($monto - $anticipo, 2);
            if ($salfo < 0) { $salfo = 0; }
        }

        // Un técnico solo puede registrar pagos de sus propias reparaciones
        if ($accion == 'crear' && !$esAdmin) {
            $chk = $conn->query("SELECT Id_reparacion FROM reparacion WHERE Id_reparacion = $Id_reparacion AND Id_tecnico = $tecnicoId");
            if ($chk->num_rows == 0) {
                header('Location: ' . BASE_URL . 'reparacion/index.php?err=' . urlencode('Solo puedes registrar pagos de tus propias reparaciones.'));
                exit;
            }
        }

        if ($accion == 'crear') {
            $sql = "INSERT INTO pago (Id_reparacion, tipo_pago, monto, anticipo, saldo_pendiente, referencia, fecha_pago)
                    VALUES ($Id_reparacion,'$tipo_pago',$monto,$anticipo,$salfo,'$referencia','$fp')";
            $ok = $conn->query($sql);
            if ($ok) { registrarBitacora($conn, 'pago', 'crear', $conn->insert_id, 'Pago de reparación #' . $Id_reparacion); }
            $redireccion($ok, $ok ? 'Pago registrado correctamente.' : 'Error al guardar: ' . $conn->error);
        } elseif ($accion == 'editar') {
            // Un técnico solo puede editar pagos de sus propias reparaciones
            if (!$esAdmin) {
                $chkP = $conn->query("SELECT p.Id_pago FROM pago p INNER JOIN reparacion r ON r.Id_reparacion = p.Id_reparacion WHERE p.Id_pago = $id AND r.Id_tecnico = $tecnicoId");
                if (!$chkP || $chkP->num_rows == 0) {
                    header('Location: ' . BASE_URL . 'reparacion/index.php?err=' . urlencode('Solo el técnico agendado puede modificar este pago.'));
                    exit;
                }
                $chkR = $conn->query("SELECT Id_reparacion FROM reparacion WHERE Id_reparacion = $Id_reparacion AND Id_tecnico = $tecnicoId");
                if (!$chkR || $chkR->num_rows == 0) {
                    header('Location: ' . BASE_URL . 'reparacion/index.php?err=' . urlencode('Solo puedes asociar el pago a tus propias reparaciones.'));
                    exit;
                }
            }
            $sql = "UPDATE pago SET Id_reparacion=$Id_reparacion, tipo_pago='$tipo_pago',
                    monto=$monto, anticipo=$anticipo, saldo_pendiente=$salfo,
                    referencia='$referencia', fecha_pago='$fp' WHERE Id_pago = $id";
            $ok = $conn->query($sql);
            if ($ok) { registrarBitacora($conn, 'pago', 'editar', $id, 'Pago #' . $id . ' de reparación #' . $Id_reparacion); }
            $redireccion($ok, $ok ? 'Pago actualizado correctamente.' : 'Error al actualizar: ' . $conn->error);
        }
        break;

    // =========================== FOTOS ===========================
    case 'fotos':
        $ruta = 'fotos/index.php';
        $redireccion_prev = function ($success, $mensaje, $extra = '') use ($ruta) {
            $tipo = $success ? 'msj' : 'err';
            header('Location: ' . BASE_URL . $ruta . '?' . $tipo . '=' . urlencode($mensaje) . $extra);
            exit;
        };

        $Id_reparacion = (int)e('Id_reparacion');
        $proceso = ($_POST['proceso'] ?? '') === '1';

        // Validar que la reparación exista
        $chk = $conn->query("SELECT Id_reparacion FROM reparacion WHERE Id_reparacion = $Id_reparacion");
        if ($chk->num_rows == 0) {
            $redireccion_prev(false, 'La reparación seleccionada no existe.');
        }

        // Permisos: administrador o técnico agendado de la reparación
        if (!$esAdmin) {
            $chkR = $conn->query("SELECT Id_reparacion FROM reparacion WHERE Id_reparacion = $Id_reparacion AND Id_tecnico = $tecnicoId");
            if (!$chkR || $chkR->num_rows == 0) {
                header('Location: ' . BASE_URL . 'fotos/index.php?err=' . urlencode('Solo el técnico agendado puede administrar estas fotos.'));
                exit;
            }
        }

        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

        // Normaliza el arreglo de archivos (soporta 1 archivo o varios)
        function archivosSubidos($campo) {
            $res = [];
            if (!isset($_FILES[$campo])) return $res;
            $f = $_FILES[$campo];
            if (is_array($f['name'])) {
                $n = count($f['name']);
                for ($i = 0; $i < $n; $i++) {
                    if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                        $res[] = array('name' => $f['name'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i]);
                    }
                }
            } elseif ($f['error'] === UPLOAD_ERR_OK) {
                $res[] = array('name' => $f['name'], 'tmp_name' => $f['tmp_name'], 'error' => $f['error']);
            }
            return $res;
        }

        // Mueve un archivo al directorio de subidas
        function moverFoto($archivo) {
            global $permitidas;
            $nombreOrig = basename($archivo['name']);
            $ext = strtolower(pathinfo($nombreOrig, PATHINFO_EXTENSION));
            if (!in_array($ext, $permitidas)) return array('ok' => false, 'msg' => 'Formato no permitido (' . $ext . ').');
            $nuevoNombre = 'foto_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $destino = UPLOAD_DIR . $nuevoNombre;
            if (!move_uploaded_file($archivo['tmp_name'], $destino)) return array('ok' => false, 'msg' => 'Error al subir la imagen.');
            return array('ok' => true, 'ruta' => 'assets/uploads/' . $nuevoNombre);
        }

        $fecha_foto = date('Y-m-d H:i:s');
        $vistas = [1 => 'vista_1', 2 => 'vista_2', 3 => 'vista_3', 4 => 'vista_4', 5 => 'vista_5'];
        $nombreVista = [1 => 'Vista superior', 2 => 'Vista inferior', 3 => 'Vista lateral derecha', 4 => 'Vista lateral izquierda', 5 => 'Vista frontal'];

        if ($accion == 'crear') {
            $guardadas = 0;
            $errores = array();
            foreach ($vistas as $num => $campo) {
                $archivos = archivosSubidos($campo);
                if (count($archivos) === 0) continue;
                foreach ($archivos as $archivo) {
                    $mv = moverFoto($archivo);
                    if (!$mv['ok']) { $errores[] = $mv['msg']; continue; }
                    $ok = $conn->query("INSERT INTO fotos (Id_reparacion, ruta_imagen, tipo_foto, fecha)
                                        VALUES ($Id_reparacion,'" . $mv['ruta'] . "',$num,'$fecha_foto')");
                    if ($ok) {
                        $guardadas++;
                        registrarBitacora($conn, 'fotos', 'crear', $conn->insert_id, $nombreVista[$num] . ' de reparación #' . $Id_reparacion);
                    } else {
                        $errores[] = $conn->error;
                    }
                }
            }
            if ($guardadas === 0) {
                $msg = 'Debe seleccionar al menos una imagen (hasta 5 vistas del equipo).';
                if (count($errores) > 0) { $msg .= ' ' . implode(' ', $errores); }
                $redireccion_prev(false, $msg);
            }
            $msg = $guardadas . ' de 5 vistas guardadas correctamente.';
            if (count($errores) > 0) { $msg .= ' Error en algunas fotos: ' . implode(' ', $errores); }
            if ($proceso) {
                header('Location: ' . BASE_URL . 'pago/create.php?rep=' . $Id_reparacion . '&msj=' . urlencode($msg));
                exit;
            }
            $redireccion_prev(true, $msg);
        } elseif ($accion == 'editar') {
            $tipo_foto = (int)e('tipo_foto');
            if ($tipo_foto < 1 || $tipo_foto > 5) { $tipo_foto = 1; }
            // Obtener la imagen actual para potencial reemplazo
            $ant = $conn->query("SELECT ruta_imagen FROM fotos WHERE id_foto = $id");
            $rutaAnterior = ($ant && $ant->num_rows) ? $ant->fetch_assoc()['ruta_imagen'] : '';
            $archivoAnterior = dirname(__DIR__) . '/' . $rutaAnterior;

            $nuevaRuta = '';
            $archivos = archivosSubidos('ruta_imagen');
            if (count($archivos) > 0) {
                $mv = moverFoto($archivos[0]);
                if ($mv['ok']) { $nuevaRuta = $mv['ruta']; }
            }

            if ($nuevaRuta !== '') {
                // Se subió imagen nueva: eliminar la anterior y actualizar ruta
                if (!empty($rutaAnterior) && file_exists($archivoAnterior)) {
                    @unlink($archivoAnterior);
                }
                $sql = "UPDATE fotos SET Id_reparacion=$Id_reparacion, ruta_imagen='$nuevaRuta',
                        tipo_foto=$tipo_foto WHERE id_foto = $id";
            } else {
                $sql = "UPDATE fotos SET Id_reparacion=$Id_reparacion, tipo_foto=$tipo_foto WHERE id_foto = $id";
            }
            $ok = $conn->query($sql);
            if ($ok) { registrarBitacora($conn, 'fotos', 'editar', $id, $nombreVista[$tipo_foto] . ' de reparación #' . $Id_reparacion); }
            $redireccion_prev($ok, $ok ? 'Foto actualizada correctamente.' : 'Error al actualizar: ' . $conn->error);
        }
        break;

    // =========================== GARANTIA ===========================
    case 'garantia':
        $ruta = 'garantias/index.php';
        $redireccion = function ($success, $mensaje) use ($ruta) {
            $tipo = $success ? 'msj' : 'err';
            header('Location: ' . BASE_URL . $ruta . '?' . $tipo . '=' . urlencode($mensaje));
            exit;
        };

        $Id_reparacion = (int)e('Id_reparacion');
        $motivo = e('motivo_garantia');
        $zonaH = e('zona_falla_h'); $zonaS = e('zona_falla_s');

        // Los técnicos solo pueden gestionar garantías de reparaciones agendadas a ellos
        $filtroTec = $esAdmin ? '' : ' AND r.Id_tecnico = ' . tecnicoIdActual();
        $cr = $conn->query("SELECT r.fecha_entrega, r.Id_tecnico FROM reparacion r WHERE r.Id_reparacion = $Id_reparacion" . $filtroTec);
        if (!$cr || $cr->num_rows == 0) {
            header('Location: ' . BASE_URL . 'garantias/index.php?err=' . urlencode('No puedes gestionar garantías de reparaciones que no estén agendadas a ti.'));
            exit;
        }
        $repData = $cr->fetch_assoc();
        // Los técnicos quedan siempre registrados como técnico responsable
        $Id_tecnico = $esAdmin ? (int)e('Id_tecnico') : (int)$repData['Id_tecnico'];

        // Fecha de entrega: siempre se toma de la reparación (fecha en que cambió a "entregado")
        $fe = ($repData['fecha_entrega']) ? $repData['fecha_entrega'] : date('Y-m-d H:i:s');
        // Vencimiento de la garantía: 60 días naturales después de la entrega (siempre calculado)
        $ftg = date('Y-m-d H:i:s', strtotime($fe . ' +60 days'));

        if ($accion == 'crear') {
            if (!$esAdmin && $repData['Id_tecnico'] != tecnicoIdActual()) {
                header('Location: ' . BASE_URL . 'garantias/index.php?err=' . urlencode('Solo puedes crear garantías de reparaciones agendadas a ti.'));
                exit;
            }
            $existe = $conn->query("SELECT Id_reparacion FROM garantias WHERE Id_reparacion = $Id_reparacion");
            if ($existe->num_rows > 0) {
                header('Location: ' . BASE_URL . 'garantias/edit.php?id=' . $Id_reparacion . '&err=' . urlencode('Esta reparación ya tiene garantía, se abrirá la edición.'));
                exit;
            }
            $sql = "INSERT INTO garantias (Id_reparacion, Id_tecnico, fecha_entrega, fecha_termino_garantia, motivo_garantia, zona_falla_h, zona_falla_s)
                    VALUES ($Id_reparacion,$Id_tecnico,'$fe','$ftg','$motivo','$zonaH','$zonaS')";
            $ok = $conn->query($sql);
            if ($ok) { registrarBitacora($conn, 'garantias', 'crear', $Id_reparacion, 'Garantía de reparación #' . $Id_reparacion); }
            $redireccion($ok, $ok ? 'Garantía registrada correctamente.' : 'Error al guardar: ' . $conn->error);
        } elseif ($accion == 'editar') {
            if (!$esAdmin) {
                $g = $conn->query("SELECT Id_tecnico FROM garantias WHERE Id_reparacion = $Id_reparacion");
                if (!$g || $g->num_rows == 0 || $g->fetch_assoc()['Id_tecnico'] != tecnicoIdActual()) {
                    header('Location: ' . BASE_URL . 'garantias/index.php?err=' . urlencode('Solo puedes editar garantías de tus propias reparaciones.'));
                    exit;
                }
            }
            $sql = "UPDATE garantias SET Id_tecnico=$Id_tecnico, fecha_entrega='$fe', fecha_termino_garantia='$ftg',
                    motivo_garantia='$motivo', zona_falla_h='$zonaH', zona_falla_s='$zonaS'
                    WHERE Id_reparacion = $Id_reparacion";
            $ok = $conn->query($sql);
            if ($ok) { registrarBitacora($conn, 'garantias', 'editar', $Id_reparacion, 'Garantía de reparación #' . $Id_reparacion); }
            $redireccion($ok, $ok ? 'Garantía actualizada correctamente.' : 'Error al actualizar: ' . $conn->error);
        }
        break;

    default:
        header('Location: ' . BASE_URL . 'index.php');
        exit;
}

$conn->close();
?>
