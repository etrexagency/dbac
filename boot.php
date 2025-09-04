<?php

// Diese Datei ist keine Pflichdatei mehr.
// Die `boot.php` wird bei jeder Aktion in REDAXO ausgeführt (Frontend und Backend). Hier können beliebige Befehle ausgeführt werden.
// Dokumentation AddOn Aufbau und Struktur https://redaxo.org/doku/master/addon-struktur

$addon = rex_addon::get('dbac');

// Eigene PHP-Funktionen im Backend und Frontend einbinden
$addon->includeFile('functions/dbac_functions.php');

// AddOn-Rechte (permissions) registieren
// Hinweis: In der `de_de.lang`-Datei sind Text-Einträge für das Backend vorhanden (z.B. perm_general_demo_addon[])
if (rex::isBackend() && is_object(rex::getUser())) {
    rex_perm::register('dbac[]');
}


// Benötigt Metainfo Funktionen
require_once rex_path::addon('metainfo', 'functions/function_metainfo.php');

// Metainfo Felder holen
if (!rex_addon::get('metainfo')->hasProperty('metaTables')) {
    rex_addon::get('metainfo')->setProperty('metaTables', [
        'art_' => rex::getTablePrefix() . 'article',
        'cat_' => rex::getTablePrefix() . 'article',
        'med_' => rex::getTablePrefix() . 'media',
        'clang_' => rex::getTablePrefix() . 'clang',
    ]);
}

// Checkbox Werte auslesen
$activateInMediapool = rex_config::get('dbac', 'mediapool');
$activateInPages = rex_config::get('dbac', 'page');

// CSS hinzufügen, wenn Module oder Pages aktiv
if ($activateInPages) {
    $cssUrl = rex_url::addonAssets('dbac', 'css/style.css') . '?v=' . time();
    echo '<link rel="stylesheet" href="' . $cssUrl . '">';
}

/**  MODULE **/
/*
if ($activateInModules) {
    registerEP_SLICE_SHOW();
}
*/

/**  MEDIENPOOL **/
if ($activateInMediapool) {

    // MetaInfo Feld hinzufügen: Legende
    if (!metainfoFieldExists('med_legend_access_control', 'legend')) {
        rex_metainfo_add_field(
            'Zugriffskontrolle',
            'med_legend_access_control',
            1,
            '',
            12,
            '',
            '',
            '',
            '',
            'med_'
        );
    }

    // MetaInfo Feld hinzufügen: Publication date
    if (!metainfoFieldExists('med_publication_date', 'text')) {
        rex_metainfo_add_field(
            'Veröffentlichungs-Datum',
            'med_publication_date',
            2,
            'class="form-control flatpickr" data-enableTime=true data-locale=en',
            1,
            '',
            '',
            '',
            '',
            'med_'
        );
    }

    // MetaInfo Feld hinzufügen: Deactivation date
    if (!metainfoFieldExists('med_deactivation_date', 'text')) {
        // Dann das Feld anlegen
        rex_metainfo_add_field(
            'Deaktivierungs-Datum',
            'med_deactivation_date',
            3,
            'class="form-control flatpickr" data-enableTime=true data-locale=de',
            1,  // 1 = Text
            '',
            '',
            '',
            ''
        );
    }

    // Nur im Frontend
    if (rex::isFrontend()) {
        registerEP_MEDIA_MANAGER_BEFORE_SEND();
    }
} else {
    if (metainfoFieldExists('med_legend_access_control', 'legend')) {
        rex_metainfo_delete_field('med_legend_access_control');
    }
    if (metainfoFieldExists('med_publication_date', 'text')) {
        rex_metainfo_delete_field('med_publication_date');
    }
    if (metainfoFieldExists('med_deactivation_date', 'text')) {
        rex_metainfo_delete_field('med_deactivation_date');
    }
}

/**  ARTIKEL **/

if ($activateInPages) {
    // MetaInfo Feld hinzufügen: Legende
    if (!metainfoFieldExists('art_legend_access_control', 'legend')) {
        rex_metainfo_add_field(
            'Zugriffskontrolle',
            'art_legend_access_control',
            1,
            '',
            12,
            '',
            '',
            '',
            '',
            'art_'
        );
    }


    // MetaInfo Feld hinzufügen: Publication date
    if (!metainfoFieldExists('art_publication_date', 'text')) {
        rex_metainfo_add_field(
            'Veröffentlichungs-Datum',
            'art_publication_date',
            2,
            'class="form-control flatpickr" data-enableTime=true data-locale=en',
            1,
            '',
            '',
            '',
            '',
            'med_'
        );
    }

    // MetaInfo Feld hinzufügen: Deactivation date
    if (!metainfoFieldExists('art_deactivation_date', 'text')) {
        // Dann das Feld anlegen
        rex_metainfo_add_field(
            'Deaktivierungs-Datum',
            'art_deactivation_date',
            3,
            'class="form-control flatpickr" data-enableTime=true data-locale=de',
            1,  // 1 = Text
            '',
            '',
            '',
            ''
        );
    }

    // Nur im Frontend
    if (rex::isFrontend()) {
        registerEP_ART_INIT();
    }
} else {
    if (metainfoFieldExists('art_legend_access_control', 'legend')) {
        rex_metainfo_delete_field('art_legend_access_control');
    }
    if (metainfoFieldExists('art_publication_date', 'text')) {
        rex_metainfo_delete_field('art_publication_date');
    }
    if (metainfoFieldExists('art_deactivation_date', 'text')) {
        rex_metainfo_delete_field('art_deactivation_date');
    }
}
