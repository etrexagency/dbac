
<?php

// Funktion fürs Prüfen ob ein Metainfo Feld existiert oder nicht
if (!function_exists('metainfoFieldExists')) {
    function metainfoFieldExists(string $name, string $type = 'text'): bool
    {
        return rex_sql::factory()->getArray("
            SELECT f.* 
            FROM rex_metainfo_field f
            LEFT JOIN rex_metainfo_type t ON f.type_id = t.id
            WHERE f.name = :name AND t.label = :type
        ", [
            'name' => $name,
            'type' => $type
        ]) !== [];
    }
}

if (!function_exists('registerEP_MEDIA_MANAGER_BEFORE_SEND')) {
    function registerEP_MEDIA_MANAGER_BEFORE_SEND(): void
    {
        rex_extension::register('MEDIA_MANAGER_BEFORE_SEND', static function (rex_extension_point $ep) {
            $mediaManager = $ep->getSubject();
            $managedMedia = $mediaManager->getMedia();
            $filename = $managedMedia->getMediaFilename();

            // rex_media prüfen
            $rexMedia = rex_media::get($filename);

            // Datei nicht gefunden -> 404
            if (!$rexMedia) {
                rex_response::cleanOutputBuffers();
                rex_response::setStatus(404);
                http_response_code(404);
                exit;
            }

            $now = time();
            $publication_date = $rexMedia->getValue('med_publication_date');
            $deactivation_date = $rexMedia->getValue('med_deactivation_date');

            $publication_timestamp = $publication_date ? strtotime($publication_date) : null;
            $deactivation_timestamp = $deactivation_date ? strtotime($deactivation_date) : null;

            // Prüfen, ob Zugriff erlaubt ist
            $accessAllowed = true;

            if ($publication_timestamp && $now < $publication_timestamp) {
                // Noch nicht veröffentlicht
                $accessAllowed = false;
            }

            if ($deactivation_timestamp && $now > $deactivation_timestamp) {
                // Bereits deaktiviert
                $accessAllowed = false;
            }

            if (!$accessAllowed) {
                rex_response::cleanOutputBuffers();
                rex_response::setStatus(404);
                http_response_code(404);
                exit;
            }
        });
    }
}

