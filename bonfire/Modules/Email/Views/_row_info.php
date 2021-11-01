<td><?= esc($email['email']) ?></td>
<td><?= ellipsize(esc($email['subject']),15) ?></td>
<td><span class="badge rounded-pill bg-light text-dark"><?= esc($email['attempts']) ?></span></td>
<td><?= ($email['sent'] == 1)? '<span class="badge rounded-pill bg-success">'.$email['sent_at'].'</span>':'<span class="badge rounded-pill bg-secondary">No</span>'; ?></td>
<td><?= $email['created_at']; ?></td>
<td>
    <?= anchor(ADMIN_AREA.'/content/email_preview/'.$email['id'],'<i class="fas fa-eye"></i> Preview','class="btn btn-sm btn-primary"'); ?>
</td>
