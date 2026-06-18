<div class="panel">
    <h2><?= isset($user) && $user ? '✏️ Editar Funcionário' : '📝 Cadastrar Novo Funcionário' ?></h2>
    <p>Preencha os dados do técnico. A evolução de cargo pode ser alterada aqui.</p>
</div>

<div class="panel">
    <form action="<?= isset($user) && $user ? BASE_URL . '/equipe/editar' : BASE_URL . '/equipe/novo' ?>" method="POST">
        <?php if (isset($user) && $user): ?>
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
        <?php endif; ?>
        
        <label style="display:block; margin-bottom: 5px; font-weight: bold;">RE / Matrícula</label>
        <input type="text" name="re_matricula" value="<?= isset($user) ? htmlspecialchars($user['re_matricula']) : '' ?>" required style="width: 100%; padding: 10px; background: var(--bg); border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
        
        <label style="display:block; margin-bottom: 5px; font-weight: bold;">Nome Completo</label>
        <input type="text" name="nome" value="<?= isset($user) ? htmlspecialchars($user['nome']) : '' ?>" required style="width: 100%; padding: 10px; background: var(--bg); border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
        
        <label style="display:block; margin-bottom: 5px; font-weight: bold;">Cargo Hierarchy (Promoção)</label>
        <select name="cargo" required style="width: 100%; padding: 10px; background: var(--bg); border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
            <?php 
            $cargos = [
                'Auxiliar', 'Cabista', 'Cabista de Fibra Óptica', 'Cabista Especial', 
                'Oficial de Redes', 'Emendador de Fibra Óptica', 'Técnico de Emenda', 
                'Backbone', 'Técnico de Transmissão', 'Supervisor de Rede', 
                'Fiscal de Obras', 'Coordenador', 'Gestor', 'Gerente Local', 'Gerente Regional', 'Admin'
            ];
            $currentCargo = isset($user) ? $user['cargo'] : '';
            foreach ($cargos as $c):
            ?>
                <option value="<?= $c ?>" <?= $currentCargo == $c ? 'selected' : '' ?>><?= $c ?></option>
            <?php endforeach; ?>
        </select>
        
        <label style="display:block; margin-bottom: 5px; font-weight: bold;">Senha de Acesso <?= isset($user) && $user ? '(Deixe em branco para não alterar)' : '' ?></label>
        <input type="password" name="senha" <?= isset($user) && $user ? '' : 'required' ?> style="width: 100%; padding: 10px; background: var(--bg); border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;">
        
        <button type="submit" class="btn" style="width: 100%;"><?= isset($user) && $user ? 'Salvar Alterações' : 'Cadastrar Funcionário' ?></button>
    </form>
</div>
