<?= lang('Auth.emailActivateMailBody') ?>


<?= view('\Bonfire\Views\Mail\auth_code', ['code' => $code, 'details' => $details], ['debug' => false]) ?>
