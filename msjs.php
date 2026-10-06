<?php
if(isset($_REQUEST['e'])){ ?>
<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
  <strong>Felicitaciones!</strong> El evento fue registrado correctamente.
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php } ?>

<?php
if(isset($_REQUEST['success'])){ ?>
<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
  <?php echo htmlspecialchars($_REQUEST['success']); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php } ?>

<?php
if(isset($_REQUEST['ea'])){ ?>
<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
  <strong>Felicitaciones!</strong> La venta se realizo correctamente.
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php } ?>

<?php
if(isset($_REQUEST['eaa'])){ ?>
<div class="alert alert-success alert-dismissible fade show text-center" role="alert">
  <strong>Felicitaciones!</strong> El cliente se agrego correctamente.
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php } ?>

<?php
if(isset($_REQUEST['error'])){ ?>
<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
  <strong>Error:</strong> <?php echo $_REQUEST['error']; ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php } ?>

<div class="alert alert-danger alert-dismissible fade show text-center" role="alert" style="display: none;">
  <strong>Felicitaciones!</strong> El evento fue borrado correctamente.
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
