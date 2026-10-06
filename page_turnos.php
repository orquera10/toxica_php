<?php
$pageTitle = "Turnos";
include 'header.php';
?>

<?php
include('config.php');
require_once('bloqueos_horarios.php');

asegurar_tabla_bloqueos_horarios($con);

$SqlEventos = "SELECT 
                  turnos.*, 
                  clientes.NOMBRE as nombre_usuario,
                  clientes.TELEFONO as telefono_usuario, 
                  canchas.NOMBRE as nombre_cancha,
                  u.NOMBRE as nombre_creador,
                  u.USUARIO as login_creador,
                  ticket.id_CLIENTE as clienteID,
                  ticket.TOTAL_CANCHA,
                  ticket.EXTRA,
                  ticket.SENIA,
                  ticket.TOTAL_DETALLE,
                  ticket.TOTAL
                FROM turnos 
                INNER JOIN canchas ON turnos.id_CANCHA = canchas._id
                LEFT JOIN usuarios u ON turnos.id_USUARIO = u._id
                LEFT JOIN ticket ON turnos._id = ticket.id_TURNO
                LEFT JOIN clientes ON ticket.id_CLIENTE = clientes._id
                WHERE VENTA = 0";

$resulEventos = mysqli_query($con, $SqlEventos);
$resulBloqueos = mysqli_query($con, "SELECT b.*, c.NOMBRE AS nombre_cancha
                                     FROM bloqueos_horarios b
                                     LEFT JOIN canchas c ON b.id_CANCHA = c._id
                                     ORDER BY b.FECHA_INICIO DESC");
?>



<div class="container">
  <div class="row">
    <div class="col msjs position-absolute top-0 start-50 translate-middle-x" style="z-index: 1000;">
      <?php
      include('msjs.php');
      ?>
    </div>
  </div>
</div>

<?php
include('headerUsuario.php');
include('barraNavegacion.php');
?>

<div class="container d-flex flex-wrap justify-content-center mt-4">
  <!-- Botón para abrir el modal de venta-->
  <button type="button" class="btn btn-primary m-1" data-toggle="modal" data-target="#modalVenta" style="min-width:23%"
    onclick="abrirModalVenta()">
    Agregar Venta
  </button>
  <!-- Botón para abrir el modal de gasto-->
  <button type="button" class="btn btn-primary m-1" style="min-width:23%" onclick="abrirModalGasto()">
    Agregar Gasto
  </button>
  <!-- Botón para abrir el modal de gasto-->
  <button type="button" class="btn btn-danger m-1 btnBaja" data-toggle="modal" data-target="#modalBajaDeStock"
    style="min-width:23%" onclick="abrirModalBajaStock()">
    Devolucion
  </button>
  <button type="button" class="btn btn-warning m-1" style="min-width:23%" onclick="abrirModalBloquearHorario()">
    Bloquear Horario
  </button>

</div>

<?php
include('modalNuevaVenta.php');
include('modalNuevoGasto.php');
include('modalBajaStock.php');
include('modalBloquearHorario.php');
?>
<div class="d-flex justify-content-center">
  <div id="calendar" class="m-4 calendario"></div>
</div>



<?php
include('modalNuevoEvento.php');
include('modalUpdateEvento.php');
?>

<?php
include 'common_scripts.php';
?>

<script type="text/javascript">
  $(document).ready(function () {
    $("#calendar").fullCalendar({
      header: {
        left: "prev,next today",
        center: "title",
        right: "month,agendaWeek,agendaDay"
      },

      minTime: '00:00:00', // Configuración para empezar a mostrar horarios desde el mediodía
      maxTime: '24:00:00', // Configuración para terminar de mostrar horarios a medianoche

      locale: 'es',

      selectable: true, // Habilitar la selección en dispositivos táctiles

      defaultView: (window.innerWidth < 768) ? 'agendaDay' : 'month',
      navLinks: true,
      // editable: true,
      eventLimit: true,
      selectable: true,
      selectHelper: false,

      //Nuevo Evento
      select: function (start, end) {

        // Construir fechas completas combinando las fechas seleccionadas del calendario con las horas seleccionadas
        var fechaInicio = start.format('DD-MM-YYYY');
        // Asignar valores a los campos de fecha y hora
        $("input[name=hidden_hora_inicio]").val(fechaInicio);

        // Formato que entiende el input datetime-local: YYYY-MM-DDTHH:MM
        var fechaInput = start.format('YYYY-MM-DD'); // Ej: 2025-04-12

        // Armar valores iniciales con hora fija (podés ajustarlo)
        var horaInicio = '13:00';
        var horaFin = '15:00';

        // Setear los valores en los inputs datetime-local
        $('#datetime_inicio').val(fechaInput + 'T' + horaInicio);
        $('#datetime_fin').val(fechaInput + 'T' + horaFin);


        //carga el desplegable de las canchas
        $.ajax({
          url: 'cargarDesplegableCancha.php',
          type: 'POST',
          data: { fecha: fechaInicio },
          success: function (response) {
            $('#canchas').html(response);
            actualizarHoraFinCumpleanios();
          },
          error: function (xhr, status, error) {
            console.error(xhr.responseText);
          }
        });

        $("#exampleModal").modal("show");
        // myModal.addEventListener('shown.bs.modal', () => {
        //   myInput.focus()
        // })
      },

      events: [
        <?php
        while ($dataEvento = mysqli_fetch_array($resulEventos)) {
          $horaInicio = $dataEvento['HORA_INICIO'];
          $horaFin = $dataEvento['HORA_FIN'];
          $inicioEvento = DateTime::createFromFormat('d-m-Y H:i', $dataEvento['FECHA'] . ' ' . $horaInicio);
          $finEvento = DateTime::createFromFormat('d-m-Y H:i', $dataEvento['FECHA'] . ' ' . ($horaFin === '24:00' ? '00:00' : $horaFin));

          // Si cruza medianoche, sumar un día solo cuando el inicio no es madrugada.
          if (
            $finEvento &&
            (in_array($horaFin, ['00:00', '00:30', '01:00', '01:30', '02:00', '24:00'], true)) &&
            !in_array($horaInicio, ['00:00', '00:30', '01:00', '01:30'], true)
          ) {
            $finEvento->modify('+1 day');
          }

          $start = $inicioEvento ? $inicioEvento->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($dataEvento['FECHA'] . ' ' . $horaInicio));
          $end = $finEvento ? $finEvento->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($dataEvento['FECHA'] . ' ' . $horaFin));
          ?>

          {
            _id: '<?php echo $dataEvento['_id']; ?>',
            title: '<?php echo $dataEvento['nombre_usuario']; ?>',
            telefono: '<?php echo $dataEvento['telefono_usuario']; ?>',
            start: '<?php echo $start; ?>',
            end: '<?php echo $end; ?>',
            fecha: '<?php echo $dataEvento['FECHA']; ?>',
            color: '<?php echo $dataEvento['COLOR']; ?>',
            cancha: '<?php echo $dataEvento['nombre_cancha']; ?>',
            creado_por: '<?php echo ($dataEvento['nombre_creador'] && trim($dataEvento['nombre_creador']) !== "") ? $dataEvento['nombre_creador'] : $dataEvento['login_creador']; ?>',
            idCliente: '<?php echo $dataEvento['clienteID']; ?>',
            finalizado: '<?php echo $dataEvento['FINALIZADO']; ?>',
            total_cancha: '<?php echo $dataEvento['TOTAL_CANCHA']; ?>',
            extra: '<?php echo $dataEvento['EXTRA']; ?>',
            senia: '<?php echo $dataEvento['SENIA']; ?>',
            total_detalle: '<?php echo $dataEvento['TOTAL_DETALLE']; ?>',
            total: '<?php echo $dataEvento['TOTAL']; ?>'
          },
        <?php } ?>
        <?php
        while ($bloqueo = mysqli_fetch_assoc($resulBloqueos)) {
          $inicioBloqueo = date('Y-m-d H:i:s', strtotime($bloqueo['FECHA_INICIO']));
          $finBloqueo = date('Y-m-d H:i:s', strtotime($bloqueo['FECHA_FIN']));
          $canchaBloqueo = $bloqueo['nombre_cancha'] ?: 'Todas las canchas';
          $motivoBloqueo = trim((string) $bloqueo['MOTIVO']);
          $tituloBloqueo = 'Bloqueado - ' . $canchaBloqueo;
          if ($motivoBloqueo !== '') {
            $tituloBloqueo .= ' (' . $motivoBloqueo . ')';
          }
          ?>
          {
            _id: 'bloqueo_<?php echo (int) $bloqueo['_id']; ?>',
            bloqueoId: '<?php echo (int) $bloqueo['_id']; ?>',
            esBloqueo: true,
            title: '<?php echo htmlspecialchars($tituloBloqueo, ENT_QUOTES); ?>',
            start: '<?php echo $inicioBloqueo; ?>',
            end: '<?php echo $finBloqueo; ?>',
            fecha: '<?php echo date('d-m-Y', strtotime($bloqueo['FECHA_INICIO'])); ?>',
            color: '#6c757d',
            textColor: '#ffffff',
            cancha: '<?php echo htmlspecialchars($canchaBloqueo, ENT_QUOTES); ?>'
          },
        <?php } ?>
      ],




      //Eliminar Evento
      eventRender: function (event, element) {
        if (event.esBloqueo) {
          element
            .find(".fc-content")
            .prepend("<span class='closeon material-icons'>&#xe5cd;</span>");

          element.find(".closeon").on("click", function (e) {
            e.stopPropagation();

            Swal.fire({
              title: 'Eliminar bloqueo?',
              text: "Esta franja volvera a estar disponible para reservas.",
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Si, eliminar',
              cancelButtonText: 'Cancelar'
            }).then((result) => {
              if (result.isConfirmed) {
                $.ajax({
                  type: "GET",
                  url: 'eliminar_bloqueo_horario.php',
                  data: { id: event.bloqueoId },
                  success: function (response) {
                    if (response.success) {
                      $("#calendar").fullCalendar("removeEvents", event._id);
                      Swal.fire('Eliminado', response.message, 'success');
                    } else {
                      Swal.fire('Error', response.message, 'error');
                    }
                  },
                  error: function () {
                    Swal.fire('Error', 'No se pudo eliminar el bloqueo.', 'error');
                  }
                });
              }
            });
          });

          return;
        }

        // Convertir la propiedad 'finalizado' a un número entero
        var finalizado = parseInt(event.finalizado);
        // Verificar si el evento no está finalizado
        if (finalizado !== 1) {
          element
            .find(".fc-content")
            .prepend("<span id='btnCerrar' class='closeon material-icons'>&#xe5cd;</span>");

          //Eliminar evento
          element.find(".closeon").on("click", function () {
            // Desactivar eventos de clic en otros elementos
            $('body').addClass('modal-open');

            Swal.fire({
              title: '¿Deseas borrar este evento?',
              text: "Esta acción no se puede deshacer",
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Sí, borrar',
              cancelButtonText: 'Cancelar',
              allowOutsideClick: false // Evitar cerrar el modal haciendo clic fuera de él
            }).then((result) => {
              if (result.isConfirmed) {
                $("#calendar").fullCalendar("removeEvents", event._id);

                $.ajax({
                  type: "POST",
                  url: 'deleteEvento.php',
                  data: { id: event._id },
                  dataType: 'json',
                  success: function (response) {
                    // Habilitar eventos de clic en otros elementos
                    $('body').removeClass('modal-open');

                    if (!response || !response.success) {
                      window.location.href = 'page_turnos.php?error=' + encodeURIComponent(response && response.message ? response.message : 'Hubo un problema al eliminar el evento.');
                      return;
                    }

                    Swal.fire({
                      icon: 'success',
                      title: 'Evento eliminado',
                      text: 'El evento ha sido eliminado correctamente.',
                      showConfirmButton: false,
                      timer: 1500
                    }).then(() => {
                      // Redireccionar a page_turnos.php
                      window.location.href = 'page_turnos.php';
                    });
                  },
                  error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    // Habilitar eventos de clic en otros elementos
                    $('body').removeClass('modal-open');

                    Swal.fire({
                      icon: 'error',
                      title: '¡Error!',
                      text: 'Hubo un problema al eliminar el evento.'
                    });
                  }
                });
              } else {
                // Habilitar eventos de clic en otros elementos
                $('body').removeClass('modal-open');
              }
            });
          });
        }
      },


      // //Moviendo Evento Drag - Drop
      // eventDrop: function (event, delta) {
      //   var idEvento = event._id;
      //   var start = (event.start.format('DD-MM-YYYY'));
      //   var end = (event.end.format("DD-MM-YYYY"));

      //   $.ajax({
      //     url: 'drag_drop_evento.php',
      //     data: 'start=' + start + '&end=' + end + '&idEvento=' + idEvento,
      //     type: "POST",
      //     success: function (response) {
      //       // $("#respuesta").html(response);
      //     }
      //   });
      // },

      //Abrir ventana modal para visualizar datos del turno y productos que contiene 
      eventClick: function (event) {
        if (event.esBloqueo) {
          return;
        }

        var idEvento = event._id;
        var idCliente = event.idCliente
        $('input[name=idEvento').val(idEvento);
        cargarSenias(idEvento);
        cargarExtras(idEvento);
        $('input[name=idCliente').val(idCliente);
        $('label[name=evento').text(event.title);
        // $('label[name="fecha_turno"]').text(moment(event.fecha).format('DD-MM-YYYY'));
        $('label[name="fecha_turno"]').text(event.fecha);
        $('label[name=fecha_inicio').text(event.start.format('HH:mm'));
        $('label[name=fecha_fin').text(event.end.format("HH:mm"));
        $('label[name=cancha').text(event.cancha);
        $('label[name=creado_por').text(event.creado_por || '');

        $('span[name=dinero_extra').text(event.extra);
        $('span[name=dinero_senia').text(event.senia);

        $('#wpContenedor').html(`<a href="https://api.whatsapp.com/send?phone=${event.telefono}" target="_blank"><i class="fab fa-whatsapp"></i> ${event.telefono}</a>`);

        $('.colorModalUpdate').css('background-color', event.color);

        // $('span[name=total_cancha').text(event.total_cancha);
        // $('span[name=total_detalle').text(event.total_detalle);
        // $('span[name=total').text(event.total);

        var finalizado = parseInt(event.finalizado);
        // Verificar si el evento no está finalizado

        // Desactivar o activar el botón agregar_extra según el estado del evento
        if (finalizado === 1) {
          $('#agregar_extra').prop('disabled', true);
          $('#extra_money').prop('disabled', true);
          $('#agregar_senia').prop('disabled', true);
          $('#senia_money').prop('disabled', true);
        } else {
          $('#agregar_extra').prop('disabled', false);
          $('#extra_money').prop('disabled', false);
          $('#agregar_senia').prop('disabled', false);
          $('#senia_money').prop('disabled', false);

        }
        if (finalizado !== 1) {
          $('#agregarProductoModalUpdate').show();
          $('#finalizarTurno').show();

          // Enviar una solicitud AJAX para cargar los productos correspondientes al idEvento en la vista de detalle
          $.ajax({
            url: 'cargarProductos.php',
            type: 'POST',
            data: { idEvento: idEvento },
            success: function (response) {
              actualizarTotales();
              // Insertar los productos en la tabla dentro del modal
              $('#tablaProductosDetalle').html(response);
              // Abrir el modal
              $("#modalUpdateEvento").modal("show");
            },
            error: function (xhr, status, error) {
              console.error(xhr.responseText);
            }
          });

        } else {
          $('#agregarProductoModalUpdate').hide();
          $('#finalizarTurno').hide();


          // Enviar una solicitud AJAX para cargar los productos correspondientes al idEvento en la vista de detalle
          $.ajax({
            url: 'cargarProductos.php',
            type: 'POST',
            data: { idEvento: idEvento },
            success: function (response) {
              actualizarTotales();
              // Insertar los productos en la tabla dentro del modal
              $('#tablaProductosDetalle').html(response);
              $('.btnEliminarProducto').hide();
              // Abrir el modal
              $("#modalUpdateEvento").modal("show");
            },
            error: function (xhr, status, error) {
              console.error(xhr.responseText);
            }
          });

        }

      },
    });

    //Oculta mensajes de Notificacion
    setTimeout(function () {
      $(".alert").slideUp(300);
    }, 3000);
  });
