<?php
/* RAC shared include. Porto HTML is identical to BOS24 when $racUsePortoLayout=true. */
if (empty($racUsePortoLayout)) {
    include __DIR__ . '/rac-legacy-footer.class.php';
    return;
}
?>
<?php
/** RAC Porto shared footer; 2026-10-30-HELP-01. */
?>
</section>
</div>
</section>
<?php include __DIR__ . '/napoveda-widget.php'; ?>
<?php include __DIR__ . '/scripts-bottom.class.php'; ?>
</body>
</html>
