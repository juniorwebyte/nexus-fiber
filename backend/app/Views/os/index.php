<div class="panel">
    <h2>📋 Ordens de Serviço (Tickets)</h2>
    <p><?= $isManager ? 'Despache e monitore as O.S da sua equipe.' : 'Sua lista de tarefas de campo do dia.' ?></p>
</div>

<?php if (isset($_GET['success'])): ?>
    <div style="background: var(--success); color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
        O.S Concluída com sucesso! Pontos de recompensa adicionados. 🎉
    </div>
<?php endif; ?>

<?php if (isset($_GET['imported'])): ?>
    <div style="background: var(--success); color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
        <?= htmlspecialchars($_GET['imported']) ?> Ordens de Serviço importadas via CSV com sucesso!
    </div>
<?php endif; ?>

<?php if ($isManager): ?>
<div class="panel" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0;">
    <div>
        <h3 style="margin: 0; color: #334155;">📊 Integração e Relatórios</h3>
        <p style="margin: 5px 0 0 0; font-size: 0.85em; color: #666;">Exporte dados de auditoria ou importe OS em lote.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="<?= BASE_URL ?>/relatorios/exportar_os" class="btn" style="background: #10b981; padding: 8px 15px;">⬇️ Exportar Auditorias (CSV)</a>
        
        <form action="<?= BASE_URL ?>/relatorios/importar_os" method="POST" enctype="multipart/form-data" style="margin: 0; display: flex; gap: 5px; align-items: center;">
            <input type="file" name="csv_file" accept=".csv" required style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" class="btn" style="background: #6366f1; padding: 8px 15px;">⬆️ Importar O.S</button>
        </form>
    </div>
</div>

<div class="panel" style="margin-bottom: 20px; background: #e0f2fe; border: 1px solid #bae6fd;">
    <h3 style="color: #0284c7; margin-top:0;">Criar Nova O.S.</h3>
    <form action="<?= BASE_URL ?>/os/nova" method="POST">
        <label style="display:block; margin-bottom:5px; font-weight:bold;">Título da Tarefa</label>
        <input type="text" name="titulo" required style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:1px solid #ccc;">
        
        <label style="display:block; margin-bottom:5px; font-weight:bold;">Descrição / Endereço</label>
        <textarea name="descricao" rows="2" style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:1px solid #ccc;"></textarea>
        
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Designar para (Técnico)</label>
                <select name="tecnico_id" required style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;">
                    <?php foreach ($tecnicos as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Tipo de O.S (Perfil)</label>
                <select name="tipo_os" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
                    <option value="instalacao">Instalação na Casa do Cliente (Multi-Skill)</option>
                    <option value="manutencao">Manutenção de Rede na Rua (Cabista/Fibra)</option>
                </select>

                <label style="display:block; margin-bottom:5px; font-weight:bold;">XP Recompensa</label>
                <input type="number" name="pontos_recompensa" value="50" required style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;">
            </div>
        </div>
        <button type="submit" class="btn" style="width: 100%;">Despachar Ticket</button>
    </form>
</div>
<?php endif; ?>

<div style="display: grid; gap: 15px;">
    <?php if (empty($orders)): ?>
        <p style="text-align: center; color: #666;">Nenhuma Ordem de Serviço encontrada.</p>
    <?php endif; ?>

    <?php foreach ($orders as $os): 
        $statusColor = $os['status'] == 'concluida' ? 'var(--success)' : 'var(--warning)';
    ?>
        <div class="card" style="border-left: 5px solid <?= $statusColor ?>;">
            <div style="padding: 15px; border-bottom: 1px solid #eee;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="margin: 0; color: var(--primary);">
                            #<?= $os['id'] ?> - <?= htmlspecialchars($os['titulo']) ?>
                        </h4>
                        <span style="font-size: 0.75em; background: <?= $os['tipo_os'] === 'instalacao' ? '#dbeafe; color: #1e40af;' : '#fef3c7; color: #92400e;' ?> padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 5px;">
                            <?= $os['tipo_os'] === 'instalacao' ? '👤 Instalação (Multi-Skill)' : '🚧 Manutenção (Cabista)' ?>
                        </span>
                    </div>
                    <span style="background: <?= $statusColor ?>; color: white; padding: 5px 10px; border-radius: 20px; font-size: 0.8em;">
                        <?= strtoupper($os['status']) ?>
                    </span>
                </div>
            </div>
            <p style="margin: 10px; color: #555;"><?= nl2br(htmlspecialchars($os['descricao'])) ?></p>
            <div style="margin: 0 10px 10px 10px; display: flex; gap: 8px;">
                <span style="font-size: 0.8em; background: #eee; padding: 3px 6px; border-radius: 4px;">XP: <?= $os['pontos_recompensa'] ?></span>
                <?php if (!empty($os['prazo_limite'])): ?>
                    <?php 
                        $isAtrasado = time() > strtotime($os['prazo_limite']) && $os['status'] !== 'concluida';
                        $slaColor = $isAtrasado ? '#ef4444' : '#10b981';
                        $slaText = $isAtrasado ? 'Atrasado' : 'No Prazo';
                        if ($os['status'] === 'concluida' && !empty($os['concluido_em'])) {
                            $isAtrasado = strtotime($os['concluido_em']) > strtotime($os['prazo_limite']);
                            $slaColor = $isAtrasado ? '#ef4444' : '#10b981';
                            $slaText = $isAtrasado ? 'Entregue Atrasado' : 'Entregue no Prazo';
                        }
                    ?>
                    <span style="font-size: 0.8em; background: <?= $slaColor ?>; color: white; padding: 3px 6px; border-radius: 4px;">SLA: <?= date('d/m H:i', strtotime($os['prazo_limite'])) ?> (<?= $slaText ?>)</span>
                <?php endif; ?>
            </div>
            
            <?php if ($isManager): ?>
                <p style="margin: 0 10px 10px 10px; font-size: 0.85em; color: #888;"><strong>Técnico:</strong> <?= htmlspecialchars($os['tecnico_nome']) ?></p>
            <?php endif; ?>

            <?php if ($os['status'] === 'concluida' && !empty($os['assinatura_url'])): ?>
                <div style="margin: 0 10px 10px 10px; padding: 5px; background: #fdf4ff; border: 1px solid #f5d0fe; border-radius: 4px; font-size: 0.85em; color: #c026d3;">
                    ✍️ Cliente Assinou o Termo de Aceite.
                </div>
            <?php endif; ?>

            <?php if ($os['status'] === 'pendente'): ?>
                <div style="display: flex; gap: 10px; padding: 0 10px 15px 10px;">
                    <a href="<?= BASE_URL ?>/apr/nova?os_id=<?= $os['id'] ?>" class="btn" style="flex: 1; text-align: center; background: #ef4444; border: 1px solid #dc2626;">🛡️ Abrir APR de Risco</a>
                    <a href="<?= BASE_URL ?>/os/checkout?id=<?= $os['id'] ?>" class="btn" style="flex: 1; text-align: center;">✅ Concluir O.S</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