</script>

<!-- Script para manejar la selección de cliente ------------------------------------------->
<!-- <script>
  // Función para manejar la selección de cliente
  $(document).on('click', '.seleccionar-cliente', function () {
    var cliente_id = $(this).data('id');
    var cliente_nombre = $(this).text();
    // Actualizar el valor del input del cliente
    $('#evento').val(cliente_nombre);
    // Opcional: Puedes guardar el ID del cliente en un campo oculto si lo necesitas
    $('#cliente_id').val(cliente_id);
    // Cerrar solo el modal de selección de clientes
    $('#clientesModal').modal('hide');
  });
</script> -->
<script>
  function cargarSenias(idEvento) {
    fetch(`get_senias.php?idEvento=${idEvento}`)
      .then(response => response.json())
      .then(data => {
        const lista = document.getElementById('listaSenias');

        if (data.length === 0) {
          lista.innerHTML = `
          <label><strong>Señas registradas:</strong></label>
          <p>No hay señas registradas para este evento.</p>
        `;
          return;
        }

        let html = `
        <label>Señas registradas:</label>
        <ul class="list-group mt-1">
      `;

        data.forEach(senia => {
          const fecha = senia.FECHA;
          const monto = senia.MONTO;
          const deja = senia.DEJA;

          html += `
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong>${deja}</strong><br>
              <small>${fecha}</small>
            </div>
            <span class="medalla badge bg-primary rounded-pill">${monto} $</span>
          </li>
        `;
        });

        html += `</ul>`;
        lista.innerHTML = html;
      })
      .catch(err => {
        console.error("Error al cargar señas:", err);
        document.getElementById('listaSenias').innerHTML = `<p class="text-danger">Error al cargar las señas</p>`;
      });
  }

  function cargarExtras(idEvento) {
    fetch(`obtener_extras.php?idEvento=${idEvento}`)
      .then(response => response.json())
      .then(data => {
        const lista = document.getElementById('listaExtras');


        if (data.length === 0) {
          lista.innerHTML = `
          <label><strong>Extras registrados:</strong></label>
          <p>No hay extras registrados para este evento.</p>
        `;
          return;
        }

        let html = `
        <label>Extras registrados:</label>
        <ul class="list-group mt-1">
      `;

        data.forEach(extra => {
          const fecha = extra.fecha;
          const monto = extra.monto;
          const detalle = extra.detalle;
          html += `
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong>${detalle}</strong><br>
              <small>${fecha}</small>
            </div>
            <span class="medalla badge bg-primary rounded-pill">${monto} $</span>
          </li>
        `;
        });

        html += `</ul>`;
        lista.innerHTML = html;
      })
      .catch(err => {
        console.error("Error al cargar extras:", err);
        document.getElementById('listaExtras').innerHTML = `<p class="text-danger">Error al cargar los extras</p>`;
      });
  }



