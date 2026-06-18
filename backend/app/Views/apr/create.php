<div class="panel">
    <h2>Criar APR (Análise Preliminar de Risco)</h2>
    <form action="<?= BASE_URL ?>/apr/salvar" method="POST" enctype="multipart/form-data" id="aprForm">
        
        <?php if(isset($_GET['os_id'])): ?>
            <input type="hidden" name="os_id" value="<?= htmlspecialchars($_GET['os_id']) ?>">
            <p><strong>Vinculado à O.S:</strong> #<?= htmlspecialchars($_GET['os_id']) ?></p>
        <?php else: ?>
            <label style="display:block; margin-bottom:5px; font-weight:bold;">Número do CRE / Ordem de Serviço</label>
            <input type="number" name="os_id" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
        <?php endif; ?>

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">1. Tipo de APR</h3>
        <label style="display:block; margin-bottom:5px; font-weight:bold;">Tipo da Atividade</label>
        <select name="tipo_apr" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            <option value="">Selecione</option>
            <option value="Altura">Altura</option>
            <option value="Espaço Confinado">Espaço Confinado</option>
            <option value="Outras">Outras atividades</option>
        </select>

        <label style="display:block; margin-bottom:5px; font-weight:bold;">Grupo (Se Altura)</label>
        <select name="grupo_altura" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            <option value="">N/A</option>
            <option value="Poste">Poste</option>
            <option value="Torre">Torre</option>
        </select>

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">2. Dados do Colaborador e Gestão</h3>
        <label style="display:block; margin-bottom:5px; font-weight:bold;">RE do Colaborador (Técnico)</label>
        <input type="text" name="re_colaborador" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">

        <label style="display:block; margin-bottom:5px; font-weight:bold;">Nome do Colaborador (e Auxiliar se houver)</label>
        <input type="text" name="nome_colaborador" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Supervisor</label>
                <input type="text" name="supervisor" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Coordenador</label>
                <input type="text" name="coordenador" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Base</label>
                <input type="text" name="base" placeholder="Ex: Jabaquara" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Operadora / Contrato</label>
                <select name="operadora_contrato" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
                    <option value="Vivo">Vivo</option>
                    <option value="Claro">Claro</option>
                    <option value="TIM">TIM</option>
                    <option value="Oi">Oi</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Setor</label>
                <input type="text" name="setor" placeholder="Ex: 4100 123 111 COJ" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Função</label>
                <input type="text" name="funcao" placeholder="Ex: Cabista" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            </div>
        </div>

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">3. Dados do Serviço</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Data/Hora de Início</label>
                <input type="datetime-local" name="data_inicio" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Data/Hora Fim (Previsão)</label>
                <input type="datetime-local" name="data_fim" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            </div>
        </div>
        <label style="display:block; margin-bottom:5px; font-weight:bold;">Endereço da Ocorrência</label>
        <input type="text" name="endereco" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px; color: #b91c1c;">4. Checklist de Segurança (Etapas)</h3>

        <!-- Etapa 1 -->
        <div style="margin-bottom: 15px; padding: 10px; background: #f9fafb; border-left: 4px solid #3b82f6;">
            <p style="margin-top: 0;"><strong>Etapa 1:</strong> As condições climáticas estão favoráveis (sem chuvas, ventos ou raios)?</p>
            <select name="etapa1_status" required style="width:100%; padding:8px; margin-bottom:5px; border-radius:4px; border:1px solid #ccc;">
                <option value="">Selecione</option>
                <option value="Confirma">Confirma</option>
                <option value="Não Confirma">Não Confirma</option>
                <option value="Não Aplicável">Não Aplicável</option>
            </select>
            <input type="text" name="etapa1_obs" placeholder="Observação se 'Não Confirma'..." style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;">
        </div>

        <!-- Etapa 2 -->
        <div style="margin-bottom: 15px; padding: 10px; background: #f9fafb; border-left: 4px solid #3b82f6;">
            <p style="margin-top: 0;"><strong>Etapa 2:</strong> A atividade em altura foi devidamente planejada e área sinalizada?</p>
            <select name="etapa2_status" required style="width:100%; padding:8px; margin-bottom:5px; border-radius:4px; border:1px solid #ccc;">
                <option value="">Selecione</option>
                <option value="Confirma">Confirma</option>
                <option value="Não Confirma">Não Confirma</option>
                <option value="Não Aplicável">Não Aplicável</option>
            </select>
            <input type="text" name="etapa2_obs" placeholder="Observação se 'Não Confirma'..." style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;">
        </div>

        <!-- Etapa 3 -->
        <div style="margin-bottom: 15px; padding: 10px; background: #f9fafb; border-left: 4px solid #3b82f6;">
            <p style="margin-top: 0;"><strong>Etapa 3:</strong> EPIs e EPCs necessários para a atividade estão em boas condições?</p>
            <select name="etapa3_status" required style="width:100%; padding:8px; margin-bottom:5px; border-radius:4px; border:1px solid #ccc;">
                <option value="">Selecione</option>
                <option value="Confirma">Confirma</option>
                <option value="Não Confirma">Não Confirma</option>
                <option value="Não Aplicável">Não Aplicável</option>
            </select>
            <input type="text" name="etapa3_obs" placeholder="Observação..." style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;">
        </div>

        <!-- Etapa 4 -->
        <div style="margin-bottom: 15px; padding: 10px; background: #f9fafb; border-left: 4px solid #3b82f6;">
            <p style="margin-top: 0;"><strong>Etapa 4:</strong> Existe alguma irregularidade no Poste ou local? Relatar não conformidade se houver.</p>
            <select name="etapa4_status" required style="width:100%; padding:8px; margin-bottom:5px; border-radius:4px; border:1px solid #ccc;">
                <option value="">Selecione</option>
                <option value="Confirma">Confirma (Seguro)</option>
                <option value="Não Confirma">Não Confirma (Com Irregularidade)</option>
                <option value="Não Aplicável">Não Aplicável</option>
            </select>
            <input type="text" name="etapa4_obs" placeholder="Descreva a irregularidade..." style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;">
        </div>

        <!-- Etapa 5 -->
        <div style="margin-bottom: 15px; padding: 10px; background: #f9fafb; border-left: 4px solid #3b82f6;">
            <p style="margin-top: 0;"><strong>Etapa 5:</strong> Proximidade com Rede Elétrica? Utiliza luva de isolamento e detector de tensão?</p>
            <select name="etapa5_status" required style="width:100%; padding:8px; margin-bottom:5px; border-radius:4px; border:1px solid #ccc;">
                <option value="">Selecione</option>
                <option value="Confirma">Confirma</option>
                <option value="Não Confirma">Não Confirma</option>
                <option value="Não Aplicável">Não Aplicável</option>
            </select>
            <input type="text" name="etapa5_obs" placeholder="Observação..." style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;">
        </div>

        <!-- Etapa 6 -->
        <div style="margin-bottom: 15px; padding: 10px; background: #f9fafb; border-left: 4px solid #3b82f6;">
            <p style="margin-top: 0;"><strong>Etapa 6:</strong> Na atividade haverá travessia segura?</p>
            <select name="etapa6_status" required style="width:100%; padding:8px; margin-bottom:5px; border-radius:4px; border:1px solid #ccc;">
                <option value="">Selecione</option>
                <option value="Confirma">Confirma</option>
                <option value="Não Confirma">Não Confirma</option>
                <option value="Não Aplicável">Não Aplicável</option>
            </select>
            <input type="text" name="etapa6_obs" placeholder="Observação..." style="width:100%; padding:8px; border-radius:4px; border:1px solid #ccc;">
        </div>

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px; color: #b91c1c;">5. Relato de Perigo (APR Risco)</h3>
        <label style="display:flex; align-items:center; gap: 10px; margin-bottom: 10px; font-weight:bold;">
            <input type="checkbox" name="relato_perigo" value="sim" id="chkPerigo" style="transform: scale(1.5);">
            Houve perigo iminente ou Risco Crítico? (Marque se SIM)
        </label>
        <div id="perigoDescDiv" style="display: none; margin-bottom: 15px;">
            <label style="display:block; margin-bottom:5px; color:#ef4444;"><strong>Descreva os perigos e riscos observados:</strong></label>
            <textarea name="relato_perigo_desc" rows="3" placeholder="Ex: Poste com vazamento de tensão elétrica... (ANJO DA GUARDA APLICÁVEL)" style="width:100%; padding:8px; border-radius:4px; border:1px solid #ef4444;"></textarea>
        </div>

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">6. Evidência Fotográfica e Geolocalização</h3>
        <label style="display:block; margin-bottom: 10px; font-weight: bold;">Tirar Foto da Atividade (Escada / Sinalização)</label>
        <input type="file" name="foto" accept="image/*" capture="environment" required style="width: 100%; padding: 10px; border: 2px dashed #ccc; text-align: center; margin-bottom: 20px;">
        
        <input type="hidden" name="latitude" id="lat">
        <input type="hidden" name="longitude" id="lng">
        <p id="geoStatus" style="font-size: 0.85em; color: #666; margin-bottom: 20px;">Obtendo Geolocalização...</p>

        <button type="submit" id="btnSalvar" class="btn" style="width: 100%; padding: 15px; font-size: 1.2em; background: var(--success); cursor: not-allowed;" disabled>Verificando Localização...</button>
    </form>
</div>

<script>
    document.getElementById('chkPerigo').addEventListener('change', function() {
        document.getElementById('perigoDescDiv').style.display = this.checked ? 'block' : 'none';
        if(this.checked) {
            alert("Atenção: Ao relatar perigo extremo, a O.S será submetida ao Anjo da Guarda!");
        }
    });

    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('lat').value = position.coords.latitude;
            document.getElementById('lng').value = position.coords.longitude;
            document.getElementById('geoStatus').innerText = "📍 Localização obtida com sucesso!";
            document.getElementById('geoStatus').style.color = "#10b981";
            
            const btn = document.getElementById('btnSalvar');
            btn.disabled = false;
            btn.style.cursor = "pointer";
            btn.innerText = "Salvar e Confirmar APR Segura";
        }, function(error) {
            document.getElementById('geoStatus').innerText = "❌ Erro ao obter localização. É obrigatório habilitar o GPS.";
            document.getElementById('geoStatus').style.color = "#ef4444";
        });
    } else {
        document.getElementById('geoStatus').innerText = "Seu dispositivo não suporta Geolocalização.";
    }
</script>
