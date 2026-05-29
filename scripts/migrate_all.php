<?php

/**
 * Script de migração de dados do JFinanceiro Legado para o Novo Sistema
 * Regras: 
 * - Categorias duplicadas por tipo (gasto, gasto futuro)
 * - Renomear 'Padrão' para 'Outros'
 * - Entradas -> ganho (usando categoria "Outros")
 * - Saídas -> gasto
 * - Contas a Pagar -> gasto futuro (com parcelas)
 * - Mantém duplicidade entre saídas e parcelas pagas conforme solicitado (Gasto Futuro + Gasto para cada pagamento)
 */

$oldDbConfig = [
    'host' => '127.0.0.1',
    'dbname' => 'jfinanceiro_old_28_05_2026',
    'user' => 'Tester',
    'pass' => 'Tester123'
];

$newDbConfig = [
    'host' => '127.0.0.1',
    'dbname' => 'jfinanceiro',
    'user' => 'Tester',
    'pass' => 'Tester123'
];

function sanitizeDate($date) {
    if (!$date || $date === '0000-00-00' || strpos($date, '00') === 0) {
        return '2021-01-01 00:00:00';
    }
    return $date . ' 00:00:00';
}

try {
    $oldPdo = new PDO("mysql:host={$oldDbConfig['host']};dbname={$oldDbConfig['dbname']};charset=utf8mb4", $oldDbConfig['user'], $oldDbConfig['pass']);
    $oldPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $newPdo = new PDO("mysql:host={$newDbConfig['host']};dbname={$newDbConfig['dbname']};charset=utf8mb4", $newDbConfig['user'], $newDbConfig['pass']);
    $newPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conexões estabelecidas.\n";

    $newPdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $newPdo->exec("TRUNCATE TABLE users");
    $newPdo->exec("TRUNCATE TABLE categorias");
    $newPdo->exec("TRUNCATE TABLE movimentacoes");
    $newPdo->exec("TRUNCATE TABLE parcelas");

    // 1. Usuários
    echo "Migrando usuários...\n";
    $users = $oldPdo->query("SELECT * FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
    $insertUser = $newPdo->prepare("INSERT INTO users (id, name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($users as $u) {
        $date = sanitizeDate($u['data_cadastro']);
        $insertUser->execute([
            $u['id'], $u['nome'], $u['email'], $u['senha'], 
            ($u['idnivel'] == 1 ? 'admin' : 'user'),
            ($u['idstatus'] == 1 ? 'ativo' : 'inativo'),
            $date, $date
        ]);
    }
    echo count($users) . " usuários.\n";

    // 2. Categorias
    echo "Migrando categorias...\n";
    $cats = $oldPdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
    $insertCat = $newPdo->prepare("INSERT INTO categorias (nome, tipo, user_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    
    $catMapping = []; 
    $types = ['gasto', 'gasto futuro']; 

    foreach ($cats as $c) {
        $nome = ($c['categoria'] === 'Padrão') ? 'Outros' : $c['categoria'];
        
        foreach ($types as $t) {
            $insertCat->execute([$nome, $t, $c['fk_idusuario']]);
            $catMapping[$c['id_categoria']][$t] = $newPdo->lastInsertId();
        }
    }
    
    $insertCat->execute(['Outros', 'ganho', null]);
    $defaultGanhoCatId = $newPdo->lastInsertId();

    // 3. Entradas
    echo "Migrando entradas...\n";
    $entradas = $oldPdo->query("SELECT * FROM entradas")->fetchAll(PDO::FETCH_ASSOC);
    $insertMov = $newPdo->prepare("INSERT INTO movimentacoes (id, user_id, categoria_id, data, descricao, valor, tipo, parcelas, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    // Nota: Removi a inserção automática de parcelas para entradas e saídas aqui, 
    // pois apenas Gasto Futuro deve ter registros na tabela 'parcelas' conforme a regra de domínio.
    
    $currentMovId = 1;
    foreach ($entradas as $e) {
        $date = sanitizeDate($e['data']);
        $insertMov->execute([$currentMovId, $e['idusuario'], $defaultGanhoCatId, $e['data'], $e['descricao'], $e['valor'], 'ganho', 1, $date, $date]);
        $currentMovId++;
    }

    // 4. Contas a Pagar
    echo "Migrando contas a pagar...\n";
    $insertPar = $newPdo->prepare("INSERT INTO parcelas (movimentacao_id, numero, valor, data_vencimento, data_pagamento, pago, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $contas = $oldPdo->query("SELECT * FROM contas_a_pagar")->fetchAll(PDO::FETCH_ASSOC);
    $oldContaToNewMov = [];
    
    foreach ($contas as $cp) {
        $date = sanitizeDate($cp['data']);
        $newCatId = $catMapping[$cp['fk_idcategoria']]['gasto futuro'] ?? null;
        
        $insertMov->execute([$currentMovId, $cp['id_usuario'], $newCatId, $cp['data'], $cp['descricao'], $cp['valor'], 'gasto futuro', $cp['parcelas'], $date, $date]);
        
        $oldContaToNewMov[$cp['id']] = $currentMovId;
        $currentMovId++;
    }

    // 5. Parcelas
    echo "Migrando parcelas...\n";
    $parcelas = $oldPdo->query("SELECT * FROM parcela")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($parcelas as $p) {
        if (!isset($oldContaToNewMov[$p['idcontapagar']])) continue;
        
        $movId = $oldContaToNewMov[$p['idcontapagar']];
        $pago = ($p['data_pagamento'] && $p['data_pagamento'] != '0000-00-00');
        $date = sanitizeDate($p['data_vencimento']);
        
        $insertPar->execute([
            $movId, $p['numero_parcela'], $p['valor_parcela'], 
            $p['data_vencimento'], ($pago ? $p['data_pagamento'] : null), 
            ($pago ? 1 : 0),
            $date, $date
        ]);
    }

    // 6. Saídas
    echo "Migrando saídas...\n";
    $saidas = $oldPdo->query("SELECT * FROM saidas")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($saidas as $s) {
        $date = sanitizeDate($s['data']);
        $newCatId = $catMapping[$s['fk_idcategoria']]['gasto'] ?? null;
        
        $insertMov->execute([$currentMovId, $s['idusuario'], $newCatId, $s['data'], $s['descricao'], $s['valor'], 'gasto', 1, $date, $date]);
        $currentMovId++;
    }

    $newPdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Migração concluída com sucesso!\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