</script>
<script>
  // Función para manejar la selección de cliente
  $(document).on('click', '.seleccionar-cliente', function () {
    var cliente_id = $(this).data('id');
    var cliente_nombre = $(this).text();

    // Verificar si el modal de venta está activo
    if ($('#exampleModal').hasClass('show')) {
      // Si el modal de venta está activo, actualizar el valor del input del cliente en ese modal
      $('#evento').val(cliente_nombre);
      $('#cliente_id').val(cliente_id);
      $('#clientesModal').modal('hide'); // Cerrar el modal de venta      
    }
    // Verificar si el modal de carga de evento está activo
    else if ($('#modalVenta').hasClass('show')) {
      // Si el modal de carga de evento está activo, actualizar el valor del input del cliente en ese modal
      $('#eventoVenta').val(cliente_nombre);
      $('#cliente_id_evento_venta').val(cliente_id);
      $('#clientesModal').modal('hide'); // Cerrar el modal de venta  
    }
    else {
      // No se está mostrando ningún modal, manejar el caso según sea necesario
      console.log('Ningún modal activo.');
    }
  });
</script>

<!-- Script para eliminar un producto del evento --------------------------------------------->
<script>
  function actualizarTablaProductos() {
    $("#tablaProductosDetalle").load("cargarProductos.php", { idEvento: $('#idEvento').val() }, function (response, status, xhr) {
      if (status == "error") {
        console.error(xhr.responseText);
      }
    });
  }
  function cargarTotal(tipoTotal) {
    $('span[name=' + tipoTotal + ']').load("cargarTotal.php", { idEvento: $('#idEvento').val(), tipoTotal: tipoTotal }, function (response, status, xhr) {
      if (status == "error") {
        console.error(xhr.responseText);
      }
    });
  }

  function actualizarTotales() {
    cargarTotal('total_cancha');
    cargarTotal('total_detalle');
    cargarTotal('total');
  }

  // Capturar el clic del botón "Eliminar"
  $(document).on("click", ".btnEliminarProducto", function () {
    // Obtener el idProducto desde el atributo data
    var idProducto = $(this).data("idproducto");
    var idEvento = $('#idEvento').val();

    // Realizar una solicitud AJAX para eliminar el producto
    $.ajax({
      url: 'deleteProductoEvento.php',
      method: 'POST',
      data: { idProducto: idProducto, idEvento: idEvento },
      success: function (response) {
        // Actualizar la tabla de productos si se eliminó correctamente
        actualizarTablaProductos();
        actualizarTotales();
      },
      error: function (xhr, status, error) {
        // Manejar errores si los hay
        console.error(xhr.responseText);
      }
    });
  });

