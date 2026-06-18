<div class="panel" style="border-top: 5px solid var(--danger);">
    <h2 style="color: var(--danger);">🛑 Cadastrar Relato de Perigo</h2>
    <form action="<?= BASE_URL ?>/perigo/salvar" method="POST" id="perigoForm">
        
        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">1. Dados de Contato e Contrato</h3>
        <label style="display:block; margin-bottom:5px; font-weight:bold;">Nº Celular Corporativo</label>
        <input type="text" name="celular_corporativo" placeholder="(DD) 99999-9999" required style="width:100%; padding:8px; margin-bottom:5px; border-radius:4px; border:1px solid #ccc;">
        <span style="display:block; font-size: 0.8em; color: #ef4444; margin-bottom: 15px;">⚠️ Obrigatório utilizar o número do Celular Corporativo (PDA/PWA). Não utilize número pessoal.</span>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Contrato / Operadora</label>
                <select name="contrato" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
                    <option value="Vivo" selected>Vivo</option>
                    <option value="Algar">Algar</option>
                    <option value="Tim">Tim</option>
                    <option value="V.tal">V.tal</option>
                    <option value="Huawei">Huawei</option>
                    <option value="Claro">Claro</option>
                    <option value="Outros">Outros</option>
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Setor</label>
                <input type="text" name="setor" placeholder="Ex: Rede, Operações..." required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            </div>
        </div>

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">2. Localização e Data</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Data do Relato</label>
                <input type="date" name="data_relato" required value="<?= date('Y-m-d') ?>" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:bold;">UF</label>
                <select name="uf" id="ufSelect" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
                    <option value="SP" selected>São Paulo (SP)</option>
                    <!-- As demais podem ser injetadas, por simplificação, focamos em SP -->
                </select>
            </div>
        </div>
        
        <label style="display:block; margin-bottom:5px; font-weight:bold;">Cidade</label>
        <select name="cidade" id="cidadeSelect" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            <option value="">Selecione a Cidade...</option>
            <option value="São Paulo">São Paulo</option>
            <option value="Campinas">Campinas</option>
            <option value="Ribeirão Preto">Ribeirão Preto</option>
            <option value="São José dos Campos">São José dos Campos</option>
            <!-- Simulando lista de cidades -->
        </select>

        <label style="display:block; margin-bottom:5px; font-weight:bold;">Endereço de onde está preenchendo o relato</label>
        <input type="text" name="endereco" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">

        <label style="display:block; margin-bottom:5px; font-weight:bold;">Ordem de Serviço (O.S / AOC)</label>
        <input type="number" name="os_id" placeholder="ID da O.S (Opcional)" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">3. Detalhes da Ocorrência</h3>
        <label style="display:block; margin-bottom:5px; font-weight:bold;">Descrição do Ocorrido</label>
        <textarea name="descricao_ocorrido" required rows="4" placeholder="Descreva com detalhes o perigo ou risco identificado..." style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;"></textarea>

        <label style="display:block; margin-bottom:5px; font-weight:bold;">Categoria do Risco</label>
        <select name="categoria_risco" required style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            <option value="">Selecione</option>
            <option value="Ataque de animal">Ataque de animal</option>
            <option value="Cordoalha energizada">Cordoalha energizada</option>
            <option value="Agressão">Agressão</option>
            <option value="Ameaça">Ameaça</option>
            <option value="EPI/EPC">Problema EPI/EPC</option>
            <option value="Poste quebrado">Poste quebrado</option>
            <option value="Choque elétrico">Choque elétrico</option>
            <option value="Atmosférico">Atmosférico (Chuva/Tempestade/Raio)</option>
            <option value="Cabo solto">Cabo solto</option>
            <option value="Zeladoria">Zeladoria</option>
            <option value="Espaço confinado">Espaço confinado</option>
            <option value="Cordoalha solta">Cordoalha solta</option>
            <option value="Travessia de via">Travessia de via perigosa</option>
            <option value="Ventos fortes">Ventos fortes</option>
        </select>

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px; color: #b91c1c;">4. Mecanismos de Segurança</h3>
        
        <!-- STOP WORK -->
        <label style="display:flex; align-items:center; gap: 10px; margin-bottom: 10px; font-weight:bold;">
            <input type="checkbox" name="stop_work" value="sim" id="chkStopWork" style="transform: scale(1.5);">
            Foi aplicado o STOP WORK? (Interrupção total das atividades)
        </label>
        
        <div id="imgStopWork" style="display:none; text-align:center; background:#fef2f2; border:2px dashed #ef4444; padding:20px; border-radius:8px; margin-bottom:20px;">
            <div style="font-size: 60px;">👷‍♂️🛑</div>
            <h3 style="color:#ef4444; margin: 10px 0 0 0;">STOP WORK APLICADO</h3>
            <p style="color:#b91c1c; font-size: 0.9em; margin:0;">Atividade interrompida devido ao alto risco!</p>
        </div>

        <!-- ANJO DA GUARDA -->
        <label style="display:block; margin-bottom:5px; font-weight:bold; margin-top: 15px;">Foi aplicado o Anjo da Guarda? (Sinalização utilizando fita zebrada)</label>
        <select name="anjo_da_guarda" id="selAnjo" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
            <option value="Não">Não</option>
            <option value="Sim">Sim</option>
            <option value="Não Aplicável">Não Aplicável</option>
        </select>

        <div id="imgAnjo" style="display:none; text-align:center; background:#fffbeb; border:2px dashed #f59e0b; padding:20px; border-radius:8px; margin-bottom:20px;">
            <div style="font-size: 60px;">👼🚧</div>
            <h3 style="color:#d97706; margin: 10px 0 0 0;">ANJO DA GUARDA ATIVADO</h3>
            <p style="color:#b45309; font-size: 0.9em; margin:0;">Área devidamente isolada e sinalizada com fita zebrada.</p>
        </div>

        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">5. Resolução</h3>
        <label style="display:block; margin-bottom:5px; font-weight:bold;">Ações tomadas pela equipe no momento</label>
        <textarea name="acoes_equipe" rows="3" placeholder="O que vocês fizeram para mitigar o perigo? (Ex: Isolamos o local...)" style="width:100%; padding:8px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;"></textarea>

        <label style="display:block; margin-bottom:5px; font-weight:bold;">Seu gestor foi avisado para abertura de chamado junto a ocorrência?</label>
        <select name="gestor_avisado" required style="width:100%; padding:8px; margin-bottom:25px; border-radius:4px; border:1px solid #ccc;">
            <option value="">Selecione</option>
            <option value="Sim">Sim, foi avisado</option>
            <option value="Não">Não</option>
            <option value="Não Aplicável">Não Aplicável</option>
        </select>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn" style="flex: 1; padding: 15px; font-size: 1.1em; background: var(--success);">✅ Salvar Relato</button>
            <a href="<?= BASE_URL ?>/perigo" class="btn" style="flex: 1; text-align: center; padding: 15px; font-size: 1.1em; background: #6b7280;">❌ Cancelar</a>
        </div>
    </form>
</div>

<script>
    // Lógica para mostrar as imagens dinâmicas
    document.getElementById('chkStopWork').addEventListener('change', function() {
        document.getElementById('imgStopWork').style.display = this.checked ? 'block' : 'none';
    });

    document.getElementById('selAnjo').addEventListener('change', function() {
        document.getElementById('imgAnjo').style.display = (this.value === 'Sim') ? 'block' : 'none';
    });
</script>
