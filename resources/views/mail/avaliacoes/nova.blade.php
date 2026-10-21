<x-mail::message>
# Nova avaliação recebida

**Nome:** {!! $nome !!}

**Nota:** {{ $estrelas }} ({{ $avaliacao->nota }} de 5)

**Permite publicar:** {{ $avaliacao->publicacao_permitida ? 'Sim' : 'Não' }}

**Conta:** {!! $conta !!}

**Enviada em:** {{ $avaliacao->created_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}

<x-mail::panel>
{!! $mensagem !!}
</x-mail::panel>

@if ($avaliacao->publicacao_permitida)
A avaliação só aparece no site depois de aprovada.

<x-mail::button :url="$urlModeracao">
Moderar avaliações
</x-mail::button>
@endif

{{ config('app.name') }}
</x-mail::message>
