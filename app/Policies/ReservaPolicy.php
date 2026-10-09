<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Auth\Access\Response;

class ReservaPolicy
{
    /** Admin cancela qualquer reserva; aluno, só a própria. */
    public function cancelar(Usuario $usuario, Reserva $reserva): Response
    {
        return $usuario->ehAdmin() || $reserva->usuario_id === $usuario->id
            ? Response::allow()
            : Response::deny('Esta reserva não é sua.');
    }
}
