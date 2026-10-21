<?php

namespace App\Mail;

use App\Models\Avaliacao;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Avisa a equipe sobre uma nova avaliação enviada pelo site.
 */
class NovaAvaliacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Avaliacao $avaliacao) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $estrelas = $this->avaliacao->nota === 1 ? '1 estrela' : "{$this->avaliacao->nota} estrelas";

        return new Envelope(
            subject: "Nova avaliação no JFinanceiro ({$estrelas})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.avaliacoes.nova',
            with: [
                'nome' => self::textoLiteral($this->avaliacao->nome),
                'mensagem' => self::textoLiteral($this->avaliacao->mensagem),
                'estrelas' => str_repeat('★', $this->avaliacao->nota).str_repeat('☆', 5 - $this->avaliacao->nota),
                'conta' => $this->avaliacao->user
                    ? self::textoLiteral("{$this->avaliacao->user->name} ({$this->avaliacao->user->email})")
                    : 'Visitante sem conta',
                'urlModeracao' => route('avaliacoes.index'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Escapa HTML e a sintaxe markdown de um texto digitado pelo visitante, para que ele
     * apareça literal no e-mail e não vire link, imagem ou formatação.
     */
    public static function textoLiteral(string $texto): string
    {
        $semHtml = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
        $semFormatacao = preg_replace('/([\\\\`*_\[\]()~|])/', '\\\\$1', $semHtml);
        $semMarcadorDeLinha = preg_replace('/^(\s*)([#>+\-])/m', '$1\\\\$2', $semFormatacao);
        $semMarkdown = preg_replace('/^(\s*\d+)\./m', '$1\\\\.', $semMarcadorDeLinha);

        return nl2br($semMarkdown, false);
    }
}
