<div class="panel">
    <h2>📸 Baixa de O.S (Auditoria)</h2>
    <p>Para concluir a <b><?= htmlspecialchars($os['titulo']) ?></b>, é obrigatório enviar uma foto do serviço finalizado.</p>
</div>

<?php if (isset($_GET['error'])): ?>
    <div style="background: var(--danger); color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
        Erro no envio da foto. Formatos permitidos: JPG, PNG.
    </div>
<?php endif; ?>

<div class="panel">
    <form action="<?= BASE_URL ?>/os/completa" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $os['id'] ?>">
        
        <?php if (!empty($myStock)): ?>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: var(--primary);">📦 Materiais Utilizados</h4>
            <p style="font-size: 0.85em; color: #666; margin-bottom: 10px;">Informe o que gastou da sua viatura nesta OS:</p>
            <?php foreach ($myStock as $mat): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <label style="font-weight: bold; flex: 1;"><?= htmlspecialchars($mat['nome']) ?> (Máx: <?= floatval($mat['quantidade']) ?>)</label>
                    <input type="number" name="materiais[<?= $mat['material_id'] ?>]" min="0" max="<?= floatval($mat['quantidade']) ?>" step="0.01" placeholder="0" style="width: 80px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; text-align: center;">
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($os['tipo_os'] === 'instalacao'): ?>
        <div style="background: #e0f2fe; border: 1px solid #bae6fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #0284c7;">📝 Croqui Técnico (Instalação)</h4>
            <p style="font-size: 0.85em; color: #666; margin-bottom: 10px;">Preencha os dados de auditoria do Cliente:</p>
            
            <label style="display:block; margin-bottom:5px; font-weight:bold;">Porta da CTO Utilizada</label>
            <input type="number" name="croqui_porta_cto" placeholder="Ex: 8" required style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:1px solid #ccc;">

            <label style="display:block; margin-bottom:5px; font-weight:bold;">Sinal Óptico Medido (dBm)</label>
            <input type="number" name="croqui_sinal_dbm" step="0.01" placeholder="Ex: -22.50" required style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:1px solid #ccc;">

            <label style="display:block; margin-bottom:5px; font-weight:bold;">MAC Address do Roteador (Opcional)</label>
            <input type="text" name="croqui_mac" placeholder="AA:BB:CC:DD:EE:FF" style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:1px solid #ccc;">

            <label style="display:block; margin-bottom:5px; font-weight:bold;">Observações Adicionais</label>
            <textarea name="croqui_obs" rows="2" style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;"></textarea>
        </div>
        <?php else: ?>
        <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #d97706;">🔧 Manutenção de Rede</h4>
            <p style="font-size: 0.85em; color: #666; margin-bottom: 10px;">Preencha os dados do reparo na rua:</p>
            
            <label style="display:block; margin-bottom:5px; font-weight:bold;">Causa do Rompimento / Problema</label>
            <select name="rede_causa_rompimento" required style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:1px solid #ccc;">
                <option value="">Selecione a Causa</option>
                <option value="Caminhão / Veículo Alto">Caminhão / Veículo Alto</option>
                <option value="Vandalismo / Furto">Vandalismo / Furto</option>
                <option value="Clima / Árvore">Queda de Árvore / Clima</option>
                <option value="Degradação">Degradação Natural</option>
                <option value="Manutenção Predial / DG">Manutenção Predial / DG</option>
                <option value="Outros">Outros</option>
            </select>

            <label style="display:block; margin-bottom:5px; font-weight:bold;">Atenuação Pós-Fusão (dBm)</label>
            <input type="number" name="rede_atenuacao_fusao" step="0.01" placeholder="Ex: 0.05" required style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:1px solid #ccc;">

            <label style="display:block; margin-bottom:5px; font-weight:bold;">Observações da Manutenção</label>
            <textarea name="croqui_obs" rows="2" style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;"></textarea>
        </div>
        <?php endif; ?>

        <label style="display:block; margin-bottom: 10px; font-weight: bold;">Tirar ou escolher foto da CTOP / Caixa</label>
        
        <!-- accept="image/*" com capture="environment" abre a câmera direto no celular -->
        <input type="file" name="foto_ctop" accept="image/*" capture="environment" required style="width: 100%; padding: 10px; border: 2px dashed #ccc; text-align: center; margin-bottom: 20px;">
        
        <?php if ($os['tipo_os'] === 'instalacao'): ?>
        <div style="background: #fdf4ff; border: 1px solid #f5d0fe; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #c026d3;">✍️ Assinatura do Cliente</h4>
            <p style="font-size: 0.85em; color: #666; margin-bottom: 10px;">Peça para o cliente assinar abaixo confirmando o serviço:</p>
            <canvas id="signatureCanvas" width="100%" height="200" style="width: 100%; height: 200px; border: 1px solid #ccc; background: #fff; border-radius: 4px; touch-action: none;"></canvas>
            <input type="hidden" name="assinatura_base64" id="assinatura_base64">
            <button type="button" id="clearCanvasBtn" style="margin-top: 5px; padding: 5px 10px; background: #ccc; border: none; border-radius: 4px; cursor: pointer;">Limpar Assinatura</button>
        </div>
        <?php endif; ?>

        <button type="submit" id="submitOsBtn" class="btn" style="width: 100%; font-size: 1.2em; padding: 15px; background: var(--success);">✅ Dar Baixa na Ordem de Serviço</button>
    </form>
</div>

<?php if ($os['tipo_os'] === 'instalacao'): ?>
<script>
    // Configuração do Canvas de Assinatura
    const canvas = document.getElementById('signatureCanvas');
    const ctx = canvas.getContext('2d');
    
    // Ajustar tamanho real do canvas para evitar distorção
    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;

    function getCoordinates(e) {
        if (e.touches && e.touches.length > 0) {
            const rect = canvas.getBoundingClientRect();
            return {
                x: e.touches[0].clientX - rect.left,
                y: e.touches[0].clientY - rect.top
            };
        }
        return {
            x: e.offsetX,
            y: e.offsetY
        };
    }

    function startDrawing(e) {
        isDrawing = true;
        const coords = getCoordinates(e);
        [lastX, lastY] = [coords.x, coords.y];
        e.preventDefault();
    }

    function draw(e) {
        if (!isDrawing) return;
        const coords = getCoordinates(e);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(coords.x, coords.y);
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.stroke();
        [lastX, lastY] = [coords.x, coords.y];
        e.preventDefault();
    }

    function stopDrawing() {
        isDrawing = false;
    }

    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);

    canvas.addEventListener('touchstart', startDrawing, {passive: false});
    canvas.addEventListener('touchmove', draw, {passive: false});
    canvas.addEventListener('touchend', stopDrawing);
    canvas.addEventListener('touchcancel', stopDrawing);

    document.getElementById('clearCanvasBtn').addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });

    // Antes de submeter, salvar a imagem em base64
    document.querySelector('form').addEventListener('submit', function(e) {
        // Verifica se o canvas está em branco (lógica simples)
        const blank = document.createElement('canvas');
        blank.width = canvas.width;
        blank.height = canvas.height;
        if (canvas.toDataURL() === blank.toDataURL()) {
            alert("A assinatura do cliente é obrigatória!");
            e.preventDefault();
            return;
        }
        document.getElementById('assinatura_base64').value = canvas.toDataURL('image/png');
    });
</script>
<?php endif; ?>
