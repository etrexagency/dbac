<?php

/*
AddOn-Einstellungen die in der Tabelle `rex_config` gespeichert werden.
Hier mit Verwendung der Klasse `rex_config_form`. Die Einstellungen werden automatisch
beim absenden des Formulars gespeichert.

Die beiden Dateien `config.rex_config_form.php` und `config.classic_form.php`
speichern die gleichen AddOn-Einstellungen.
Anhand der identischen Kommentare können die beiden Dateien "verglichen" werden.

https://redaxo.org/doku/master/konfiguration_form
*/

$addon = rex_addon::get('dbac');

// Instanzieren des Formulars
$form = rex_config_form::factory('dbac');

$field = $form->addFieldset("Publikations- und Deaktivierungsdatum anzeigen für");

// Checkboxen initialisieren
$field = $form->addCheckboxField('mediapool');
$field->addOption("Medienpool", 1);

$field = $form->addCheckboxField('page');
$field->addOption("Artikel (Seiten)", 1);

/*
$field = $form->addCheckboxField('module');
$field->addOption("Modul", 1);
*/

// Modul Input
$phpInputCode = <<<'CODE'
<?php
	
$idStatic = 1;
$mformStatic = MForm::factory()
		
->addTabElement('Inhalt', MForm::factory()
    ->addTextField("$idStatic.title", ['label' => 'Title'])->setFull()
)
->addTabElement('Zugriff', MForm::factory()
    ->addColumnElement(6, MForm::factory()
        /* --  PUBLICATION DATE  -- */
        ->addTextField("$idStatic.publication_date", [
            'label' => 'Publication Date',
            'class' => 'form-control flatpickr',
            'data-enableTime' => 'true',
            'data-locale' => 'de',
        ])->setFull()
    )
    ->addColumnElement(6, MForm::factory()
        /* --  DEACTIVATION DATE  -- */
        ->addTextField("$idStatic.deactivation_date", [
            'label' => 'Deactivation Date',
            'class' => 'form-control flatpickr',
            'data-enableTime' => 'true',
            'data-locale' => 'de',
        ])->setFull()
    )
, true);

echo $mformStatic->show();

?>
CODE;

$field = $form->addFieldset("Modul Input.php (für Developer AddOn)");
$form->addRawField('<pre><code>' . htmlspecialchars(string: $phpInputCode) . '</code></pre>');

$phpOutputCode = <<<'CODE'
<?php

$mformStatic = rex_var::toArray('REX_VALUE[1]');

// Get dates and convert to timestamps for comparison 
$publication_date = $mformStatic['publication_date'] ?? null;
$deactivation_date = $mformStatic['deactivation_date'] ?? null;
$publication_timestamp = $publication_date ? strtotime($publication_date) : null;
$deactivation_timestamp = $deactivation_date ? strtotime($deactivation_date) : null;
$current_timestamp = time();

$loggedIn = rex_backend_login::hasSession();
$showModule = true;
$showPublicationNotice = false;
$showDeactivationNotice = false;

// If one of the two dates is set, check for access possibilities
if ($publication_date || $deactivation_date) {
    $isWithinPublication = !$publication_date || $current_timestamp >= $publication_timestamp;
    $isWithinDeactivation = !$deactivation_date || $current_timestamp <= $deactivation_timestamp;
    $showModule = $isWithinPublication && $isWithinDeactivation;
    $showPublicationNotice = !$isWithinPublication;
    $showDeactivationNotice = !$isWithinDeactivation;
}
?>

<?php if (rex::isBackend()) { ?>

    <!-- Backend -->

    <?php if ($publication_date && $publication_timestamp > $current_timestamp): ?>
        <div class="alert alert-info">
            Will be published on <b><?= date("d.m.Y H:i:s", $publication_timestamp) ?></b>
        </div>

    <?php endif; ?>
    <?php if ($deactivation_date && $deactivation_timestamp > $current_timestamp): ?>
        <div class="alert alert-info">
            Will be deactivated on <b><?= date("d.m.Y H:i:s", $deactivation_timestamp) ?></b>
        </div>
    <?php endif; ?>

	<?php if ($mformStatic['title']) { ?>
		<h2><?= $mformStatic['title']; ?></h2>
	<?php } ?>

<?php } else if ($loggedIn || $showModule) { ?>

    <!-- Frontend -->

    <div class="<?= ($loggedIn && ($showPublicationNotice || $showDeactivationNotice)) ? 'dbac_bg-unpublished' : '' ?>">
        <?= ($loggedIn && $showPublicationNotice) ? '<p>Publication Date — <span>' . date('d.m.Y H:i:s', $publication_timestamp) . '</p></span>' : '' ?>
        <?= ($loggedIn && $showDeactivationNotice) ? '<p>Deactivation Date — <span>' . date('d.m.Y H:i:s', $deactivation_timestamp) . '</p></span>' : '' ?>
                
        <!-- Modul Content -->
        <?php if ($mformStatic['title']) { ?>
            <h2><?= $mformStatic['title']; ?></h2>
        <?php } ?>
    </div>

<?php } ?>
CODE;

// Modul Output
$field = $form->addFieldset("Modul Output.php (für Developer AddOn)");
$form->addRawField('<pre><code>' . htmlspecialchars($phpOutputCode) . '</code></pre>');


// Ausgabe des Formulars
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('dbac_config'), false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');
