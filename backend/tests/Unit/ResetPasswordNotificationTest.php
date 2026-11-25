<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Notifications\ResetPasswordNotification;
use Tests\TestCase;

class ResetPasswordNotificationTest extends TestCase
{
    public function test_to_mail_genera_el_enlace_del_frontend(): void
    {
        config(['app.frontend_url' => 'http://frontend.test']);

        $user = new Account([
            'name' => 'Ana Torres',
            'email' => 'ana@test.local',
        ]);

        $mail = (new ResetPasswordNotification('token-seguro'))->toMail($user);

        $this->assertSame('Restablece tu contraseña', $mail->subject);
        $this->assertSame('Restablecer contraseña', $mail->actionText);
        $this->assertStringStartsWith('http://frontend.test/reset-password?', $mail->actionUrl);
        $this->assertStringContainsString('token=token-seguro', $mail->actionUrl);
        $this->assertStringContainsString('email=ana%40test.local', $mail->actionUrl);
        $this->assertContains(
            'Recibimos una solicitud para restablecer la contraseña de tu cuenta.',
            $mail->introLines
        );
        $this->assertContains(
            'Este enlace expira en 30 minutos y solo puede usarse una vez.',
            $mail->outroLines
        );
        $this->assertContains(
            'Si no solicitaste este cambio, ignora este correo.',
            $mail->outroLines
        );
    }
}