</script>

<!-- script para al hacer click en una tarjeta y obtener datos del producto seleccionado ----------------->
<script>
  $(document).ready(function () {
    // Al hacer clic en una tarjeta
    $('.tarjeta').click(function () {
      // Quitar la clase 'selected' de todas las tarjetas
      $('.tarjeta').removeClass('selected');

      // Agregar la clase 'selected' a la tarjeta clicada
      $(this).addClass('selected');

      // Obtener los datos del producto seleccionado
      var nombre = $(this).data('nombre');
      var precio = $(this).data('precio');
      var idProducto = $(this).data('id');

      // Mostrar los detalles del producto en los cuadros de texto
      $('#nombreProducto').val(nombre);
      $('#precioProducto').val(precio);
      $('#idProducto').val(idProducto);
    });
  });
</script>

<!-- Script para manejar el evento de clic del botón "Agregar" en el modal de productos -->
<script>
  $(document).ready(function () {
    // Evento de clic del botón "Agregar" en el modal de productos
    $('#agregarProducto').click(function () {
      // Obtener los detalles del producto seleccionado
      var idEvento = $('#idEvento').val();
      var idProducto = $('#idProducto').val();
      var cantidad = $('#cantidadProducto').val();

      // Validar la cantidad
      if (cantidad <= 0) {
        alert('La cantidad debe ser mayor que cero.');
        return;
      }

      // Realizar la petición AJAX para insertar el detalle del ticket
      $.ajax({
        url: 'agregarProductoTicket.php', // URL del script PHP que insertará el detalle del ticket
        method: 'POST',
        data: {
          idEvento: idEvento,
          idProducto: idProducto,
          cantidad: cantidad
        },
        success: function (response) {
          var jsonResponse = JSON.parse(response);
          // Manejar la respuesta del servidor
          if (jsonResponse.success) {
            // Actualizar la tabla de productos en el modal
            actualizarTablaProductos();
            actualizarTotales();

            // Cerrar el modal de productos
            $('#productosModal').modal('hide');
          } else {
            // Error al agregar el producto al ticket
            alert('Error al agregar el producto al ticket: ' + response.message);
          }
        },
        error: function () {
          alert('Error de conexión con el servidor.');
        }
      });
    });
  });
