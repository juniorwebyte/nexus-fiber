<?php
namespace App\Controllers;

use App\Helpers\FiberData;

class EngineeringController
{
    public function simulator()
    {
        $activeMenu = 'simulador';
        $viewPath = __DIR__ . '/../Views/engineering/simulador.php';
        
        $fiberColors = FiberData::$fiberColors;
        
        require_once __DIR__ . '/../Views/layout.php';
    }

    public function calculator()
    {
        $activeMenu = 'calculadora';
        $viewPath = __DIR__ . '/../Views/engineering/calculadora.php';
        
        $splitters = FiberData::$splitters;
        $attenuations = FiberData::$attenuations;

        require_once __DIR__ . '/../Views/layout.php';
    }
}
