<div class="panel">
    <h2>📄 Relatório Oficial de Perigo #<?= $relato['id'] ?></h2>
    
    <button onclick="window.print()" class="btn" style="background: #3b82f6; margin-bottom: 20px;">🖨️ Imprimir / Salvar PDF</button>

    <div style="background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px;">
        <h4 style="margin-top: 0; color: var(--primary);">Dados do Relato</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <p><strong>Data do Relato:</strong> <?= date('d/m/Y', strtotime($relato['data_relato'])) ?></p>
            <p><strong>Nº Corporativo:</strong> <?= htmlspecialchars($relato['celular_corporativo']) ?></p>
            <p><strong>Operadora:</strong> <?= htmlspecialchars($relato['contrato']) ?></p>
            <p><strong>Setor:</strong> <?= htmlspecialchars($relato['setor']) ?></p>
            <p><strong>Localidade:</strong> <?= htmlspecialchars($relato['cidade']) ?> - <?= htmlspecialchars($relato['uf']) ?></p>
            <p><strong>Endereço Ocorrência:</strong> <?= htmlspecialchars($relato['endereco']) ?></p>
            <p><strong>O.S (AOC):</strong> <?= $relato['os_id'] ? '#' . $relato['os_id'] : 'N/A' ?></p>
            <p><strong>Gestor Avisado:</strong> <?= htmlspecialchars($relato['gestor_avisado']) ?></p>
        </div>
    </div>

    <div style="background: #fef2f2; padding: 15px; border: 1px solid #fecaca; border-radius: 8px; margin-bottom: 20px;">
        <h4 style="margin-top: 0; color: #b91c1c;">Detalhes da Ocorrência</h4>
        <p><strong>Categoria do Risco:</strong> <?= htmlspecialchars($relato['categoria_risco']) ?></p>
        <p><strong>Descrição:</strong><br> <?= nl2br(htmlspecialchars($relato['descricao_ocorrido'])) ?></p>
    </div>

    <!-- Indicadores Visuais de Segurança -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <!-- STOP WORK -->
        <?php if($relato['stop_work']): ?>
            <div style="text-align:center; background:#fef2f2; border:2px dashed #ef4444; padding:20px; border-radius:8px;">
                <div style="font-size: 60px;">👷‍♂️🛑</div>
                <h3 style="color:#ef4444; margin: 10px 0 0 0;">STOP WORK APLICADO</h3>
                <p style="color:#b91c1c; font-size: 0.9em; margin:0;">Atividade foi interrompida.</p>
            </div>
        <?php else: ?>
            <div style="text-align:center; background:#f9fafb; border:1px solid #e5e7eb; padding:20px; border-radius:8px;">
                <h3 style="color:#6b7280; margin: 10px 0 0 0;">Sem Stop Work</h3>
                <p style="color:#9ca3af; font-size: 0.9em; margin:0;">Atividade não precisou ser paralisada.</p>
            </div>
        <?php endif; ?>

        <!-- ANJO DA GUARDA -->
        <?php if($relato['anjo_da_guarda'] === 'Sim'): ?>
            <div style="text-align:center; background:#fffbeb; border:2px dashed #f59e0b; padding:20px; border-radius:8px;">
                <div style="font-size: 60px;">👼🚧</div>
                <h3 style="color:#d97706; margin: 10px 0 0 0;">ANJO DA GUARDA ATIVADO</h3>
                <p style="color:#b45309; font-size: 0.9em; margin:0;">Sinalização com fita zebrada isolou o local.</p>
            </div>
        <?php else: ?>
            <div style="text-align:center; background:#f9fafb; border:1px solid #e5e7eb; padding:20px; border-radius:8px;">
                <h3 style="color:#6b7280; margin: 10px 0 0 0;">Anjo da Guarda: <?= htmlspecialchars($relato['anjo_da_guarda']) ?></h3>
                <p style="color:#9ca3af; font-size: 0.9em; margin:0;">Isolamento não aplicado.</p>
            </div>
        <?php endif; ?>
    </div>

    <div style="background: #f0fdf4; padding: 15px; border: 1px solid #bbf7d0; border-radius: 8px;">
        <h4 style="margin-top: 0; color: #15803d;">Ações Tomadas pela Equipe</h4>
        <p><?= nl2br(htmlspecialchars($relato['acoes_equipe'])) ?: 'Nenhuma ação preenchida.' ?></p>
    </div>

</div>