</script>

<!-- script para reestablecer el modal de agregar productos ---------------------------->
<script>
  $(document).ready(function () {
    // Evento que se activa cuando se muestra el modal de productos
    $('#productosModal').on('show.bs.modal', function () {
      // Restablecer la selección de tarjetas
      $('.tarjeta').removeClass('selected');

      // Restablecer los valores de los campos
      $('#nombreProducto').val('');
      $('#precioProducto').val('');
      $('#cantidadProducto').val('1');
    });
  });
</script>

<!-- Script para actualizar productos en el modal de pago --------------------------------------------->
<script>
  $('#modalPago').on('show.bs.modal', function () {
    idEvento = $('input[name=idEvento').val();
    $.ajax({
      url: 'cargarProductos.php',
      type: 'POST',
      data: { idEvento: idEvento },
      success: function (response) {
        actualizarTotales();
        // Insertar los productos en la tabla dentro del modal
        $('#tablaProductosPago').html(response);
        $('.btnEliminarProducto').hide();
      },
      error: function (xhr, status, error) {
        console.error(xhr.responseText);
      }
    });
    actualizarTotales();
  });

</script>

<!-- Guardar detalle del pago por transferencia y efectivo -->
<script>
  $(document).ready(function () {
    $('#btnFinalizar').click(function () {
      // Obtener los valores de los campos
      var idEvento = $('#idEvento').val();
      var pagoEfectivo = $('#pagoEfectivo').val();
      var pagoTransf = $('#pagoTransf').val();
      var fechaPago = $('#fechaPago').val(); // <-- capturamos la fecha y hora

      // Recopilar los datos de la tabla tablaPagos
      var nombrePagos = [];
      var montoTransferencias = [];
      var montoEfectivos = [];

      $('#tablaPagos tr').each(function () {
        var nombrePago = $(this).find('td:eq(0)').text();
        var montoTransferencia = $(this).find('td:eq(1)').text();
        var montoEfectivo = $(this).find('td:eq(2)').text();
        nombrePagos.push(nombrePago);
        montoTransferencias.push(montoTransferencia);
        montoEfectivos.push(montoEfectivo);
      });

      // Realizar la solicitud AJAX
      $.ajax({
        url: 'finalizarTurno.php',
        type: 'POST',
        data: {
          idTurno: idEvento,
          pagoEfectivo: pagoEfectivo,
          pagoTransferencia: pagoTransf,
          fechaPago: fechaPago, // <-- agregamos la fecha en el cuerpo del POST
          nombrePagos: nombrePagos,
          montoTransferencias: montoTransferencias,
          montoEfectivos: montoEfectivos
        },
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            Swal.fire({
              icon: 'success',
              title: '¡Turno finalizado!',
              text: 'El turno se finalizó correctamente.',
              confirmButtonText: 'Aceptar'
            }).then((result) => {
              window.location.href = 'page_turnos.php';
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Ocurrió un error al finalizar el turno: ' + response.message,
              confirmButtonText: 'Aceptar'
            });
          }
        },
        error: function (xhr, status, error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al procesar la solicitud: ' + error,
            confirmButtonText: 'Aceptar'
          });
        }
      });
    });
  });
