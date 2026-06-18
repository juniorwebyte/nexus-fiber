<div class="panel">
    <h2>Visão Geral da Operação</h2>
    <h2 style="color: var(--primary);">Olá, <?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?></h2>
    <p>Pontuação de Experiência: <strong><?= $user['pontuacao'] ?? 0 ?> XP</strong></p>
    <a href="<?= BASE_URL ?>/academia" class="btn" style="display:inline-block; margin-right: 10px;">Acessar Treinamentos</a>
    <a href="<?= BASE_URL ?>/os" class="btn" style="display:inline-block; background: var(--success);">Minhas Tarefas (O.S)</a>
</div>

<?php if (isset($isManager) && $isManager): ?>
<div class="panel">
    <h3 style="color: var(--primary);">📊 Indicadores da Equipe (Gestão)</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
        <div style="background: var(--bg); padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #ddd;">
            <h4 style="margin: 0; color: #666;">Total de Técnicos</h4>
            <p style="font-size: 2em; font-weight: bold; margin: 10px 0 0 0; color: var(--text);"><?= $stats['total_users'] ?></p>
        </div>
        <div style="background: var(--bg); padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #ddd;">
            <h4 style="margin: 0; color: #666;">Provas Concluídas</h4>
            <p style="font-size: 2em; font-weight: bold; margin: 10px 0 0 0; color: var(--success);"><?= $stats['total_quizzes'] ?></p>
        </div>
    </div>
</div>

<div class="panel">
    <h3 style="color: var(--primary);">🏆 Top Performers</h3>
    <ul style="list-style: none; padding: 0;">
        <?php foreach ($stats['top_users'] as $idx => $user): ?>
            <li style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
                <span><?= ($idx+1) ?>º <?= htmlspecialchars($user['nome']) ?></span>
                <span style="font-weight: bold; color: var(--primary);"><?= $user['pontuacao'] ?> pts</span>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <div style="margin-top: 15px;">
        <a href="<?= BASE_URL ?>/equipe" class="btn" style="display:block; text-align:center; padding: 10px;">👥 Gerenciar Equipe (RH)</a>
    </div>
</div>
<?php endif; ?>

<div style="display: grid; gap: 15px;">
    <a href="<?= BASE_URL ?>/estoque" style="text-decoration: none;">
        <div class="card" style="background: #fdfbed; border: 1px solid #fde68a;">
            <h3 style="color: #d97706;">📦 Meu Estoque / Almoxarifado</h3>
            <p style="color: #555;">Controle do material na sua viatura. O gestor pode transferir novos ativos.</p>
        </div>
    </a>

    <a href="<?= BASE_URL ?>/apr" style="text-decoration: none;">
        <div class="card" style="background: #fef2f2; border-color: #fecaca;">
            <h3 style="color: #b91c1c;">🛡️ Seg. do Trabalho (APR)</h3>
            <p style="color: #666;">Preencha e visualize as Análises Preliminares de Risco de suas atividades de campo.</p>
        </div>
    </a>

    <a href="<?= BASE_URL ?>/perigo" style="text-decoration: none;">
        <div class="card" style="background: #fff1f2; border-color: #fecdd3;">
            <h3 style="color: #e11d48;">🛑 Relato de Perigo</h3>
            <p style="color: #881337;">Reporte interrupções (Stop Work) ou acionamentos do Anjo da Guarda.</p>
        </div>
    </a>

    <a href="<?= BASE_URL ?>/mapa/upload" style="text-decoration: none;">
        <div class="card">
            <h3>🗺️ Rede / Projetos</h3>
            <p>Carregue a MUBI ou acesse as rotas do projeto pelo mapa interativo.</p>
        </div>
    </a>
    
    <a href="<?= BASE_URL ?>/biblioteca" style="text-decoration: none;">
        <div class="card">
            <h3>📚 Biblioteca Técnica</h3>
            <p>Consulte manuais, limites de atenuação, tipos de splitters e imagens.</p>
        </div>
    </a>
    
    <a href="<?= BASE_URL ?>/simulador" style="text-decoration: none;">
        <div class="card">
            <h3>⚙️ Engenharia e Testes</h3>
            <p>Utilize a calculadora óptica e valide os parâmetros de perda dBm na rede.</p>
        </div>
    </a>
    
    <a href="<?= BASE_URL ?>/academia" style="text-decoration: none;">
        <div class="card">
            <h3>🎓 Drop Academia</h3>
            <p>Assista tutoriais, responda quizzes de validação técnica e suba de cargo.</p>
        </div>
    </a>
</div>
