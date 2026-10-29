<?php // The toast of the action run just queued, if any. It polls until the run ends. ?>
<?= service('actionRuns')->toast() ?>