</script>


<script>
  // Restablecer la visibilidad de todas las tarjetas al abrir el modal para cargar un producto a un turno
  $('#productosModal').on('shown.bs.modal', function () {
    $('.tarjeta').show();
  });
</script>

<script>
  $(document).ready(function () {
    $('#formVenta').submit(function (event) {
      // Evitar que el formulario se envíe normalmente
      event.preventDefault();

      // Obtener los datos del formulario
      var formData = $(this).serialize();

      // Realizar la solicitud AJAX
      $.ajax({
        type: 'POST',
        url: 'nuevaVenta.php',
        data: formData,
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            // Aquí puedes manejar la respuesta del servidor, por ejemplo, mostrar un mensaje de éxito
            Swal.fire({
              title: '¡Venta realizada!',
              text: 'La venta se ha registrado correctamente.',
              icon: 'success',
              confirmButtonText: 'Aceptar'
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = 'page_turnos.php';
              }
            });
          } else {
            // Mostrar un mensaje de error utilizando SweetAlert2
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Error en la venta: ' + response.message,
              confirmButtonText: 'Aceptar'
            });
          }

        },
        error: function (xhr, status, error) {
          // Mostrar mensaje de error con SweetAlert
          Swal.fire({
            title: 'Error',
            text: 'Error al enviar la venta: ' + error,
            icon: 'error',
            confirmButtonText: 'OK'
          });
        }
      });
    });
  });

