<?php
// Shim: expõe o instalador via navegador quando o document root é /public.
// A lógica real vive em /database/install.php. Apague os dois arquivos após instalar.
require __DIR__ . '/../database/install.php';
