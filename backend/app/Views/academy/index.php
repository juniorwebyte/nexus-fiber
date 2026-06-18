<div class="panel" style="text-align: center;">
    <h2 style="margin-bottom: 5px;">Academia Drop Nexus</h2>
    <p style="color: var(--primary); font-weight: bold; margin-top: 0; font-size: 1.2em;">Módulo <?= htmlspecialchars($_SESSION['cargo']) ?></p>
    
    <div style="background: var(--bg); padding: 15px; border-radius: 8px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: bold;">Seus Pontos:</span>
        <span style="background: var(--success); color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold;">🏆 <?= $pontos ?></span>
    </div>
</div>

<div class="panel">
    <h2>Vídeos Didáticos</h2>
    <div style="display: grid; gap: 15px;">
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
            <div style="background: #ccc; height: 120px; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                ▶ Play (FOSC / CTOP)
            </div>
            <strong>Como preparar um cabo Drop</strong>
        </div>
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
            <div style="background: #ccc; height: 120px; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                ▶ Play (OTDR)
            </div>
            <strong>Analisando Eventos no OTDR</strong>
        </div>
    </div>
</div>

<div class="panel">
    <h2>Treinamento & Avaliação</h2>
    <p>Responda quizzes técnicos para subir no ranking e promover seu cargo!</p>
    <a href="<?= BASE_URL ?>/academia/quiz" class="btn" style="background: var(--primary);">Iniciar Quiz Teórico</a>
</div>

<div class="panel">
    <h2>🏆 Ranking da Equipe</h2>
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <tr style="border-bottom: 2px solid #ddd;">
            <th style="padding: 10px 0;">Nome</th>
            <th style="padding: 10px 0;">Cargo</th>
            <th style="padding: 10px 0;">Pts</th>
        </tr>
        <?php foreach ($rankings as $idx => $r): ?>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 10px 0;"><?= ($idx+1) ?>º <?= htmlspecialchars($r['nome']) ?></td>
            <td style="padding: 10px 0; font-size: 0.9em; color: #666;"><?= htmlspecialchars($r['cargo']) ?></td>
            <td style="padding: 10px 0; font-weight: bold; color: var(--primary);"><?= $r['pontuacao'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