</script>

<script>
  $(document).ready(function () {
    // Función para buscar nombres coincidentes
    function buscarNombresCoincidentes() {
      var nombre = $('#nombrePago').val(); // Obtener el valor del campo de entrada de nombre

      // Realizar una solicitud AJAX para buscar nombres coincidentes
      $.ajax({
        url: 'buscar_nombres.php',
        method: 'POST',
        data: { nombre: nombre }, // Enviar el nombre ingresado al servidor
        success: function (response) {
          // Mostrar los nombres coincidentes en el div correspondiente
          $('#nombresCoincidentes').html(response);

          // Agregar evento de clic a los nombres mostrados
          $('#nombresCoincidentes .nombre-coincidente').click(function () {
            var nombreSeleccionado = $(this).text(); // Obtener el nombre seleccionado
            $('#nombrePago').val(nombreSeleccionado); // Insertar el nombre seleccionado en el campo de entrada de nombre
            $('#nombresCoincidentes').html(''); // Limpiar la lista de nombres coincidentes
          });
        }
      });
    }

    // Detectar cambios en el campo de entrada de nombre
    $('#nombrePago').on('input', function () {
      buscarNombresCoincidentes(); // Llamar a la función para buscar nombres coincidentes
    });
  });
</script>

<script>

  document.getElementById("guardarExtra").addEventListener("click", function () {
    var idEvento = $('#idEvento').val();
    var montoExtra = parseInt(document.getElementById("montoExtra").value);
    var fechaExtra = document.getElementById("fechaExtra").value;
    var detalleExtra = document.getElementById("detalleExtra").value;

    if (!montoExtra || !fechaExtra || !detalleExtra) {
      Swal.fire({
        icon: 'warning',
        title: 'Campos incompletos',
        text: 'Por favor, completá todos los campos.'
      });
      return;
    }

    var dineroExtra = parseInt(document.getElementById("dinero_extra").innerText) || 0;
    var nuevoDineroExtra = dineroExtra + montoExtra;

    $.ajax({
      type: "POST",
      url: "actualizar_extra.php",
      data: {
        dinero_extra: montoExtra,
        fecha_extra: fechaExtra,
        detalle_extra: detalleExtra,
        idEvento: idEvento
      },
      success: function (response) {
        actualizarTotales();
        $('span[name=dinero_extra]').text(nuevoDineroExtra);
        $('#modalExtra').modal('hide');

        // Limpiar campos
        $('#montoExtra').val('');
        $('#fechaExtra').val('');
        $('#detalleExtra').val('');

        // Mostrar mensaje con SweetAlert2 y luego recargar
        Swal.fire({
          icon: 'success',
          title: 'Extra guardado',
          text: 'El monto extra fue registrado correctamente.'
        }).then(() => {
          location.reload();
        });

        console.log("Extra guardado correctamente.");
        
      },
      error: function (xhr, status, error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Hubo un problema al guardar el extra.'
        });
        console.error("Error al guardar el extra:", error);
      }
    });
  });


  document.getElementById("agregar_senia").addEventListener("click", function () {
    var idEvento = $('#idEvento').val();
    var idCliente = $('#idCliente').val();


    // Obtener el valor ingresado en extra_money
    var seniaMoney = parseInt(document.getElementById("senia_money").value);
    // Obtener el valor actual de dinero_extra
    var dineroSenia = parseInt(document.getElementById("dinero_senia").innerText);
    // Sumar el valor ingresado al valor actual
    var nuevoDineroSenia = dineroSenia + seniaMoney;

    // Ejecutar la solicitud AJAX para actualizar la base de datos
    $.ajax({
      type: "POST",
      url: "actualizar_senia.php",
      data: { dinero_senia: seniaMoney, idEvento: idEvento, idCliente: idCliente },
      success: function (response) {
        actualizarTotales();
        $('span[name=dinero_senia]').text(nuevoDineroSenia);
        $('#senia_money').val(0);
        console.log("Base de datos actualizada correctamente.");
      },
      error: function (xhr, status, error) {
        console.error("Error al actualizar la base de datos:", error);
      }
    });
  });

