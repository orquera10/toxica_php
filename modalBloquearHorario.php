<!-- Modal para bloquear horarios de reserva -->
<div class="modal fade" id="modalBloquearHorario" tabindex="-1" aria-labelledby="modalBloquearHorarioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalBloquearHorarioLabel">Bloquear horario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formBloquearHorario" action="guardar_bloqueo_horario.php" method="POST">
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="bloqueo_inicio" class="form-label">Inicio</label>
              <input type="datetime-local" class="form-control" id="bloqueo_inicio" name="bloqueo_inicio" required>
            </div>
            <div class="col-md-6">
              <label for="bloqueo_fin" class="form-label">Fin</label>
              <input type="datetime-local" class="form-control" id="bloqueo_fin" name="bloqueo_fin" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="bloqueo_cancha" class="form-label">Cancha</label>
            <select class="form-select" id="bloqueo_cancha" name="bloqueo_cancha">
              <option value="">Todas las canchas</option>
              <?php
              $canchas_bloqueo = mysqli_query($con, "SELECT _id, NOMBRE FROM canchas WHERE _id != 9 ORDER BY NOMBRE");
              while ($cancha_bloqueo = mysqli_fetch_assoc($canchas_bloqueo)) {
                  echo '<option value="' . (int) $cancha_bloqueo['_id'] . '">' . htmlspecialchars($cancha_bloqueo['NOMBRE']) . '</option>';
              }
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="bloqueo_motivo" class="form-label">Motivo</label>
            <input type="text" class="form-control" id="bloqueo_motivo" name="bloqueo_motivo" maxlength="255" placeholder="Ej: mantenimiento, evento privado">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary">Guardar bloqueo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function abrirModalBloquearHorario() {
    function formatoDatetimeLocal(fecha) {
      var anio = fecha.getFullYear();
      var mes = String(fecha.getMonth() + 1).padStart(2, '0');
      var dia = String(fecha.getDate()).padStart(2, '0');
      var hora = String(fecha.getHours()).padStart(2, '0');
      var minutos = String(fecha.getMinutes()).padStart(2, '0');

      return anio + '-' + mes + '-' + dia + 'T' + hora + ':' + minutos;
    }

    var ahora = new Date();
    ahora.setMinutes(0, 0, 0);
    var despues = new Date(ahora.getTime() + 60 * 60 * 1000);

    document.getElementById('bloqueo_inicio').value = formatoDatetimeLocal(ahora);
    document.getElementById('bloqueo_fin').value = formatoDatetimeLocal(despues);
    $('#modalBloquearHorario').modal('show');
  }
</script>
