<?php

// Jalankan dari root laravelback:
// php tools/makefont-geoslab.php

require __DIR__ . '/../vendor/setasign/fpdf/makefont/makefont.php';

// Path ke font TTF
$regular = __DIR__ . '/../resources/fonts/GeoSlab703-MdCnBT.ttf';
$bold    = __DIR__ . '/../resources/fonts/GeoSlab703-MdCnBT-Bold.ttf';

// Generate tanpa subset (argumen ke-4 = false)
MakeFont($regular, 'cp1252', true, false);
MakeFont($bold, 'cp1252', true, false);