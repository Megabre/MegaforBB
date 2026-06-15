<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $siteName = '{site_name}';
        $defaults = [
            'email_verification' => [
                'subject' => $siteName . ' — E-posta adresinizi doğrulayın',
                'body_html' => '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
<h2 style="color: #206bc4;">E-posta Doğrulama</h2>
<p>Merhaba <strong>{name}</strong>,</p>
<p>Kaydınızı tamamlamak için aşağıdaki bağlantıya tıklayarak e-posta adresinizi doğrulayın:</p>
<p style="margin: 24px 0;"><a href="{verify_url}" style="display: inline-block; padding: 12px 24px; background: #206bc4; color: #fff !important; text-decoration: none; border-radius: 6px; font-weight: bold;">E-postamı doğrula</a></p>
<p style="color: #666; font-size: 14px;">Bu link 24 saat geçerlidir. Eğer bu kaydı siz yapmadıysanız bu e-postayı yok sayabilirsiniz.</p>
<hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">
<p style="color: #999; font-size: 12px;">— {site_name}</p>
</body></html>',
            ],
            'password_reset' => [
                'subject' => 'Şifre sıfırlama — ' . $siteName,
                'body_html' => '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
<h2 style="color: #206bc4;">Şifre Sıfırlama</h2>
<p>Merhaba,</p>
<p>Şifre sıfırlama talebinde bulundunuz. Aşağıdaki bağlantıya tıklayarak <strong>yeni şifrenizi</strong> belirleyebilirsiniz:</p>
<p style="margin: 24px 0;"><a href="{url}" style="display: inline-block; padding: 12px 24px; background: #206bc4; color: #fff !important; text-decoration: none; border-radius: 6px;">Şifremi sıfırla</a></p>
<p style="color: #666; font-size: 14px;">Bu link <strong>60 dakika</strong> geçerlidir. Bu talebi siz yapmadıysanız bu e-postayı yok sayabilirsiniz.</p>
<hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">
<p style="color: #999; font-size: 12px;">— {site_name}</p>
</body></html>',
            ],
            'login_code' => [
                'subject' => $siteName . ' — Giriş doğrulama kodu',
                'body_html' => '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
<h2 style="color: #206bc4;">Giriş Doğrulama Kodu</h2>
<p>Merhaba <strong>{username}</strong>,</p>
<p>Giriş yapmak için aşağıdaki doğrulama kodunu kullanın:</p>
<p style="margin: 24px 0; font-size: 28px; letter-spacing: 8px; font-weight: bold; color: #206bc4;">{code}</p>
<p style="color: #666; font-size: 14px;">Kod 10 dakika geçerlidir. Bu girişi siz yapmadıysanız bu e-postayı yok sayın.</p>
<hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">
<p style="color: #999; font-size: 12px;">— {site_name}</p>
</body></html>',
            ],
            'contact_reply' => [
                'subject' => 'Yanıt: İletişim mesajınız',
                'body_html' => '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
<h2 style="color: #206bc4;">İletişim Yanıtı</h2>
<p>Merhaba <strong>{name}</strong>,</p>
<p>İletişim formunuzdaki mesajınıza yanıt veriyoruz:</p>
<div style="margin: 20px 0; padding: 16px; background: #f5f5f5; border-radius: 8px; border-left: 4px solid #206bc4;">
{reply_body}
</div>
<p>Başka sorunuz olursa tekrar yazabilirsiniz.</p>
<hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">
<p style="color: #999; font-size: 12px;">— {site_name}</p>
</body></html>',
            ],
        ];

        $stmt = $pdo->prepare('INSERT INTO mail_templates (template_key, name, subject, body_html) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE subject = VALUES(subject), body_html = VALUES(body_html)');
        $names = [
            'email_verification' => 'E-posta doğrulama',
            'password_reset' => 'Şifre sıfırlama',
            'login_code' => 'Giriş doğrulama kodu',
            'contact_reply' => 'İletişim formu yanıtı',
        ];
        foreach ($defaults as $key => $row) {
            $stmt->execute([$key, $names[$key], $row['subject'], $row['body_html']]);
        }

        // Varsayılan mesaj şablonları (yoksa ekle)
        $msgTpl = $pdo->query("SELECT COUNT(*) FROM message_templates")->fetchColumn();
        if ((int) $msgTpl === 0) {
            $ins = $pdo->prepare('INSERT INTO message_templates (name, subject, body_html) VALUES (?, ?, ?)');
            $ins->execute([
                'Bayram Tebriği',
                'Mutlu Bayramlar — {site_name}',
                '<p>Sayın <strong>{username}</strong>,</p><p><span style="color: #206bc4;">{website_name}</span> ailesi olarak bayramınızı kutlar, sağlık ve mutluluk dileriz.</p><p>Bizi tercih ettiğiniz için teşekkür ederiz.</p><p>— <strong>{site_name}</strong></p>',
            ]);
            $ins->execute([
                'Hoş Geldiniz',
                'Hoş geldiniz — {site_name}',
                '<h3>Merhaba {username}!</h3><p><strong>{website_name}</strong> topluluğumuza katıldığınız için teşekkür ederiz.</p><p>Site adresimiz: <a href="{website}">{website}</a></p><p>Keyifli vakit geçirmenizi dileriz.</p><p>— {site_name}</p>',
            ]);
        }
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec("UPDATE mail_templates SET subject = '', body_html = NULL WHERE template_key IN ('email_verification', 'password_reset', 'login_code', 'contact_reply')");
        $pdo->exec("DELETE FROM message_templates WHERE name IN ('Bayram Tebriği', 'Hoş Geldiniz')");
    },
];
