<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <h2>Logs : <?php echo $log_file_pretty; ?></h2>
        <a href="/<?= ADMIN_AREA ?>tools/logs" class="back">&larr; Logs</a>
        <h2><Logs</h2>
    </x-page-head>

    <x-admin-box>

  <div class="table-responsive">
    <table class="table table-hover nowrap" id="log">
      <tr>
                      <th><?= lang('Logs.level'); ?></th>
                      <th><?= lang('Logs.date'); ?></th>
                      <th><?= lang('Logs.content'); ?></th>

                    </tr>
      <tbody>
        <?php foreach($log_content as $key => $log): ?>

                         <tr <?php if(array_key_exists("extra", $log)){ ?> style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#stack<?= $key; ?>" aria-controls="stack<?= $key; ?>" aria-expanded="false" <?php } ?>  >

                             <td class="text-<?= $log['class']; ?>">
                                 <span class="<?= $log['icon']; ?>" aria-hidden="true"></span>
                                 &nbsp;<?= $log['level']; ?>
                             </td>
                             <td class="date"><?= $log['date']; ?></td>
                             <td class="text">
                                 <?= $log['content']; ?><?php echo (array_key_exists("extra", $log))? '...':''; ?>
                             </td>
                         </tr>

                         <?php if (array_key_exists("extra", $log)): ?>

                               <tr class="collapse bg-light" id="stack<?= $key; ?>">
                                 <td colspan="3">
                                 <pre><?= $log['extra'] ?></pre>
                           </td>
                         </tr>
                         <?php endif; ?>
                     <?php endforeach; ?>
      </tbody>
    </table>

<?= $pager->links() ?>

  </div>

        <?php if ($canDelete){ ?>

            <?php echo form_open(site_url(ADMIN_AREA . 'tools/logs'), array('class' => 'form-horizontal')); ?>

              <input type="hidden" name="checked[]" value="<?= $log_file; ?>" />
              <input type="submit" name="delete" class="btn btn-danger btn-sm" value="<?php echo lang('Logs.delete_file'); ?>" onclick="return confirm('<?= lang('Logs.delete_confirm') ?>')" />

            <?php echo form_close(); ?>

<?php } ?>

</x-admin-box>

<?php $this->endSection() ?>
