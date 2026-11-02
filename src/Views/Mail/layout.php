<?php
/** @var string $content */
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body { margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
p, li { color: #18181b; font-size: 15px; line-height: 1.6; }
p { margin: 0 0 16px; }
ul { margin: 0 0 16px; padding: 0 0 0 20px; }
h1 { color: #09090b; font-size: 28px; letter-spacing: 4px; margin: 0; text-align: center; }
code { font-family: Menlo, Consolas, monospace; font-size: 13px; word-break: break-word; }
</style>
</head>
<body>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center" style="padding: 24px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: #ffffff;">
                <tr>
                    <td height="4" style="height: 4px; font-size: 0; line-height: 0; background-color: <?= admin_accent() ?>;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="padding: 24px 24px 0; font-size: 18px; font-weight: 600; color: #09090b;"><?= esc(setting('Site.siteName') ?? 'Bonfire') ?></td>
                </tr>
                <tr>
                    <td style="padding: 16px 24px 8px;">
<?= $content ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