if (!function_exists('registerEP_SLICE_SHOW')) {
    function registerEP_SLICE_SHOW(): void
    {
        rex_extension::register('SLICE_SHOW', function (rex_extension_point $ep) {
            $addon = rex_addon::get(addon: 'dbac');

            // Slice anhand ID holen
            $slice_id = $ep->getParam('slice_id');
            $slice = rex_article_slice::getArticleSliceById($slice_id);

            // Modul anhand Slice holen
            $module_id = $slice ? $slice->getModuleId() : null;

            // Modul input auslesen
            $module = rex_sql::factory();
            $module->setQuery('SELECT * FROM ' . rex::getTable('module') . ' WHERE id = ?', [$module_id]);

            if ($module->getRows() > 0) {
                $input = $module->getValue('input');
                $output = $module->getValue('output');
            }

            // Falls kein input oder output im input vorhanden ist, abbrechen
            if (!str_contains($input, 'publication_date') || !str_contains($input, 'deactivation_date')) {
                return;
            }

            $publication_date = null;
            $deactivation_date = null;

            // Zugriff auf die REX_VALUE Felder
            $values = $slice->getValue('values') ?? [];
            foreach ($values as $value) {
                $decoded_value = json_decode($value, true);

                if ($decoded_value["publication_date"]) {
                    $publication_date = $decoded_value["publication_date"];
                }

                if ($decoded_value["deactivation_date"]) {
                    $deactivation_date = $decoded_value["deactivation_date"];
                }
            }
            if ($publication_date || $deactivation_date) {

                // Publikationsdatum
                // Wenn das Publikationsdatum gesetzt ist, wandle es in einen Timestamp um
                $publication_timestamp = $publication_date ? strtotime($publication_date) : null;

                // Deaktivierungsdatum
                // Wenn das Deaktivierungsdatum gesetzt ist, wandle es in einen Timestamp um
                $deactivation_timestamp = $deactivation_date ? strtotime($deactivation_date) : null;

                // Aktueller Zeitstempel
                $current_timestamp = time();

                $loggedIn = rex_backend_login::hasSession();

                $showPublicationNotice = false;
                $showDeactivationNotice = false;
                $showModule = true;

                $isWithinPublication = $publication_date && $current_timestamp >= $publication_timestamp;
                $isWithinDeactivation = $deactivation_date && $current_timestamp <= $deactivation_timestamp;

                if ($publication_date && $deactivation_date) {
                    $showModule = $isWithinPublication && $isWithinDeactivation;
                    $showPublicationNotice = !$isWithinPublication;
                    $showDeactivationNotice = !$isWithinDeactivation;
                } elseif ($publication_date) {
                    $showModule = $isWithinPublication;
                    $showPublicationNotice = !$isWithinPublication;
                } elseif ($deactivation_date) {
                    $showModule = $isWithinDeactivation;
                    $showDeactivationNotice = !$isWithinDeactivation;
                }

                $htmlBackend = "";
                if ($loggedIn && $showPublicationNotice) {
                    $htmlBackend .= '<div class="alert alert-info">' . $addon->i18n('general_modul') . ' ' . $addon->i18n('publication_date') . ' — <span>' . date('d.m.Y H:i:s', $publication_timestamp) . '</span></div>';
                }

                if ($loggedIn && $showDeactivationNotice) {
                    $htmlBackend .= '<div class="alert alert-danger">' . $addon->i18n('general_modul') . ' ' . $addon->i18n('deactivation_date') . ' — <span>' . date('d.m.Y H:i:s', $deactivation_timestamp) . '</span></div>';
                }

                if ($loggedIn || $showModule) {
                    if (rex::isBackend() && $htmlBackend !== "") {

                        // Suche den Start der panel-body und hänge das HTML direkt danach ein
                        $updatedSubject = preg_replace(
                            '/(<div class="panel-body">)/i',
                            '$1' . $htmlBackend,
                            $ep->getSubject(),
                            1 // Nur das erste Vorkommen ersetzen
                        );

                        $ep->setSubject($updatedSubject);
                    }
                } else if (!$loggedIn && !$showModule) {
                    $ep->setSubject("");
                }
            }
        });
    }
}

