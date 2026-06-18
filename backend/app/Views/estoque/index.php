<div class="panel">
    <h2>📦 Almoxarifado / Estoque</h2>
    <p><?= $isManager ? 'Transfira materiais para a viatura dos técnicos.' : 'Visão do porta-malas da sua Viatura. Preste contas do seu material!' ?></p>
</div>

<?php if (isset($_GET['success'])): ?>
    <div style="background: var(--success); color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
        Material movimentado com sucesso! ✅
    </div>
<?php endif; ?>

<div class="panel" style="margin-bottom: 20px;">
    <h3 style="color: var(--primary); margin-top:0;">🚗 Meu Estoque (Na Viatura)</h3>
    <?php if (empty($myStock)): ?>
        <p style="color: #666;">Você não possui nenhum material no seu estoque atualmente.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="background: var(--bg); border-bottom: 2px solid #ddd;">
                <th style="padding: 10px; text-align: left;">Material</th>
                <th style="padding: 10px; text-align: center;">Quantidade em Mãos</th>
            </tr>
            <?php foreach ($myStock as $s): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;"><strong><?= htmlspecialchars($s['nome']) ?></strong></td>
                <td style="padding: 10px; text-align: center; font-size: 1.2em; font-weight: bold; color: var(--primary);">
                    <?= floatval($s['quantidade']) ?> <?= htmlspecialchars($s['unidade']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php if ($isManager): ?>
<div class="panel" style="background: #fffbeb; border: 1px solid #fde68a;">
    <h3 style="color: #d97706; margin-top:0;">Saída de Almoxarifado (Transferir para Viatura)</h3>
    <form action="<?= BASE_URL ?>/estoque/transferir" method="POST">
        <label style="display:block; margin-bottom:5px; font-weight:bold;">Selecione o Técnico</label>
        <select name="tecnico_id" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            <?php foreach ($tecnicos as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        
        <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Material</label>
                <select name="material_id" required style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;">
                    <?php foreach ($materiais as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?> (<?= $m['unidade'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Quantidade</label>
                <input type="number" step="0.01" name="quantidade" min="0.01" required style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;">
            </div>
        </div>
        
        <button type="submit" class="btn" style="width: 100%; background: #d97706;">Transferir Material</button>
    </form>
</div>
<?php endif; ?>
