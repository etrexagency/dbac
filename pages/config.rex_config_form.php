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


$field = $form->addCheckboxField('module');
$field->addOption("Modul", 1);


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


// Ausgabe des Formulars
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('dbac_config'), false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');
