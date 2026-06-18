<div class="panel">
    <h2>Mapeamento de Rede (MUBI)</h2>
    <p>Área restrita à gerência, coordenação e técnicos de campo.</p>
    
    <?php if (isset($error)): ?>
        <div style="background: var(--danger); color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/mapa/upload" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
        <label style="display:block; margin-bottom: 10px; font-weight: bold;">Selecione o arquivo do projeto (.dwg, .kml ou .kmz)</label>
        <input type="file" name="mubi_file" accept=".dwg,.kml,.kmz" required style="width: 100%; padding: 10px; background: var(--bg); border-radius: 8px; border: 1px solid #ddd; margin-bottom: 20px;">
        
        <button type="submit" class="btn">Carregar Projeto no Mapa</button>
    </form>
</div>
