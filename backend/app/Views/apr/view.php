<div class="panel">
    <h2>📄 Relatório de APR #<?= $apr['id'] ?></h2>
    
    <button onclick="window.print()" class="btn" style="background: #3b82f6; margin-bottom: 20px;">🖨️ Imprimir APR / Salvar PDF</button>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Bloco 1: Dados Gerais -->
        <div style="background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px;">
            <h4 style="margin-top: 0; color: var(--primary);">Dados Gerais</h4>
            <p><strong>Tipo de APR:</strong> <?= htmlspecialchars($apr['tipo_apr']) ?></p>
            <?php if($apr['grupo_altura']): ?><p><strong>Grupo:</strong> <?= htmlspecialchars($apr['grupo_altura']) ?></p><?php endif; ?>
            <p><strong>RE Colaborador:</strong> <?= htmlspecialchars($apr['re_colaborador']) ?></p>
            <p><strong>Nome:</strong> <?= htmlspecialchars($apr['nome_colaborador']) ?></p>
            <p><strong>Base:</strong> <?= htmlspecialchars($apr['base']) ?></p>
            <p><strong>Setor:</strong> <?= htmlspecialchars($apr['setor']) ?></p>
            <p><strong>Operadora:</strong> <?= htmlspecialchars($apr['operadora_contrato']) ?></p>
            <p><strong>Endereço Ocorrência:</strong> <?= htmlspecialchars($apr['endereco']) ?></p>
            <p><strong>O.S (CRE):</strong> <?= $apr['os_id'] ? '#' . $apr['os_id'] : 'N/A' ?></p>
            <p><strong>Início:</strong> <?= date('d/m/Y H:i', strtotime($apr['data_inicio'])) ?></p>
            <p><strong>Fim (Prev):</strong> <?= date('d/m/Y H:i', strtotime($apr['data_fim'])) ?></p>
        </div>

        <!-- Bloco 2: Segurança e Anjo da Guarda -->
        <div style="background: #fef2f2; padding: 15px; border: 1px solid #fecaca; border-radius: 8px;">
            <h4 style="margin-top: 0; color: #b91c1c;">Relato de Perigo & Anjo da Guarda</h4>
            <?php if($apr['relato_perigo']): ?>
                <div style="background: #ef4444; color: white; padding: 10px; border-radius: 4px; font-weight: bold; margin-bottom: 10px;">
                    ⚠️ Risco Extremo Reportado
                </div>
                <p><strong>Descrição:</strong><br> <?= nl2br(htmlspecialchars($apr['relato_perigo_desc'])) ?></p>
                <p style="color: #b91c1c; font-weight: bold;">(Serviço sob análise do Anjo da Guarda)</p>
            <?php else: ?>
                <div style="background: #10b981; color: white; padding: 10px; border-radius: 4px; font-weight: bold; text-align: center;">
                    ✅ Condição Segura
                </div>
                <p>Nenhum perigo extremo reportado pelo colaborador no momento da APR.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bloco 3: Etapas do Checklist -->
    <h3 style="margin-top: 30px; border-bottom: 2px solid #eee;">Checklist de Segurança (6 Etapas)</h3>
    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr style="background: #f3f4f6; text-align: left;">
            <th style="padding: 10px; border: 1px solid #e5e7eb;">Etapa</th>
            <th style="padding: 10px; border: 1px solid #e5e7eb;">Status</th>
            <th style="padding: 10px; border: 1px solid #e5e7eb;">Observações</th>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #e5e7eb;">1. Condições Climáticas Favoráveis</td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><strong><?= $apr['etapa1_status'] ?></strong></td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><?= htmlspecialchars($apr['etapa1_obs']) ?></td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #e5e7eb;">2. Planejamento e Sinalização (Altura)</td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><strong><?= $apr['etapa2_status'] ?></strong></td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><?= htmlspecialchars($apr['etapa2_obs']) ?></td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #e5e7eb;">3. EPIs e EPCs Boas Condições</td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><strong><?= $apr['etapa3_status'] ?></strong></td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><?= htmlspecialchars($apr['etapa3_obs']) ?></td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #e5e7eb;">4. Irregularidade no Poste / Local</td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><strong><?= $apr['etapa4_status'] ?></strong></td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><?= htmlspecialchars($apr['etapa4_obs']) ?></td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #e5e7eb;">5. Proximidade com Rede Elétrica</td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><strong><?= $apr['etapa5_status'] ?></strong></td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><?= htmlspecialchars($apr['etapa5_obs']) ?></td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #e5e7eb;">6. Travessia Segura</td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><strong><?= $apr['etapa6_status'] ?></strong></td>
            <td style="padding: 10px; border: 1px solid #e5e7eb;"><?= htmlspecialchars($apr['etapa6_obs']) ?></td>
        </tr>
    </table>

    <!-- Bloco 4: Evidências -->
    <h3 style="margin-top: 30px; border-bottom: 2px solid #eee;">Evidências (Foto e Localização)</h3>
    <?php if($apr['foto_url']): ?>
        <div style="text-align: center; margin-top: 15px;">
            <img src="<?= BASE_URL ?>/uploads/<?= $apr['foto_url'] ?>" style="max-width: 100%; border-radius: 8px; border: 1px solid #ccc; max-height: 400px; object-fit: cover;">
            <p style="color: #666; font-size: 0.9em; margin-top: 10px;">
                📍 GPS Latitude: <strong><?= $apr['latitude'] ?></strong> | Longitude: <strong><?= $apr['longitude'] ?></strong>
            </p>
        </div>
    <?php else: ?>
        <p>Nenhuma foto anexada.</p>
    <?php endif; ?>

</div>
