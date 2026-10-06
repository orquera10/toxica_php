<!-- Modal -->
<div class="modal fade" id="modalPago" tabindex="-1" aria-labelledby="modalPagoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPagoLabel">Detalles del Turno y Productos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Parte 1: Datos del Turno -->
        <div class="mb-3">
          <h5>Datos del Turno:</h5>
          <!-- Aquí puedes mostrar los datos del turno -->
          <p>Cliente: <label name="evento"></label></p>
          <p>Hora Entrada: <label name="fecha_inicio"></label></p>
          <p>Hora Salida: <label name="fecha_fin"></label></p>
          <p>Cancha: <label name="cancha"></label></p>
        </div>

        <!-- Parte 2: Lista de Productos -->
        <div class="mb-3">
          <h5>Productos:</h5>
          <div class="styleTabla">
            <table class="table">
              <thead>
                <tr>
                  <th></th>
                  <th>Producto</th>
                  <th>Cantidad</th>
                  <th>Precio</th>
                  <th>Subtotal</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="tablaProductosPago">
                <!-- Agrega más filas según los productos -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- Parte 3: Totales -->
        <div class="mb-3">
          <h5>Totales:</h5>
          <p>Total Cancha: <span name="total_cancha" id="total_cancha"></span></p>
          <p>Extra: <span name="dinero_extra" id="dinero_extra"></span></p>
          <p>Total Productos: <span name="total_detalle" id="total_detalle"></span></p>
          <p style="font-size:1.2rem;" class="mb-0">Total: <span name="total_general" id="total_general"></span></p>
          <p style="font-size:0.8rem;">Seña: - <span name="dinero_senia" id="dinero_senia"></span></p>
          <p style="font-size:1.2rem;">Total a pagar: <span name='total' id="totalFinalizarPago"></span></p>
        </div>

        <!-- Parte 4: Inputs para tipo de pago -->
        <div class="mb-3">
          <h5>Pago:</h5>

          <!-- Tabla para mostrar los pagos agregados -->
          <div class="mb-3">
            <p>Listado de pagos:</p>
            <div class="styleTabla">
              <table class="table">
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Monto Transferencia</th>
                    <th>Monto Efectivo</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="tablaPagos">
                  <!-- Aquí se agregarán las filas de pagos -->
                </tbody>
              </table>
            </div>
          </div>
          <!-- Botón para abrir el modal de agregar nuevo pago -->
          <div class="d-flex justify-content-center">
            <button class="btn" id="btnAgregarPago">
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor"
                class="bi bi-plus-circle-fill iconAdd" viewBox="0 0 16 16">
                <path
                  d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
              </svg>
            </button>
          </div>
          <div class="form-group w-50 px-2">
            <label for="fechaPago">Fecha y Hora del Pago</label>
            <input type="datetime-local" class="form-control" id="fechaPago" name="fechaPago"
              value="<?php echo date('Y-m-d\TH:i'); ?>">
          </div>

          <div class="row m-0 p-0 mt-4">
            <div class="col-md-6 mt-2">
              <label for="pagoTransf" class="col-sm-12 control-label">Pago Transferencia: </label>
              <input type="number" class="form-control" name="pagoTransf" id="pagoTransf" value="0" required />
            </div>
            <div class="col-md-6 mt-2">
              <label for="pagoEfectivo" class="col-sm-12 control-label">Pago efectivo: </label>
              <input type="number" class="form-control" name="pagoEfectivo" id="pagoEfectivo" value="0" required />
            </div>
            <div class="mt-3 d-flex justify-content-center">
              <p>Faltante: <span id="faltaParaTotal">0</span></p>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
        <!-- Botón para realizar alguna acción, como guardar los datos -->
        <button type="button" class="btn btn-primary" id="btnFinalizar">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalAgregarPago" tabindex="-1" aria-labelledby="modalAgregarPagoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content shadow">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAgregarPagoLabel">Agregar Nuevo Pago</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Formulario para agregar nuevo pago -->
        <form id="formAgregarPago">
          <div class="mb-3">
            <label for="nombrePago" class="form-label">Nombre:</label>
            <input type="text" class="form-control" id="nombrePago" name="nombrePago" required>
            <!-- Div para mostrar nombres coincidentes -->
            <div id="nombresCoincidentes" class="list-group" style="max-height: 90px; overflow-y: auto;"></div>
          </div>

          <div class="mb-3">
            <label for="montoTransferencia" class="form-label">Monto Transferencia:</label>
            <input type="number" class="form-control" id="montoTransferencia" name="montoTransferencia" value="0"
              required>
          </div>
          <div class="mb-3">
            <label for="montoEfectivo" class="form-label">Monto Efectivo:</label>
            <input type="number" class="form-control" id="montoEfectivo" name="montoEfectivo" value="0" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="btnCerrarModalPago" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarPago">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Elementos de totales
    const totalCancha = document.getElementById('total_cancha');
    const dineroExtra = document.getElementById('dinero_extra');
    const totalDetalle = document.getElementById('total_detalle');

    const totalGeneral = document.getElementById('total_general');

    // Función para calcular el total general
    function calcularTotalGeneral() {
      const totalCanchaValue = parseFloat(totalCancha.innerText) || 0;
      const dineroExtraValue = parseFloat(dineroExtra.innerText) || 0;
      const totalDetalleValue = parseFloat(totalDetalle.innerText) || 0;

      const total = totalCanchaValue + dineroExtraValue + totalDetalleValue;
      totalGeneral.innerText = total;
    }

    // Observar cambios en los elementos de totales
    const observer = new MutationObserver(calcularTotalGeneral);

    observer.observe(totalCancha, { childList: true, subtree: true });
    observer.observe(dineroExtra, { childList: true, subtree: true });
    observer.observe(totalDetalle, { childList: true, subtree: true });

    // Calcular el total general inicialmente
    calcularTotalGeneral();
  });
</script>