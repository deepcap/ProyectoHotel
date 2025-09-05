<?php
// backend/lib/ui.php
declare(strict_types=1);

/**
 * Renderiza una página de aviso con estilos del sitio.
 * $variant: 'success' | 'error'
 * $actions: [['href'=>'/public/pages/menu-completo.html','label'=>'Menú'], ...]
 * $bgUrl:   imagen de fondo opcional (usa la del index por defecto)
 */
function page_notice(string $title, string $htmlBody, string $variant = 'success', array $actions = [], ?string $bgUrl = null): void {
    $bg = $bgUrl ?: "https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg";
    $variantClass = $variant === 'error' ? 'notice-error' : 'notice-success';
    // Construir acciones
    $acts = '';
    foreach ($actions as $a) {
        $href  = htmlspecialchars($a['href'] ?? '#', ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($a['label'] ?? 'Volver', ENT_QUOTES, 'UTF-8');
        $acts .= '<a class="btn-ghost" href="'.$href.'">'.$label.'</a>';
    }
    // Salida HTML completa (sin BOM)
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8" />'
       . '<meta name="viewport" content="width=device-width, initial-scale=1" />'
       . '<title>Hotel Paraíso · Aviso</title>'
       . '<link rel="stylesheet" href="/public/assets/css/app.css" />'
       . '<link rel="stylesheet" href="/public/assets/css/notice.css" />'
       . '</head><body>'
       . '<nav class="topbar" style="background: linear-gradient(90deg, var(--primary-1), var(--primary-2));">'
       . '  <div class="brand"><div class="brand-badge">H</div><div>Hotel&nbsp;Paraíso</div></div>'
       . '  <div class="nav-links">'
       . '    <a href="/public/index.html">Inicio</a>'
       . '    <a href="/public/pages/menu-completo.html">Menú</a>'
       . '    <a href="/public/pages/consultas.html">Consultas</a>'
       . '  </div>'
       . '</nav>'
       . '<header class="notice-wrap" style="--hero-bg:url(\''.$bg.'\')">'
       . '  <div class="notice-card '.$variantClass.'">'
       . '    <h1 class="notice-title">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h1>'
       . '    <p class="notice-meta">'.($variant === 'error' ? 'Ocurrió un problema' : 'Operación completada').'</p>'
       . '    <div class="notice-body">'.$htmlBody.'</div>'
       . '    <div class="notice-actions">'.$acts.'</div>'
       . '  </div>'
       . '</header>'
       . '<footer class="footer">© '.date('Y').' Hotel Paraíso</footer>'
       . '</body></html>';
}
