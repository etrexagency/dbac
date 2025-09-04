# ETX: Date-based Access Controller

## Beschreibung

Mit diesem AddOn kann der Zugriff für Medienpool Dateien, Artikel (Seiten) und Module anhand einer Datumangabe für die Publizierung und Deaktivierung eingeschränkt werden. Der Zugriff für Medienpool Dateien und Artikel (Seiten) kann über die Metainformationen gesteuert werden. Für Module ist dies nicht über Metainformationen möglich, hierfür wird der Code zur Verfügung gestellt, welcher im Input und Output des Developer Addons hinzugefügt werden kann.

### Einschränkung von Medienpool Dateien

Wählt man in den Einstellungen des AddOns Medienpool aus, werden im AddOn **Meta infos** die Felder `med_publication_date` und `med_deactivation_date` unter der Kategorie **Medien** erstellt. Mittels dem Extension Points `MEDIA_MANAGER_BEFORE_SEND` wird beim Aufrufen der Datei geprüft, ob man auf die Datei zugreifen darf oder nicht. Falls nicht, kann die Datei im Frontend nicht aufgerufen werden und ein 404 wird geworfen.

### Einschränkung von Artikel (Seiten)

Wählt man in den Einstellungen des AddOns **Artikel (Seiten)** aus, werden die Metainformationsfelder `art_publication_date` und `art_deactivation_date` unter der Kategorie **Artikel** erstellt. Mittels dem Extension Points `ART_INIT` wird beim Aufrufen der Datei geprüft, ob man auf den Artikel zugreifen darf oder nicht. Falls nicht, kann die Seite für abgemeldete Benutzer nicht aufgerufen werden und ein 404 wird geworfen.

### Einschränkung von Modulen

In den Einstellungen befindet sich Beispiel Code für den Input und Output (Developer AddOn) zum kopieren um die Zugriffseinschränkung für Module zu implementieren.

## Voraussetzungen

Entwickelt auf:

- Redaxo Version: 5.17.0

Zugriffseinschränkung auf Medienpool und Artikel (Seiten) Ebene:

- mediapool (Redaxo Standard) >= 2.10.0 für den Medienpool
- metainfo (Redaxo Standard) >= 2.10.0 für die Erfassung der Felder für "Publikationsdatum" und "Deaktivierungsdatum"
- [flatpickr](https://github.com/FriendsOfREDAXO/flatpickr) >= 8.0.4 für die visuelle Datumauswahl bei der Angabe des Datums

Zugriffseinschränkung auf Modul Ebene (Code Vorlage):

- [developer](https://github.com/FriendsOfREDAXO/developer) >= 3.9.0 für Input und Output eines Moduls
- [MForm](https://github.com/FriendsOfREDAXO/mform) >= 8.0.0 für Modul Eingabefelder
