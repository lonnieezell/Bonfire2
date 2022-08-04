<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head>
        <x-module-title><i class="fas fa-envelope"></i> Email</x-module-title>
        <h2>Settings</h2>
    </x-page-head>

    <x-admin-box>
        <form action="/admin/settings/email" method="post">
            <?= csrf_field() ?>

            <fieldset>
                <legend>From</legend>

                <p>This specifies the default email address and name that will be used when sending an email.</p>

                <div class="row">
                    <!-- Name -->
                    <div class="form-group col">
                        <label class="form-label" for="fromName">Name</label>
                        <input type="text" class="form-control" name="fromName" value="<?= old('fromName', setting('Email.fromName')) ?>">
                        <?php if (has_error('fromName')) : ?>
                            <p class="text-danger"><?= error('fromName') ?></p>
                        <?php endif ?>
                    </div>

                    <!-- Email -->
                    <div class="form-group col">
                        <label class="form-label" for="fromName">Email</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="text" class="form-control" name="fromEmail" value="<?= old('fromEmail', setting('Email.fromEmail')) ?>" />
                        </div>
                        <?php if (has_error('fromEmail')) : ?>
                            <p class="text-danger"><?= error('fromEmail') ?></p>
                        <?php endif ?>
                    </div>
                </div>
            </fieldset>

            <hr>

            <fieldset x-data="{openTab: '<?= $activeTab ?>'}">
                <legend>Mail Settings</legend>

                <p>Select the protocol used when sending mail. The most common scenario is using SMTP.</p>

                <div class="row">
                    <div class="form-group col-12 col-sm-6 col-md-3">
                        <label class="form-label" for="protocol">Protocol</label>
                        <select name="protocol" id="protocol" class="form-select" @change="openTab = event.target.value">
                            <option value="smtp" <?php if (old('protocol', setting('Email.protocol')) === 'smtp') : ?> selected <?php endif?>>smtp</option>
                            <option value="mail" <?php if (old('protocol', setting('Email.protocol')) === 'mail') : ?> selected <?php endif?>>mail</option>
                            <option value="sendmail" <?php if (old('protocol', setting('Email.protocol')) === 'sendmail') : ?> selected <?php endif?>>sendmail</option>
                        </select>
                    </div>
                </div>

                <div class="tab-content mx-5 mt-3">
                    <!-- Mail Settings -->
                    <div id="mail-settings" x-show="openTab === 'mail'" x-transition>
                        <p class="alert alert-info">Mail is only available on Linux servers. There are no options.</p>
                    </div>

                    <!-- SendMail Settings -->
                    <div id="mail-settings" x-show="openTab === 'sendmail'" x-transition>

                        <div class="row">
                            <div class="form-group col-12 col-sm-6 col-md-4">
                                <label for="mailPath" class="form-label">Path the the <b>sendmail</b> executable.</label>
                                <input type="text" name="mailPath" class="form-control" value="<?= old('mailPath', setting('Email.mailPath')) ?>">
                                <?php if (has_error('mailPath')) : ?>
                                    <p class="text-danger"><?= error('mailPath') ?></p>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>

                    <!-- SMTP Settings -->
                    <div id="mail-settings" x-show="openTab === 'smtp'" x-transition>
                        <!-- Host -->
                        <div class="row" x-data="{open: 0}">
                            <div class="form-group col-12 col-sm-6">
                                <label for="SMTPHost" class="form-label">Host</label>
                                <input type="text" name="SMTPHost" class="form-control" value="<?= old('SMTPHost', setting('Email.SMTPHost')) ?>">
                                <?php if (has_error('SMTPHost')) : ?>
                                    <p class="text-danger"><?= error('SMTPHost') ?></p>
                                <?php endif ?>
                            </div>
                            <!-- Port -->
                            <div class="form-group col-12 col-sm-3">
                                <label for="SMTPPort" class="form-label">Port</label>
                                <select name="SMTPPort" class="form-select">
                                    <option value="25" @click="open = false" <?php if (old('SMTPPort', setting('Email.SMTPPort')) === '25') : ?> selected <?php endif?>>25</option>
                                    <option value="587" @click="open = false" <?php if (old('SMTPPort', setting('Email.SMTPPort')) === '587') : ?> selected <?php endif?>>587</option>
                                    <option value="465" @click="open = false" <?php if (old('SMTPPort', setting('Email.SMTPPort')) === '465') : ?> selected <?php endif?>>465</option>
                                    <option value="2525" @click="open = false" <?php if (old('SMTPPort', setting('Email.SMTPPort')) === '2525') : ?> selected <?php endif?>>2525</option>
                                    <option value="other" @click="open = ! open" <?php if (old('SMTPPort', setting('Email.SMTPPort')) === 'other') : ?> selected <?php endif?>>other</option>
                                </select>
                                <?php if (has_error('SMTPPort')) : ?>
                                    <p class="text-danger"><?= error('SMTPPort') ?></p>
                                <?php endif ?>
                            </div>
                            <!-- Custom Port -->
                            <div class="form-group col-12 col-sm-3" x-show="open">
                                <label for="SMTPPortOther" class="form-label">Other</label>
                                <input type="text" name="SMTPHostOther" class="form-control" value="<?= old('SMTPHostOther') ?>">
                                <?php if (has_error('SMTPPortOther')) : ?>
                                    <p class="text-danger"><?= error('SMTPPortOther') ?></p>
                                <?php endif ?>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Username -->
                            <div class="form-group col-12 col-sm-6">
                                <label for="SMTPUser" class="form-label">Username</label>
                                <input type="text" name="SMTPUser" class="form-control" value="<?= old('SMTPUser', setting('Email.SMTPUser')) ?>">
                                <?php if (has_error('SMTPUser')) : ?>
                                    <p class="text-danger"><?= error('SMTPUser') ?></p>
                                <?php endif ?>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Password -->
                            <div class="form-group col-12 col-sm-6">
                                <label for="SMTPPass" class="form-label">Password</label>
                                <input type="password" name="SMTPPass" class="form-control" value="<?= old('SMTPPass', setting('Email.SMTPPass')) ?>">
                                <?php if (has_error('SMTPPass')) : ?>
                                    <p class="text-danger"><?= error('SMTPPass') ?></p>
                                <?php endif ?>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Crypto -->
                            <div class="form-group col-12 col-sm-6">
                                <label for="SMTPCrypto" class="form-label">Encryption</label>
                                <select name="SMTPCrypto" class="form-select">
                                    <option value="" <?php if (old('SMTPCrypto', setting('Email.SMTPCrypto')) == '') : ?> selected <?php endif?>>None</option>
                                    <option value="tls" <?php if (old('SMTPCrypto', setting('Email.SMTPCrypto')) === 'tls') : ?> selected <?php endif?>>TLS</option>
                                    <option value="ssl" <?php if (old('SMTPCrypto', setting('Email.SMTPCrypto')) === 'ssl') : ?> selected <?php endif?>>SSL</option>
                                </select>
                                <?php if (has_error('SMTPCrypto')) : ?>
                                    <p class="text-danger"><?= error('SMTPCrypto') ?></p>
                                <?php endif ?>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Timeout -->
                            <div class="form-group col-12 col-sm-3">
                                <label for="SMTPTimeout" class="form-label">Timeout (in seconds)</label>
                                <input type="number" name="SMTPTimeout" class="form-control" value="<?= old('SMTPTimeout', setting('Email.SMTPTimeout')) ?>">
                                <?php if (has_error('SMTPTimeout')) : ?>
                                    <p class="text-danger"><?= error('SMTPTimeout') ?></p>
                                <?php endif ?>
                            </div>

                            <!-- Timeout -->
                            <div class="form-group col-12 col-sm-3">
                                <label for="SMTPKeepAlive" class="form-label">Persistant Connection?</label>
                                <select name="SMTPKeepAlive" class="form-select">
                                    <option value="0" <?php if (old('SMTPKeepAlive', ! setting('Email.SMTPKeepAlive'))) : ?> selected <?php endif?>>No</option>
                                    <option value="1" <?php if (old('SMTPKeepAlive', setting('Email.SMTPKeepAlive'))) : ?> selected <?php endif?>>Yes</option>
                                </select>
                                <?php if (has_error('SMTPKeepAlive')) : ?>
                                    <p class="text-danger"><?= error('SMTPKeepAlive') ?></p>
                                <?php endif ?>
                            </div>
                        </div>

                    </div>
                </div>

            </fieldset>

            <br><hr>

            <div class="text-end px-5 py-3">
                <input type="submit" value="Save Settings" class="btn btn-primary btn-lg">
            </div>
        </form>
    </x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>

</script>
<?php $this->endSection() ?>
