<?php if (isset($error)): ?>
    <div style="background: var(--danger); color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>/login" method="POST" style="background: var(--box-bg); padding: 30px; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.08);">
    <label style="display:block; margin-bottom: 5px; font-weight: bold; color: var(--text);">RE / Matrícula</label>
    <input type="text" name="re_matricula" placeholder="Ex: ADMIN01" required style="width: 100%; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; font-size: 1em;">
    
    <label style="display:block; margin-bottom: 5px; font-weight: bold; color: var(--text);">Senha</label>
    <input type="password" name="senha" placeholder="Sua senha" required style="width: 100%; padding: 15px; margin-bottom: 25px; border: 1px solid #ddd; border-radius: 8px; font-size: 1em;">
    
    <button type="submit" class="btn" style="padding: 15px; font-size: 1.1em; border-radius: 8px;">Entrar na Plataforma</button>
</form>
