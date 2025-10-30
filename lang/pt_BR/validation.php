<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => 'O campo :attribute deve ser aceito.',
    'active_url'           => 'O campo :attribute não contém uma URL válida.',
    'after'                => 'O campo :attribute deve conter uma data posterior a :date.',
    'after_or_equal'       => 'O campo :attribute deve conter uma data superior ou igual a :date.',
    'alpha'                => 'O campo :attribute deve conter apenas letras.',
    'alpha_dash'           => 'O campo :attribute deve conter apenas letras, números e traços.',
    'alpha_num'            => 'O campo :attribute deve conter apenas letras e números.',
    'array'                => 'O campo :attribute deve conter um array.',
    'before'               => 'O campo :attribute deve conter uma data anterior a :date.',
    'before_or_equal'      => 'O campo :attribute deve conter uma data inferior ou igual a :date.',
    'between'              => [
        'numeric' => 'O campo :attribute deve conter um valor entre :min e :max.',
        'file'    => 'O campo :attribute deve conter um tamanho entre :min e :max kilobytes.',
        'string'  => 'O campo :attribute deve conter entre :min e :max caracteres.',
        'array'   => 'O campo :attribute deve conter de :min a :max itens.',
    ],
    'boolean'              => 'O campo :attribute deve conter o valor verdadeiro ou falso.',
    'confirmed'            => 'A confirmação para o campo :attribute não coincide.',
    'date'                 => 'O campo :attribute não contém uma data válida.',
    'date_format'          => 'A data indicada para o campo :attribute não respeita o formato :format.',
    'different'            => 'Os campos :attribute e :other devem conter valores diferentes.',
    'digits'               => 'O campo :attribute deve conter :digits dígitos.',
    'digits_between'       => 'O campo :attribute deve conter entre :min e :max dígitos.',
    'dimensions'           => 'O campo :attribute não possui dimensões de imagem válidas.',
    'distinct'             => 'O campo :attribute possui um valor duplicado.',
    'email'                => 'O campo :attribute não contém um endereço de e-mail válido.',
    'exists'               => 'O valor selecionado para o campo :attribute é inválido.',
    'file'                 => 'O campo :attribute deve conter um arquivo.',
    'filled'               => 'O campo :attribute é obrigatório.',
    'gt'                   => [
        'numeric' => 'O campo :attribute deve ser maior que :value.',
        'file'    => 'O campo :attribute deve ser maior que :value kilobytes.',
        'string'  => 'O campo :attribute deve conter mais de :value caracteres.',
        'array'   => 'O campo :attribute deve conter mais de :value itens.',
    ],
    'gte'                  => [
        'numeric' => 'O campo :attribute deve ser maior ou igual a :value.',
        'file'    => 'O campo :attribute deve ser maior ou igual a :value kilobytes.',
        'string'  => 'O campo :attribute deve conter mais ou igual a :value caracteres.',
        'array'   => 'O campo :attribute deve conter :value itens ou mais.',
    ],
    'image'                => 'O campo :attribute deve conter uma imagem.',
    'in'                   => 'O campo :attribute não contém um valor válido.',
    'in_array'             => 'O campo :attribute não existe em :other.',
    'integer'              => 'O campo :attribute deve conter um número inteiro.',
    'ip'                   => 'O campo :attribute deve conter um IP válido.',
    'ipv4'                 => 'O campo :attribute deve conter um IPv4 válido.',
    'ipv6'                 => 'O campo :attribute deve conter um IPv6 válido.',
    'json'                 => 'O campo :attribute deve conter uma string JSON válida.',
    'lt'                   => [
        'numeric' => 'O campo :attribute deve ser menor que :value.',
        'file'    => 'O campo :attribute deve ser menor que :value kilobytes.',
        'string'  => 'O campo :attribute deve conter menos de :value caracteres.',
        'array'   => 'O campo :attribute deve conter menos de :value itens.',
    ],
    'lte'                  => [
        'numeric' => 'O campo :attribute deve ser menor ou igual a :value.',
        'file'    => 'O campo :attribute deve ser menor ou igual a :value kilobytes.',
        'string'  => 'O campo :attribute deve conter menos ou igual a :value caracteres.',
        'array'   => 'O campo :attribute não deve conter mais que :value itens.',
    ],
    'max'                  => [
        'numeric' => 'O campo :attribute não deve conter um valor superior a :max.',
        'file'    => 'O campo :attribute não deve conter um tamanho de arquivo superior a :max kilobytes.',
        'string'  => 'O campo :attribute não deve conter mais de :max caracteres.',
        'array'   => 'O campo :attribute deve conter no máximo :max itens.',
    ],
    'mimes'                => 'O campo :attribute deve conter um arquivo do tipo: :values.',
    'mimetypes'            => 'O campo :attribute deve conter um arquivo do tipo: :values.',
    'min'                  => [
        'numeric' => 'O campo :attribute deve conter um valor superior ou igual a :min.',
        'file'    => 'O campo :attribute deve conter um tamanho de arquivo de no mínimo :min kilobytes.',
        'string'  => 'O campo :attribute deve conter no mínimo :min caracteres.',
        'array'   => 'O campo :attribute deve conter no mínimo :min itens.',
    ],
    'not_in'               => 'O campo :attribute contém um valor inválido.',
    'not_regex'            => 'O formato do campo :attribute é inválido.',
    'numeric'              => 'O campo :attribute deve conter um valor numérico.',
    'present'              => 'O campo :attribute deve estar presente.',
    'regex'                => 'O formato do campo :attribute é inválido.',
    'required'             => 'O campo :attribute é obrigatório.',
    'required_if'          => 'O campo :attribute é obrigatório quando o valor do campo :other é igual a :value.',
    'required_unless'      => 'O campo :attribute é obrigatório exceto se :other estiver presente em :values.',
    'required_with'        => 'O campo :attribute é obrigatório quando :values está presente.',
    'required_with_all'    => 'O campo :attribute é obrigatório quando :values está presente.',
    'required_without'     => 'O campo :attribute é obrigatório quando :values não está presente.',
    'required_without_all' => 'O campo :attribute é obrigatório quando nenhum destes estão presentes: :values.',
    'same'                 => 'Os campos :attribute e :other devem conter valores iguais.',
    'size'                 => [
        'numeric' => 'O campo :attribute deve conter o valor :size.',
        'file'    => 'O campo :attribute deve conter um arquivo com o tamanho de :size kilobytes.',
        'string'  => 'O campo :attribute deve conter :size caracteres.',
        'array'   => 'O campo :attribute deve conter :size itens.',
    ],
    'string'               => 'O campo :attribute deve ser uma string.',
    'timezone'             => 'O campo :attribute deve conter um fuso horário válido.',
    'unique'               => 'O valor indicado para o campo :attribute já se encontra em utilização.',
    'uploaded'             => 'Ocorreu uma falha no upload do campo :attribute.',
    'url'                  => 'O formato da URL indicada para o campo :attribute é inválido.',
    'uuid'                 => 'O campo :attribute deve ser um UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'address'               => 'endereço',
        'age'                   => 'idade',
        'body'                  => 'conteúdo',
        'city'                  => 'cidade',
        'country'               => 'país',
        'date'                  => 'data',
        'day'                   => 'dia',
        'description'           => 'descrição',
        'email'                 => 'e-mail',
        'excerpt'               => 'excerto',
        'first_name'            => 'primeiro nome',
        'gender'                => 'gênero',
        'hour'                  => 'hora',
        'last_name'             => 'sobrenome',
        'message'               => 'mensagem',
        'minute'                => 'minuto',
        'mobile'                => 'celular',
        'month'                 => 'mês',
        'name'                  => 'nome',
        'password'              => 'senha',
        'password_confirmation' => 'confirmação da senha',
        'phone'                 => 'telefone',
        'second'                => 'segundo',
        'sex'                   => 'sexo',
        'subject'               => 'assunto',
        'time'                  => 'hora',
        'title'                 => 'título',
        'username'              => 'usuário',
        'year'                  => 'ano',
    ],

];
