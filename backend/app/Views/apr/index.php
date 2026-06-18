<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>🛡️ Análises Preliminares de Risco (APR)</h2>
        <a href="<?= BASE_URL ?>/apr/nova" class="btn" style="background: var(--primary);">+ Criar Nova APR</a>
    </div>
    <p>Visualize as APRs de segurança criadas pelos técnicos.</p>
</div>

<div class="panel" style="margin-top: 20px;">
    <h3>Filtros</h3>
    <form method="GET" action="<?= BASE_URL ?>/apr">
        <div style="display: flex; gap: 10px;">
            <input type="date" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <input type="date" name="end_date" value="<?= $_GET['end_date'] ?? '' ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" class="btn">Filtrar</button>
            <a href="<?= BASE_URL ?>/apr" class="btn" style="background: #ccc;">Limpar</a>
        </div>
    </form>
</div>

<?php if (empty($aprs)): ?>
    <p style="text-align: center; color: #666; margin-top: 20px;">Nenhuma APR encontrada.</p>
<?php else: ?>
    <?php foreach ($aprs as $apr): ?>
        <div class="card" style="margin-top: 15px; padding: 0;">
            <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h4 style="margin: 0; color: var(--primary);">APR #<?= $apr['id'] ?></h4>
                    <span style="font-size: 0.8em; color: #666;"><?= date('d/m/Y H:i', strtotime($apr['criado_em'])) ?></span>
                    <?php if ($apr['relato_perigo']): ?>
                        <span style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75em;">⚠️ Risco Identificado</span>
                    <?php else: ?>
                        <span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75em;">✅ Seguro</span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 5px;">
                    <a href="<?= BASE_URL ?>/apr/view?id=<?= $apr['id'] ?>" class="btn" style="background: #3b82f6; padding: 5px 10px;" title="Visualizar">👁️ Ver</a>
                    <button class="btn" style="background: #10b981; padding: 5px 10px;" onclick="window.print()" title="Baixar / Imprimir">📥 Baixar</button>
                    <button class="btn" style="background: #f3f4f6; color: #000; padding: 5px 10px;" onclick="toggleApr(<?= $apr['id'] ?>)">➕ Exp</button>
                </div>
            </div>
            <div id="apr-details-<?= $apr['id'] ?>" style="display: none; padding: 15px; background: #f9fafb;">
                <p><strong>Tipo:</strong> <?= htmlspecialchars($apr['tipo_apr']) ?></p>
                <p><strong>Técnico:</strong> <?= htmlspecialchars($apr['nome_colaborador'] ?: 'N/A') ?> (RE: <?= htmlspecialchars($apr['re_colaborador']) ?>)</p>
                <p><strong>Local:</strong> <?= htmlspecialchars($apr['endereco']) ?></p>
                <?php if ($apr['os_id']): ?>
                    <p><strong>O.S Vinculada:</strong> #<?= $apr['os_id'] ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function toggleApr(id) {
    const el = document.getElementById('apr-details-' + id);
    if (el.style.display === 'none') {
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}
</script>
