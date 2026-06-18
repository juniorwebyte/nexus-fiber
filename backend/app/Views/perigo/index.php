<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>⚠️ Lista de Relatos de Perigo</h2>
        <a href="<?= BASE_URL ?>/perigo/novo" class="btn" style="background: var(--danger);">+ Cadastrar Relato de Perigo</a>
    </div>
    <p>Visualize os relatórios de interrupção ou identificação de risco crítico na operação.</p>
</div>

<div class="panel" style="margin-top: 20px;">
    <h3>Filtros</h3>
    <form method="GET" action="<?= BASE_URL ?>/perigo">
        <div style="display: flex; gap: 10px;">
            <input type="date" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <input type="date" name="end_date" value="<?= $_GET['end_date'] ?? '' ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" class="btn">Filtrar</button>
            <a href="<?= BASE_URL ?>/perigo" class="btn" style="background: #ccc;">Limpar</a>
        </div>
    </form>
</div>

<?php if (empty($relatos)): ?>
    <p style="text-align: center; color: #666; margin-top: 20px;">Nenhum Relato de Perigo cadastrado.</p>
<?php else: ?>
    <?php foreach ($relatos as $relato): ?>
        <div class="card" style="margin-top: 15px; padding: 0;">
            <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h4 style="margin: 0; color: var(--danger);">Relato #<?= $relato['id'] ?></h4>
                    <span style="font-size: 0.8em; color: #666;"><?= date('d/m/Y H:i', strtotime($relato['criado_em'])) ?></span>
                    <?php if ($relato['stop_work']): ?>
                        <span style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75em; font-weight: bold;">🛑 STOP WORK</span>
                    <?php endif; ?>
                    <?php if ($relato['anjo_da_guarda'] === 'sim'): ?>
                        <span style="background: #f59e0b; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75em; font-weight: bold;">👼 Anjo da Guarda</span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 5px;">
                    <a href="<?= BASE_URL ?>/perigo/view?id=<?= $relato['id'] ?>" class="btn" style="background: #3b82f6; padding: 5px 10px;" title="Visualizar">👁️ Ver</a>
                    <button class="btn" style="background: #10b981; padding: 5px 10px;" onclick="window.print()" title="Baixar / Imprimir">📥 Baixar</button>
                    <button class="btn" style="background: #f3f4f6; color: #000; padding: 5px 10px;" onclick="toggleRelato(<?= $relato['id'] ?>)">➕ Exp</button>
                </div>
            </div>
            <div id="relato-details-<?= $relato['id'] ?>" style="display: none; padding: 15px; background: #fef2f2;">
                <p><strong>Categoria de Risco:</strong> <?= htmlspecialchars($relato['categoria_risco']) ?></p>
                <p><strong>Local:</strong> <?= htmlspecialchars($relato['cidade']) ?> - <?= htmlspecialchars($relato['uf']) ?></p>
                <p><strong>Operadora:</strong> <?= htmlspecialchars($relato['contrato']) ?></p>
                <?php if ($relato['os_id']): ?>
                    <p><strong>O.S Vinculada:</strong> #<?= $relato['os_id'] ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function toggleRelato(id) {
    const el = document.getElementById('relato-details-' + id);
    if (el.style.display === 'none') {
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}
</script>
