<?php

namespace App\Http\Controllers;

use App\Http\Requests\Avaliacoes\StoreAvaliacaoRequest;
use App\Mail\NovaAvaliacaoMail;
use App\Models\Avaliacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AvaliacaoController extends Controller
{
    public const MENSAGEM_LIMITE = 'Você já enviou várias avaliações nesta hora. Tente novamente mais tarde.';

    /**
     * Quantidade de avaliações que um mesmo IP pode registrar por hora.
     */
    public const LIMITE_AVALIACOES_POR_HORA = 3;

    /**
     * Exibe o formulário público de avaliação.
     */
    public function create(): Response
    {
        return Inertia::render('avaliacoes/Create', [
            'nomeSugerido' => auth()->user()?->name,
            'recaptchaSiteKey' => config('services.recaptcha.site_key'),
        ]);
    }

    /**
     * Registra a avaliação e avisa a equipe por e-mail.
     *
     * Só as avaliações salvas contam para o limite por hora, para que erros de preenchimento
     * não bloqueiem o visitante. O e-mail é enviado na hora, porque a produção não tem worker
     * de fila. Uma falha no envio é registrada no log, mas não descarta a avaliação nem mostra
     * erro ao visitante.
     */
    public function store(StoreAvaliacaoRequest $request): RedirectResponse
    {
        $chaveLimite = 'avaliacoes-salvas:'.$request->ip();

        if (RateLimiter::tooManyAttempts($chaveLimite, self::LIMITE_AVALIACOES_POR_HORA)) {
            return back()->withInput()->withErrors(['envio' => self::MENSAGEM_LIMITE]);
        }

        $avaliacao = Avaliacao::create([
            ...$request->safe()->only(['nome', 'nota', 'mensagem', 'publicacao_permitida']),
            'user_id' => $request->user()?->id,
        ]);

        RateLimiter::hit($chaveLimite, 3600);

        try {
            Mail::to(config('mail.contato.address'))->send(new NovaAvaliacaoMail($avaliacao));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('avaliacoes.create')
            ->with('success', 'Obrigado pela sua avaliação!');
    }

    /**
     * Lista as avaliações para moderação, das mais recentes para as mais antigas.
     */
    public function index(): Response
    {
        $avaliacoes = Avaliacao::query()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(30);

        return Inertia::render('avaliacoes/Index', [
            'avaliacoes' => $avaliacoes,
        ]);
    }

    /**
     * Aprova a avaliação para exibição no site. Só é possível quando o autor permitiu.
     */
    public function aprovar(Avaliacao $avaliacao): RedirectResponse
    {
        abort_unless($avaliacao->publicacao_permitida, 422, 'O autor não permitiu publicar esta avaliação.');

        $avaliacao->update(['aprovada_em' => now()]);

        return back()->with('success', 'Avaliação aprovada para o site.');
    }

    /**
     * Retira a avaliação do site.
     */
    public function reprovar(Avaliacao $avaliacao): RedirectResponse
    {
        $avaliacao->update(['aprovada_em' => null]);

        return back()->with('success', 'Avaliação retirada do site.');
    }
}
