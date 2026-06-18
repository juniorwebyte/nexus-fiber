<div class="panel">
    <h2>Quiz Teórico</h2>
    
    <?php if (isset($mensagem)): ?>
        <div style="background: <?= $sucesso ? 'var(--success)' : 'var(--danger)' ?>; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center;">
            <?= htmlspecialchars($mensagem) ?>
        </div>
        <div style="text-align: center;">
            <a href="<?= BASE_URL ?>/academia/quiz" class="btn">Próxima Pergunta</a>
            <br><br>
            <a href="<?= BASE_URL ?>/academia" style="color: var(--primary);">Voltar para Academia</a>
        </div>
    <?php else: ?>
        <?php if ($question): ?>
            <p style="font-weight: bold; font-size: 1.1em; color: var(--text); margin-bottom: 20px;">
                <?= htmlspecialchars($question['pergunta']) ?>
            </p>
            
            <form action="<?= BASE_URL ?>/academia/quiz" method="POST">
                <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; background: var(--bg); padding: 15px; border-radius: 8px; cursor: pointer; border: 1px solid #ddd;">
                        <input type="radio" name="resposta" value="a" required style="margin-right: 10px;">
                        <?= htmlspecialchars($question['opcao_a']) ?>
                    </label>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; background: var(--bg); padding: 15px; border-radius: 8px; cursor: pointer; border: 1px solid #ddd;">
                        <input type="radio" name="resposta" value="b" required style="margin-right: 10px;">
                        <?= htmlspecialchars($question['opcao_b']) ?>
                    </label>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; background: var(--bg); padding: 15px; border-radius: 8px; cursor: pointer; border: 1px solid #ddd;">
                        <input type="radio" name="resposta" value="c" required style="margin-right: 10px;">
                        <?= htmlspecialchars($question['opcao_c']) ?>
                    </label>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; background: var(--bg); padding: 15px; border-radius: 8px; cursor: pointer; border: 1px solid #ddd;">
                        <input type="radio" name="resposta" value="d" required style="margin-right: 10px;">
                        <?= htmlspecialchars($question['opcao_d']) ?>
                    </label>
                </div>
                
                <button type="submit" class="btn">Confirmar Resposta</button>
            </form>
        <?php else: ?>
            <p>Nenhuma pergunta encontrada para o seu cargo atual.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
