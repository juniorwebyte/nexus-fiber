<div class="panel">
    <h2>📚 Biblioteca Técnica de Campo</h2>
    <p>Acesso rápido aos padrões de engenharia da rede.</p>
</div>

<div class="panel">
    <h3 style="color: var(--primary);">Tabela de Perdas (Atenuação de Splitters)</h3>
    <p style="font-size: 0.9em; color: #666;">Valores médios considerando normas ITU-T e perda de inserção.</p>
    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr style="border-bottom: 2px solid #ddd; background: #f9f9f9;">
            <th style="padding: 10px; text-align: left;">Splitter (Razão)</th>
            <th style="padding: 10px; text-align: left;">Perda Teórica (dB)</th>
            <th style="padding: 10px; text-align: left;">Perda Máxima Tolerável (dB)</th>
        </tr>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 10px;">1:2</td>
            <td style="padding: 10px;">3.01</td>
            <td style="padding: 10px; color: var(--danger); font-weight: bold;">~3.8</td>
        </tr>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 10px;">1:4</td>
            <td style="padding: 10px;">6.02</td>
            <td style="padding: 10px; color: var(--danger); font-weight: bold;">~7.4</td>
        </tr>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 10px;">1:8</td>
            <td style="padding: 10px;">9.03</td>
            <td style="padding: 10px; color: var(--danger); font-weight: bold;">~10.5</td>
        </tr>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 10px;">1:16</td>
            <td style="padding: 10px;">12.04</td>
            <td style="padding: 10px; color: var(--danger); font-weight: bold;">~13.8</td>
        </tr>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 10px;">1:32</td>
            <td style="padding: 10px;">15.05</td>
            <td style="padding: 10px; color: var(--danger); font-weight: bold;">~17.1</td>
        </tr>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 10px;">1:64</td>
            <td style="padding: 10px;">18.06</td>
            <td style="padding: 10px; color: var(--danger); font-weight: bold;">~20.5</td>
        </tr>
    </table>
</div>

<div class="panel" style="margin-top: 20px;">
    <h3 style="color: var(--primary);">Glossário de Caixas de Emenda</h3>
    <div style="display: grid; gap: 15px; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); margin-top: 15px;">
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
            <div style="background: #e0f2fe; height: 120px; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; font-weight: bold; color: #0284c7;">
                [Imagem CTO]
            </div>
            <strong>CTO (Caixa de Terminação Óptica)</strong>
            <p style="font-size: 0.85em; color: #666;">Ponto final de distribuição para clientes. Geralmente abriga splitter 1:8 ou 1:16.</p>
        </div>
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
            <div style="background: #e0f2fe; height: 120px; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; font-weight: bold; color: #0284c7;">
                [Imagem CEO/FOSC]
            </div>
            <strong>CEO / FOSC</strong>
            <p style="font-size: 0.85em; color: #666;">Caixa de Emenda Óptica. Responsável pela fusão dos cabos troncais (Backbone) e distribuição primária.</p>
        </div>
    </div>
</div>
