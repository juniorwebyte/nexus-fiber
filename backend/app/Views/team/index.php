<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0;">👥 Gestão de Equipe (RH)</h2>
        <a href="<?= BASE_URL ?>/equipe/novo" class="btn" style="padding: 8px 15px; font-size: 0.9em;">+ Novo Técnico</a>
    </div>
    <p>Adicione, promova ou edite o cadastro dos funcionários da operação.</p>
</div>

<div class="panel" style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; min-width: 500px;">
        <tr style="background: var(--bg); border-bottom: 2px solid #ddd;">
            <th style="padding: 10px; text-align: left;">Matrícula</th>
            <th style="padding: 10px; text-align: left;">Nome</th>
            <th style="padding: 10px; text-align: left;">Cargo</th>
            <th style="padding: 10px; text-align: center;">Pontuação</th>
            <th style="padding: 10px; text-align: center;">Ações</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 10px;"> <?= htmlspecialchars($u['re_matricula']) ?> </td>
            <td style="padding: 10px;"> <strong><?= htmlspecialchars($u['nome']) ?></strong> </td>
            <td style="padding: 10px; color: var(--primary);"> <?= htmlspecialchars($u['cargo']) ?> </td>
            <td style="padding: 10px; text-align: center; font-weight: bold;"> <?= $u['pontuacao'] ?> </td>
            <td style="padding: 10px; text-align: center;">
                <a href="<?= BASE_URL ?>/equipe/editar?id=<?= $u['id'] ?>" style="color: #0284c7; text-decoration: none; font-weight: bold; margin-right: 10px;">Editar</a>
                <form action="<?= BASE_URL ?>/equipe/excluir" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" style="background:none; border:none; color: var(--danger); font-weight: bold; cursor: pointer;">Excluir</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
