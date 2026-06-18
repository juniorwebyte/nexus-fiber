<div class="panel">
    <h2>Simulador de Cores FTTH</h2>
    <p>Guia de cores do Padrão Vivo (12 Fibras).</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 20px;">
        <?php foreach ($fiberColors as $color): ?>
            <div style="background: <?= $color['hex'] ?>; color: <?= $color['textColor'] ?>; padding: 15px 10px; border-radius: 8px; text-align: center; font-weight: bold; border: 1px solid #ddd;">
                <?= $color['number'] ?> - <?= $color['name'] ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel">
    <h2>OTDR e Troubleshooting</h2>
    <p>Regras Críticas (Extraídas dos Manuais):</p>
    <ul style="line-height: 1.6;">
        <li>Use a <b>Bobina de Lançamento (500m)</b> para superar a zona morta do equipamento.</li>
        <li><b>Atenuação Limite de Emenda:</b> ≤ 0,10 dB (0,05 dB na prática).</li>
        <li><b>Spike Reflexivo:</b> Indica conectores mal encaixados ou ruptura com fibra exposta ao ar.</li>
    </ul>
</div>
