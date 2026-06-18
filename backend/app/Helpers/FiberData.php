<?php
namespace App\Helpers;

class FiberData
{
    // Cores das Fibras Ópticas (Padrão Vivo)
    public static $fiberColors = [
        ['number' => 1, 'name' => 'Verde', 'hex' => '#22c55e', 'textColor' => '#000000'],
        ['number' => 2, 'name' => 'Amarela', 'hex' => '#eab308', 'textColor' => '#000000'],
        ['number' => 3, 'name' => 'Branca', 'hex' => '#f8fafc', 'textColor' => '#000000'],
        ['number' => 4, 'name' => 'Azul', 'hex' => '#3b82f6', 'textColor' => '#ffffff'],
        ['number' => 5, 'name' => 'Vermelha', 'hex' => '#ef4444', 'textColor' => '#ffffff'],
        ['number' => 6, 'name' => 'Lilás', 'hex' => '#a855f7', 'textColor' => '#ffffff'],
        ['number' => 7, 'name' => 'Marrom', 'hex' => '#78350f', 'textColor' => '#ffffff'],
        ['number' => 8, 'name' => 'Rosa', 'hex' => '#ec4899', 'textColor' => '#ffffff'],
        ['number' => 9, 'name' => 'Preta', 'hex' => '#1c1917', 'textColor' => '#ffffff'],
        ['number' => 10, 'name' => 'Cinza', 'hex' => '#6b7280', 'textColor' => '#ffffff'],
        ['number' => 11, 'name' => 'Laranja', 'hex' => '#f97316', 'textColor' => '#000000'],
        ['number' => 12, 'name' => 'Água', 'hex' => '#06b6d4', 'textColor' => '#000000'],
    ];

    // Splitters Balanceados (Extraídos das normas e manuais)
    public static $splitters = [
        ['ratio' => '1:2', 'loss' => 3.7],
        ['ratio' => '1:4', 'loss' => 7.1],
        ['ratio' => '1:8', 'loss' => 10.5],
        ['ratio' => '1:16', 'loss' => 13.8], // Margem Coringa baseada na norma
        ['ratio' => '1:32', 'loss' => 17.0],
        ['ratio' => '1:64', 'loss' => 20.5],
    ];

    // Atenuações Lineares Padrões
    public static $attenuations = [
        '1310nm' => 0.35, // dB/km
        '1490nm' => 0.22, // dB/km
        '1550nm' => 0.20, // dB/km
        'fusion' => 0.05, // dB prática por fusão
        'connector' => 0.30 // dB prática por par conectorizado
    ];

    // Tubos (Barramento Único)
    public static $tubes = [
        ['number' => 1, 'color' => 'Preta/Vinho', 'range' => '01-08'],
        ['number' => 2, 'color' => 'Vermelha', 'range' => '09-16'],
        ['number' => 3, 'color' => 'Azul', 'range' => '17-24'],
        ['number' => 4, 'color' => 'Branca', 'range' => '25-32'],
        ['number' => 5, 'color' => 'Amarela', 'range' => '33-40'],
        ['number' => 6, 'color' => 'Verde', 'range' => '41-48'],
        ['number' => 7, 'color' => 'Verde', 'range' => '49-56'],
        ['number' => 8, 'color' => 'Verde', 'range' => '57-64'],
    ];
}