</script>

<script>
  // Función para calcular y mostrar los totales en los campos de pago
  function calcularTotales() {
    // Calcular la suma de los montos de transferencia
    var sumaMontoTransferencia = 0;
    $("#tablaPagos tr").each(function () {
      var montoTransferencia = parseFloat($(this).find('td:nth-child(2)').text());
      sumaMontoTransferencia += montoTransferencia;
    });

    // Calcular la suma de los montos en efectivo
    var sumaMontoEfectivo = 0;
    $("#tablaPagos tr").each(function () {
      var montoEfectivo = parseFloat($(this).find('td:nth-child(3)').text());
      sumaMontoEfectivo += montoEfectivo;
    });
    // Mostrar las sumas en los campos de pago
    $("#pagoTransf").val(sumaMontoTransferencia);
    $("#pagoEfectivo").val(sumaMontoEfectivo);
  }

  // Función para calcular la cantidad restante para alcanzar el total
  function calcularFaltaParaTotal(totalGeneral, pagoTransf, pagoEfectivo) {
    var totalPagos = pagoTransf + pagoEfectivo;
    var faltaParaTotal = totalGeneral - totalPagos;
    return faltaParaTotal;
  }

  // Función para calcular la cantidad restante y actualizarla en el modal
  function calcularCantidadRestante() {
    var totalGeneral = parseFloat($("#totalFinalizarPago").text());
    var pagoTransf = parseFloat($("#pagoTransf").val());
    var pagoEfectivo = parseFloat($("#pagoEfectivo").val());
    var faltaParaTotal = calcularFaltaParaTotal(totalGeneral, pagoTransf, pagoEfectivo);
    $("#faltaParaTotal").text(faltaParaTotal);
  }

  $(document).ready(function () {
    // Función para abrir el modal de agregar nuevo pago
    $("#btnAgregarPago").click(function () {
      // $("#modalPago").modal("hide");
      $("#modalAgregarPago").modal("show");
    });

    $("#btnGuardarPago").click(function () {
      // Obtener los valores ingresados en el formulario
      var nombrePago = $("#nombrePago").val();
      var montoTransferencia = $("#montoTransferencia").val().trim() !== "" ? parseFloat($("#montoTransferencia").val()) : 0;
      var montoEfectivo = $("#montoEfectivo").val().trim() !== "" ? parseFloat($("#montoEfectivo").val()) : 0;

      // Validar que se ingrese el nombre del pago y que al menos uno de los montos sea mayor que 0
      if (nombrePago !== "" && (montoTransferencia > 0 || montoEfectivo > 0)) {
        // Construir la fila de la tabla con los datos ingresados
        var newRow = "<tr class='align-middle'><td>" + nombrePago + "</td><td>" + montoTransferencia + "</td><td>" + montoEfectivo + "</td><td><button class='btn btnEliminarPago'><i class='fas fa-trash-alt btnDeletePago'></i></button></td></tr>";

        // Agregar la nueva fila a la tabla
        $("#tablaPagos").append(newRow);

        // Recalcular los totales y mostrarlos en los campos de pago
        calcularTotales();
        calcularCantidadRestante();

        // Limpiar los campos del formulario después de agregar el pago
        $("#nombrePago").val("");
        $("#montoTransferencia").val("0");
        $("#montoEfectivo").val("0");

        // Cerrar el modal después de guardar el pago
        $("#modalAgregarPago").modal("hide");
        $("#modalPago").modal("show");
      } else {
        // Mostrar SweetAlert2 en lugar de alert
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'Por favor, ingrese el nombre del pago y asegúrese de que al menos uno de los montos sea mayor que 0.',
        });
      }
    });


    $("#btnCerrarModalPago").click(function () {
      // Limpiar los campos del formulario después de agregar el pago
      $("#nombrePago").val("");
      $("#montoTransferencia").val("0");
      $("#montoEfectivo").val("0");
      $("#modalAgregarPago").modal("hide");
      $("#modalPago").modal("show");
    });

    // Función para eliminar un pago de la tabla
    $(document).on("click", ".btnEliminarPago", function () {
      $(this).closest("tr").remove();
      calcularTotales();
      calcularCantidadRestante();
    });

    //Calcular la cantidad restante cuando se muestre el modal
    $('#modalPago').on('shown.bs.modal', function (e) {
      calcularCantidadRestante();
    });

    // Recalcular la cantidad restante cuando cambien los valores de pagoTransf y pagoEfectivo
    $("#pagoTransf, #pagoEfectivo").change(function () {
      calcularCantidadRestante();
    });
  });

</script>

</body>

</html>