if (!function_exists('registerEP_ART_INIT')) {
    function registerEP_ART_INIT(): void
    {
        rex_extension::register('ART_INIT', function (rex_extension_point $ep) {
            $addon = rex_addon::get(addon: 'dbac');
            $showError = false;
            // Publikationsdatum
            $publication_date = (rex_article::getCurrent()->getValue("art_publication_date")) ? rex_article::getCurrent()->getValue("art_publication_date") : null;
            // Deaktivierungsdatum
            $deactivation_date = (rex_article::getCurrent()->getValue("art_deactivation_date")) ? rex_article::getCurrent()->getValue("art_deactivation_date") : null;
            // Wenn das Publication / Deactivation Date gesetzt ist, wandle es in einen Timestamp um
            $publication_timestamp = $publication_date ? strtotime($publication_date) : null;
            $deactivation_timestamp = $deactivation_date ? strtotime($deactivation_date) : null;

            // Aktueller Zeitstempel
            $current_timestamp = time();
            // Beide Daten sind gesetzt
            if ($publication_date || $deactivation_date) {
                echo "<div class='dbac_tags'>";
            }
            if ($publication_date && $deactivation_date) {

                // Sinnvolles Zeitfenster prüfen
                if ($publication_timestamp < $deactivation_timestamp) {

                    // Jetziges Datum liegt in Zeitfenster
                    if ($current_timestamp > $publication_timestamp && $deactivation_timestamp > $current_timestamp) {
                        // Nichts unternehmen
                    } else {
                        // Jetziges Datum liegt ausserhalb Zeitfenster
                        if (rex_backend_login::hasSession()) {
                            // Artikel anzeigen mit Hinweis auf zukünftige Veröffentlichung
                            $date = date('d.m.Y H:i:s', $publication_timestamp);
                            echo "<p class='dbac_unpublished-tag'>" . $addon->i18n('general_site') . " " . $addon->i18n('publication_date') . " — <span>" . $date . "</span></p>";
                            $date = date('d.m.Y H:i:s', $deactivation_timestamp);
                            echo "<p class='dbac_deactivated-tag'>" . $addon->i18n('general_site') . " " . $addon->i18n('deactivation_date') . " — <span>" . $date . "</span></p>";
                        } else {
                            $showError = true;
                        }
                    }
                    // Jetziges Datum liegt ausserhalb vom Zeitfenster
                } else {
                    // Zeitfenster ist nicht sinnvoll
                    if (rex_backend_login::hasSession()) {
                        // Artikel anzeigen mit Hinweis auf zukünftige Veröffentlichung
                        $date = date('d.m.Y H:i:s', $publication_timestamp);
                        echo "<p class='dbac_unpublished-tag'>" . $addon->i18n('general_site') . " " . $addon->i18n('publication_date') . " — <span>" . $date . "</span></p>";
                        $date = date('d.m.Y H:i:s', $deactivation_timestamp);
                        echo "<p class='dbac_deactivated-tag'>" . $addon->i18n('general_site') . " " . $addon->i18n('deactivation_date') . " — <span>" . $date . "</span></p>";
                    } else {
                        $showError = true;
                    }
                }
            } else if ($publication_date) {
                // Wenn Veröffentlichkeitsdatum gesetzt ist...

                // Veröffentlichungsdatum liegt in die Zukunft und Benutzer ist nicht eingeloggt
                if ($publication_timestamp > $current_timestamp && !rex_backend_login::hasSession()) {
                    $showError = true;

                    // Veröffentlichungsdatum in der Zukunft und Benutzer ist eingeloggt
                } else if ($publication_timestamp > $current_timestamp && rex_backend_login::hasSession()) {
                    // Artikel anzeigen mit Hinweis auf zukünftige Veröffentlichung
                    $date = date('d.m.Y H:i:s', $publication_timestamp);
                    echo "<p class='dbac_unpublished-tag'>" . $addon->i18n('general_site') . " " . $addon->i18n('publication_date') . " — <span>" . $date . "</span></p>";


                    // Veröffentlichungsdatum ist in der Vergangenheit oder Gegenwart
                } else {
                    // Nichts unternehmen

                }
            } else if ($deactivation_date) {
                // Wenn Deaktivierungsdatum gesetzt ist...

                // Deaktivierungsdatum liegt in die Zukunft und Benutzer ist nicht eingeloggt
                if ($deactivation_timestamp < $current_timestamp && !rex_backend_login::hasSession()) {
                    $showError = true;

                    // Deaktivierungsdatum in der Zukunft und Benutzer ist eingeloggt
                } else if ($deactivation_timestamp < $current_timestamp && rex_backend_login::hasSession()) {
                    // Artikel anzeigen mit Hinweis auf vergangene Veröffentlichung
                    $date = date('d.m.Y H:i:s', $deactivation_timestamp);
                    echo "<p class='dbac_deactivated-tag'>" . $addon->i18n('general_site') . " " . $addon->i18n('deactivation_date') . " — <span>" . $date . "</span></p>";

                    // Deaktivierungsdatum ist in der Zukunft oder Gegenwart
                } else {
                    // Nichts unternehmen
                }
            } else {
                // Wenn kein Veröffentlichungsdatum oder Deaktivierungsdatum gesetzt ist

                // Nichts unternehmen
            }


            if ($publication_date || $deactivation_date) {
                echo "</div>";
            }

            if ($showError) {
                // zu 404 Seite weiterleiten
                rex_response::sendRedirect(rex_getUrl(rex_article::getNotfoundArticleId()));
            }
        });
    }
}
