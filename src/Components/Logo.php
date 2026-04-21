<?php

namespace Src\Components;

class Logo {
    /**
     * Renderiza o logo SVG da CaaS Express
     * @param string $type 'full' (com texto) ou 'symbol' (apenas ícone)
     * @param int $width Largura do logo
     * @param int $height Altura do logo
     * @param string $textColor Cor do texto principal
     * @return string O código SVG filtrado
     */
    public static function render($type = 'full', $width = null, $height = null, $textColor = '#1D3557') {
        $primaryRed = "#E63946";
        $secondaryRed = "#D62828";
        
        if ($type === 'symbol') {
            $w = $width ?: 100;
            $h = $height ?: 100;
            return '
            <svg width="'.$w.'" height="'.$h.'" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <rect width="100" height="100" rx="20" fill="'.$primaryRed.'"/>
                <path d="M30 50C30 38.9543 38.9543 30 50 30H65L55 50L65 70H50C38.9543 70 30 61.0457 30 50Z" fill="white"/>
                <path d="M45 45L53 50L45 55" stroke="'.$primaryRed.'" stroke-width="3" fill="none" stroke-linecap="round"/>
            </svg>';
        }

        $w = $width ?: 250;
        $h = $height ?: 60;
        
        return '
        <svg width="'.$w.'" height="'.$h.'" viewBox="0 0 250 60" xmlns="http://www.w3.org/2000/svg" class="logo-svg">
            <defs>
                <linearGradient id="logo-grad" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" style="stop-color:'.$primaryRed.';stop-opacity:1" />
                    <stop offset="100%" style="stop-color:'.$secondaryRed.';stop-opacity:1" />
                </linearGradient>
            </defs>
            <path d="M10 30C10 18.9543 18.9543 10 30 10H45L35 30L45 50H30C18.9543 50 10 41.0457 10 30Z" fill="url(#logo-grad)"/>
            <path d="M25 25L35 30L25 35" stroke="white" stroke-width="3" fill="none" stroke-linecap="round"/>
            <text x="60" y="42" font-family="Outfit, sans-serif" font-size="32" font-weight="800" fill="'.$textColor.'">CaaS</text>
            <text x="142" y="42" font-family="Outfit, sans-serif" font-size="32" font-weight="300" fill="'.$primaryRed.'">Express</text>
        </svg>';
    }
}
