<div class="panel">
    <h2>Calculadora de Enlace (Potência)</h2>
    <p>Calcule a perda teórica do enlace baseada nas normativas.</p>

    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <h4 style="margin-top: 0;">Atenuações Base</h4>
        <ul style="margin-bottom: 0;">
            <?php foreach ($attenuations as $key => $value): ?>
                <li><b><?= ucfirst($key) ?>:</b> <?= $value ?> dB</li>
            <?php endforeach; ?>
        </ul>
    </div>

    <form onsubmit="event.preventDefault(); calcular();">
        <label style="display:block; margin-bottom: 5px;">Comprimento da Fibra (km):</label>
        <input type="number" id="distancia" value="10" step="0.1" style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px;">

        <label style="display:block; margin-bottom: 5px;">Comprimento de Onda:</label>
        <select id="onda" style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px;">
            <option value="0.35">1310 nm (0.35 dB/km)</option>
            <option value="0.22">1490 nm (0.22 dB/km)</option>
            <option value="0.20">1550 nm (0.20 dB/km)</option>
        </select>

        <label style="display:block; margin-bottom: 5px;">Splitter Primário:</label>
        <select id="splitter" style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px;">
            <option value="0">Nenhum</option>
            <?php foreach ($splitters as $sp): ?>
                <option value="<?= $sp['loss'] ?>"><?= $sp['ratio'] ?> (Max: <?= $sp['loss'] ?> dB)</option>
            <?php endforeach; ?>
        </select>

        <label style="display:block; margin-bottom: 5px;">Número de Fusões:</label>
        <input type="number" id="fusoes" value="2" style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px;">

        <label style="display:block; margin-bottom: 5px;">Pares Conectorizados:</label>
        <input type="number" id="conectores" value="2" style="width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 6px;">

        <button type="submit" class="btn" style="background: var(--success);">Calcular Orçamento</button>
    </form>

    <div id="resultado" style="margin-top: 20px; font-weight: bold; font-size: 1.2em; text-align: center; color: var(--primary);"></div>
</div>

<script>
function calcular() {
    var dist = parseFloat(document.getElementById('distancia').value) || 0;
    var onda = parseFloat(document.getElementById('onda').value) || 0;
    var sp = parseFloat(document.getElementById('splitter').value) || 0;
    var fus = parseFloat(document.getElementById('fusoes').value) || 0;
    var con = parseFloat(document.getElementById('conectores').value) || 0;

    var perdaFibra = dist * onda;
    var perdaFusao = fus * 0.05; // Prática
    var perdaConector = con * 0.30; // Prática

    var total = perdaFibra + sp + perdaFusao + perdaConector;

    document.getElementById('resultado').innerHTML = "Perda Total Estimada: " + total.toFixed(2) + " dB";
}
</script>
