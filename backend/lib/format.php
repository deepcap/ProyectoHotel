<?php
// Formatos comunes
function money_mx(float $n, bool $with_code=false): string {
  $txt = '$' . number_format($n, 2, '.', ',');
  return $with_code ? ($txt . ' MXN') : $txt;
}